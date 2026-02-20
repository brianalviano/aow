<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\DTOs\PurchaseOrder\{PurchaseOrderData, PurchaseOrderItemData};
use App\Enums\PurchaseOrderStatus;
use App\DTOs\PurchaseReturn\{PurchaseReturnData, PurchaseReturnItemData};
use App\Enums\{PurchaseReturnStatus, PurchaseReturnResolution, PurchaseReturnReason};
use App\Models\{Product, Warehouse, Supplier, User, ValueAddedTax};
use App\Services\{PurchaseOrderService, StockService, SupplierDeliveryOrderService, PurchaseReturnService};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{Notification, Mail};

/**
 * Seeder untuk membuat data contoh Purchase Order.
 *
 * Membuat beberapa PO dengan variasi:
 * - Status: draft, pending_ho_approval, supplier_confirmed, in_delivery (dengan SDO)
 * - PPN aktif/non-aktif, termasuk/terpisah
 * - Diskon dan biaya pengiriman
 *
 * Menggunakan PurchaseOrderService agar perhitungan total mengikuti aturan domain.
 *
 * @throws \Throwable
 */
class PurchaseOrderSeeder extends Seeder
{
    /**
     * Jalankan seeder untuk membuat Purchase Order contoh.
     *
     * @return void
     */
    public function run(): void
    {
        Notification::fake();
        Mail::fake();
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

        $supplier = Supplier::query()->where('is_active', true)->first();
        if (!$supplier) {
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

        $vat = ValueAddedTax::query()
            ->where('is_active', true)
            ->orderByDesc('percentage')
            ->first();
        $vatId = $vat ? (string) $vat->id : null;
        $vatPctStr = $vat && $vat->percentage !== null ? (string) $vat->percentage : null;

        $service = app(PurchaseOrderService::class);
        $stock = app(StockService::class);
        $sdoService = app(SupplierDeliveryOrderService::class);

        $makeItems = function (int $count) use ($productIds): array {
            $pick = collect($productIds)->shuffle()->take($count)->values()->all();
            $items = [];
            foreach ($pick as $pid) {
                $qty = random_int(2, 6);
                $items[] = new PurchaseOrderItemData(
                    purchaseRequestItemId: null,
                    productId: (string) $pid,
                    quantity: (int) $qty,
                    price: 0,
                    notes: 'Seeder'
                );
            }
            return $items;
        };

        $scenarios = [
            [
                'status' => PurchaseOrderStatus::Draft->value,
                'vat_enabled' => false,
                'vat_included' => false,
                'discount' => null,
                'delivery_cost' => 0,
                'make_sdo' => false,
            ],
            [
                'status' => PurchaseOrderStatus::PendingHoApproval->value,
                'vat_enabled' => true,
                'vat_included' => false,
                'discount' => '2.00',
                'delivery_cost' => 75000,
                'make_sdo' => false,
            ],
            [
                'status' => PurchaseOrderStatus::SupplierConfirmed->value,
                'vat_enabled' => true,
                'vat_included' => true,
                'discount' => '3.00',
                'delivery_cost' => 0,
                'make_sdo' => false,
            ],
            [
                'status' => PurchaseOrderStatus::PendingSupplierApproval->value,
                'vat_enabled' => true,
                'vat_included' => false,
                'discount' => null,
                'delivery_cost' => 50000,
                'make_sdo' => true,
            ],
        ];

        foreach (range(1, 100) as $i) {
            $scenario = $scenarios[($i - 1) % count($scenarios)];
            $orderDate = now()->toDateString();
            $dueDate = now()->addDays(random_int(7, 14))->toDateString();
            $itemsCount = random_int(3, 6);
            $items = $makeItems($itemsCount);

            $data = new PurchaseOrderData(
                supplierId: (string) $supplier->id,
                warehouseId: (string) $warehouse->id,
                orderDate: $orderDate,
                dueDate: $dueDate,
                notes: 'PO contoh #' . $i,
                status: (string) $scenario['status'],
                deliveryCost: (int) $scenario['delivery_cost'],
                discountPercentage: $scenario['discount'],
                isValueAddedTaxEnabled: (bool) $scenario['vat_enabled'],
                valueAddedTaxIncluded: (bool) $scenario['vat_included'],
                valueAddedTaxId: $scenario['vat_enabled'] ? $vatId : null,
                valueAddedTaxPercentage: $scenario['vat_enabled'] ? $vatPctStr : null,
                isIncomeTaxEnabled: false,
                incomeTaxId: null,
                incomeTaxPercentage: null,
                supplierInvoiceNumber: null,
                supplierInvoiceFile: null,
                supplierInvoiceDate: null,
                createdById: (string) $user->id,
                items: $items
            );

            $po = $service->create($data);

            if ((bool) $scenario['make_sdo']) {
                $poItems = \App\Models\PurchaseOrderItem::query()
                    ->where('purchase_order_id', $po->getKey())
                    ->get(['product_id', 'quantity']);
                $mode = $i % 3;
                if ($mode === 0) {
                    $fullItems = [];
                    foreach ($poItems as $row) {
                        $fullItems[] = [
                            'product_id' => (string) $row->product_id,
                            'quantity' => (int) $row->quantity,
                            'notes' => 'Full via seeder #' . $i,
                        ];
                    }
                    if (!empty($fullItems)) {
                        $sdo = $sdoService->createFromPurchaseOrder(
                            $po,
                            $fullItems,
                            now()->toDateString(),
                            null,
                            'Seeder DO Full #' . $i
                        );
                        $stock->receiveSupplierDeliveryOrder($sdo, $fullItems, (string) $user->id);

                        $returnItems = [];
                        $returnPick = collect($fullItems)->shuffle()->take(max(1, min(2, count($fullItems))))->values()->all();
                        foreach ($returnPick as $ri) {
                            $qty = (int) $ri['quantity'];
                            $retQty = max(1, (int) floor($qty / 2));
                            $returnItems[] = new PurchaseReturnItemData(
                                productId: (string) $ri['product_id'],
                                quantity: (int) $retQty,
                                price: 0,
                                notes: 'Retur penggantian via seeder #' . $i
                            );
                        }
                        if (!empty($returnItems)) {
                            $prData = new PurchaseReturnData(
                                purchaseOrderId: (string) $po->getKey(),
                                supplierId: (string) $po->supplier_id,
                                warehouseId: (string) $po->warehouse_id,
                                number: null,
                                returnDate: now()->toDateString(),
                                reason: PurchaseReturnReason::Damaged->value,
                                resolution: PurchaseReturnResolution::Replace->value,
                                status: PurchaseReturnStatus::Draft->value,
                                notes: 'Seeder Retur Penggantian #' . $i,
                                createdById: (string) $user->id,
                                items: $returnItems
                            );
                            $prService = app(PurchaseReturnService::class);
                            $return = $prService->create($prData);
                            $return = $prService->advanceStatus($return, now()->toDateString(), null, 'Seeder Retur → InDelivery #' . $i);

                            $replacementItems = [];
                            foreach ($returnItems as $rit) {
                                $replacementItems[] = [
                                    'product_id' => (string) $rit->productId,
                                    'quantity' => (int) $rit->quantity,
                                    'notes' => 'Penggantian via seeder #' . $i,
                                ];
                            }
                            if (!empty($replacementItems)) {
                                $prService->receiveReplacementForPurchaseReturn($return, $replacementItems, (string) $user->id);
                            }
                        }
                    }
                } elseif ($mode === 1) {
                    $halfItems = [];
                    foreach ($poItems as $row) {
                        $half = max(1, (int) floor(((int) $row->quantity) / 2));
                        $halfItems[] = [
                            'product_id' => (string) $row->product_id,
                            'quantity' => (int) $half,
                            'notes' => 'Half via seeder #' . $i,
                        ];
                    }
                    if (!empty($halfItems)) {
                        $sdo = $sdoService->createFromPurchaseOrder(
                            $po,
                            $halfItems,
                            now()->toDateString(),
                            null,
                            'Seeder DO Half #' . $i
                        );
                        $stock->receiveSupplierDeliveryOrder($sdo, $halfItems, (string) $user->id);

                        $returnItems = [];
                        $returnPick = collect($halfItems)->shuffle()->take(max(1, min(2, count($halfItems))))->values()->all();
                        foreach ($returnPick as $ri) {
                            $qty = (int) $ri['quantity'];
                            $retQty = max(1, (int) floor($qty / 2));
                            $returnItems[] = new PurchaseReturnItemData(
                                productId: (string) $ri['product_id'],
                                quantity: (int) $retQty,
                                price: 0,
                                notes: 'Retur penggantian via seeder #' . $i
                            );
                        }
                        if (!empty($returnItems)) {
                            $prData = new PurchaseReturnData(
                                purchaseOrderId: (string) $po->getKey(),
                                supplierId: (string) $po->supplier_id,
                                warehouseId: (string) $po->warehouse_id,
                                number: null,
                                returnDate: now()->toDateString(),
                                reason: PurchaseReturnReason::WrongItem->value,
                                resolution: PurchaseReturnResolution::Replace->value,
                                status: PurchaseReturnStatus::Draft->value,
                                notes: 'Seeder Retur Penggantian #' . $i,
                                createdById: (string) $user->id,
                                items: $returnItems
                            );
                            $prService = app(PurchaseReturnService::class);
                            $return = $prService->create($prData);
                            $return = $prService->advanceStatus($return, now()->toDateString(), null, 'Seeder Retur → InDelivery #' . $i);

                            $replacementItems = [];
                            foreach ($returnItems as $rit) {
                                $replacementItems[] = [
                                    'product_id' => (string) $rit->productId,
                                    'quantity' => (int) $rit->quantity,
                                    'notes' => 'Penggantian via seeder #' . $i,
                                ];
                            }
                            if (!empty($replacementItems)) {
                                $prService->receiveReplacementForPurchaseReturn($return, $replacementItems, (string) $user->id);
                            }
                        }
                    }
                } else {
                    $halfItems = [];
                    foreach ($poItems as $row) {
                        $half = max(1, (int) floor(((int) $row->quantity) / 2));
                        $halfItems[] = [
                            'product_id' => (string) $row->product_id,
                            'quantity' => (int) $half,
                            'notes' => 'Half via seeder #' . $i,
                        ];
                    }
                    if (!empty($halfItems)) {
                        $sdoService->createFromPurchaseOrder(
                            $po,
                            $halfItems,
                            now()->toDateString(),
                            null,
                            'Seeder DO Half (no receive) #' . $i
                        );
                    }
                }
            }
        }
    }
}
