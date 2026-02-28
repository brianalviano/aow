<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    AccountSettingController,
    ChefController,
    LoginController as AdminLoginController,
    CustomerController,
    DashboardController,
    DropPointController,
    FoodRequestController as AdminFoodRequestController,
    NotificationController,
    OrderController,
    PasswordResetController,
    PaymentGuideController,
    PaymentMethodController,
    ProductCategoryController,
    ProductController,
    ReportController,
    SettingController,
    SliderController,
    UserController
};
use App\Http\Controllers\Chef\{
    DashboardController as ChefDashboardController,
    LoginController as ChefLoginController
};
use App\Http\Controllers\Customer\{
    AuthController,
    HomeController,
    MenuController,
    DropPointController as CustomerDropPointController,
    ProductController as CustomerProductController,
    PrivacyPolicyController,
    TermsAndConditionController,
    CheckoutController,
    PaymentController,
    FeedbackController,
    FoodRequestController,
    CustomerAddressController,
};

/*
|--------------------------------------------------------------------------
| Customer Routes (root level)
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/drop-points', [CustomerDropPointController::class, 'index'])->name('customer.drop-points.index');
Route::get('/drop-points/{id}', [CustomerDropPointController::class, 'show'])->name('customer.drop-points.show');
Route::get('/drop-points/{id}/products', [CustomerProductController::class, 'index'])->name('customer.products');
Route::get('/order-type', [\App\Http\Controllers\Customer\OrderTypeController::class, 'index'])->name('customer.order-type.index');
Route::post('/order-type', [\App\Http\Controllers\Customer\OrderTypeController::class, 'store'])->name('customer.order-type.store');
Route::get('/custom-address', [CustomerAddressController::class, 'index'])->name('customer.addresses.index');
Route::post('/custom-address', [CustomerAddressController::class, 'store'])->name('customer.addresses.store');
Route::put('/custom-address/{address}', [CustomerAddressController::class, 'update'])->name('customer.addresses.update');
Route::delete('/custom-address/{address}', [CustomerAddressController::class, 'destroy'])->name('customer.addresses.destroy');
Route::get('/products', [CustomerProductController::class, 'generalIndex'])->name('customer.products.general');
Route::get('/menu', [MenuController::class, 'index'])->name('customer.menu');
Route::get('/privacy-policy', [PrivacyPolicyController::class, 'index'])->name('customer.privacy-policy');
Route::get('/terms-and-conditions', [TermsAndConditionController::class, 'index'])->name('customer.terms-and-conditions');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('customer.checkout');
Route::post('/checkout/session', [CheckoutController::class, 'store'])->name('customer.checkout.session');
Route::post('/checkout/update-session', [CheckoutController::class, 'update'])->name('customer.checkout.update-session');
Route::get('/payment-summary', [PaymentController::class, 'index'])->name('customer.payment-summary');
Route::get('/payment/{order}', [PaymentController::class, 'show'])->name('customer.payment.show');
Route::post('/payment', [PaymentController::class, 'processPayment'])->name('customer.payment.store');
Route::post('/payment/{order}/proof', [PaymentController::class, 'uploadProof'])->name('customer.payment.proof');

// Midtrans Redirects
Route::get('/payment/finish', function () {
    return redirect()->route('customer.products')->with('success', 'Pembayaran sedang diproses atau sudah berhasil.');
})->name('payment.finish');

Route::get('/checkout/unfinish', function (\Illuminate\Http\Request $request) {
    if ($request->has('order_id')) {
        $order = \App\Models\Order::where('number', $request->order_id)->first();
        if ($order) {
            return redirect()->route('customer.payment.show', $order->id)->with('warning', 'Pembayaran belum diselesaikan.');
        }
    }
    return redirect()->route('customer.payment-summary')->with('warning', 'Pembayaran belum diselesaikan.');
})->name('payment.unfinish');

Route::get('/checkout/error', function (\Illuminate\Http\Request $request) {
    if ($request->has('order_id')) {
        $order = \App\Models\Order::where('number', $request->order_id)->first();
        if ($order) {
            return redirect()->route('customer.payment.show', $order->id)->with('error', 'Terjadi kesalahan saat memproses pembayaran.');
        }
    }
    return redirect()->route('customer.payment-summary')->with('error', 'Terjadi kesalahan saat memproses pembayaran.');
})->name('payment.error');

// Customer Guest Routes
Route::middleware('guest:customer')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('customer.login');
    Route::post('/login', [AuthController::class, 'login'])->name('customer.login.store');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('customer.register');
    Route::post('/register', [AuthController::class, 'register'])->name('customer.register.store');
});

// Customer Authenticated Routes
Route::middleware('auth:customer')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('customer.logout');
    Route::get('/profile', [\App\Http\Controllers\Customer\ProfileController::class, 'edit'])->name('customer.profile.edit');
    Route::put('/profile', [\App\Http\Controllers\Customer\ProfileController::class, 'update'])->name('customer.profile.update');
    Route::get('/notifications', [\App\Http\Controllers\Customer\NotificationController::class, 'index'])->name('customer.notifications.index');
    Route::post('/notifications/mark-as-read', [\App\Http\Controllers\Customer\NotificationController::class, 'markAsRead'])->name('customer.notifications.mark-as-read');

    // Orders
    Route::get('/orders', [\App\Http\Controllers\Customer\OrderController::class, 'index'])->name('customer.orders.index');
    Route::get('/orders/{order}', [\App\Http\Controllers\Customer\OrderController::class, 'show'])->name('customer.orders.show');
    Route::post('/orders/{order}/complete', [\App\Http\Controllers\Customer\OrderController::class, 'complete'])->name('customer.orders.complete');
    // Testimonials (Refactored to Order Item)
    Route::post('/order-items/{orderItem}/testimonial', [\App\Http\Controllers\Customer\OrderController::class, 'testimonial'])->name('customer.order-items.testimonial');

    // Feedback
    Route::get('/feedback', [FeedbackController::class, 'index'])->name('customer.feedback.index');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('customer.feedback.store');

    // Food Request
    Route::get('/food-requests', [FoodRequestController::class, 'index'])->name('customer.food-requests.index');
    Route::post('/food-requests', [FoodRequestController::class, 'store'])->name('customer.food-requests.store');
});

/*
|--------------------------------------------------------------------------
| Admin Routes (/admin prefix)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/', fn() => redirect()->route('admin.login'));

    // Admin guest routes
    Route::get('/login', [AdminLoginController::class, 'show'])
        ->name('login');
    Route::post('/login', [AdminLoginController::class, 'authenticate'])
        ->name('login.store');
    Route::post('/logout', [AdminLoginController::class, 'logout'])
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
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
        Route::post('/orders/{order}/ship', [OrderController::class, 'ship'])->name('orders.ship');
        Route::post('/orders/{order}/deliver', [OrderController::class, 'deliver'])->name('orders.deliver');

        // Testimonial Management
        Route::patch('/testimonials/{testimonial}/approve', [OrderController::class, 'approveTestimonial'])->name('orders.testimonial.approve');
        Route::delete('/testimonials/{testimonial}', [OrderController::class, 'rejectTestimonial'])->name('orders.testimonial.reject');

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

        // Sliders
        Route::get('/sliders', [SliderController::class, 'index'])->name('sliders.index');
        Route::get('/sliders/create', [SliderController::class, 'create'])->name('sliders.create');
        Route::post('/sliders', [SliderController::class, 'store'])->name('sliders.store');
        Route::get('/sliders/{slider}/edit', [SliderController::class, 'edit'])->name('sliders.edit');
        Route::put('/sliders/{slider}', [SliderController::class, 'update'])->name('sliders.update');
        Route::delete('/sliders/{slider}', [SliderController::class, 'destroy'])->name('sliders.destroy');

        // Drop Points
        Route::get('/drop-points', [DropPointController::class, 'index'])->name('drop-points.index');
        Route::get('/drop-points/create', [DropPointController::class, 'create'])->name('drop-points.create');
        Route::post('/drop-points', [DropPointController::class, 'store'])->name('drop-points.store');
        Route::get('/drop-points/{dropPoint}/edit', [DropPointController::class, 'edit'])->name('drop-points.edit');
        Route::put('/drop-points/{dropPoint}', [DropPointController::class, 'update'])->name('drop-points.update');
        Route::delete('/drop-points/{dropPoint}', [DropPointController::class, 'destroy'])->name('drop-points.destroy');

        // Payment Methods
        Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::get('/payment-methods/create', [PaymentMethodController::class, 'create'])->name('payment-methods.create');
        Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
        Route::get('/payment-methods/{paymentMethod}/edit', [PaymentMethodController::class, 'edit'])->name('payment-methods.edit');
        Route::put('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('payment-methods.update');
        Route::delete('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');

        // Payment Guides
        Route::resource('payment-guides', PaymentGuideController::class)->except(['show']);

        // Reports
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
        Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');

        // Customers
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

        // Food Requests
        Route::get('/food-requests', [AdminFoodRequestController::class, 'index'])->name('food-requests.index');
        Route::patch('/food-requests/{foodRequest}', [AdminFoodRequestController::class, 'update'])->name('food-requests.update');

        // Chefs
        Route::get('/chefs', [ChefController::class, 'index'])->name('chefs.index');
        Route::get('/chefs/create', [ChefController::class, 'create'])->name('chefs.create');
        Route::post('/chefs', [ChefController::class, 'store'])->name('chefs.store');
        Route::get('/chefs/{chef}', [ChefController::class, 'show'])->name('chefs.show');
        Route::get('/chefs/{chef}/edit', [ChefController::class, 'edit'])->name('chefs.edit');
        Route::put('/chefs/{chef}', [ChefController::class, 'update'])->name('chefs.update');
        Route::delete('/chefs/{chef}', [ChefController::class, 'destroy'])->name('chefs.destroy');
        Route::post('/chefs/{chef}/transfers', [ChefController::class, 'storeTransfer'])->name('chefs.transfers.store');

        // Users (Admins)
        Route::resource('users', UserController::class)->middleware('can:admin-only');

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

/*
|--------------------------------------------------------------------------
| Chef Routes (/chef prefix)
|--------------------------------------------------------------------------
*/

Route::prefix('chef')->name('chef.')->group(function () {

    // Chef authenticated routes
    Route::middleware('auth:chef')->group(function () {
        Route::get('/', [ChefDashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [ChefLoginController::class, 'logout'])->name('logout');
    });

    // Chef guest routes
    Route::middleware('guest:chef')->group(function () {
        Route::get('/login', [ChefLoginController::class, 'show'])->name('login');
        Route::post('/login', [ChefLoginController::class, 'login'])->name('login.store');
    });
});
