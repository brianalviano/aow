<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\DTOs\Sales\{SalesData, SalesItemData, SalesPaymentData};
use App\Enums\{SalesDeliveryType, SalesReturnReason, SalesReturnResolution, CashSessionStatus};
use App\Models\{Product, Warehouse, Customer, PaymentMethod, User, ValueAddedTax, SalesItem, Sales, CashierSession, Voucher};
use App\Services\{SalesService, ProductPriceService, CashierSessionService};
use Illuminate\Database\Seeder;

class SalesSeeder extends Seeder
{
    /**
     * Jalankan seeder untuk membuat data transaksi penjualan contoh.
     *
     * Membuat sejumlah transaksi dengan variasi status pembayaran (paid, partially_paid, unpaid),
     * pengiriman, diskon item & tambahan (global), serta BOGO. Seeder mengikuti
     * flow bisnis saat ini: seluruh nominal (subtotal, total diskon item, diskon tambahan,
     * total setelah diskon, PPN, grand total) dihitung di sini dan dipersist melalui SalesService
     * tanpa ada re-komputasi di server.
     *
     * @return void
     */
    public function run(): void
    {
        $warehouse = Warehouse::query()
            ->where('is_active', true)
            ->where('is_central', true)
            ->first();
        if (!$warehouse) {
            return;
        }

        $user = User::query()->first();
        if (!$user) {
            return;
        }

        $products = Product::query()
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(10)
            ->get(['id', 'name']);
        if ($products->isEmpty()) {
            return;
        }

        $productIds = $products->pluck('id')->map(fn($id) => (string) $id)->all();
        $priceService = app(ProductPriceService::class);
        $priceMap = $priceService->getSellingPriceMainMap($productIds);

        $customer = Customer::query()->where('is_active', true)->first();
        $paymentMethod = PaymentMethod::query()->where('is_active', true)->first();
        $vat = ValueAddedTax::query()->where('is_active', true)->orderByDesc('percentage')->first();

        $service = app(SalesService::class);

        $cashierService = app(CashierSessionService::class);
        $session = CashierSession::query()
            ->where('user_id', (string) $user->id)
            ->whereNull('closed_at')
            ->where('status', CashSessionStatus::Open->value)
            ->first();
        if (!$session) {
            $session = $cashierService->openShift((string) $user->id, 0);
        }
        $cashierSessionId = (string) $session->id;

        $count = 100;
        for ($i = 0; $i < $count; $i++) {
            $scenario = $i % 3; // 0=unpaid, 1=partial, 2=paid
            $hasCustomerAndPayment = ($customer !== null && $paymentMethod !== null);
            $payPercent = $scenario === 2 ? 100 : ($scenario === 1 ? 50 : 0);
            if (!$hasCustomerAndPayment) {
                $payPercent = 0;
            }
            $requiresDelivery = $scenario !== 0;
            $discountPct = random_int(0, 10);
            $discountPercentage = $discountPct > 0 ? (string) $discountPct : null;
            $shippingAmount = $requiresDelivery ? random_int(0, 15000) : null;
            $roundingAmount = random_int(-500, 500);
            if ($roundingAmount === 0) {
                $roundingAmount = null;
            }
            $isVatEnabled = $vat !== null ? (bool) random_int(0, 1) : false;
            $saleDatetime = now()->subMinutes($i)->toDateTimeString();

            $this->createExampleSale(
                $service,
                $priceService,
                $priceMap,
                $products,
                (string) $warehouse->id,
                $cashierSessionId,
                (string) $user->id,
                $customer ? (string) $customer->id : null,
                $paymentMethod ? (string) $paymentMethod->id : null,
                $vat ? (string) $vat->id : null,
                $isVatEnabled,
                $discountPercentage,
                $requiresDelivery,
                $shippingAmount,
                $roundingAmount,
                $payPercent,
                $saleDatetime
            );

            if (random_int(0, 100) < 30) {
                $sale = Sales::query()->latest('created_at')->first();
                if ($sale) {
                    $saleItems = SalesItem::query()
                        ->where('sales_id', (string) $sale->id)
                        ->get();
                    if (!$saleItems->isEmpty()) {
                        $ri = [];
                        $takeCount = min(max(1, (int) $saleItems->count()), 2);
                        foreach ($saleItems->shuffle()->take($takeCount) as $si) {
                            $maxQty = max((int) $si->quantity - (int) $si->returned_quantity, 0);
                            if ($maxQty <= 0) {
                                continue;
                            }
                            $qty = min(random_int(1, 2), $maxQty);
                            $ri[] = [
                                'sales_item_id' => (string) $si->id,
                                'quantity' => (int) $qty,
                                'notes' => null,
                            ];
                        }
                        if (!empty($ri)) {
                            $reason = [SalesReturnReason::Damaged->value, SalesReturnReason::WrongItem->value, SalesReturnReason::Others->value][random_int(0, 2)];
                            $resCandidates = [SalesReturnResolution::Refund->value, SalesReturnResolution::Exchange->value, SalesReturnResolution::StoreCredit->value];
                            $resolution = $resCandidates[random_int(0, 2)];
                            if ($resolution === SalesReturnResolution::StoreCredit->value && $sale->customer_id === null) {
                                $resolution = SalesReturnResolution::Refund->value;
                            }
                            $service->createReturn($sale, $ri, $reason, $resolution, 'Retur dari seeder', (string) $user->id);
                        }
                    }
                }
            }
        }
    }

