<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Marketing\{
    AuthController as MarketingAuthController,
    CustomerController as MarketingCustomerController,
    SalesController as MarketingSalesController,
    ProfileController as MarketingProfileController,
    NotificationController as MarketingNotificationController,
    DiscountController as MarketingDiscountController,
    VoucherController as MarketingVoucherController,
    ProductController as MarketingProductController,
    VatController as MarketingVatController,
    PriceLevelController as MarketingPriceLevelController,
    PriceController as MarketingPriceController,
    PaymentMethodController as MarketingPaymentMethodController,
    SyncController as MarketingSyncController,
    CashierController as MarketingCashierController,
};

Route::prefix('marketing')->group(function (): void {
    Route::post('/login', [MarketingAuthController::class, 'login'])
        ->name('api.marketing.login')
        ->middleware('throttle:10,1');

    Route::post('/forgot-password', [MarketingAuthController::class, 'forgotPassword'])
        ->name('api.marketing.password.email')
        ->middleware('throttle:6,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/profile', [MarketingProfileController::class, 'show'])
            ->name('api.marketing.profile.show');
        Route::put('/profile', [MarketingProfileController::class, 'update'])
            ->name('api.marketing.profile.update');
        Route::post('/sync', [MarketingSyncController::class, 'sync'])
            ->name('api.marketing.sync');
        // Cashier
        Route::post('/cashier/open', [MarketingCashierController::class, 'open'])
            ->name('api.marketing.cashier.open');
        Route::post('/cashier/close', [MarketingCashierController::class, 'close'])
            ->name('api.marketing.cashier.close');
        // Notifications
        Route::get('/notifications', [MarketingNotificationController::class, 'index'])
            ->name('api.marketing.notifications.index');
        Route::put('/notifications/{notification}/read', [MarketingNotificationController::class, 'mark'])
            ->name('api.marketing.notifications.mark');
        // Sales
        Route::get('/sales/summary', [MarketingSalesController::class, 'summary'])
            ->name('api.marketing.sales.summary');
        Route::get('/sales', [MarketingSalesController::class, 'index'])
            ->name('api.marketing.sales.index');
        Route::get('/sales/{sales}', [MarketingSalesController::class, 'show'])
            ->name('api.marketing.sales.show');
        Route::post('/sales', [MarketingSalesController::class, 'store'])
            ->name('api.marketing.sales.store');
        Route::put('/sales/{sales}', [MarketingSalesController::class, 'update'])
            ->name('api.marketing.sales.update');

        // Discounts & Vouchers
        Route::get('/discounts/all', [MarketingDiscountController::class, 'all'])
            ->name('api.marketing.discounts.all');
        Route::get('/vouchers/all', [MarketingVoucherController::class, 'all'])
            ->name('api.marketing.vouchers.all');

        // Products
        Route::get('/products/all', [MarketingProductController::class, 'all'])
            ->name('api.marketing.products.all');

        // VAT
        Route::get('/vat', [MarketingVatController::class, 'index'])
            ->name('api.marketing.vat.index');

        // Selling Price Levels
        Route::get('/price-levels/all', [MarketingPriceLevelController::class, 'all'])
            ->name('api.marketing.price-levels.all');

        // Selling Prices
        Route::get('/prices/selling', [MarketingPriceController::class, 'selling'])
            ->name('api.marketing.prices.selling');

        // Payment Methods
        Route::get('/payment-methods/all', [MarketingPaymentMethodController::class, 'all'])
            ->name('api.marketing.payment-methods.all');

        // Customer
        Route::get('/customers', [MarketingCustomerController::class, 'index'])
            ->name('api.marketing.customers.index');
        Route::get('/customers/all', [MarketingCustomerController::class, 'all'])
            ->name('api.marketing.customers.all');
        Route::get('/customers/{customer}', [MarketingCustomerController::class, 'show'])
            ->name('api.marketing.customers.show');
        Route::post('/customers', [MarketingCustomerController::class, 'store'])
            ->name('api.marketing.customers.store');
        Route::put('/customers/{customer}', [MarketingCustomerController::class, 'update'])
            ->name('api.marketing.customers.update');
    });
});
