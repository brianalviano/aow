<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Sales\SalesData;
use App\DTOs\Sales\SalesPaymentData;
use App\Enums\{SalesStatus, SalesPaymentStatus, PaymentDirection, PaymentStatus, SalesChargeType, SalesDeliveryType, StockBucket, SalesReturnStatus};
use App\Models\{
    Sales,
    SalesItem,
    SalesCharge,
    SalesDelivery,
    SalesDeliveryItem,
    Payment,
    PaymentAllocation,
    Customer,
    CashierSession,
    ValueAddedTax,
    SalesReturn,
    SalesReturnItem,
    CustomerStoreCredit
};
use App\Traits\RetryableTransactionsTrait;
use App\Models\{Discount, Product, ProductCategory, ProductSubCategory, ProductSellingPrice, ProductPurchasePrice, AverageCost, Voucher, Warehouse, Stock};
use Illuminate\Support\Facades\{DB, Log};
use Carbon\Carbon;

/**
 * Layanan domain untuk membuat transaksi penjualan (POS).
 *
 * Tanggung jawab:
 * - Bangun entitas Sales dan SalesItem
 * - Hitung subtotal, diskon, pajak, biaya, dan grand total
 * - Buat Payment (bila ada) dan alokasinya ke Sales
 * - Update agregat shift kasir per metode pembayaran
 * - Kurangi stok secara prioritas melalui StockService
 *
 * Transaksi dilindungi retryable untuk deadlock/serialization errors.
 */
final class SalesService
{
    use RetryableTransactionsTrait;

