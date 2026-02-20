<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    LoginController,
    SettingController,
    AccountSettingController,
    PasswordResetController,
    NotificationController
};
use App\Http\Controllers\Admin\Sales\{
    CustomerController as SalesCustomerController,
    DiscountController as SalesDiscountController,
    DashboardController as SalesDashboardController,
};

/*
|--------------------------------------------------------------------------
| Redirect Root
|--------------------------------------------------------------------------
*/

Route::get('/', fn() => redirect()->route('login'));
Route::get('/dashboard', fn() => redirect()->route('login'));

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
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
/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [SalesDashboardController::class, 'index'])
        ->name('dashboard');

    // Customers
    Route::prefix('customers')->name('customers.')->middleware('can:sales-master-manage')->group(function () {
        Route::get('export', [SalesCustomerController::class, 'export'])->name('export');
        Route::post('import', [SalesCustomerController::class, 'import'])->name('import');
        Route::get('import/template', [SalesCustomerController::class, 'importTemplate'])->name('import.template');
    });
    Route::resource('customers', SalesCustomerController::class)->except(['destroy'])->middleware('can:sales-master-manage');
    Route::delete('customers/{customer}', [SalesCustomerController::class, 'destroy'])->middleware('can:admin-manager-only-delete')->name('customers.destroy');

    Route::resource('discounts', SalesDiscountController::class)->except(['destroy'])->middleware('can:sales-master-manage');
    Route::delete('discounts/{discount}', [SalesDiscountController::class, 'destroy'])->middleware('can:admin-manager-only-delete')->name('discounts.destroy');

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
