<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

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
     */
    public function boot(): void
    {
        $this->configureHttps();
        $this->configureVitePrefetch();
        $this->configureInertia();
        $this->registerAuthRedirect();
        $this->registerAuthorizationGates();
        $this->configurePasswordResets();
    }

    /**
     * Konfigurasi URL reset password untuk berbagai guard.
     * Memastikan Admin dan Customer mendapatkan link reset yang sesuai.
     */
    private function configurePasswordResets(): void
    {
        ResetPassword::createUrlUsing(function ($user, string $token) {
            if ($user instanceof User) {
                return url(config('app.url').route('admin.password.reset', [
                    'token' => $token,
                    'email' => $user->getEmailForPasswordReset(),
                ], false));
            }

            return url(config('app.url').route('password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ], false));
        });
    }

    /**
     * Konfigurasi HTTPS pada environment non-local untuk memastikan
     * URL dan request dianggap aman.
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
     */
    private function configureVitePrefetch(): void
    {
        Vite::prefetch(concurrency: 3);
    }

    /**
     * Aktifkan enkripsi riwayat (history) untuk Inertia
     * guna meningkatkan keamanan navigasi client-side.
     */
    private function configureInertia(): void
    {
        Inertia::encryptHistory();
    }

    /**
     * Atur perilaku redirect jika user sudah terautentikasi,
     * mengarahkan ke portal dashboard.
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
