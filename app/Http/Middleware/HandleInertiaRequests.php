<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\{Setting, LeaveRequest, PurchaseOrder, StockTransfer, StockOpname, StockOpnameAssignment, SupplierDeliveryOrder};
use App\Enums\{LeaveRequestStatus, PurchaseOrderStatus, RoleName, StockTransferStatus, StockOpnameStatus, SupplierDeliveryOrderStatus};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Cache};
use Inertia\Middleware;
use App\Services\NotificationService;

class HandleInertiaRequests extends Middleware
{
    protected $withAllErrors = true;
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $base = parent::share($request);
        $auth = $this->sharedAuth($base);

        return [
            ...$base,
            'auth' => $auth,
            'settings' => $this->sharedSettings(),
            'badges' => Auth::check() ? null : [],
            'menu' => $this->getSidebarMenu($auth['user']['role'] ?? null),
            'roles_config' => ['highest' => RoleName::highest()],
            'notification_stats' => Auth::check()
                ? app(NotificationService::class)->getStatsForUser(Auth::user())
                : ['total' => 0, 'unread' => 0, 'read' => 0],
        ];
    }

    protected function sharedAuth(array $base): array
    {
        $user = Auth::user();

        return [
            ...($base['auth'] ?? []),
            'guard' => $user ? 'web' : null,
            'user' => $user ? [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->name,
            ] : ($base['auth']['user'] ?? null),
        ];
    }

    protected function sharedSettings(): ?array
    {
        return Cache::remember(
            'settings:shared',
            600,
            fn() =>
            Setting::query()->first()?->only([
                'site_name',
                'contact_email',
                'whatsapp_number',
                'logo',
                'address',
                'latitude',
                'longitude',
                'bank_name',
                'bank_account_name',
                'bank_account_number'
            ])
        );
    }

    private function getSidebarMenu(?string $role): array
    {
        $badges = $this->getBadges($role);
        $menu = $this->buildMenu($badges);
        $menu = $this->configureDashboards($menu, $role);
        $menu = $this->filterMenu($menu, $role);

        return $menu;
    }

    private function getBadges(?string $role): array
    {
        if (!$role) {
            return ['po' => null, 'leaves' => 0, 'stockTransfer' => null, 'stockOpname' => null, 'goodsReceipts' => null];
        }

        return Cache::remember("badges:{$role}:" . Auth::id(), 60, function () use ($role) {
            return [
                'po' => $this->getPurchaseOrdersBadgeCount($role),
                'leaves' => $this->getLeavesBadgeCount($role),
                'stockTransfer' => $this->getStockTransfersBadgeCount(),
                'stockOpname' => $this->getStockOpnamesBadgeCount($role),
                'goodsReceipts' => $this->getGoodsReceiptsBadgeCount($role),
            ];
        });
    }

    private function getLeavesBadgeCount(string $role): int
    {
        return in_array($role, RoleName::highest(), true)
            ? LeaveRequest::where('status', LeaveRequestStatus::Pending)->count()
            : 0;
    }

    private function getPurchaseOrdersBadgeCount(string $role): ?int
    {
        return match (true) {
            in_array($role, RoleName::highest(), true) =>
            PurchaseOrder::where('status', PurchaseOrderStatus::PendingHoApproval)->count(),

            $role === RoleName::ManagerLogistic->value =>
            PurchaseOrder::where('status', PurchaseOrderStatus::SupplierConfirmed)->count(),

            $role === RoleName::StaffLogistic->value =>
            PurchaseOrder::whereIn('status', [
                PurchaseOrderStatus::InDelivery,
                PurchaseOrderStatus::PartiallyDelivered,
            ])->count(),

            default => null,
        };
    }

    private function getStockTransfersBadgeCount(): int
    {
        return StockTransfer::where('status', StockTransferStatus::InTransit)->count();
    }

    private function getStockOpnamesBadgeCount(string $role): ?int
    {
        $user = Auth::user();
        if (!$user) return null;

        $statuses = [StockOpnameStatus::Scheduled, StockOpnameStatus::InProgress];
        $query = StockOpname::whereIn('status', $statuses);

        if (!in_array($role, RoleName::highest(), true)) {
            $query->whereIn(
                'id',
                StockOpnameAssignment::select('stock_opname_id')
                    ->where('user_id', $user->getKey())
            );
        }

        return $query->count();
    }

    private function getGoodsReceiptsBadgeCount(string $role): ?int
    {
        return match (true) {
            in_array($role, RoleName::highest(), true),
            $role === RoleName::ManagerLogistic->value,
            $role === RoleName::StaffLogistic->value
            => SupplierDeliveryOrder::where('status', SupplierDeliveryOrderStatus::InDelivery)->count(),
            default => null,
        };
    }

    private function buildMenu(array $badges): array
    {
        return [
            [
                'title' => 'Dashboard',
                'items' => [
                    ['id' => 'home', 'label' => 'Dashboard HR', 'icon' => 'fa-home', 'link' => route('dashboard.hr')],
                ],
            ],
            [
                'title' => 'Menu Logistik',
                'items' => [
                    ['id' => 'products', 'label' => 'Produk', 'icon' => 'fa-box', 'link' => route('products.index')],
                    ['id' => 'product-prices', 'label' => 'Harga Produk', 'icon' => 'fa-tags', 'link' => route('product-prices.index')],
                    ['id' => 'warehouses', 'label' => 'Gudang', 'icon' => 'fa-warehouse', 'link' => route('warehouses.index')],
                    ['id' => 'stock-opnames', 'label' => 'Stok Opname', 'icon' => 'fa-clipboard-check', 'link' => route('stock-opnames.index'), 'badge' => $badges['stockOpname'] ?: null],
                    ['id' => 'stock-transfers', 'label' => 'Mutasi Stok', 'icon' => 'fa-right-left', 'link' => route('stock-transfers.index'), 'badge' => $badges['stockTransfer'] ?: null],
                    ['id' => 'stock-documents', 'label' => 'Dokumen Stok', 'icon' => 'fa-file-lines', 'link' => route('stock-documents.index')],
                    ['id' => 'goods-receipts', 'label' => 'Penerimaan Barang', 'icon' => 'fa-truck-ramp-box', 'link' => route('goods-receipts.index'), 'badge' => $badges['goodsReceipts'] ?: null],
                ],
            ],
            [
                'title' => 'Menu Purchasing',
                'items' => [
                    ['id' => 'suppliers', 'label' => 'Supplier', 'icon' => 'fa-truck', 'link' => route('suppliers.index')],
                    ['id' => 'purchase-orders', 'label' => 'Purchase Orders', 'icon' => 'fa-file-invoice', 'link' => route('purchase-orders.index'), 'badge' => $badges['po'] ?: null],
                    ['id' => 'purchase-returns', 'label' => 'Retur Pembelian', 'icon' => 'fa-rotate-left', 'link' => route('purchase-returns.index')],
                ],
            ],
            [
                'title' => 'Menu HR',
                'items' => [
                    ['id' => 'absent', 'label' => 'Absensi', 'icon' => 'fa-fingerprint', 'link' => route('absents.index')],
                    ['id' => 'my-schedule', 'label' => 'Jadwal Saya', 'icon' => 'fa-calendar-days', 'link' => route('my-schedule.index')],
                    ['id' => 'leaves', 'label' => 'Cuti Saya', 'icon' => 'fa-calendar-minus', 'link' => route('leaves.index')],
                    ['id' => 'organization-structure', 'label' => 'Struktur Organisasi', 'icon' => 'fa-sitemap', 'link' => route('organization-structure.index')],
                ],
            ],
            [
                'title' => 'Menu Sales',
                'items' => [
                    ['id' => 'pos', 'label' => 'Point of Sales', 'icon' => 'fa-cart-shopping', 'link' => route('pos.index')],
                    ['id' => 'pos-products', 'label' => 'Produk POS', 'icon' => 'fa-box', 'link' => route('pos.products.index')],
                    ['id' => 'discounts', 'label' => 'Diskon', 'icon' => 'fa-percent', 'link' => route('discounts.index')],
                    ['id' => 'payment-methods', 'label' => 'Metode Pembayaran', 'icon' => 'fa-credit-card', 'link' => route('payment-methods.index')],
                    ['id' => 'sales-history', 'label' => 'Riwayat Penjualan', 'icon' => 'fa-clock-rotate-left', 'link' => route('sales.index')],
                    ['id' => 'cash-history', 'label' => 'Riwayat Kas', 'icon' => 'fa-money-bill', 'link' => route('cashier-sessions.index')],
                ],
            ],
            [
                'title' => 'Master Data HR',
                'items' => [
                    ['id' => 'employee', 'label' => 'Karyawan', 'icon' => 'fa-user-tie', 'link' => route('employees.index')],
                    ['id' => 'shift', 'label' => 'Shift', 'icon' => 'fa-clock', 'link' => route('shifts.index')],
                    ['id' => 'schedule', 'label' => 'Jadwal', 'icon' => 'fa-calendar-alt', 'link' => route('schedules.index')],
                    ['id' => 'schedule-rules', 'label' => 'Aturan Jadwal', 'icon' => 'fa-calendar', 'link' => route('schedule-rules.index')],
                    ['id' => 'manage-leaves', 'label' => 'Izin Karyawan', 'icon' => 'fa-clipboard-list', 'link' => route('leaves.manage.index'), 'badge' => $badges['leaves'] ?: null],
                ],
            ],
            [
                'title' => 'Laporan Logistik',
                'items' => [
                    ['id' => 'stock-histories', 'label' => 'Riwayat Stok', 'icon' => 'fa-clipboard', 'link' => route('stock-histories.index')],
                    ['id' => 'product-stocks', 'label' => 'Stok Produk', 'icon' => 'fa-cubes', 'link' => route('product-stocks.index')],
                ],
            ],
            [
                'title' => 'Laporan HR',
                'items' => [
                    ['id' => 'report-absent', 'label' => 'Laporan Absensi', 'icon' => 'fa-chart-line', 'link' => route('reports.absents.index')],
                ],
            ],
        ];
    }

    private function configureDashboards(array $menu, ?string $role): array
    {
        $dashboards = [
            'highest' => [
                ['id' => 'home-logistic', 'label' => 'Dashboard Logistik', 'icon' => 'fa-warehouse', 'link' => route('dashboard.logistic')],
                ['id' => 'home-purchasing', 'label' => 'Dashboard Purchasing', 'icon' => 'fa-file-invoice', 'link' => route('dashboard.purchasing')],
                ['id' => 'home-sales', 'label' => 'Dashboard Sales', 'icon' => 'fa-bag-shopping', 'link' => route('dashboard.sales')],
            ],
            RoleName::ManagerLogistic->value => ['home' => ['Dashboard Logistik', route('dashboard.logistic')]],
            RoleName::StaffLogistic->value => ['home' => ['Dashboard Logistik', route('dashboard.logistic')]],
            RoleName::ManagerSales->value => ['home' => ['Dashboard Sales', route('dashboard.sales')]],
            RoleName::Cashier->value => ['home' => ['Dashboard Sales', route('dashboard.sales')]],
            RoleName::Sales->value => ['home' => ['Dashboard Sales', route('dashboard.sales')]],
        ];

        $config = match (true) {
            in_array($role, RoleName::highest(), true) => $dashboards['highest'],
            isset($dashboards[$role]) => $dashboards[$role],
            default => ['home' => ['Dashboard HR', route('dashboard.hr')]],
        };

        if (isset($config['home'])) {
            [$label, $link] = $config['home'];
            $menu[0]['items'][0] = array_merge($menu[0]['items'][0], ['label' => $label, 'link' => $link]);
        } else {
            foreach ($config as $dashboard) {
                $exists = collect($menu[0]['items'])->contains('id', $dashboard['id']);
                if (!$exists) {
                    $menu[0]['items'][] = $dashboard;
                }
            }
        }

        return $menu;
    }

    private function filterMenu(array $menu, ?string $role): array
    {
        $hiddenItems = $this->getHiddenMenuItems($role);

        foreach ($menu as &$group) {
            $group['items'] = array_values(array_filter(
                $group['items'],
                fn($item) => !in_array($item['id'], $hiddenItems, true)
            ));
        }
        unset($group);

        if ($role === RoleName::SuperAdmin->value) {
            return $menu;
        }

        return array_values(array_filter($menu, function ($group) {
            return !empty($group['items'])
                && !str_starts_with($group['title'], 'Master Data')
                && !str_starts_with($group['title'], 'Laporan');
        }));
    }

    private function getHiddenMenuItems(?string $role): array
    {
        if (!$role) return [];

        $isHighest = in_array($role, RoleName::highest(), true);
        $isCashier = $role === RoleName::Cashier->value;
        $isMarketing = $role === RoleName::Marketing->value;
        $isSalesRoles = in_array($role, [RoleName::ManagerSales->value, RoleName::Cashier->value, RoleName::Sales->value], true);

        $hidden = [];

        if ($isHighest) {
            $hidden = array_merge($hidden, ['absent', 'my-schedule', 'leaves']);
        }

        if ($role === RoleName::ManagerHR->value) {
            $hidden[] = 'my-schedule';
        }

        if ($isCashier) {
            $hidden = array_merge($hidden, ['purchase-orders', 'goods-receipts', 'purchase-returns', 'products', 'product-prices', 'suppliers', 'warehouses', 'stock-opnames', 'stock-documents']);
        }

        if ($isMarketing) {
            $hidden = array_merge($hidden, ['purchase-orders', 'goods-receipts', 'purchase-returns', 'products', 'product-prices', 'suppliers', 'warehouses', 'stock-documents']);
        }

        if (!$isSalesRoles && !$isHighest) {
            $hidden = array_merge($hidden, ['pos', 'pos-products', 'sales-history', 'cash-history', 'discounts', 'payment-methods']);
        }

        if ($role !== RoleName::SuperAdmin->value) {
            $hidden[] = 'organization-structure';
        }

        return $hidden;
    }
}
