<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\{Warehouse, Customer, SalesItem, Sales, SalesDelivery, SalesCharge, PaymentAllocation, Discount, Product, ProductCategory, ProductSubCategory};
use App\Enums\{SalesPaymentStatus, SalesChargeType, SalesDeliveryStatus, SalesStatus};
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

/**
 * Service util untuk membangun payload tampilan transaksi penjualan (POS).
 *
 * Menyediakan helper non-transaksional yang aman digunakan dari Controller.
 *
 * @throws \Throwable Saat terjadi kegagalan akses data ataupun pemrosesan payload.
 */
final class SalesViewService
{
    /**
     * Bangun payload lengkap transaksi penjualan untuk tampilan/detail/print.
     *
     * @param Sales $sales
     * @param bool $includeDeliveryNumber Sertakan nomor Surat Jalan (delivery_number) bila tersedia.
     * @return array
     * @throws \Throwable
     */
    public function buildSalePayload(Sales $sales, bool $includeDeliveryNumber = false): array
    {
        try {
            $warehouse = $this->getWarehousePayload($sales->warehouse_id !== null ? (string) $sales->warehouse_id : null);
            $customer = $this->getCustomerPayload($sales->customer_id !== null ? (string) $sales->customer_id : null);
            $statusValue = $sales->payment_status instanceof SalesPaymentStatus
                ? $sales->payment_status->value
                : (string) $sales->payment_status;
            $paymentStatusLabel = SalesPaymentStatus::tryFrom($statusValue)?->label() ?? 'Tidak Diketahui';
            $itemsPayload = $this->getItemsPayload((string) $sales->id);
            $delivery = $this->getLatestDelivery((string) $sales->id);
            $shippingAmount = $this->getShippingAmount((string) $sales->id);
            $roundingAmount = $this->getRoundingAmount((string) $sales->id);
            $paymentsInfo = $this->getPaymentsInfo((string) $sales->id, (int) $sales->grand_total);

            $docStatusValue = $sales->status instanceof SalesStatus ? $sales->status->value : (string) $sales->status;
            $paymentStatusLabelEn = $statusValue === 'paid' ? 'Paid' : ($statusValue === 'partially_paid' ? 'Deposit' : 'Unpaid');
            $salesStateValue = $statusValue === 'paid'
                ? 'paid'
                : ($statusValue === 'partially_paid'
                    ? 'deposit'
                    : ($docStatusValue === 'draft'
                        ? 'draft'
                        : ($docStatusValue === 'canceled' ? 'canceled' : 'unpaid')));
            $salesStateLabel = $salesStateValue === 'paid' ? 'Paid'
                : ($salesStateValue === 'deposit' ? 'Deposit'
                    : ($salesStateValue === 'draft' ? 'Draft'
                        : ($salesStateValue === 'canceled' ? 'Canceled' : 'Unpaid')));

            $requiresDelivery = (bool) $sales->requires_delivery;
            $hasCompletedDelivery = $requiresDelivery
                ? SalesDelivery::query()
                ->where('sales_id', (string) $sales->id)
                ->where('status', SalesDeliveryStatus::Completed->value)
                ->exists()
                : false;
            $shippingStatusValue = $requiresDelivery ? ($hasCompletedDelivery ? 'shipped' : 'not_shipped') : 'not_applicable';
            $shippingStatusLabel = $shippingStatusValue === 'shipped' ? 'Shipped'
                : ($shippingStatusValue === 'not_shipped' ? 'Not Shipped' : 'Not Applicable');

            $totalsBreakdown = [
                'subtotal' => (int) $sales->subtotal,
                'item_discount_total' => (int) ($sales->item_discount_total ?? 0),
                'extra_discount_total' => (int) ($sales->extra_discount_total ?? 0),
                'voucher_code' => $sales->voucher_code !== null ? (string) $sales->voucher_code : null,
                'voucher_amount' => (int) ($sales->voucher_amount ?? 0),
                'tax_base' => (int) $sales->total_after_discount,
                'vat_percentage' => $sales->value_added_tax_percentage !== null ? (string) $sales->value_added_tax_percentage : null,
                'vat_amount' => (int) $sales->value_added_tax_amount,
                'shipping_amount' => (int) $shippingAmount,
                'rounding_amount' => (int) ($roundingAmount ?? 0),
                'grand_total' => (int) $sales->grand_total,
            ];

            $payload = [
                'id' => (string) $sales->id,
                'receipt_number' => (string) ($sales->receipt_number ?? ''),
                'invoice_number' => (string) ($sales->invoice_number ?? ''),
                'sale_datetime' => (string) ($sales->sale_datetime ?? ''),
                'warehouse' => $warehouse,
                'customer' => $customer,
                'payment_status' => $statusValue,
                'payment_status_label' => $paymentStatusLabel,
                'payment_status_label_en' => $paymentStatusLabelEn,
                'sales_state' => $salesStateValue,
                'sales_state_label' => $salesStateLabel,
                'subtotal' => (int) $sales->subtotal,
                'discount_percentage' => $sales->discount_percentage !== null ? (string) $sales->discount_percentage : null,
                'discount_amount' => (int) $sales->discount_amount,
                'discount' => (int) $sales->discount,
                'item_discount_total' => (int) ($sales->item_discount_total ?? 0),
                'extra_discount_total' => (int) ($sales->extra_discount_total ?? 0),
                'voucher_code' => $sales->voucher_code !== null ? (string) $sales->voucher_code : null,
                'voucher_amount' => (int) ($sales->voucher_amount ?? 0),
                'total_after_discount' => (int) $sales->total_after_discount,
                'is_value_added_tax_enabled' => (bool) $sales->is_value_added_tax_enabled,
                'value_added_tax_percentage' => $sales->value_added_tax_percentage !== null ? (string) $sales->value_added_tax_percentage : null,
                'value_added_tax_amount' => (int) $sales->value_added_tax_amount,
                'grand_total' => (int) $sales->grand_total,
                'outstanding_amount' => (int) $sales->outstanding_amount,
                'requires_delivery' => $requiresDelivery,
                'shipping_status' => $shippingStatusValue,
                'shipping_status_label' => $shippingStatusLabel,
                'items' => $itemsPayload,
                'shipping_amount' => (int) $shippingAmount,
                'rounding_amount' => (int) ($roundingAmount ?? 0),
                'shipping_recipient_name' => $delivery ? ($delivery->recipient_name !== null ? (string) $delivery->recipient_name : null) : null,
                'shipping_recipient_phone' => $delivery ? ($delivery->recipient_phone !== null ? (string) $delivery->recipient_phone : null) : null,
                'shipping_address' => $delivery ? ($delivery->delivery_address !== null ? (string) $delivery->delivery_address : null) : null,
                'shipping_note' => $delivery ? ($delivery->notes !== null ? (string) $delivery->notes : null) : null,
                'payments' => $paymentsInfo['payments'],
                'payment_total' => (int) $paymentsInfo['payment_total'],
                'change_amount' => (int) $sales->change_amount,
                'shortage_amount' => (int) $paymentsInfo['shortage_amount'],
                'totals_breakdown' => $totalsBreakdown,
            ];

            if ($includeDeliveryNumber && $delivery) {
                $payload['delivery_number'] = (string) $delivery->number;
            }

            return $payload;
        } catch (Throwable $e) {
            Log::error('SalesViewService.buildSalePayload failed', [
                'error' => $e->getMessage(),
                'sales_id' => (string) $sales->id,
                'include_delivery_number' => $includeDeliveryNumber,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function getWarehousePayload(?string $warehouseId): ?array
    {
        try {
            if ($warehouseId === null) {
                return null;
            }
            $wh = Warehouse::query()->where('id', $warehouseId)->first(['id', 'name']);
            if (!$wh) {
                return null;
            }
            return ['id' => (string) $wh->id, 'name' => (string) $wh->name];
        } catch (Throwable $e) {
            Log::error('SalesViewService.getWarehousePayload failed', [
                'error' => $e->getMessage(),
                'warehouse_id' => $warehouseId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function getCustomerPayload(?string $customerId): ?array
    {
        try {
            if ($customerId === null) {
                return null;
            }
            $c = Customer::query()->where('id', $customerId)->first(['id', 'name', 'phone', 'address']);
            if (!$c) {
                return null;
            }
            return [
                'id' => (string) $c->id,
                'name' => (string) $c->name,
                'phone' => $c->phone !== null ? (string) $c->phone : null,
                'address' => $c->address !== null ? (string) $c->address : null,
            ];
        } catch (Throwable $e) {
            Log::error('SalesViewService.getCustomerPayload failed', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function getItemsPayload(string $salesId): array
    {
        try {
            $itemsRows = SalesItem::query()
                ->where('sales_id', $salesId)
                ->with(['product'])
                ->get();
            $saleRow = Sales::query()->where('id', $salesId)->first(['sale_datetime']);
            $saleDate = $saleRow && $saleRow->sale_datetime
                ? Carbon::parse((string) $saleRow->sale_datetime)->format('Y-m-d H:i:s')
                : now()->format('Y-m-d H:i:s');
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
            $specificDiscounts = $discounts->filter(static fn($d) => (string) $d->scope === 'specific');
            $productIds = $itemsRows->map(static fn($si) => (string) $si->product_id)->unique()->values()->all();
            $prodRows = Product::query()
                ->whereIn('id', $productIds)
                ->get(['id', 'product_category_id', 'product_sub_category_id']);
            $categoryMap = [];
            $subCategoryMap = [];
            foreach ($prodRows as $pr) {
                $categoryMap[(string) $pr->id] = $pr->product_category_id ? (string) $pr->product_category_id : null;
                $subCategoryMap[(string) $pr->id] = $pr->product_sub_category_id ? (string) $pr->product_sub_category_id : null;
            }
            return $itemsRows->map(function ($si) use ($specificDiscounts, $categoryMap, $subCategoryMap) {
                $discounted = false;
                $bestText = null;
                $bestAmount = 0;
                $bestBundles = 0;
                $bestMinQty = 0;
                if ($specificDiscounts->isNotEmpty()) {
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
                            if ($discountType === 'bogo') {
                                $minQtyBuy = (int) ($di->min_qty_buy ?? 0);
                                $freeQtyGet = (int) ($di->free_qty_get ?? 0);
                                if ($minQtyBuy > 0 && $freeQtyGet > 0 && ((int) $si->quantity) >= $minQtyBuy) {
                                    $discounted = true;
                                    break 2;
                                }
                            } else {
                                $valueTypeRaw = strtolower((string) $d->value_type);
                                $isAmountType = in_array($valueTypeRaw, ['amount', 'nominal'], true);
                                $value = $di->custom_value !== null ? (float) $di->custom_value : ($d->value !== null ? (float) $d->value : 0.0);
                                $minQtyBuy = (int) ($di->min_qty_buy ?? 0);
                                $qty = (int) $si->quantity;
                                if ($minQtyBuy > 0 && $qty < $minQtyBuy) {
                                    continue;
                                }
                                if ($value > 0.0) {
                                    $amount = 0;
                                    $bundlesApplied = 0;
                                    if (!$isAmountType && $valueTypeRaw === 'percentage') {
                                        if ($minQtyBuy > 0) {
                                            $bundles = intdiv($qty, $minQtyBuy);
                                            $isMultiple = (bool) ($di->is_multiple ?? false);
                                            $bundlesApplied = $isMultiple && $bundles > 0 ? $bundles : ($bundles >= 1 ? 1 : 0);
                                            $baseAmount = ((int) $si->price) * $minQtyBuy * $bundlesApplied;
                                            $amount = (int) round($baseAmount * $value / 100.0);
                                        } else {
                                            $base = (int) ($si->subtotal ?? (((int) $si->price) * $qty));
                                            $amount = (int) round($base * $value / 100.0);
                                        }
                                    } else {
                                        if ($minQtyBuy > 0) {
                                            $bundles = intdiv($qty, $minQtyBuy);
                                            $isMultiple = (bool) ($di->is_multiple ?? false);
                                            $bundlesApplied = $isMultiple && $bundles > 0 ? $bundles : ($bundles >= 1 ? 1 : 0);
                                            $amount = (int) round($value) * max(0, $bundlesApplied);
                                        } else {
                                            $amount = (int) round($value) * $qty;
                                        }
                                    }
                                    if ($amount > $bestAmount) {
                                        $bestAmount = $amount;
                                        $bestBundles = $bundlesApplied;
                                        $bestMinQty = $minQtyBuy;
                                        if (!$isAmountType && $valueTypeRaw === 'percentage') {
                                            $pctStr = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
                                            $bestText = $minQtyBuy > 0
                                                ? $pctStr . '% per ' . (string) $minQtyBuy . ' qty'
                                                : $pctStr . '%';
                                        } else {
                                            $nomStr = number_format((int) round($value), 0, '.', '.');
                                            $bestText = $minQtyBuy > 0
                                                ? $nomStr . ' per ' . (string) $minQtyBuy . ' qty'
                                                : $nomStr;
                                        }
                                        $discounted = true;
                                    }
                                }
                            }
                        }
                    }
                }
                if ($bestText !== null && $bestBundles > 1) {
                    $bestText .= ' × ' . (string) $bestBundles;
                }
                $unitPrice = (int) $si->price;
                $qty = (int) $si->quantity;
                $subtotal = $si->subtotal !== null ? (int) $si->subtotal : (int) ($unitPrice * $qty);
                $discountAmount = (int) $bestAmount;
                $voucherShare = (int) ($si->voucher_share ?? 0);
                $totalAfterDiscount = max($subtotal - $discountAmount, 0);
                $totalAfterDiscountAndVoucher = max($totalAfterDiscount - $voucherShare, 0);

                return [
                    'sales_item_id' => (string) $si->id,
                    'id' => (string) $si->product_id,
                    'name' => (string) ($si->product?->name ?? ($si->product_name ?? '')),
                    'price' => $unitPrice,
                    'unit_price' => $unitPrice,
                    'qty' => $qty,
                    'subtotal' => $subtotal,
                    'note' => $si->notes !== null ? (string) $si->notes : null,
                    'discounted' => $discounted,
                    'discount_text' => $bestText,
                    'discount_amount' => $discountAmount,
                    'voucher_share' => $voucherShare,
                    'total_after_discount' => $totalAfterDiscount,
                    'total_after_discount_and_voucher' => $totalAfterDiscountAndVoucher,
                ];
            })->all();
        } catch (Throwable $e) {
            Log::error('SalesViewService.getItemsPayload failed', [
                'error' => $e->getMessage(),
                'sales_id' => $salesId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function getLatestDelivery(string $salesId): ?SalesDelivery
    {
        try {
            return SalesDelivery::query()
                ->where('sales_id', $salesId)
                ->latest('delivery_date')
                ->first(['number', 'recipient_name', 'recipient_phone', 'delivery_address', 'notes']);
        } catch (Throwable $e) {
            Log::error('SalesViewService.getLatestDelivery failed', [
                'error' => $e->getMessage(),
                'sales_id' => $salesId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function getShippingAmount(string $salesId): int
    {
        try {
            $row = SalesCharge::query()
                ->where('sales_id', $salesId)
                ->where('type', SalesChargeType::Shipping->value)
                ->first(['amount']);
            return $row ? (int) $row->amount : 0;
        } catch (Throwable $e) {
            Log::error('SalesViewService.getShippingAmount failed', [
                'error' => $e->getMessage(),
                'sales_id' => $salesId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function getRoundingAmount(string $salesId): int
    {
        try {
            $row = SalesCharge::query()
                ->where('sales_id', $salesId)
                ->where('type', SalesChargeType::Rounding->value)
                ->first(['amount']);
            return $row ? (int) $row->amount : 0;
        } catch (Throwable $e) {
            Log::error('SalesViewService.getRoundingAmount failed', [
                'error' => $e->getMessage(),
                'sales_id' => $salesId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function getPaymentsInfo(string $salesId, int $grandTotal): array
    {
        try {
            $allocations = PaymentAllocation::query()
                ->where('referencable_id', $salesId)
                ->where('referencable_type', Sales::class)
                ->with(['payment' => function ($q) {
                    $q->with(['paymentMethod']);
                }])
                ->orderBy('allocated_at')
                ->get();
            $paymentsPayload = $allocations->map(function ($a) {
                $pay = $a->payment;
                $pmName = $pay && $pay->paymentMethod ? (string) $pay->paymentMethod->name : null;
                return [
                    'payment_method_id' => $pay ? (string) $pay->payment_method_id : null,
                    'payment_method_name' => $pmName,
                    'amount' => $pay ? (int) $pay->amount : 0,
                    'notes' => $pay && $pay->notes !== null ? (string) $pay->notes : null,
                    'payment_date' => $pay && $pay->payment_date !== null ? (string) $pay->payment_date->toDateString() : null,
                    'paid_at' => $a->allocated_at !== null ? (string) $a->allocated_at->format('Y-m-d H:i:s') : null,
                ];
            })->all();
            $paymentTotal = collect($paymentsPayload)
                ->reduce(function ($s, $p) {
                    return (int) $s + (int) ($p['amount'] ?? 0);
                }, 0);
            $changeAmount = max($paymentTotal - $grandTotal, 0);
            $shortageAmount = max($grandTotal - $paymentTotal, 0);
            return [
                'payments' => $paymentsPayload,
                'payment_total' => (int) $paymentTotal,
                'change_amount' => (int) $changeAmount,
                'shortage_amount' => (int) $shortageAmount,
            ];
        } catch (Throwable $e) {
            Log::error('SalesViewService.getPaymentsInfo failed', [
                'error' => $e->getMessage(),
                'sales_id' => $salesId,
                'grand_total' => $grandTotal,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
