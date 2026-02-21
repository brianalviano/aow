<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    LoginController,
    SettingController,
    AccountSettingController,
    DashboardController,
    PasswordResetController,
    NotificationController,
    OrderController,
    ProductController,
    ProductCategoryController,
    DropPointController,
    ReportController,
};
use App\Http\Controllers\Customer\CustomerLoginController;

/*
|--------------------------------------------------------------------------
| Customer Routes (root level)
|--------------------------------------------------------------------------
*/

// Public homepage – browse drop points and menu without logging in
Route::get('/', fn() => inertia('Customer/Home'))
    ->name('customer.home');

// Customer guest routes
Route::middleware('guest:customer')->group(function () {
    Route::get('/login', [CustomerLoginController::class, 'show'])
        ->name('customer.login');
    Route::post('/login', [CustomerLoginController::class, 'authenticate'])
        ->name('customer.login.store');
});

// Customer authenticated routes
Route::middleware('auth:customer')->group(function () {
    Route::get('/dashboard', fn() => inertia('Customer/Dashboard'))
        ->name('customer.dashboard');
    Route::post('/logout', [CustomerLoginController::class, 'logout'])
        ->name('customer.logout');
});

/*
|--------------------------------------------------------------------------
| Admin Routes (/admin prefix)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {

    // Admin guest routes
    Route::get('/login', [LoginController::class, 'show'])
        ->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])
        ->name('login.store');
    Route::post('/logout', [LoginController::class, 'logout'])
        ->name('logout');

    Route::middleware('guest')->group(function () {
        Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])
            ->name('password.request');
        Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
            ->middleware('throttle:6,1')
            ->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])
            ->name('password.reset');
        Route::post('/reset-password', [PasswordResetController::class, 'reset'])
            ->middleware('throttle:6,1')
            ->name('password.update');
    });

    // Admin authenticated routes
    Route::middleware('auth')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        // Orders
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

        // Products
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        // Product Categories
        Route::get('/product-categories', [ProductCategoryController::class, 'index'])->name('product-categories.index');
        Route::get('/product-categories/create', [ProductCategoryController::class, 'create'])->name('product-categories.create');
        Route::post('/product-categories', [ProductCategoryController::class, 'store'])->name('product-categories.store');
        Route::get('/product-categories/{category}/edit', [ProductCategoryController::class, 'edit'])->name('product-categories.edit');
        Route::put('/product-categories/{category}', [ProductCategoryController::class, 'update'])->name('product-categories.update');
        Route::delete('/product-categories/{category}', [ProductCategoryController::class, 'destroy'])->name('product-categories.destroy');

        // Drop Points
        Route::get('/drop-points', [DropPointController::class, 'index'])->name('drop-points.index');
        Route::get('/drop-points/create', [DropPointController::class, 'create'])->name('drop-points.create');
        Route::post('/drop-points', [DropPointController::class, 'store'])->name('drop-points.store');
        Route::get('/drop-points/{dropPoint}/edit', [DropPointController::class, 'edit'])->name('drop-points.edit');
        Route::put('/drop-points/{dropPoint}', [DropPointController::class, 'update'])->name('drop-points.update');
        Route::delete('/drop-points/{dropPoint}', [DropPointController::class, 'destroy'])->name('drop-points.destroy');

        // Reports
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

        Route::get('/settings', [SettingController::class, 'index'])
            ->middleware('can:admin-only')
            ->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])
            ->middleware('can:admin-only')
            ->name('settings.update');

        Route::get('/account/settings', [AccountSettingController::class, 'index'])
            ->name('account.settings.index');
        Route::put('/account/settings', [AccountSettingController::class, 'update'])
            ->name('account.settings.update');

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::get('/notifications/stats', [NotificationController::class, 'stats'])
            ->name('notifications.stats');
        Route::get('/notifications/list', [NotificationController::class, 'list'])
            ->name('notifications.list');
        Route::patch('/notifications/{notification}', [NotificationController::class, 'mark'])
            ->name('notifications.mark');
        Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAll'])
            ->name('notifications.mark_all');
    });
});