    private function createExampleSale(
        SalesService $service,
        ProductPriceService $priceService,
        array $priceMap,
        \Illuminate\Support\Collection $products,
        string $warehouseId,
        string $cashierSessionId,
        string $createdById,
        ?string $customerId,
        ?string $paymentMethodId,
        ?string $vatId,
        bool $isVatEnabled,
        ?string $discountPercentage,
        bool $requiresDelivery,
        ?int $shippingAmount,
        ?int $roundingAmount,
        int $payPercent,
        string $saleDatetime
    ): void {
        $pick = $products->shuffle()->take(min(3, max(1, (int) $products->count())));
        $items = [];
        foreach ($pick as $p) {
            $pid = (string) $p->id;
            $price = $priceMap[$pid] ?? $priceService->getPurchasePrice($pid);
            if ($price <= 0) {
                continue;
            }
            $qty = random_int(1, 3);
            $items[] = new SalesItemData($pid, $qty, (int) $price);
        }
        if (empty($items)) {
            return;
        }

        $subtotal = array_reduce(
            $items,
            fn($s, $i) => (int) $s + ((int) $i->price * (int) $i->quantity),
            0
        );

        $itemDiscountTotal = 0;
        foreach ($items as $i) {
            $lineSubtotal = (int) ($i->price * $i->quantity);
            $percentCandidates = [0, 5, 10, 15];
            $perItemPct = (float) $percentCandidates[random_int(0, count($percentCandidates) - 1)];
            $perItemPctAmount = (int) round(((float) $lineSubtotal * $perItemPct) / 100.0);

            $bogoAmount = 0;
            $applyBogo = (bool) (random_int(0, 100) < 20);
            if ($applyBogo) {
                $minQtyBuy = random_int(1, 2);
                $freeQtyGet = 1;
                $bundleSize = $minQtyBuy + $freeQtyGet;
                if ($bundleSize > 0) {
                    $freeUnits = (int) floor(((int) $i->quantity) / $bundleSize) * $freeQtyGet;
                    if ($freeUnits > 0) {
                        $bogoAmount = (int) ($i->price * $freeUnits);
                    }
                }
            }

            $bestPerItem = max($perItemPctAmount, $bogoAmount);
            $itemDiscountTotal += (int) $bestPerItem;
        }
        $itemDiscountTotal = (int) min($itemDiscountTotal, $subtotal);

        $discPct = $discountPercentage !== null ? (float) $discountPercentage : 0.0;
        $baseAfterItem = (int) max($subtotal - $itemDiscountTotal, 0);
        $extraDiscountTotal = (int) round(((float) $baseAfterItem * $discPct) / 100.0);
        $extraDiscountTotal = (int) min($extraDiscountTotal, $baseAfterItem);

        $totalAfterDiscountBeforeVoucher = (int) max($baseAfterItem - $extraDiscountTotal, 0);

        $voucherCode = null;
        $voucherAmount = null;
        if (random_int(0, 100) < 40 && $totalAfterDiscountBeforeVoucher > 0) {
            $saleDate = $saleDatetime;
            $candidates = Voucher::query()
                ->where('is_active', true)
                ->where(function ($q) use ($saleDate) {
                    $q->whereNull('start_at')->orWhere('start_at', '<=', $saleDate);
                })
                ->where(function ($q) use ($saleDate) {
                    $q->whereNull('end_at')->orWhere('end_at', '>=', $saleDate);
                })
                ->get();
            if ($candidates->isNotEmpty()) {
                $filtered = $candidates->filter(function ($v) use ($totalAfterDiscountBeforeVoucher) {
                    $minOrder = (int) (string) ($v->min_order_amount ?? '0');
                    $limit = (int) ($v->usage_limit ?? 0);
                    $used = (int) ($v->used_count ?? 0);
                    $limitOk = $limit <= 0 || $used < $limit;
                    return $limitOk && $totalAfterDiscountBeforeVoucher >= $minOrder;
                });
                if ($filtered->isNotEmpty()) {
                    $v = $filtered->random();
                    $valType = strtolower((string) ($v->value_type ?? ''));
                    $val = (float) (string) ($v->value ?? '0');
                    $computed = 0;
                    if ($valType === 'percentage') {
                        $computed = (int) round(((float) $totalAfterDiscountBeforeVoucher * $val) / 100.0);
                    } else {
                        $computed = (int) round($val);
                    }
                    $computed = (int) max(0, min($computed, $totalAfterDiscountBeforeVoucher));
                    if ($computed > 0) {
                        $voucherCode = (string) $v->code;
                        $voucherAmount = (int) $computed;
                    }
                }
            }
        }

        $totalAfterDiscount = (int) max($totalAfterDiscountBeforeVoucher - (int) ($voucherAmount ?? 0), 0);

        $vatPct = $isVatEnabled ? $this->resolveVatPercentageFromId($vatId) : 0.0;
        $valueAddedTaxAmount = (int) round(((float) $totalAfterDiscount * $vatPct) / 100.0);

        $extraCharges = (int) (($shippingAmount ?? 0) + ($roundingAmount ?? 0));
        $grandTotal = (int) max($totalAfterDiscount + $valueAddedTaxAmount + $extraCharges, 0);

        $payments = [];
        if ($customerId !== null && $paymentMethodId !== null && $payPercent > 0) {
            $payAmount = (int) floor(((float) $grandTotal * (float) $payPercent) / 100.0);
            if ($payAmount > 0) {
                $payments[] = new SalesPaymentData($payAmount, (string) $paymentMethodId, null, null, 'Seeder');
            }
        }

        $data = new SalesData(
            warehouseId: $warehouseId,
            cashierSessionId: $cashierSessionId,
            saleDatetime: $saleDatetime,
            customerId: $customerId,
            deliveryType: $requiresDelivery ? SalesDeliveryType::Delivery->value : SalesDeliveryType::WalkIn->value,
            requiresDelivery: $requiresDelivery,
            shippingRecipientName: $requiresDelivery ? 'Pelanggan' : null,
            shippingRecipientPhone: null,
            shippingAddress: null,
            shippingNote: $requiresDelivery ? 'Pengiriman dari seeder' : null,
            subtotal: (int) $subtotal,
            discountPercentage: $discountPercentage,
            itemDiscountTotal: (int) $itemDiscountTotal,
            extraDiscountTotal: (int) $extraDiscountTotal,
            totalAfterDiscount: (int) $totalAfterDiscount,
            isValueAddedTaxEnabled: $isVatEnabled,
            valueAddedTaxId: $isVatEnabled ? $vatId : null,
            valueAddedTaxPercentage: null,
            valueAddedTaxAmount: (int) $valueAddedTaxAmount,
            voucherCode: $voucherCode,
            voucherAmount: $voucherAmount,
            shippingAmount: $shippingAmount,
            roundingAmount: $roundingAmount,
            grandTotal: (int) $grandTotal,
            changeAmount: 0,
            notes: 'Data contoh penjualan',
            createdById: $createdById,
            items: $items,
            payments: $payments
        );

        $service->create($data);
    }

    private function resolveVatPercentageFromId(?string $vatId): float
    {
        if ($vatId === null) {
            return 0.0;
        }
        $row = ValueAddedTax::query()->where('id', $vatId)->first(['percentage']);
        return $row && $row->percentage !== null ? (float) $row->percentage : 0.0;
    }
}
