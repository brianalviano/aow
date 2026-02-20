<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\{Vite, Gate, URL};
use Inertia\Inertia;
use App\Models\User;
use App\Enums\RoleName;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Menginisialisasi konfigurasi aplikasi pada saat bootstrapping.
     * Memisahkan setiap konfigurasi ke dalam metode privat agar mudah dirawat.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->configureHttps();
        $this->configureVitePrefetch();
        $this->configureInertia();
        $this->registerAuthRedirect();
        $this->registerAuthorizationGates();
    }

    /**
     * Konfigurasi HTTPS pada environment non-local untuk memastikan
     * URL dan request dianggap aman.
     *
     * @return void
     */
    private function configureHttps(): void
    {
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
            $this->app['request']->server->set('HTTPS', true);
        }
    }

    /**
     * Konfigurasi prefetch untuk Vite agar optimalkan pengambilan aset.
     * Sesuaikan concurrency jika diperlukan berdasarkan kapasitas server.
     *
     * @return void
     */
    private function configureVitePrefetch(): void
    {
        Vite::prefetch(concurrency: 3);
    }

    /**
     * Aktifkan enkripsi riwayat (history) untuk Inertia
     * guna meningkatkan keamanan navigasi client-side.
     *
     * @return void
     */
    private function configureInertia(): void
    {
        Inertia::encryptHistory();
    }

    /**
     * Atur perilaku redirect jika user sudah terautentikasi,
     * mengarahkan ke portal dashboard.
     *
     * @return void
     */
    private function registerAuthRedirect(): void
    {
        RedirectIfAuthenticated::redirectUsing(function () {
            return route('dashboard.hr');
        });
    }

    /**
     * Daftarkan Gates untuk otorisasi aplikasi.
     * Termasuk gate 'admin-only' untuk membatasi akses fitur Admin.
     *
     * @return void
     */
    private function registerAuthorizationGates(): void
    {
        $roleOf = static function (User $user): ?string {
            return $user->role?->name ?? null;
        };

        $isHighest = static function (User $user) use ($roleOf): bool {
            return in_array($roleOf($user), RoleName::highest(), true);
        };

        $hasAny = static function (User $user, array $roles) use ($roleOf): bool {
            return in_array($roleOf($user), $roles, true);
        };

        Gate::define('admin-only', static function (User $user) use ($roleOf): bool {
            return $roleOf($user) === RoleName::SuperAdmin->value;
        });

        Gate::define('attendance-access', static function (User $user) use ($isHighest): bool {
            return !$isHighest($user);
        });

        Gate::define('highest-only', static function (User $user) use ($isHighest): bool {
            return $isHighest($user);
        });

        Gate::define('hr-master-manage', static function (User $user) use ($isHighest, $roleOf): bool {
            return $roleOf($user) === RoleName::ManagerHR->value || $isHighest($user);
        });

        Gate::define('hr-report-access', static function (User $user) use ($isHighest, $roleOf): bool {
            return $roleOf($user) === RoleName::ManagerHR->value || $isHighest($user);
        });

        Gate::define('logistic-master-manage', static function (User $user) use ($isHighest, $roleOf): bool {
            return $roleOf($user) === RoleName::ManagerLogistic->value || $isHighest($user);
        });

        Gate::define('logistic-stockcard', static function (User $user) use ($isHighest, $hasAny): bool {
            return $hasAny($user, [
                RoleName::ManagerLogistic->value,
                RoleName::StaffLogistic->value,
            ]) || $isHighest($user);
        });

        Gate::define('logistic-report-access', static function (User $user) use ($isHighest, $roleOf): bool {
            return $roleOf($user) === RoleName::ManagerLogistic->value || $isHighest($user);
        });

        Gate::define('stock-opname-access', static function (User $user) use ($isHighest, $hasAny): bool {
            return $hasAny($user, [
                RoleName::ManagerLogistic->value,
                RoleName::StaffLogistic->value,
                RoleName::Marketing->value,
            ]) || $isHighest($user);
        });

        Gate::define('po-ho-approve', static function (User $user) use ($isHighest): bool {
            return $isHighest($user);
        });

        Gate::define('po-manage', static function (User $user) use ($isHighest, $roleOf): bool {
            return $roleOf($user) === RoleName::ManagerLogistic->value || $isHighest($user);
        });

        Gate::define('po-operate', static function (User $user) use ($isHighest, $hasAny): bool {
            return $hasAny($user, [
                RoleName::ManagerLogistic->value,
                RoleName::StaffLogistic->value,
            ]) || $isHighest($user);
        });

        Gate::define('sales-master-manage', static function (User $user) use ($isHighest, $roleOf): bool {
            return $roleOf($user) === RoleName::ManagerSales->value || $isHighest($user);
        });

        Gate::define('pos-operate', static function (User $user) use ($isHighest, $hasAny): bool {
            return $hasAny($user, [
                RoleName::ManagerSales->value,
                RoleName::Cashier->value,
                RoleName::Sales->value,
                RoleName::Marketing->value,
            ]) || $isHighest($user);
        });

        Gate::define('admin-manager-only-delete', static function (User $user) use ($hasAny): bool {
            return $hasAny($user, [
                RoleName::ManagerHR->value,
                RoleName::ManagerLogistic->value,
                RoleName::ManagerSales->value,
                RoleName::ManagerFinance->value,
                RoleName::SuperAdmin->value,
                RoleName::Director->value,
            ]);
        });
    }
}
