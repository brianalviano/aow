<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\{RoleName, StockOpnameStatus, StockTransferStatus, SupplierDeliveryOrderStatus};
use App\Models\{
    StockOpname,
    StockOpnameAssignment,
    StockTransfer,
    SupplierDeliveryOrder,
    GoodsCome,
    Product,
    Stock,
    User,
    Warehouse
};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LogisticDashboardService
{
    public function buildStatsFor(string $role, User $user): array
    {
        $stockTransferCount = $this->getStockTransfersBadgeCount();
        $stockOpnameCount = $this->getStockOpnamesBadgeCount($role, $user);
        $warehousesTotal = Warehouse::query()->count();
        $lowStockCount = $this->getLowStockCount();
        $inDeliveryCount = $this->getSupplierDeliveriesInDeliveryCount();
        $receivingsTodayCount = $this->getReceivingsTodayCount();
        $productsTotal = Product::query()->count();
        $productsActive = Product::query()->where('is_active', true)->count();
        $productsInactive = Product::query()->where('is_active', false)->count();
        $stocksUpdatedToday = $this->getStocksUpdatedTodayCount();

        return [
            'summary' => [
                'stock_transfers_in_transit' => $stockTransferCount,
                'stock_opnames_active' => $stockOpnameCount,
                'warehouses_total' => $warehousesTotal,
                'low_stock_items' => $lowStockCount,
                'supplier_deliveries_in_delivery' => $inDeliveryCount,
                'receivings_today' => $receivingsTodayCount,
                'products_total' => $productsTotal,
                'products_active' => $productsActive,
                'products_inactive' => $productsInactive,
                'stocks_updated_today' => $stocksUpdatedToday,
            ],
            'lists' => [
                'stock_transfers' => $this->getRecentStockTransfers(),
                'stock_opnames' => $this->getRecentStockOpnamesForRole($role, $user),
                'low_stocks' => $this->getLowStockAlerts(),
                'upcoming_deliveries' => $this->getUpcomingDeliveries(),
                'recent_receivings' => $this->getRecentReceivings(),
                'recent_stock_updates' => $this->getRecentStockUpdates(),
            ],
        ];
    }

    private function getStockTransfersBadgeCount(): ?int
    {
        return StockTransfer::query()
            ->where('status', StockTransferStatus::InTransit->value)
            ->count();
    }

    private function getRecentStockTransfers(): array
    {
        return StockTransfer::query()
            ->where('status', StockTransferStatus::InTransit->value)
            ->latest('transfer_date')
            ->latest('created_at')
            ->take(5)
            ->get(['id', 'number', 'transfer_date', 'status'])
            ->map(fn($t) => [
                'id' => (string) $t->getKey(),
                'number' => (string) ($t->number ?? ''),
                'transfer_date' => $t->transfer_date ? $t->transfer_date->toDateString() : null,
                'status' => (string) ($t->status instanceof StockTransferStatus ? $t->status->value : (string) $t->status),
            ])
            ->toArray();
    }

    private function getStockOpnamesBadgeCount(string $role, User $user): ?int
    {
        $statuses = [StockOpnameStatus::Scheduled->value, StockOpnameStatus::InProgress->value];
        if (in_array($role, RoleName::highest(), true)) {
            return StockOpname::query()
                ->whereIn('status', $statuses)
                ->count();
        }
        return StockOpname::query()
            ->whereIn('status', $statuses)
            ->whereIn(
                'id',
                StockOpnameAssignment::query()
                    ->select('stock_opname_id')
                    ->where('user_id', (string) $user->getKey())
            )
            ->count();
    }

    private function getRecentStockOpnamesForRole(string $role, User $user): array
    {
        $statuses = [StockOpnameStatus::Scheduled->value, StockOpnameStatus::InProgress->value];
        $query = StockOpname::query()->whereIn('status', $statuses);
        if (!in_array($role, RoleName::highest(), true)) {
            $query->whereIn(
                'id',
                StockOpnameAssignment::query()
                    ->select('stock_opname_id')
                    ->where('user_id', (string) $user->getKey())
            );
        }
        return $query
            ->latest('scheduled_date')
            ->latest('created_at')
            ->take(5)
            ->get(['id', 'number', 'status'])
            ->map(fn($op) => [
                'id' => (string) $op->getKey(),
                'number' => (string) ($op->number ?? ''),
                'status' => (string) ($op->status instanceof StockOpnameStatus ? $op->status->value : (string) $op->status),
                'status_label' => $op->status instanceof StockOpnameStatus
                    ? $op->status->label()
                    : StockOpnameStatus::from((string) $op->status)->label(),
            ])
            ->toArray();
    }

    private function getLowStockCount(): int
    {
        return (int) Product::query()
            ->where('is_active', true)
            ->where('min_stock', '>', 0)
            ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
            ->select(['products.id', 'products.min_stock'])
            ->groupBy('products.id', 'products.min_stock')
            ->havingRaw('COALESCE(SUM(stocks.quantity), 0) < products.min_stock')
            ->count();
    }

    private function getLowStockAlerts(): array
    {
        $rows = Product::query()
            ->where('is_active', true)
            ->where('min_stock', '>', 0)
            ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
            ->select([
                'products.id',
                'products.name',
                'products.min_stock',
                DB::raw('COALESCE(SUM(stocks.quantity), 0) AS total_stock'),
            ])
            ->groupBy('products.id', 'products.name', 'products.min_stock')
            ->havingRaw('COALESCE(SUM(stocks.quantity), 0) < products.min_stock')
            ->orderByRaw('(products.min_stock - COALESCE(SUM(stocks.quantity), 0)) DESC')
            ->limit(5)
            ->get();

        return $rows->map(fn($row) => [
            'product' => [
                'id' => (string) $row->id,
                'name' => (string) $row->name,
            ],
            'min_stock' => (int) $row->min_stock,
            'total_stock' => (int) $row->total_stock,
            'shortage' => max(0, (int) $row->min_stock - (int) $row->total_stock),
        ])->toArray();
    }

    private function getSupplierDeliveriesInDeliveryCount(): int
    {
        return (int) SupplierDeliveryOrder::query()
            ->where('status', SupplierDeliveryOrderStatus::InDelivery->value)
            ->count();
    }

    private function getUpcomingDeliveries(): array
    {
        return SupplierDeliveryOrder::query()
            ->with(['supplier'])
            ->where('status', SupplierDeliveryOrderStatus::InDelivery->value)
            ->orderBy('delivery_date')
            ->orderBy('created_at')
            ->take(5)
            ->get(['id', 'number', 'delivery_date', 'status', 'supplier_id'])
            ->map(fn($s) => [
                'id' => (string) $s->getKey(),
                'number' => (string) ($s->number ?? ''),
                'delivery_date' => $s->delivery_date ? (string) $s->delivery_date->format('Y-m-d') : null,
                'supplier' => ['name' => (string) ($s->supplier->name ?? '')],
                'status' => (string) ($s->status instanceof SupplierDeliveryOrderStatus ? $s->status->value : (string) $s->status),
                'status_label' => $s->status instanceof SupplierDeliveryOrderStatus
                    ? $s->status->label()
                    : SupplierDeliveryOrderStatus::from((string) $s->status)->label(),
            ])
            ->toArray();
    }

    private function getReceivingsTodayCount(): int
    {
        $today = Carbon::today();
        return (int) GoodsCome::query()
            ->whereDate('created_at', $today)
            ->count();
    }

    private function getStocksUpdatedTodayCount(): int
    {
        $today = Carbon::today();
        return (int) Stock::query()
            ->whereDate('updated_at', $today)
            ->count();
    }

    private function getRecentReceivings(): array
    {
        $today = Carbon::today();
        return GoodsCome::query()
            ->with(['product', 'warehouse'])
            ->whereDate('created_at', $today)
            ->latest('created_at')
            ->take(5)
            ->get(['id', 'product_id', 'warehouse_id', 'quantity', 'supplier_name', 'invoice_number', 'created_at'])
            ->map(fn($g) => [
                'id' => (string) $g->getKey(),
                'product' => ['name' => (string) ($g->product->name ?? '')],
                'warehouse' => ['name' => (string) ($g->warehouse->name ?? '')],
                'quantity' => (int) $g->quantity,
                'supplier_name' => (string) ($g->supplier_name ?? ''),
                'invoice_number' => (string) ($g->invoice_number ?? ''),
                'received_at' => $g->created_at ? (string) $g->created_at->format('H:i') : null,
            ])
            ->toArray();
    }

    private function getRecentStockUpdates(): array
    {
        $today = Carbon::today();
        return \App\Models\StockCard::query()
            ->with(['stock.product', 'stock.warehouse'])
            ->whereDate('created_at', $today)
            ->latest('created_at')
            ->take(5)
            ->get(['id', 'stock_id', 'type', 'quantity', 'created_at'])
            ->map(function ($c) {
                $typeValue = $c->type instanceof \App\Enums\StockCardType ? (string) $c->type->value : (string) $c->type;
                $typeLabel = $c->type instanceof \App\Enums\StockCardType ? $c->type->label() : \App\Enums\StockCardType::from($typeValue)->label();
                return [
                    'id' => (string) $c->getKey(),
                    'product' => ['name' => (string) ($c->stock->product->name ?? '')],
                    'warehouse' => ['name' => (string) ($c->stock->warehouse->name ?? '')],
                    'quantity' => (int) $c->quantity,
                    'type' => $typeValue,
                    'type_label' => $typeLabel,
                    'time' => $c->created_at ? (string) $c->created_at->format('H:i') : null,
                ];
            })
            ->toArray();
    }
}
