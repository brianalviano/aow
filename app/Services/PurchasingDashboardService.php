<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\{PurchaseOrderStatus, SupplierDeliveryOrderStatus};
use App\Models\{PurchaseOrder, SupplierDeliveryOrder, GoodsCome, Supplier, User};
use Illuminate\Support\Carbon;

class PurchasingDashboardService
{
    public function buildStatsFor(string $role, User $user): array
    {
        $poPendingHoCount = $this->countPoByStatus(PurchaseOrderStatus::PendingHoApproval->value);
        $poPendingSupplierCount = $this->countPoByStatus(PurchaseOrderStatus::PendingSupplierApproval->value);
        $poSupplierConfirmedCount = $this->countPoByStatus(PurchaseOrderStatus::SupplierConfirmed->value);
        $poInDeliveryCount = $this->countPoByStatus(PurchaseOrderStatus::InDelivery->value);
        $poPartiallyDeliveredCount = $this->countPoByStatus(PurchaseOrderStatus::PartiallyDelivered->value);
        $suppliersTotal = Supplier::query()->count();

        return [
            'summary' => [
                'purchase_orders_pending_ho' => $poPendingHoCount,
                'purchase_orders_pending_supplier' => $poPendingSupplierCount,
                'purchase_orders_supplier_confirmed' => $poSupplierConfirmedCount,
                'purchase_orders_in_delivery' => $poInDeliveryCount,
                'purchase_orders_partially_delivered' => $poPartiallyDeliveredCount,
                'suppliers_total' => $suppliersTotal,
            ],
            'lists' => [
                'recent_purchase_orders' => $this->getRecentPurchaseOrders(),
            ],
        ];
    }

    private function countPoByStatus(string $status): int
    {
        return (int) PurchaseOrder::query()->where('status', $status)->count();
    }

    private function getRecentPurchaseOrders(): array
    {
        return PurchaseOrder::query()
            ->with(['supplier:id,name', 'warehouse:id,name'])
            ->latest('order_date')
            ->latest('created_at')
            ->take(5)
            ->get(['id', 'number', 'order_date', 'status', 'grand_total', 'supplier_id', 'warehouse_id'])
            ->map(function ($po) {
                $statusValue = $po->status instanceof PurchaseOrderStatus ? (string) $po->status->value : (string) $po->status;
                $statusLabel = $po->status instanceof PurchaseOrderStatus
                    ? $po->status->label()
                    : PurchaseOrderStatus::from($statusValue)->label();
                return [
                    'id' => (string) $po->getKey(),
                    'number' => (string) ($po->number ?? ''),
                    'supplier' => [
                        'id' => (string) ($po->supplier_id ?? ''),
                        'name' => (string) ($po->supplier?->name ?? ''),
                    ],
                    'warehouse' => [
                        'id' => (string) ($po->warehouse_id ?? ''),
                        'name' => (string) ($po->warehouse?->name ?? ''),
                    ],
                    'order_date' => $po->order_date ? (string) $po->order_date->format('Y-m-d') : null,
                    'status' => $statusValue,
                    'status_label' => $statusLabel,
                    'grand_total' => (int) ($po->grand_total ?? 0),
                ];
            })
            ->toArray();
    }
}
