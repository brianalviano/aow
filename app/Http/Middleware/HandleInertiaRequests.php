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
        $base = parent::share($request);
        $auth = $this->sharedAuth($base);

        return [
            ...$base,
            'auth' => $auth,
            'settings' => $this->sharedSettings(),
            'badges' => Auth::check() ? null : [],
            'menu' => $this->getSidebarMenu($auth['user']['role'] ?? null),
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
            CompanyProfile::query()->first()?->only([
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
            return [];
        }

        return Cache::remember("badges:{$role}:" . Auth::id(), 60, function () use ($role) {
            return [];
        });
    }

    private function buildMenu(array $badges): array
    {
        return [
            [
                'title' => 'Dashboard',
                'items' => [
                    ['id' => 'home', 'label' => 'Dashboard', 'icon' => 'fa-home', 'link' => route('dashboard')],
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

        $hidden = [];

        return $hidden;
    }
}