    /**
     * Buat transaksi penjualan baru.
     *
     * @param SalesData $data
     * @return Sales
     *
     * @throws \Throwable
     */
    public function create(SalesData $data): Sales
    {
        return $this->runWithRetry(function () use ($data) {
            try {
                return DB::transaction(function () use ($data) {
                    if ($data->cashierSessionId === null) {
                        throw new \RuntimeException('Kasir belum dibuka atau sudah ditutup. Penjualan tidak diizinkan.');
                    }
                    $session = CashierSession::query()
                        ->where('id', (string) $data->cashierSessionId)
                        ->lockForUpdate()
                        ->first();
                    if (!$session) {
                        throw new \RuntimeException('Sesi kasir tidak ditemukan.');
                    }
                    if ($session->closed_at !== null || $session->status === \App\Enums\CashSessionStatus::Closed) {
                        throw new \RuntimeException('Sesi kasir sudah ditutup. Penjualan tidak diizinkan.');
                    }
                    if ((string) $session->user_id !== (string) $data->createdById) {
                        throw new \RuntimeException('Sesi kasir tidak sesuai pengguna.');
                    }

                    $sale = new Sales();
                    $sale->warehouse_id = $data->warehouseId ?: $this->resolveDefaultWarehouseId();
                    $sale->cashier_session_id = $data->cashierSessionId;
                    $sale->receipt_number = $this->generateReceiptNumber();
                    $sale->invoice_number = $this->generateInvoiceNumber();
                    $sale->sale_datetime = Carbon::parse($data->saleDatetime)->toDateTimeString();
                    $sale->customer_id = $data->customerId;
                    $sale->delivery_type = $data->deliveryType !== null
                        ? $data->deliveryType
                        : ($data->requiresDelivery
                            ? SalesDeliveryType::Delivery->value
                            : SalesDeliveryType::WalkIn->value);
                    $sale->requires_delivery = (bool) $data->requiresDelivery;
                    $sale->discount_percentage = null;
                    $sale->is_value_added_tax_enabled = (bool) $data->isValueAddedTaxEnabled;
                    $sale->value_added_tax_id = $data->valueAddedTaxId;
                    $sale->value_added_tax_percentage = $this->resolveVatPercentage($data);
                    $sale->notes = $data->notes;
                    $sale->status = SalesStatus::Confirmed->value;
                    $sale->created_by_id = $data->createdById;
                    $sale->subtotal = (int) $data->subtotal;
                    $sale->discount_amount = (int) ($data->itemDiscountTotal + $data->extraDiscountTotal);
                    $sale->discount = (int) ($data->itemDiscountTotal + $data->extraDiscountTotal);
                    $sale->item_discount_total = (int) $data->itemDiscountTotal;
                    $sale->extra_discount_total = (int) $data->extraDiscountTotal;
                    $sale->discount_percentage = $data->discountPercentage;
                    $sale->total_after_discount = (int) $data->totalAfterDiscount;
                    $sale->value_added_tax_amount = (int) $data->valueAddedTaxAmount;
                    $sale->grand_total = (int) $data->grandTotal;
                    $sale->payment_status = SalesPaymentStatus::Unpaid->value;
                    $sale->outstanding_amount = 0;
                    $sale->save();

                    $items = [];
                    foreach ($data->items as $entry) {
                        $si = new SalesItem();
                        $si->sales_id = (string) $sale->id;
                        $si->product_id = $entry->productId;
                        $si->quantity = $entry->quantity;
                        $si->price = $entry->price;
                        $si->subtotal = (int) ($entry->price * $entry->quantity);
                        $si->notes = $entry->notes;
                        $si->delivered_quantity = 0;
                        $si->returned_quantity = 0;
                        $si->cost_price = 0;
                        $si->save();
                        $items[] = $si;
                    }

                    $extraCharges = 0;
                    if (($data->shippingAmount ?? 0) > 0) {
                        $extraCharges += (int) $data->shippingAmount;
                        $this->createCharge($sale, SalesChargeType::Shipping->value, 'Biaya Pengiriman', (int) $data->shippingAmount);
                    }
                    if (($data->roundingAmount ?? 0) !== null && (int) $data->roundingAmount !== 0) {
                        $extraCharges += (int) $data->roundingAmount;
                        $desc = (int) $data->roundingAmount >= 0 ? 'Pembulatan +' : 'Pembulatan -';
                        $this->createCharge($sale, SalesChargeType::Rounding->value, $desc, (int) $data->roundingAmount);
                    }

                    $voucherCode = $data->voucherCode ? trim((string) $data->voucherCode) : '';
                    if ($voucherCode !== '') {
                        $saleDate = Carbon::parse($data->saleDatetime)->format('Y-m-d H:i:s');
                        $voucher = Voucher::query()
                            ->where('code', $voucherCode)
                            ->where('is_active', true)
                            ->where(function ($q) use ($saleDate) {
                                $q->whereNull('start_at')->orWhere('start_at', '<=', $saleDate);
                            })
                            ->where(function ($q) use ($saleDate) {
                                $q->whereNull('end_at')->orWhere('end_at', '>=', $saleDate);
                            })
                            ->lockForUpdate()
                            ->first();
                        if (!$voucher) {
                            throw new \RuntimeException('Voucher tidak valid atau tidak aktif.');
                        }
                        $minOrder = (int) (string) ($voucher->min_order_amount ?? '0');
                        $baseBeforeVoucher = (int) max((int) $sale->subtotal - (int) $sale->discount_amount, 0);
                        if ($baseBeforeVoucher < $minOrder) {
                            throw new \RuntimeException('Transaksi belum memenuhi minimum untuk voucher.');
                        }
                        $limit = (int) ($voucher->usage_limit ?? 0);
                        $used = (int) ($voucher->used_count ?? 0);
                        if ($limit > 0 && $used >= $limit) {
                            throw new \RuntimeException('Voucher telah mencapai batas pemakaian.');
                        }
                        $providedAmount = (int) max((int) ($data->voucherAmount ?? 0), 0);
                        $providedAmount = (int) min($providedAmount, $baseBeforeVoucher);
                        if ($providedAmount > 0) {
                            $sale->voucher_code = (string) $voucher->code;
                            $sale->voucher_amount = (int) $providedAmount;
                            $sumSub = array_reduce($items, static fn($s, $it) => (int) $s + (int) $it->subtotal, 0);
                            if ($sumSub > 0) {
                                $allocated = 0;
                                $lastIndex = count($items) - 1;
                                foreach ($items as $idx => $it) {
                                    if ($idx === $lastIndex) {
                                        $share = (int) ($providedAmount - $allocated);
                                    } else {
                                        $ratio = ((float) $it->subtotal) / ((float) $sumSub);
                                        $share = (int) round(((float) $providedAmount) * $ratio);
                                        $allocated += $share;
                                    }
                                    $it->voucher_share = (int) max(0, $share);
                                    $it->save();
                                }
                            }
                            $voucher->used_count = (int) ($used + 1);
                            $voucher->save();
                        }
                    }

                    $sale->grand_total = (int) max(0, (int) ($sale->total_after_discount + $sale->value_added_tax_amount + $extraCharges));

                    $paidAmount = 0;
                    if (!empty($data->payments) && $data->customerId !== null) {
                        $paidAmount = $this->createPaymentsAndAllocations($sale, $data);
                    }

                    if ($sale->cashier_session_id) {
                        $this->accumulateShiftTotals((string) $sale->cashier_session_id, (int) $sale->grand_total, (int) $paidAmount);
                    }

                    $outstanding = (int) max($sale->grand_total - $paidAmount, 0);
                    $sale->outstanding_amount = $outstanding;
                    $sale->change_amount = (int) max((int) $data->changeAmount, 0);
                    if ($paidAmount <= 0) {
                        $sale->payment_status = SalesPaymentStatus::Unpaid->value;
                    } elseif ($paidAmount >= $sale->grand_total) {
                        $sale->payment_status = SalesPaymentStatus::Paid->value;
                    } else {
                        $sale->payment_status = SalesPaymentStatus::PartiallyPaid->value;
                    }
                    $sale->save();

                    if ($sale->customer_id) {
                        Customer::query()
                            ->where('id', (string) $sale->customer_id)
                            ->update(['last_transaction_at' => Carbon::parse((string) $sale->sale_datetime)]);
                    }

                    if ($sale->requires_delivery) {
                        $this->createDeliveryWithItems($sale, $items, $data);
                    }

                    $this->issueStockForSales($sale, $items);

                    return $sale;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('SalesService::create failed', [
                    'user_id' => $data->createdById,
                    'warehouse_id' => $data->warehouseId,
                    'customer_id' => $data->customerId,
                    'item_count' => is_array($data->items) ? count($data->items) : 0,
                    'payload' => [
                        'sale_datetime' => $data->saleDatetime,
                        'requires_delivery' => (bool) $data->requiresDelivery,
                        'shipping_amount' => $data->shippingAmount,
                        'rounding_amount' => $data->roundingAmount,
                        'vat_enabled' => (bool) $data->isValueAddedTaxEnabled,
                        'vat_id' => $data->valueAddedTaxId,
                        'subtotal' => (int) $data->subtotal,
                        'item_discount_total' => (int) $data->itemDiscountTotal,
                        'extra_discount_total' => (int) $data->extraDiscountTotal,
                        'total_after_discount' => (int) $data->totalAfterDiscount,
                        'voucher_code' => $data->voucherCode,
                        'voucher_amount' => (int) ($data->voucherAmount ?? 0),
                        'vat_amount' => (int) $data->valueAddedTaxAmount,
                        'grand_total' => (int) $data->grandTotal,
                    ],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    private function resolveDefaultWarehouseId(): string
    {
        $wh = Warehouse::query()
            ->where('is_active', true)
            ->orderByDesc('is_central')
            ->orderBy('name')
            ->first(['id']);
        if (!$wh) {
            throw new \RuntimeException('Tidak ada gudang aktif untuk penjualan.');
        }
        return (string) $wh->id;
    }

    public function receivePayments(Sales $sale, array $payments, string $createdById): Sales
    {
        return $this->runWithRetry(function () use ($sale, $payments, $createdById) {
            try {
                return DB::transaction(function () use ($sale, $payments, $createdById) {
                    $sumPaid = 0;
                    $remaining = (int) max($sale->outstanding_amount, 0);
                    foreach ($payments as $p) {
                        if (!$p instanceof SalesPaymentData) {
                            continue;
                        }
                        $amount = (int) $p->amount;
                        if ($amount <= 0) {
                            continue;
                        }
                        $pay = new Payment();
                        $pay->direction = PaymentDirection::In->value;
                        $pay->party_id = $sale->customer_id ? (string) $sale->customer_id : null;
                        $pay->party_type = Customer::class;
                        $pay->payment_date = Carbon::parse((string) $sale->sale_datetime)->toDateString();
                        $pay->payment_method_id = (string) $p->paymentMethodId;
                        $pay->amount = $amount;
                        $pay->reference_number = $p->referenceNumber;
                        $pay->cash_bank_account_id = $p->cashBankAccountId;
                        $pay->notes = $p->notes;
                        $pay->created_by_id = $createdById;
                        $pay->status = PaymentStatus::Posted->value;
                        $pay->posted_at = now()->toDateTimeString();
                        $pay->save();

                        $allocAmount = min($amount, $remaining);
                        if ($allocAmount > 0) {
                            $alloc = new PaymentAllocation();
                            $alloc->payment_id = (string) $pay->id;
                            $alloc->referencable_id = (string) $sale->id;
                            $alloc->referencable_type = Sales::class;
                            $alloc->amount = $allocAmount;
                            $alloc->allocated_at = now()->toDateTimeString();
                            $alloc->allocated_by_id = $createdById;
                            $alloc->is_void = false;
                            $alloc->save();
                        }

                        if ($sale->cashier_session_id) {
                            $this->accumulateShiftPayment((string) $sale->cashier_session_id, (string) $p->paymentMethodId, $allocAmount);
                        }

                        $sumPaid += $allocAmount;
                        $remaining -= $allocAmount;
                        if ($remaining <= 0) {
                            break;
                        }
                    }

                    $sale->outstanding_amount = (int) max(((int) $sale->outstanding_amount) - $sumPaid, 0);
                    if ($sale->outstanding_amount <= 0) {
                        $sale->payment_status = SalesPaymentStatus::Paid->value;
                    } elseif ($sale->outstanding_amount >= (int) $sale->grand_total) {
                        $sale->payment_status = SalesPaymentStatus::Unpaid->value;
                    } else {
                        $sale->payment_status = SalesPaymentStatus::PartiallyPaid->value;
                    }
                    $sale->save();

                    return $sale;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('SalesService::receivePayments failed', [
                    'sales_id' => (string) $sale->id,
                    'user_id' => $createdById,
                    'payments_count' => is_array($payments) ? count($payments) : 0,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    /**
     * Perbarui transaksi penjualan untuk skenario draft/deposit.
     *
     * Mendukung:
     * - Update informasi pengiriman dan charges (shipping/rounding)
     * - Update flag pajak, voucher, catatan, dan tanggal transaksi
     * - Tambah pembayaran (payments/payment) untuk mengurangi outstanding
     *
     * Tidak mengubah item; perubahan item dilakukan via retur/pengiriman terpisah.
     *
     * @param Sales  $sale
     * @param array  $payload
     * @param string $userId
     * @return Sales
     *
     * @throws \Throwable
     */
    public function update(Sales $sale, array $payload, string $userId): Sales
    {
        return $this->runWithRetry(function () use ($sale, $payload, $userId) {
            try {
                return DB::transaction(function () use ($sale, $payload, $userId) {
                    /** @var Sales $locked */
                    $locked = Sales::query()->where('id', (string) $sale->id)->lockForUpdate()->first();
                    if (!$locked) {
                        throw new \InvalidArgumentException('Sales not found');
                    }
                    if (isset($payload['sale_datetime']) && $payload['sale_datetime']) {
                        $locked->sale_datetime = Carbon::parse((string) $payload['sale_datetime'])->toDateTimeString();
                    }
                    if (array_key_exists('customer_id', $payload)) {
                        $locked->customer_id = $payload['customer_id'] ? (string) $payload['customer_id'] : null;
                    }
                    if (isset($payload['requires_delivery'])) {
                        $locked->requires_delivery = (bool) $payload['requires_delivery'];
                    }
                    if (isset($payload['is_value_added_tax_enabled'])) {
                        $locked->is_value_added_tax_enabled = (bool) $payload['is_value_added_tax_enabled'];
                    }
                    if (array_key_exists('value_added_tax_id', $payload)) {
                        $locked->value_added_tax_id = $payload['value_added_tax_id'] ? (string) $payload['value_added_tax_id'] : null;
                    }
                    if (array_key_exists('value_added_tax_percentage', $payload)) {
                        $locked->value_added_tax_percentage = $payload['value_added_tax_percentage'] !== null
                            ? (string) $payload['value_added_tax_percentage']
                            : $locked->value_added_tax_percentage;
                    }
                    if (array_key_exists('notes', $payload)) {
                        $locked->notes = $payload['notes'] ? (string) $payload['notes'] : null;
                    }
                    $existingItems = SalesItem::query()->where('sales_id', (string) $locked->id)->get();
                    if (isset($payload['items']) && is_array($payload['items'])) {
                        $hasCompletedDelivery = SalesDelivery::query()
                            ->where('sales_id', (string) $locked->id)
                            ->where('status', \App\Enums\SalesDeliveryStatus::Completed->value)
                            ->exists();
                        if ($hasCompletedDelivery) {
                            throw new \RuntimeException('Items tidak dapat diubah karena pengiriman sudah selesai.');
                        }
                        $warehouseId = (string) $locked->warehouse_id;
                        $existingAgg = [];
                        foreach ($existingItems as $it) {
                            $pid = (string) $it->product_id;
                            $existingAgg[$pid] = ($existingAgg[$pid] ?? 0) + (int) $it->quantity;
                        }
                        $newItemsData = [];
                        $newAgg = [];
                        $priceMap = [];
                        foreach ($payload['items'] as $row) {
                            $pid = (string) $row['product_id'];
                            $qty = (int) $row['quantity'];
                            $price = (int) $row['price'];
                            $notes = isset($row['notes']) ? (string) $row['notes'] : null;
                            $newItemsData[] = ['product_id' => $pid, 'quantity' => $qty, 'price' => $price, 'notes' => $notes];
                            $newAgg[$pid] = ($newAgg[$pid] ?? 0) + $qty;
                            $priceMap[$pid][] = ['qty' => $qty, 'price' => $price];
                        }
                        $allPids = array_values(array_unique(array_merge(array_keys($existingAgg), array_keys($newAgg))));
                        foreach ($allPids as $pid) {
                            $before = (int) ($existingAgg[$pid] ?? 0);
                            $after = (int) ($newAgg[$pid] ?? 0);
                            $delta = $after - $before;
                            if ($delta > 0) {
                                $avgPrice = 0;
                                if (!empty($priceMap[$pid])) {
                                    $sumQty = 0;
                                    $sumVal = 0;
                                    foreach ($priceMap[$pid] as $pp) {
                                        $sumQty += (int) $pp['qty'];
                                        $sumVal += (int) ($pp['qty'] * $pp['price']);
                                    }
                                    $avgPrice = $sumQty > 0 ? (int) round($sumVal / $sumQty) : 0;
                                }
                                app(StockService::class)->deductStockWithPriority(
                                    $warehouseId,
                                    (string) $pid,
                                    (int) $delta,
                                    (string) $locked->id,
                                    Sales::class,
                                    null,
                                    $userId,
                                    'SLS_OUT',
                                    (int) $avgPrice,
                                    $userId
                                );
                            } elseif ($delta < 0) {
                                $bucket = $locked->is_value_added_tax_enabled ? StockBucket::Vat : StockBucket::NonVat;
                                $stockRow = app(StockService::class)->getOrCreateStockByBucket($warehouseId, (string) $pid, null, null, $bucket);
                                $qtyIn = (int) abs($delta);
                                $prevQty = (int) $stockRow->quantity;
                                $newQty = $prevQty + $qtyIn;
                                $unitCost = $this->resolveAdjustmentUnitCostForProduct((string) $warehouseId, (string) $pid, $bucket);
                                app(StockService::class)->updateAverageCostInbound($warehouseId, (string) $pid, $qtyIn, $unitCost, $bucket);
                                $stockRow->quantity = $newQty;
                                $stockRow->save();
                                app(StockService::class)->createStockCard(
                                    $stockRow,
                                    'SLS_IN',
                                    $qtyIn,
                                    $newQty,
                                    (string) $locked->id,
                                    Sales::class,
                                    null,
                                    $userId,
                                    (int) $unitCost
                                );
                            }
                        }
                        SalesItem::query()->where('sales_id', (string) $locked->id)->delete();
                        $sumSubtotal = 0;
                        $newItemsRecords = [];
                        foreach ($newItemsData as $row) {
                            $si = new SalesItem();
                            $si->sales_id = (string) $locked->id;
                            $si->product_id = (string) $row['product_id'];
                            $si->quantity = (int) $row['quantity'];
                            $si->price = (int) $row['price'];
                            $si->subtotal = (int) ($si->price * $si->quantity);
                            $si->notes = $row['notes'];
                            $si->delivered_quantity = 0;
                            $si->returned_quantity = 0;
                            $si->cost_price = 0;
                            $si->save();
                            $sumSubtotal += (int) $si->subtotal;
                            $newItemsRecords[] = $si;
                        }
                        $locked->subtotal = (int) $sumSubtotal;
                        $deliveriesToSync = SalesDelivery::query()
                            ->where('sales_id', (string) $locked->id)
                            ->where('status', '!=', \App\Enums\SalesDeliveryStatus::Completed->value)
                            ->get();
                        foreach ($deliveriesToSync as $del) {
                            SalesDeliveryItem::query()
                                ->where('sales_delivery_id', (string) $del->id)
                                ->delete();
                            foreach ($newItemsRecords as $si) {
                                $di = new SalesDeliveryItem();
                                $di->sales_delivery_id = (string) $del->id;
                                $di->sales_item_id = (string) $si->id;
                                $di->product_id = (string) $si->product_id;
                                $di->quantity = (int) $si->quantity;
                                $di->notes = null;
                                $di->save();
                            }
                        }
                    } else {
                        if (array_key_exists('subtotal', $payload)) {
                            $locked->subtotal = (int) ($payload['subtotal'] ?? (int) $locked->subtotal);
                        }
                    }
                    if (array_key_exists('item_discount_total', $payload) || array_key_exists('extra_discount_total', $payload)) {
                        $itemDisc = (int) ($payload['item_discount_total'] ?? (int) $locked->item_discount_total);
                        $extraDisc = (int) ($payload['extra_discount_total'] ?? (int) $locked->extra_discount_total);
                        $locked->item_discount_total = $itemDisc;
                        $locked->extra_discount_total = $extraDisc;
                        $locked->discount_amount = (int) ($itemDisc + $extraDisc);
                        $locked->discount = (int) ($itemDisc + $extraDisc);
                    }
                    if (array_key_exists('discount_percentage', $payload)) {
                        $locked->discount_percentage = $payload['discount_percentage'] !== null ? (string) $payload['discount_percentage'] : null;
                    }
                    if (array_key_exists('total_after_discount', $payload)) {
                        $locked->total_after_discount = (int) ($payload['total_after_discount'] ?? (int) $locked->total_after_discount);
                    } else {
                        $locked->total_after_discount = (int) max((int) $locked->subtotal - (int) $locked->discount_amount, 0);
                    }
                    if (array_key_exists('value_added_tax_amount', $payload)) {
                        $locked->value_added_tax_amount = (int) ($payload['value_added_tax_amount'] ?? (int) $locked->value_added_tax_amount);
                    }
                    if (array_key_exists('voucher_code', $payload)) {
                        $locked->voucher_code = $payload['voucher_code'] ? (string) $payload['voucher_code'] : null;
                    }
                    if (array_key_exists('voucher_amount', $payload)) {
                        $locked->voucher_amount = $payload['voucher_amount'] !== null ? (int) $payload['voucher_amount'] : null;
                        $saleItems = SalesItem::query()->where('sales_id', (string) $locked->id)->get();
                        $sumSub = (int) $saleItems->sum(fn($it) => (int) ($it->subtotal ?? 0));
                        $providedAmount = (int) max((int) ($locked->voucher_amount ?? 0), 0);
                        $allocated = 0;
                        $lastIndex = count($saleItems) - 1;
                        foreach ($saleItems->values() as $idx => $it) {
                            if ($providedAmount <= 0 || $sumSub <= 0) {
                                $it->voucher_share = 0;
                            } else {
                                if ($idx === $lastIndex) {
                                    $share = (int) ($providedAmount - $allocated);
                                } else {
                                    $ratio = ((float) $it->subtotal) / ((float) $sumSub);
                                    $share = (int) round(((float) $providedAmount) * $ratio);
                                    $allocated += $share;
                                }
                                $it->voucher_share = (int) max(0, $share);
                            }
                            $it->save();
                        }
                    }
                    $locked->save();
                    if (array_key_exists('shipping_amount', $payload) && $payload['shipping_amount'] !== null) {
                        $this->upsertShippingAmount($locked, (int) $payload['shipping_amount']);
                        $locked = Sales::query()->where('id', (string) $locked->id)->lockForUpdate()->first();
                    }
                    if (array_key_exists('rounding_amount', $payload) && $payload['rounding_amount'] !== null) {
                        $this->upsertRoundingAmount($locked, (int) $payload['rounding_amount']);
                        $locked = Sales::query()->where('id', (string) $locked->id)->lockForUpdate()->first();
                    }
                    $paid = (int) PaymentAllocation::query()
                        ->where('referencable_id', (string) $locked->id)
                        ->where('referencable_type', Sales::class)
                        ->sum('amount');
                    $locked->grand_total = (int) max(0, (int) ($locked->total_after_discount + $locked->value_added_tax_amount + (int) SalesCharge::query()->where('sales_id', (string) $locked->id)->sum('amount')));
                    $locked->outstanding_amount = (int) max($locked->grand_total - $paid, 0);
                    if ($locked->outstanding_amount <= 0) {
                        $locked->payment_status = SalesPaymentStatus::Paid->value;
                    } elseif ($locked->outstanding_amount >= (int) $locked->grand_total) {
                        $locked->payment_status = SalesPaymentStatus::Unpaid->value;
                    } else {
                        $locked->payment_status = SalesPaymentStatus::PartiallyPaid->value;
                    }
                    $locked->save();
                    $newPayments = [];
                    if (isset($payload['payments']) && is_array($payload['payments'])) {
                        foreach ($payload['payments'] as $row) {
                            $dto = SalesPaymentData::fromArray($row);
                            if ($dto !== null) {
                                $newPayments[] = $dto;
                            }
                        }
                    } elseif (isset($payload['payment']) && is_array($payload['payment'])) {
                        $dto = SalesPaymentData::fromArray($payload['payment']);
                        if ($dto !== null) {
                            $newPayments[] = $dto;
                        }
                    }
                    if (!empty($newPayments) && $locked->customer_id) {
                        $locked = $this->receivePayments($locked, $newPayments, $userId);
                    }
                    return $locked;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('SalesService::update failed', [
                    'sales_id' => (string) $sale->id,
                    'user_id' => $userId,
                    'payload_keys' => array_keys($payload),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    public function createManualDelivery(
        Sales $sale,
        ?string $recipientName,
        ?string $recipientPhone,
        ?string $deliveryAddress,
        ?string $shippingNote,
        ?int $shippingAmount,
        string $createdById
    ): SalesDelivery {
        return $this->runWithRetry(function () use ($sale, $recipientName, $recipientPhone, $deliveryAddress, $shippingNote, $shippingAmount, $createdById) {
            try {
                return DB::transaction(function () use ($sale, $recipientName, $recipientPhone, $deliveryAddress, $shippingNote, $shippingAmount, $createdById) {
                    /** @var Sales $locked */
                    $locked = Sales::query()->where('id', (string) $sale->id)->lockForUpdate()->first();
                    if (!$locked) {
                        throw new \InvalidArgumentException('Sales not found');
                    }
                    $del = new SalesDelivery();
                    $del->sales_id = (string) $locked->id;
                    $del->warehouse_id = (string) $locked->warehouse_id;
                    $del->number = $this->generateDeliveryNumber();
                    $del->delivery_date = Carbon::parse((string) $locked->sale_datetime)->toDateString();
                    $del->recipient_name = $recipientName ?: ($locked->customer ? (string) ($locked->customer->name ?? '') : null);
                    $del->recipient_phone = $recipientPhone ?: ($locked->customer ? (string) ($locked->customer->phone ?? '') : null);
                    $del->delivery_address = $deliveryAddress ?: ($locked->customer ? (string) ($locked->customer->address ?? '') : null);
                    $del->notes = $shippingNote;
                    $del->created_by_id = $createdById;
                    $del->save();

                    $items = SalesItem::query()->where('sales_id', (string) $locked->id)->get();
                    foreach ($items as $si) {
                        $di = new SalesDeliveryItem();
                        $di->sales_delivery_id = (string) $del->id;
                        $di->sales_item_id = (string) $si->id;
                        $di->product_id = (string) $si->product_id;
                        $di->quantity = (int) $si->quantity;
                        $di->notes = null;
                        $di->save();
                    }

                    if ($shippingAmount !== null) {
                        $this->upsertShippingAmount($locked, (int) $shippingAmount);
                    }

                    return $del;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('SalesService::createManualDelivery failed', [
                    'sales_id' => (string) $sale->id,
                    'user_id' => $createdById,
                    'shipping_amount' => $shippingAmount,
                    'recipient_name' => $recipientName,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    private function upsertShippingAmount(Sales $sale, int $amount): void
    {
        $row = SalesCharge::query()
            ->where('sales_id', (string) $sale->id)
            ->where('type', SalesChargeType::Shipping->value)
            ->lockForUpdate()
            ->first();
        if (!$row) {
            $row = new SalesCharge();
            $row->sales_id = (string) $sale->id;
            $row->type = SalesChargeType::Shipping->value;
            $row->description = 'Biaya Pengiriman';
            $row->amount = (int) $amount;
            $row->save();
        } else {
            $row->amount = (int) $amount;
            $row->save();
        }

        $sumCharges = (int) SalesCharge::query()
            ->where('sales_id', (string) $sale->id)
            ->sum('amount');
        $sale->grand_total = (int) ($sale->total_after_discount + $sale->value_added_tax_amount + $sumCharges);

        $paid = (int) PaymentAllocation::query()
            ->where('referencable_id', (string) $sale->id)
            ->where('referencable_type', Sales::class)
            ->sum('amount');
        $sale->outstanding_amount = (int) max($sale->grand_total - $paid, 0);
        if ($sale->outstanding_amount <= 0) {
            $sale->payment_status = SalesPaymentStatus::Paid->value;
        } elseif ($sale->outstanding_amount >= (int) $sale->grand_total) {
            $sale->payment_status = SalesPaymentStatus::Unpaid->value;
        } else {
            $sale->payment_status = SalesPaymentStatus::PartiallyPaid->value;
        }
        $sale->save();
    }

    private function upsertRoundingAmount(Sales $sale, int $amount): void
    {
        $row = SalesCharge::query()
            ->where('sales_id', (string) $sale->id)
            ->where('type', SalesChargeType::Rounding->value)
            ->lockForUpdate()
            ->first();
        if (!$row) {
            $row = new SalesCharge();
            $row->sales_id = (string) $sale->id;
            $row->type = SalesChargeType::Rounding->value;
            $row->description = (int) $amount >= 0 ? 'Pembulatan +' : 'Pembulatan -';
            $row->amount = (int) $amount;
            $row->save();
        } else {
            $row->amount = (int) $amount;
            $row->description = (int) $amount >= 0 ? 'Pembulatan +' : 'Pembulatan -';
            $row->save();
        }
        $sumCharges = (int) SalesCharge::query()
            ->where('sales_id', (string) $sale->id)
            ->sum('amount');
        $sale->grand_total = (int) ($sale->total_after_discount + $sale->value_added_tax_amount + $sumCharges);
        $paid = (int) PaymentAllocation::query()
            ->where('referencable_id', (string) $sale->id)
            ->where('referencable_type', Sales::class)
            ->sum('amount');
        $sale->outstanding_amount = (int) max($sale->grand_total - $paid, 0);
        if ($sale->outstanding_amount <= 0) {
            $sale->payment_status = SalesPaymentStatus::Paid->value;
        } elseif ($sale->outstanding_amount >= (int) $sale->grand_total) {
            $sale->payment_status = SalesPaymentStatus::Unpaid->value;
        } else {
            $sale->payment_status = SalesPaymentStatus::PartiallyPaid->value;
        }
        $sale->save();
    }

    private function resolveAdjustmentUnitCostForProduct(string $warehouseId, string $productId, StockBucket $bucket): int
    {
        $avg = AverageCost::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('bucket', $bucket)
            ->lockForUpdate()
            ->first();
        return $avg ? (int) $avg->cost : 0;
    }

    /**
     * Tentukan persentase PPN dari DTO (id atau nilai bebas).
     *
     * @param SalesData $data
     * @return string|null
     */
    private function resolveVatPercentage(SalesData $data): ?string
    {
        if ($data->valueAddedTaxId) {
            $row = ValueAddedTax::query()->where('id', (string) $data->valueAddedTaxId)->first(['percentage']);
            if ($row && $row->percentage !== null) {
                return (string) $row->percentage;
            }
        }
        return $data->valueAddedTaxPercentage;
    }

    private function resolveDiscountFromMaster(Sales $sale, array $items): array
    {
        $saleDate = Carbon::parse((string) $sale->sale_datetime)->format('Y-m-d H:i:s');
        $discounts = Discount::query()
            ->where('is_active', true)
            ->where(function ($q) use ($saleDate) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $saleDate);
            })
            ->where(function ($q) use ($saleDate) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $saleDate);
            })
            ->with(['items'])
            ->get();

        if ($discounts->isEmpty()) {
            return [0, null];
        }

        $productIds = array_values(array_unique(array_map(static fn($si) => (string) $si->product_id, $items)));
        $prodRows = Product::query()
            ->whereIn('id', $productIds)
            ->get(['id', 'product_category_id', 'product_sub_category_id']);
        $categoryMap = [];
        $subCategoryMap = [];
        foreach ($prodRows as $pr) {
            $categoryMap[(string) $pr->id] = $pr->product_category_id ? (string) $pr->product_category_id : null;
            $subCategoryMap[(string) $pr->id] = $pr->product_sub_category_id ? (string) $pr->product_sub_category_id : null;
        }

        $subtotal = (int) $sale->subtotal;
        $bestGlobalAmount = 0;
        $bestGlobalPct = null;
        foreach ($discounts as $d) {
            $scope = (string) $d->scope;
            $valueType = (string) $d->value_type;
            $value = $d->value !== null ? (float) $d->value : 0.0;
            if ($scope === 'global') {
                $amt = 0;
                if ($valueType === 'percentage') {
                    $amt = (int) round(((float) $subtotal * $value) / 100.0);
                } else {
                    $amt = (int) min((int) round($value), $subtotal);
                }
                if ($amt > $bestGlobalAmount) {
                    $bestGlobalAmount = $amt;
                    $bestGlobalPct = $valueType === 'percentage' ? (string) $d->value : null;
                }
            }
        }

        $sumSpecificAmount = 0;
        $specificDiscounts = $discounts->filter(static fn($d) => (string) $d->scope === 'specific');
        if ($specificDiscounts->isNotEmpty()) {
            foreach ($items as $si) {
                $bestPerItem = 0;
                foreach ($specificDiscounts as $d) {
                    foreach ($d->items as $di) {
                        $match = false;
                        $type = (string) $di->itemable_type;
                        $itemableId = (string) $di->itemable_id;
                        if ($type === Product::class && $itemableId === (string) $si->product_id) {
                            $match = true;
                        } elseif ($type === ProductCategory::class) {
                            $catId = $categoryMap[(string) $si->product_id] ?? null;
                            if ($catId !== null && $itemableId === (string) $catId) {
                                $match = true;
                            }
                        } elseif ($type === ProductSubCategory::class) {
                            $subCatId = $subCategoryMap[(string) $si->product_id] ?? null;
                            if ($subCatId !== null && $itemableId === (string) $subCatId) {
                                $match = true;
                            }
                        }
                        if (!$match) {
                            continue;
                        }
                        $discountType = strtolower((string) $d->type);
                        $amount = 0;
                        if ($discountType === 'bogo') {
                            $minQtyBuy = (int) ($di->min_qty_buy ?? 0);
                            $freeQtyGet = (int) ($di->free_qty_get ?? 0);
                            $isMultiple = (bool) ($di->is_multiple ?? false);
                            if ($minQtyBuy > 0 && $freeQtyGet > 0) {
                                $bundles = $isMultiple
                                    ? (int) floor(((int) $si->quantity) / $minQtyBuy)
                                    : (((int) $si->quantity) >= $minQtyBuy ? 1 : 0);
                                if ($bundles > 0) {
                                    $freeUnits = $bundles * $freeQtyGet;
                                    $freeProductId = $di->free_product_id ? (string) $di->free_product_id : (string) $si->product_id;
                                    $unitPrice = 0;
                                    if ($freeProductId === (string) $si->product_id) {
                                        $unitPrice = (int) $si->price;
                                    } else {
                                        $unitPrice = (int) (ProductSellingPrice::query()
                                            ->where('product_id', $freeProductId)
                                            ->whereNull('selling_price_level_id')
                                            ->value('price') ?? 0);
                                        if ($unitPrice <= 0) {
                                            $unitPrice = (int) (ProductPurchasePrice::query()
                                                ->where('product_id', $freeProductId)
                                                ->value('price') ?? 0);
                                        }
                                    }
                                    if ($unitPrice > 0) {
                                        $amount = (int) ($unitPrice * $freeUnits);
                                    }
                                }
                            }
                        } else {
                            $valueType = (string) $d->value_type;
                            $rawVal = $di->custom_value !== null ? (float) $di->custom_value : ($d->value !== null ? (float) $d->value : 0.0);
                            $minQtyBuy = (int) ($di->min_qty_buy ?? 0);
                            $isMultiple = (bool) ($di->is_multiple ?? false);
                            if ($minQtyBuy > 0 && ((int) $si->quantity) < $minQtyBuy) {
                                $amount = 0;
                            } elseif ($valueType === 'percentage') {
                                $base = (int) round(((float) $si->subtotal * $rawVal) / 100.0);
                                if ($minQtyBuy > 0) {
                                    $bundles = $isMultiple
                                        ? (int) floor(((int) $si->quantity) / $minQtyBuy)
                                        : (((int) $si->quantity) >= $minQtyBuy ? 1 : 0);
                                    $amount = (int) min($base * max($bundles, 0), (int) $si->subtotal);
                                } else {
                                    $amount = $base;
                                }
                            } else {
                                if ($minQtyBuy > 0) {
                                    $bundles = $isMultiple
                                        ? (int) floor(((int) $si->quantity) / $minQtyBuy)
                                        : (((int) $si->quantity) >= $minQtyBuy ? 1 : 0);
                                    $amount = (int) round($rawVal) * max($bundles, 0);
                                } else {
                                    $amount = (int) round($rawVal) * (int) $si->quantity;
                                }
                            }
                        }
                        if ($amount > $bestPerItem) {
                            $bestPerItem = $amount;
                        }
                    }
                }
                $sumSpecificAmount += (int) $bestPerItem;
            }
        }

        if ($sumSpecificAmount >= $bestGlobalAmount) {
            return [(int) $sumSpecificAmount, null];
        }
        return [(int) $bestGlobalAmount, $bestGlobalPct];
    }

    /**
     * Buat baris biaya penjualan.
     *
     * @param Sales $sale
     * @param string $type
     * @param string|null $description
     * @param int $amount
     * @return void
     */
    private function createCharge(Sales $sale, string $type, ?string $description, int $amount): void
    {
        $c = new SalesCharge();
        $c->sales_id = (string) $sale->id;
        $c->type = $type;
        $c->description = $description;
        $c->amount = $amount;
        $c->save();
    }

    /**
     * Buat Payment IN dan alokasikan ke Sales.
     *
     * @param Sales $sale
     * @param SalesData $data
     * @return int jumlah dibayar
     */
    private function createPaymentsAndAllocations(Sales $sale, SalesData $data): int
    {
        $remaining = (int) $sale->grand_total;
        $sumPaid = 0;
        foreach ($data->payments as $p) {
            $amount = (int) $p->amount;
            if ($amount <= 0) {
                continue;
            }
            $pay = new Payment();
            $pay->direction = PaymentDirection::In->value;
            $pay->party_id = (string) $data->customerId;
            $pay->party_type = Customer::class;
            $pay->payment_date = Carbon::parse((string) $sale->sale_datetime)->toDateString();
            $pay->payment_method_id = (string) $p->paymentMethodId;
            $pay->amount = $amount;
            $pay->reference_number = $p->referenceNumber;
            $pay->cash_bank_account_id = $p->cashBankAccountId;
            $pay->notes = $p->notes;
            $pay->created_by_id = $data->createdById;
            $pay->status = PaymentStatus::Posted->value;
            $pay->posted_at = now()->toDateTimeString();
            $pay->save();

            $allocAmount = min($amount, $remaining);
            if ($allocAmount > 0) {
                $alloc = new PaymentAllocation();
                $alloc->payment_id = (string) $pay->id;
                $alloc->referencable_id = (string) $sale->id;
                $alloc->referencable_type = Sales::class;
                $alloc->amount = $allocAmount;
                $alloc->allocated_at = now()->toDateTimeString();
                $alloc->allocated_by_id = $data->createdById;
                $alloc->is_void = false;
                $alloc->save();
            }

            if ($sale->cashier_session_id) {
                $this->accumulateShiftPayment((string) $sale->cashier_session_id, (string) $p->paymentMethodId, $allocAmount);
            }

            $sumPaid += $allocAmount;
            $remaining -= $allocAmount;
            if ($remaining <= 0) {
                break;
            }
        }
        return $sumPaid;
    }

    /**
     * Akumulasi total per-metode pembayaran pada shift kasir.
     *
     * @param string $shiftId
     * @param string $paymentMethodId
     * @param int $amount
     * @return void
     */
    private function accumulateShiftPayment(string $shiftId, string $paymentMethodId, int $amount): void
    {
        $session = CashierSession::query()->where('id', $shiftId)->lockForUpdate()->first();
        if ($session) {
            $session->expected_cash = (int) $session->expected_cash + (int) $amount;
            $session->save();
        }
    }

    /**
     * Akumulasi total penjualan dan kas masuk pada shift kasir.
     *
     * @param string $shiftId
     * @param int $grandTotal
     * @param int $cashIn
     * @return void
     */
    private function accumulateShiftTotals(string $shiftId, int $grandTotal, int $cashIn): void
    {
        $session = CashierSession::query()->where('id', $shiftId)->lockForUpdate()->first();
        if ($session) {
            $session->expected_cash = (int) $session->expected_cash + (int) $cashIn;
            $session->save();
        }
    }

    private function createDeliveryWithItems(Sales $sale, array $items, SalesData $data): void
    {
        $del = new SalesDelivery();
        $del->sales_id = (string) $sale->id;
        $del->warehouse_id = (string) $sale->warehouse_id;
        $del->number = $this->generateDeliveryNumber();
        $del->delivery_date = Carbon::parse((string) $sale->sale_datetime)->toDateString();
        $del->recipient_name = $data->shippingRecipientName;
        $del->recipient_phone = $data->shippingRecipientPhone;
        $del->delivery_address = $data->shippingAddress;
        $del->notes = $data->shippingNote;
        $del->created_by_id = $data->createdById;
        $del->save();

        foreach ($items as $si) {
            $di = new SalesDeliveryItem();
            $di->sales_delivery_id = (string) $del->id;
            $di->sales_item_id = (string) $si->id;
            $di->product_id = (string) $si->product_id;
            $di->quantity = (int) $si->quantity;
            $di->notes = null;
            $di->save();
        }
    }

    private function generateDeliveryNumber(): string
    {
        $monthYear = now()->format('mY');
        $prefix = 'SJ/' . $monthYear . '/';
        $last = SalesDelivery::query()
            ->where('number', 'like', $prefix . '%')
            ->orderByDesc('number')
            ->value('number');
        $seq = 1;
        if (is_string($last)) {
            $parts = explode('/', $last);
            $lastSeq = (int) ($parts[2] ?? 0);
            $seq = $lastSeq + 1;
        }
        return $prefix . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }

    public function createReturn(
        Sales $sale,
        array $items,
        string $reason,
        string $resolution,
        ?string $notes,
        string $createdById
    ): SalesReturn {
        return $this->runWithRetry(function () use ($sale, $items, $reason, $resolution, $notes, $createdById) {
            return DB::transaction(function () use ($sale, $items, $reason, $resolution, $notes, $createdById) {
                $locked = Sales::query()->where('id', (string) $sale->id)->lockForUpdate()->first();
                if (!$locked) {
                    throw new \InvalidArgumentException('Sales not found');
                }
                $ret = new SalesReturn();
                $ret->sales_id = (string) $locked->id;
                $ret->warehouse_id = (string) $locked->warehouse_id;
                $ret->number = $this->generateReturnNumber();
                $ret->return_datetime = now()->toDateTimeString();
                $ret->reason = $reason;
                $ret->resolution = $resolution;
                $ret->status = \App\Enums\SalesReturnStatus::Draft->value;
                $ret->notes = $notes;
                $ret->created_by_id = $createdById;
                $ret->refund_amount = 0;
                $ret->is_stock_returned = false;
                $ret->save();

                $saleItems = SalesItem::query()
                    ->where('sales_id', (string) $locked->id)
                    ->get()
                    ->keyBy(fn($si) => (string) $si->id);

                $total = 0;
                $returnItemsForStock = [];
                foreach ($items as $entry) {
                    $sid = (string) ($entry['sales_item_id'] ?? '');
                    $qty = (int) ($entry['quantity'] ?? 0);
                    if ($sid === '' || $qty <= 0) {
                        continue;
                    }
                    $si = $saleItems->get($sid);
                    if (!$si) {
                        throw new \InvalidArgumentException('Sales item not found: ' . $sid);
                    }
                    $maxReturnable = max((int) $si->quantity - (int) $si->returned_quantity, 0);
                    if ($qty > $maxReturnable) {
                        throw new \InvalidArgumentException('Return quantity exceeds allowed for item: ' . $sid);
                    }
                    $price = (int) $si->price;
                    $subtotal = (int) ($price * $qty);
                    $ri = new SalesReturnItem();
                    $ri->sales_return_id = (string) $ret->id;
                    $ri->sales_item_id = (string) $si->id;
                    $ri->product_id = (string) $si->product_id;
                    $ri->quantity = $qty;
                    $ri->price = $price;
                    $ri->subtotal = $subtotal;
                    $ri->notes = isset($entry['notes']) ? (string) $entry['notes'] : null;
                    $ri->save();
                    $si->returned_quantity = (int) $si->returned_quantity + $qty;
                    $si->save();
                    $total += $subtotal;
                    $returnItemsForStock[] = [
                        'product_id' => (string) $si->product_id,
                        'quantity' => $qty,
                        'notes' => isset($entry['notes']) ? (string) $entry['notes'] : null,
                        'sales_item_id' => (string) $si->id,
                    ];
                }

                if ($resolution === \App\Enums\SalesReturnResolution::Refund->value) {
                    $ret->refund_amount = (int) $total;
                } elseif ($resolution === \App\Enums\SalesReturnResolution::StoreCredit->value) {
                    if ($locked->customer_id) {
                        $credit = new CustomerStoreCredit();
                        $credit->customer_id = (string) $locked->customer_id;
                        $credit->source_referencable_type = \App\Models\SalesReturn::class;
                        $credit->source_referencable_id = (string) $ret->id;
                        $credit->amount = (int) $total;
                        $credit->remaining_amount = (int) $total;
                        $credit->status = \App\Enums\CustomerStoreCreditStatus::Active->value;
                        $credit->notes = $notes;
                        $credit->save();
                        $ret->customer_store_credit_id = (string) $credit->id;
                    }
                    $ret->refund_amount = 0;
                } else {
                    $ret->refund_amount = 0;
                }
                $ret->save();

                $this->returnStockForSalesReturn($ret, $returnItemsForStock);

                return $ret;
            }, 5);
        }, 3);
    }

    /**
     * Kurangi stok untuk transaksi Sales (POS) berdasarkan item penjualan.
     *
     * - Prioritas pengurangan: NonVat → Vat, lalu fallback owner stock jika relevan.
     * - Menulis kartu stok (StockCard) tipe SLS_OUT per item.
     * - Menandai status Sales menjadi Completed bila belum disetel.
     *
     * @param Sales      $sales Transaksi penjualan yang diproses.
     * @param array      $items Daftar item SalesItem atau payload array yang berisi product_id, quantity, notes.
     * @return Sales              Model Sales yang telah diperbarui.
     *
     * @throws \Throwable
     */
    public function issueStockForSales(Sales $sales, array $items): Sales
    {
        return $this->runWithRetry(function () use ($sales, $items) {
            try {
                return DB::transaction(function () use ($sales, $items) {
                    $warehouseId = (string) $sales->warehouse_id;
                    $ownerId = app(StockService::class)->resolveOwnerUserIdFromData(['created_by_id' => $sales->created_by_id]);
                    foreach ($items as $item) {
                        $si = $item instanceof SalesItem ? $item : $this->hydrateSalesItem($sales, $item);
                        $productId = (string) $si->product_id;
                        $qty = (int) $si->quantity;
                        $unitPrice = (int) ($si->price ?? 0);
                        if ($ownerId !== null) {
                            $remaining = $qty;
                            $rows = Stock::query()
                                ->where('product_id', $productId)
                                ->where('owner_user_id', $ownerId)
                                ->where('bucket', StockBucket::NonVat->value)
                                ->orderByDesc('quantity')
                                ->get(['warehouse_id', 'quantity']);
                            foreach ($rows as $r) {
                                if ($remaining <= 0) {
                                    break;
                                }
                                $locked = app(StockService::class)->getStockRowByBucket((string) $r->warehouse_id, $productId, null, null, StockBucket::NonVat, $ownerId);
                                if (!$locked || (int) $locked->quantity <= 0) {
                                    continue;
                                }
                                $take = min((int) $locked->quantity, $remaining);
                                $locked->quantity = (int) $locked->quantity - $take;
                                $locked->save();
                                app(StockService::class)->createStockCard($locked, 'SLS_OUT', $take, (int) $locked->quantity, (string) $sales->id, Sales::class, $si->notes, $sales->created_by_id, $unitPrice);
                                $remaining -= $take;
                            }
                            if ($remaining > 0) {
                                $rows = Stock::query()
                                    ->where('product_id', $productId)
                                    ->where('owner_user_id', $ownerId)
                                    ->whereNull('bucket')
                                    ->orderByDesc('quantity')
                                    ->get(['warehouse_id', 'quantity']);
                                foreach ($rows as $r) {
                                    if ($remaining <= 0) {
                                        break;
                                    }
                                    $locked = app(StockService::class)->getStockRowByNullBucket((string) $r->warehouse_id, $productId, null, null, $ownerId);
                                    if (!$locked || (int) $locked->quantity <= 0) {
                                        continue;
                                    }
                                    $take = min((int) $locked->quantity, $remaining);
                                    $locked->quantity = (int) $locked->quantity - $take;
                                    $locked->save();
                                    app(StockService::class)->createStockCard($locked, 'SLS_OUT', $take, (int) $locked->quantity, (string) $sales->id, Sales::class, $si->notes, $sales->created_by_id, $unitPrice);
                                    $remaining -= $take;
                                }
                            }
                            if ($remaining > 0) {
                                $rows = Stock::query()
                                    ->where('product_id', $productId)
                                    ->where('owner_user_id', $ownerId)
                                    ->where('bucket', StockBucket::Vat->value)
                                    ->orderByDesc('quantity')
                                    ->get(['warehouse_id', 'quantity']);
                                foreach ($rows as $r) {
                                    if ($remaining <= 0) {
                                        break;
                                    }
                                    $locked = app(StockService::class)->getStockRowByBucket((string) $r->warehouse_id, $productId, null, null, StockBucket::Vat, $ownerId);
                                    if (!$locked || (int) $locked->quantity <= 0) {
                                        continue;
                                    }
                                    $take = min((int) $locked->quantity, $remaining);
                                    $locked->quantity = (int) $locked->quantity - $take;
                                    $locked->save();
                                    app(StockService::class)->createStockCard($locked, 'SLS_OUT', $take, (int) $locked->quantity, (string) $sales->id, Sales::class, $si->notes, $sales->created_by_id, $unitPrice);
                                    $remaining -= $take;
                                }
                            }
                            if ($remaining > 0) {
                                $rows = Stock::query()
                                    ->where('product_id', $productId)
                                    ->whereNull('owner_user_id')
                                    ->where('bucket', StockBucket::NonVat->value)
                                    ->orderByDesc('quantity')
                                    ->get(['warehouse_id', 'quantity']);
                                foreach ($rows as $r) {
                                    if ($remaining <= 0) {
                                        break;
                                    }
                                    $locked = app(StockService::class)->getStockRowByBucket((string) $r->warehouse_id, $productId, null, null, StockBucket::NonVat, null);
                                    if (!$locked || (int) $locked->quantity <= 0) {
                                        continue;
                                    }
                                    $take = min((int) $locked->quantity, $remaining);
                                    $locked->quantity = (int) $locked->quantity - $take;
                                    $locked->save();
                                    app(StockService::class)->createStockCard($locked, 'SLS_OUT', $take, (int) $locked->quantity, (string) $sales->id, Sales::class, $si->notes, $sales->created_by_id, $unitPrice);
                                    $remaining -= $take;
                                }
                            }
                            if ($remaining > 0) {
                                $rows = Stock::query()
                                    ->where('product_id', $productId)
                                    ->whereNull('owner_user_id')
                                    ->whereNull('bucket')
                                    ->orderByDesc('quantity')
                                    ->get(['warehouse_id', 'quantity']);
                                foreach ($rows as $r) {
                                    if ($remaining <= 0) {
                                        break;
                                    }
                                    $locked = app(StockService::class)->getStockRowByNullBucket((string) $r->warehouse_id, $productId, null, null, null);
                                    if (!$locked || (int) $locked->quantity <= 0) {
                                        continue;
                                    }
                                    $take = min((int) $locked->quantity, $remaining);
                                    $locked->quantity = (int) $locked->quantity - $take;
                                    $locked->save();
                                    app(StockService::class)->createStockCard($locked, 'SLS_OUT', $take, (int) $locked->quantity, (string) $sales->id, Sales::class, $si->notes, $sales->created_by_id, $unitPrice);
                                    $remaining -= $take;
                                }
                            }
                            if ($remaining > 0) {
                                $rows = Stock::query()
                                    ->where('product_id', $productId)
                                    ->whereNull('owner_user_id')
                                    ->where('bucket', StockBucket::Vat->value)
                                    ->orderByDesc('quantity')
                                    ->get(['warehouse_id', 'quantity']);
                                foreach ($rows as $r) {
                                    if ($remaining <= 0) {
                                        break;
                                    }
                                    $locked = app(StockService::class)->getStockRowByBucket((string) $r->warehouse_id, $productId, null, null, StockBucket::Vat, null);
                                    if (!$locked || (int) $locked->quantity <= 0) {
                                        continue;
                                    }
                                    $take = min((int) $locked->quantity, $remaining);
                                    $locked->quantity = (int) $locked->quantity - $take;
                                    $locked->save();
                                    app(StockService::class)->createStockCard($locked, 'SLS_OUT', $take, (int) $locked->quantity, (string) $sales->id, Sales::class, $si->notes, $sales->created_by_id, $unitPrice);
                                    $remaining -= $take;
                                }
                            }
                        } else {
                            app(StockService::class)->deductStockWithPriority(
                                $warehouseId,
                                $productId,
                                $qty,
                                (string) $sales->id,
                                Sales::class,
                                $si->notes,
                                $sales->created_by_id,
                                'SLS_OUT',
                                $unitPrice,
                                null
                            );
                        }
                    }
                    $sales->status = $sales->status ?: SalesStatus::Completed->value;
                    $sales->save();
                    return $sales;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('SalesService::issueStockForSales failed', [
                    'sales_id' => (string) $sales->id,
                    'warehouse_id' => (string) $sales->warehouse_id,
                    'items_count' => is_array($items) ? count($items) : 0,
                    'user_id' => $sales->created_by_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    /**
     * Kurangi stok untuk Sales Delivery (pengiriman) berdasarkan item pengiriman.
     *
     * - Menulis kartu stok tipe SLS_OUT per item pengiriman.
     * - Menandai status Delivery menjadi Completed bila belum disetel.
     *
     * @param SalesDelivery $delivery Dokumen pengiriman terkait penjualan.
     * @param array         $items    Daftar SalesDeliveryItem atau payload array product_id, quantity, notes.
     * @return SalesDelivery          Model SalesDelivery yang telah diperbarui.
     *
     * @throws \Throwable
     */
    public function issueStockForSalesDelivery(SalesDelivery $delivery, array $items): SalesDelivery
    {
        return $this->runWithRetry(function () use ($delivery, $items) {
            try {
                return DB::transaction(function () use ($delivery, $items) {
                    $warehouseId = (string) $delivery->warehouse_id;
                    foreach ($items as $item) {
                        $di = $item instanceof SalesDeliveryItem ? $item : $this->hydrateSalesDeliveryItem($delivery, $item);
                        $productId = (string) $di->product_id;
                        $qty = (int) $di->quantity;

                        $linked = $di->sales_item_id ? SalesItem::query()->where('id', (string) $di->sales_item_id)->first() : null;
                        $unitPrice = $linked ? (int) ($linked->price ?? 0) : 0;

                        app(StockService::class)->deductStockWithPriority(
                            $warehouseId,
                            $productId,
                            $qty,
                            (string) $delivery->id,
                            SalesDelivery::class,
                            $di->notes,
                            $delivery->created_by_id,
                            'SLS_OUT',
                            $unitPrice,
                            $delivery->created_by_id
                        );
                    }
                    $delivery->status = $delivery->status ?: \App\Enums\SalesDeliveryStatus::Completed->value;
                    $delivery->save();
                    return $delivery;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('SalesService::issueStockForSalesDelivery failed', [
                    'delivery_id' => (string) $delivery->id,
                    'warehouse_id' => (string) $delivery->warehouse_id,
                    'items_count' => is_array($items) ? count($items) : 0,
                    'user_id' => $delivery->created_by_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    /**
     * Kembalikan stok untuk Sales Return berdasarkan item retur.
     *
     * - Menentukan bucket NonVat/Vat dari SalesItem terkait.
     * - Update Average Cost inbound sesuai bucket.
     * - Menulis kartu stok tipe SLS_IN.
     * - Menandai SalesReturn sebagai Completed dan is_stock_returned = true.
     *
     * @param SalesReturn $return Dokumen retur penjualan.
     * @param array       $items  Daftar SalesReturnItem atau payload array product_id, quantity, notes, sales_item_id.
     * @return SalesReturn        Model SalesReturn yang telah diperbarui.
     *
     * @throws \Throwable
     */
    public function returnStockForSalesReturn(SalesReturn $return, array $items): SalesReturn
    {
        return $this->runWithRetry(function () use ($return, $items) {
            try {
                return DB::transaction(function () use ($return, $items) {
                    $warehouseId = (string) $return->warehouse_id;
                    foreach ($items as $item) {
                        $ri = $item instanceof SalesReturnItem ? $item : $this->hydrateSalesReturnItem($return, $item);
                        $productId = (string) $ri->product_id;
                        $qty = (int) $ri->quantity;

                        $bucket = $this->resolveReturnBucketFromSalesReturnItem($ri);
                        $stockRow = app(StockService::class)->getOrCreateStockByBucket($warehouseId, $productId, null, null, $bucket);
                        $prevQty = (int) $stockRow->quantity;
                        $newQty = $prevQty + $qty;

                        $unitCost = $this->resolveReturnUnitCost($ri, $warehouseId, $productId);
                        app(StockService::class)->updateAverageCostInbound($warehouseId, $productId, $qty, $unitCost, $bucket);

                        $stockRow->quantity = $newQty;
                        $stockRow->save();

                        app(StockService::class)->createStockCard(
                            $stockRow,
                            'SLS_IN',
                            $qty,
                            $newQty,
                            (string) $return->id,
                            SalesReturn::class,
                            $ri->notes,
                            $return->created_by_id,
                            $unitCost
                        );
                    }
                    $return->is_stock_returned = true;
                    $return->status = $return->status ?: SalesReturnStatus::Completed->value;
                    $return->save();
                    return $return;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('SalesService::returnStockForSalesReturn failed', [
                    'sales_return_id' => (string) $return->id,
                    'warehouse_id' => (string) $return->warehouse_id,
                    'items_count' => is_array($items) ? count($items) : 0,
                    'user_id' => $return->created_by_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    /**
     * Bentuk SalesItem dari payload array sederhana.
     *
     * @param Sales $sales
     * @param array $data
     * @return SalesItem
     */
    private function hydrateSalesItem(Sales $sales, array $data): SalesItem
    {
        $si = new SalesItem();
        $si->sales_id = (string) $sales->id;
        $si->product_id = (string) $data['product_id'];
        $si->quantity = (int) $data['quantity'];
        $si->notes = isset($data['notes']) ? (string) $data['notes'] : null;
        return $si;
    }

    /**
     * Bentuk SalesDeliveryItem dari payload array sederhana.
     *
     * @param SalesDelivery $delivery
     * @param array         $data
     * @return SalesDeliveryItem
     */
    private function hydrateSalesDeliveryItem(SalesDelivery $delivery, array $data): SalesDeliveryItem
    {
        $di = new SalesDeliveryItem();
        $di->sales_delivery_id = (string) $delivery->id;
        $di->product_id = (string) $data['product_id'];
        $di->quantity = (int) $data['quantity'];
        $di->notes = isset($data['notes']) ? (string) $data['notes'] : null;
        return $di;
    }

    /**
     * Bentuk SalesReturnItem dari payload array sederhana.
     *
     * @param SalesReturn $return
     * @param array       $data
     * @return SalesReturnItem
     */
    private function hydrateSalesReturnItem(SalesReturn $return, array $data): SalesReturnItem
    {
        $ri = new SalesReturnItem();
        $ri->sales_return_id = (string) $return->id;
        $ri->product_id = (string) $data['product_id'];
        $ri->quantity = (int) $data['quantity'];
        $ri->notes = isset($data['notes']) ? (string) $data['notes'] : null;
        $ri->sales_item_id = isset($data['sales_item_id']) ? (string) $data['sales_item_id'] : null;
        return $ri;
    }

    /**
     * Tentukan biaya satuan untuk retur berdasarkan SalesItem yang ditautkan,
     * fallback ke AverageCost per bucket jika tidak tersedia.
     *
     * @param SalesReturnItem $ri
     * @param string          $warehouseId
     * @param string          $productId
     * @return int
     */
    private function resolveReturnUnitCost(SalesReturnItem $ri, string $warehouseId, string $productId): int
    {
        $cost = 0;
        if ($ri->sales_item_id) {
            $linked = SalesItem::query()->where('id', (string) $ri->sales_item_id)->first();
            if ($linked && $linked->cost_price !== null) {
                $cost = (int) $linked->cost_price;
            }
        }
        if ($cost === 0) {
            $bucket = $this->resolveReturnBucketFromSalesReturnItem($ri);
            $avg = AverageCost::query()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('bucket', $bucket)
                ->lockForUpdate()
                ->first();
            if ($avg) {
                $cost = (int) $avg->cost;
            }
        }
        return $cost;
    }

    /**
     * Tentukan bucket retur berdasarkan flag PPN dari Sales terkait item.
     *
     * @param SalesReturnItem $ri
     * @return StockBucket
     */
    private function resolveReturnBucketFromSalesReturnItem(SalesReturnItem $ri): StockBucket
    {
        if ($ri->sales_item_id) {
            $si = SalesItem::query()->where('id', (string) $ri->sales_item_id)->first();
            if ($si && $si->sales) {
                $sales = $si->sales;
                if ($sales->is_value_added_tax_enabled) {
                    return StockBucket::Vat;
                }
            }
        }
        return StockBucket::NonVat;
    }

    private function generateInvoiceNumber(): string
    {
        $monthYear = now()->format('mY');
        $prefix = 'INV/' . $monthYear . '/';
        $last = Sales::query()
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');
        $seq = 1;
        if (is_string($last)) {
            $parts = explode('/', $last);
            $lastSeq = (int) ($parts[2] ?? 0);
            $seq = $lastSeq + 1;
        }
        return $prefix . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }

    private function generateReceiptNumber(): string
    {
        $monthYear = now()->format('mY');
        $prefix = 'RCP/' . $monthYear . '/';
        $last = Sales::query()
            ->where('receipt_number', 'like', $prefix . '%')
            ->orderByDesc('receipt_number')
            ->value('receipt_number');
        $seq = 1;
        if (is_string($last)) {
            $parts = explode('/', $last);
            $lastSeq = (int) ($parts[2] ?? 0);
            $seq = $lastSeq + 1;
        }
        return $prefix . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }

    private function generateReturnNumber(): string
    {
        $monthYear = now()->format('mY');
        $prefix = 'SR/' . $monthYear . '/';
        $last = SalesReturn::query()
            ->where('number', 'like', $prefix . '%')
            ->orderByDesc('number')
            ->value('number');
        $seq = 1;
        if (is_string($last)) {
            $parts = explode('/', (string) $last);
            $lastSeq = (int) ($parts[2] ?? 0);
            $seq = $lastSeq + 1;
        }
        return $prefix . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}
