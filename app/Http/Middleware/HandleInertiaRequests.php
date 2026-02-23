<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\{CompanyProfile};
use App\Enums\{RoleName};
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
        $base  = parent::share($request);
        $auth  = $this->sharedAuth($base);

        return [
            ...$base,
            'auth'               => $auth,
            'settings'           => $this->sharedSettings(),
            'menu'               => $this->getSidebarMenu($auth['user']['role'] ?? null),
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
            'user'  => $user ? [
                'id'    => $user->getKey(),
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role?->name,
            ] : ($base['auth']['user'] ?? null),
        ];
    }

    protected function sharedSettings(): ?array
    {
        return Cache::remember(
            'settings:shared',
            600,
            fn() =>
            CompanyProfile::query()->first()?->only([
                'name',
                'contact_email',
                'whatsapp_number',
                'logo',
                'address',
                'latitude',
                'longitude',
                'bank_name',
                'bank_account_name',
                'bank_account_number',
            ])
        );
    }

    private function getSidebarMenu(?string $role): array
    {
        if (!$role) {
            return [];
        }

        return $this->buildAdminMenu();
    }

    private function buildAdminMenu(): array
    {
        return [
            [
                'title' => 'Main',
                'items' => [
                    [
                        'id'    => 'dashboard',
                        'label' => 'Dashboard',
                        'icon'  => 'fa-gauge-high',
                        'link'  => route('admin.dashboard'),
                    ],
                ],
            ],
            [
                'title' => 'Transaksi',
                'items' => [
                    [
                        'id'    => 'orders',
                        'label' => 'Pesanan',
                        'icon'  => 'fa-bag-shopping',
                        'link'  => route('admin.orders.index'),
                    ],
                ],
            ],
            [
                'title' => 'Katalog',
                'items' => [
                    [
                        'id'    => 'products',
                        'label' => 'Product',
                        'icon'  => 'fa-box',
                        'link'  => route('admin.products.index'),
                    ],
                    [
                        'id'    => 'product-categories',
                        'label' => 'Kategori Produk',
                        'icon'  => 'fa-tags',
                        'link'  => route('admin.product-categories.index'),
                    ],
                ],
            ],
            [
                'title' => 'Operasional',
                'items' => [
                    [
                        'id'    => 'customers',
                        'label' => 'Customer',
                        'icon'  => 'fa-users',
                        'link'  => route('admin.customers.index'),
                    ],
                    [
                        'id'    => 'drop-points',
                        'label' => 'Drop Points',
                        'icon'  => 'fa-location-dot',
                        'link'  => route('admin.drop-points.index'),
                    ],
                ],
            ],
            [
                'title' => 'Laporan',
                'items' => [
                    [
                        'id'    => 'reports',
                        'label' => 'Laporan',
                        'icon'  => 'fa-chart-bar',
                        'link'  => route('admin.reports.index'),
                    ],
                ],
            ],
        ];
    }
}
