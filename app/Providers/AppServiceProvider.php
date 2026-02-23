<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Vite, Gate, URL};
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
        RedirectIfAuthenticated::redirectUsing(function (Request $request) {
            // Customer guard → customer dashboard
            if (Auth::guard('customer')->check()) {
                return route('home');
            }

            // Default: admin/web guard → admin dashboard
            return route('admin.dashboard');
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

        Gate::define('admin-only', static function (User $user) use ($roleOf): bool {
            return in_array($roleOf($user), [RoleName::Admin->value, RoleName::SuperAdmin->value], true);
        });

        Gate::define('super-admin-only', static function (User $user) use ($roleOf): bool {
            return $roleOf($user) === RoleName::SuperAdmin->value;
        });
    }
}
