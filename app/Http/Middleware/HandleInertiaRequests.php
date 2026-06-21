<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\CompanyProfile;
use App\Models\Order;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

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
        $user = $auth['guard'] ? Auth::guard($auth['guard'])->user() : null;

        return [
            ...$base,
            'auth' => $auth,
            'settings' => $this->sharedSettings(),
            'menu' => $this->getSidebarMenu($auth['user']['role'] ?? null),
            'notification_stats' => $user
                ? app(NotificationService::class)->getStatsForUser($user)
                : ['total' => 0, 'unread' => 0, 'read' => 0],
        ];
    }

    protected function sharedAuth(array $base): array
    {
        $isAdminRequest = request()->is('admin') || request()->is('admin/*');
        $isChefRequest = request()->is('chef') || request()->is('chef/*');
        $isPicRequest = request()->is('pic') || request()->is('pic/*');

        if ($isAdminRequest) {
            $guard = Auth::guard('web')->check() ? 'web' : null;
        } elseif ($isChefRequest) {
            $guard = Auth::guard('chef')->check() ? 'chef' : null;
        } elseif ($isPicRequest) {
            $guard = Auth::guard('pickup_officer')->check() ? 'pickup_officer' : null;
        } else {
            $guard = Auth::guard('customer')->check() ? 'customer' : null;
        }

        $user = $guard ? Auth::guard($guard)->user() : null;

        return [
            ...($base['auth'] ?? []),
            'guard' => $guard,
            'user' => $user ? [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $guard === 'customer' ? 'customer' : ($guard === 'chef' ? 'chef' : ($guard === 'pickup_officer' ? 'pickup_officer' : $user->role?->name)),
            ] : ($base['auth']['user'] ?? null),
        ];
    }

    protected function sharedSettings(): ?array
    {
        return Cache::remember(
            'settings:shared',
            600,
            fn () => CompanyProfile::query()->first()?->only([
                'name',
                'email',
                'phone',
                'whatsapp',
                'address',
                'instagram',
                'facebook',
                'tiktok',
            ])
        );
    }

    private function getSidebarMenu(?string $role): array
    {
        if (! $role || in_array($role, ['customer', 'chef'])) {
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
                        'id' => 'dashboard',
                        'label' => 'Dashboard',
                        'icon' => 'fa-gauge-high',
                        'link' => route('admin.dashboard'),
                    ],
                ],
            ],
            [
                'title' => 'Transaksi',
                'items' => [
                    [
                        'id' => 'orders',
                        'label' => 'Semua Pesanan',
                        'icon' => 'fa-bag-shopping',
                        'link' => route('admin.orders.index'),
                    ],
                    [
                        'id' => 'orders-resume',
                        'label' => 'Resume Order',
                        'icon' => 'fa-list-check',
                        'link' => route('admin.orders.resume'),
                    ],
                    [
                        'id' => 'orders-payments',
                        'label' => 'Approval Bayar',
                        'icon' => 'fa-money-check',
                        'link' => route('admin.orders.payments'),
                        'badge' => Order::query()
                            ->where('payment_status', 'pending')
                            ->whereDoesntHave('paymentMethod', fn ($q) => $q->where('category', 'cash'))
                            ->count(),
                    ],
                    [
                        'id' => 'orders-processing',
                        'label' => 'Proses Pesanan',
                        'icon' => 'fa-kitchen-set',
                        'link' => route('admin.orders.processing'),
                        'badge' => Order::query()
                            ->where(function ($q) {
                                $q->where('payment_status', '!=', 'pending')
                                    ->orWhereHas('paymentMethod', fn ($pq) => $pq->where('category', 'cash'));
                            })
                            ->whereIn('order_status', ['pending', 'confirmed', 'shipped'])
                            ->count(),
                    ],
                ],
            ],
            [
                'title' => 'Katalog',
                'items' => [
                    [
                        'id' => 'products',
                        'label' => 'Product',
                        'icon' => 'fa-box',
                        'link' => route('admin.products.index'),
                    ],
                    [
                        'id' => 'product-categories',
                        'label' => 'Kategori Produk',
                        'icon' => 'fa-tags',
                        'link' => route('admin.product-categories.index'),
                    ],
                ],
            ],
            [
                'title' => 'Operasional',
                'items' => [
                    [
                        'id' => 'drop-points',
                        'label' => 'Drop Points',
                        'icon' => 'fa-location-dot',
                        'link' => route('admin.drop-points.index'),
                    ],
                    [
                        'id' => 'pick-up-points',
                        'label' => 'Pick Up Points',
                        'icon' => 'fa-map-pin',
                        'link' => route('admin.pick-up-points.index'),
                    ],
                    [
                        'id' => 'pick-up-point-officers',
                        'label' => 'PIC Pick Up Point',
                        'icon' => 'fa-user-tie',
                        'link' => route('admin.pick-up-point-officers.index'),
                    ],
                    [
                        'id' => 'chefs',
                        'label' => 'Chef',
                        'icon' => 'fa-user-chef',
                        'submenu' => [
                            [
                                'label' => 'Semua Chef',
                                'link' => route('admin.chefs.index'),
                            ],
                            [
                                'label' => 'Dapur SBY Timur',
                                'link' => route('admin.chefs.region', 'sby_timur'),
                            ],
                            [
                                'label' => 'Dapur SBY Barat',
                                'link' => route('admin.chefs.region', 'sby_barat'),
                            ],
                            [
                                'label' => 'Dapur SBY Utara',
                                'link' => route('admin.chefs.region', 'sby_utara'),
                            ],
                            [
                                'label' => 'Dapur SBY Selatan',
                                'link' => route('admin.chefs.region', 'sby_selatan'),
                            ],
                            [
                                'label' => 'Dapur SBY Pusat',
                                'link' => route('admin.chefs.region', 'sby_pusat'),
                            ],
                        ],
                    ],
                    [
                        'id' => 'customers',
                        'label' => 'Customer',
                        'icon' => 'fa-users',
                        'link' => route('admin.customers.index'),
                    ],
                    [
                        'id' => 'payment-methods',
                        'label' => 'Metode Pembayaran',
                        'icon' => 'fa-credit-card',
                        'link' => route('admin.payment-methods.index'),
                    ],
                    [
                        'id' => 'payment-guides',
                        'label' => 'Panduan Pembayaran',
                        'icon' => 'fa-book',
                        'link' => route('admin.payment-guides.index'),
                    ],
                ],
            ],
            [
                'title' => 'Laporan',
                'items' => [
                    [
                        'id' => 'reports',
                        'label' => 'Laporan',
                        'icon' => 'fa-chart-bar',
                        'link' => route('admin.reports.index'),
                    ],
                ],
            ],
            [
                'title' => 'Lainnya',
                'items' => [
                    [
                        'id' => 'food-requests',
                        'label' => 'Request Menu',
                        'icon' => 'fa-utensils',
                        'link' => route('admin.food-requests.index'),
                    ],
                    [
                        'id' => 'sliders',
                        'label' => 'Slider',
                        'icon' => 'fa-images',
                        'link' => route('admin.sliders.index'),
                    ],
                    [
                        'id' => 'testimonial-templates',
                        'label' => 'Template Testimoni',
                        'icon' => 'fa-comments',
                        'link' => route('admin.testimonial-templates.index'),
                    ],
                ],
            ],
        ];
    }
}
