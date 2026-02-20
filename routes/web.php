<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    LoginController,
    SettingController,
    AccountSettingController,
    PasswordResetController,
    NotificationController
};
use App\Http\Controllers\Admin\HR\{
    EmployeeController,
    OrganizationStructureController,
    ScheduleController,
    ScheduleRuleController,
    ShiftController,
    AttendanceController,
    ReportAttendanceController,
    SelfScheduleController,
    SelfLeaveController,
    LeaveController,
    DashboardController as HRDashboardController
};
use App\Http\Controllers\Admin\Logistic\{
    ProductController as LogisticProductController,
    WarehouseController as LogisticWarehouseController,
    StockHistoryController as LogisticStockHistoryController,
    ProductPriceController as LogisticProductPriceController,
    ProductStockController as LogisticProductStockController,
    StockTransferController as LogisticStockTransferController,
    StockOpnameController as LogisticStockOpnameController,
    DashboardController as LogisticDashboardController,
    GoodsReceiptController as LogisticGoodsReceiptController,
    StockDocumentController as LogisticStockDocumentController
};
use App\Http\Controllers\Admin\Purchasing\{
    SupplierController as PurchasingSupplierController,
    PurchaseReturnController as PurchasingPurchaseReturnController,
    PurchaseOrderController as PurchasingPurchaseOrderController,
    DashboardController as PurchasingDashboardController,
};
use App\Http\Controllers\Admin\Sales\{
    CustomerController as SalesCustomerController,
    PosController as SalesPosController,
    PaymentMethodController as SalesPaymentMethodController,
    CashierSessionController as CashierSessionController,
    PosProductController as SalesPosProductController,
    SalesHistoryController,
    DiscountController as SalesDiscountController,
    DashboardController as SalesDashboardController,
    CashHistoryController,
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

    Route::get('/dashboard/hr', [HRDashboardController::class, 'index'])
        ->name('dashboard.hr');
    Route::get('/dashboard/logistic', [LogisticDashboardController::class, 'index'])
        ->middleware('can:logistic-stockcard')
        ->name('dashboard.logistic');
    Route::get('/dashboard/purchasing', [PurchasingDashboardController::class, 'index'])
        ->middleware('can:po-operate')
        ->name('dashboard.purchasing');
    Route::get('/dashboard/sales', [SalesDashboardController::class, 'index'])
        ->middleware('can:pos-operate')
        ->name('dashboard.sales');

    // Products
    Route::prefix('products')->name('products.')->middleware('can:logistic-master-manage')->group(function () {
        Route::get('export', [LogisticProductController::class, 'export'])->name('export');
        Route::post('import', [LogisticProductController::class, 'import'])->name('import');
        Route::get('import/template', [LogisticProductController::class, 'importTemplate'])->name('import.template');
    });
    Route::resource('products', LogisticProductController::class)->except(['destroy'])->middleware('can:logistic-master-manage');
    Route::delete('products/{product}', [LogisticProductController::class, 'destroy'])->middleware('can:admin-manager-only-delete')->name('products.destroy');
    Route::get('products/{product}/print', [LogisticProductController::class, 'print'])->middleware('can:logistic-master-manage')->name('products.print');
    Route::get('/product-prices', [LogisticProductPriceController::class, 'index'])->middleware('can:logistic-master-manage')->name('product-prices.index');
    Route::post('/product-prices', [LogisticProductPriceController::class, 'store'])->middleware('can:logistic-master-manage')->name('product-prices.store');
    Route::post('/product-prices/levels', [LogisticProductPriceController::class, 'storeLevel'])->middleware('can:logistic-master-manage')->name('product-prices.levels.store');
    Route::delete('/product-prices/levels/{level}', [LogisticProductPriceController::class, 'destroyLevel'])->middleware('can:logistic-master-manage')->name('product-prices.levels.destroy');
    Route::post('/product-prices/levels/{level}/adjust', [LogisticProductPriceController::class, 'adjustLevel'])->middleware('can:logistic-master-manage')->name('product-prices.levels.adjust');
    Route::post('/product-prices/suppliers/{supplier}/adjust', [LogisticProductPriceController::class, 'adjustSupplier'])->middleware('can:logistic-master-manage')->name('product-prices.suppliers.adjust');

    // Suppliers
    Route::prefix('suppliers')->name('suppliers.')->middleware('can:logistic-master-manage')->group(function () {
        Route::get('export', [PurchasingSupplierController::class, 'export'])->name('export');
        Route::post('import', [PurchasingSupplierController::class, 'import'])->name('import');
        Route::get('import/template', [PurchasingSupplierController::class, 'importTemplate'])->name('import.template');
    });
    Route::resource('suppliers', PurchasingSupplierController::class)->except(['destroy'])->middleware('can:logistic-master-manage');
    Route::delete('suppliers/{supplier}', [PurchasingSupplierController::class, 'destroy'])->middleware('can:admin-manager-only-delete')->name('suppliers.destroy');

    // Warehouses
    Route::prefix('warehouses')->name('warehouses.')->middleware('can:logistic-master-manage')->group(function () {
        Route::get('export', [LogisticWarehouseController::class, 'export'])->name('export');
        Route::post('import', [LogisticWarehouseController::class, 'import'])->name('import');
        Route::get('import/template', [LogisticWarehouseController::class, 'importTemplate'])->name('import.template');
    });
    Route::resource('warehouses', LogisticWarehouseController::class)->except(['destroy'])->middleware('can:logistic-master-manage');
    Route::delete('warehouses/{warehouse}', [LogisticWarehouseController::class, 'destroy'])->middleware('can:admin-manager-only-delete')->name('warehouses.destroy');

    // Stock History
    Route::prefix('stock-histories')->name('stock-histories.')->middleware('can:logistic-stockcard')->group(function () {
        Route::get('/', [LogisticStockHistoryController::class, 'index'])->name('index');
        Route::get('export', [LogisticStockHistoryController::class, 'export'])->name('export');
        Route::get('product-price', [LogisticStockHistoryController::class, 'productPrice'])->name('product-price');
        Route::post('in', [LogisticStockHistoryController::class, 'storeIn'])->name('in.store');
        Route::post('out', [LogisticStockHistoryController::class, 'storeOut'])->name('out.store');
        Route::post('import/in', [LogisticStockHistoryController::class, 'importIn'])->name('import.in');
        Route::post('import/out', [LogisticStockHistoryController::class, 'importOut'])->name('import.out');
        Route::get('import/in/template', [LogisticStockHistoryController::class, 'importInTemplate'])->name('import.in.template');
        Route::get('import/out/template', [LogisticStockHistoryController::class, 'importOutTemplate'])->name('import.out.template');
    });
    Route::get('/product-stocks', [LogisticProductStockController::class, 'index'])->middleware('can:logistic-stockcard')->name('product-stocks.index');
    Route::prefix('stock-documents')->name('stock-documents.')->middleware('can:logistic-stockcard')->group(function () {
        Route::get('/', [LogisticStockDocumentController::class, 'index'])->name('index');
        Route::get('/export', [LogisticStockDocumentController::class, 'export'])->name('export');
        Route::post('/import', [LogisticStockDocumentController::class, 'import'])->name('import');
        Route::get('/import/in/template', [LogisticStockDocumentController::class, 'importInTemplate'])->name('import.in.template');
        Route::get('/import/out/template', [LogisticStockDocumentController::class, 'importOutTemplate'])->name('import.out.template');
        Route::get('/create', [LogisticStockDocumentController::class, 'create'])->name('create');
        Route::post('/', [LogisticStockDocumentController::class, 'store'])->name('store');
        Route::get('/{stock_document}/show', [LogisticStockDocumentController::class, 'show'])->name('show');
        Route::get('/{stock_document}/edit', [LogisticStockDocumentController::class, 'edit'])->name('edit');
        Route::put('/{stock_document}', [LogisticStockDocumentController::class, 'update'])->name('update');
        Route::delete('/{stock_document}', [LogisticStockDocumentController::class, 'destroy'])->name('destroy');
        Route::get('/{stock_document}/print', [LogisticStockDocumentController::class, 'print'])->name('print');
        Route::post('/{stock_document}/advance', [LogisticStockDocumentController::class, 'advance'])->name('advance');
        Route::put('/{stock_document}/reject/ho', [LogisticStockDocumentController::class, 'rejectHo'])->name('reject.ho');
    });
    Route::prefix('stock-transfers')->name('stock-transfers.')->middleware('can:logistic-stockcard')->group(function () {
        Route::post('{stock_transfer}/advance', [LogisticStockTransferController::class, 'advance'])->name('advance');
        Route::get('{stock_transfer}/print', [LogisticStockTransferController::class, 'print'])->name('print');
    });
    Route::resource('stock-transfers', LogisticStockTransferController::class)->except(['destroy'])->middleware('can:logistic-stockcard');
    Route::delete('stock-transfers/{stock_transfer}', [LogisticStockTransferController::class, 'destroy'])->middleware('can:admin-manager-only-delete')->name('stock-transfers.destroy');
    // Goods Receipts
    Route::prefix('goods-receipts')->name('goods-receipts.')->middleware('can:po-operate')->group(function () {
        Route::get('/', [LogisticGoodsReceiptController::class, 'index'])->name('index');
        Route::get('{supplier_delivery_order}/create', [LogisticGoodsReceiptController::class, 'createBySdo'])->name('create');
        Route::get('{supplier_delivery_order}/print', [LogisticGoodsReceiptController::class, 'printBySdo'])->name('print');
        Route::get('{supplier_delivery_order}/show', [LogisticGoodsReceiptController::class, 'showBySdo'])->name('show');
        Route::post('returns/{purchase_return}', [LogisticGoodsReceiptController::class, 'storeReturn'])->name('returns.store');
    });

    // Stock Opnames
    Route::prefix('stock-opnames')->name('stock-opnames.')->group(function () {
        Route::get('/', [LogisticStockOpnameController::class, 'index'])->middleware('can:stock-opname-access')->name('index');
        Route::get('/create', [LogisticStockOpnameController::class, 'create'])->middleware('can:logistic-stockcard')->name('create');
        Route::post('/', [LogisticStockOpnameController::class, 'store'])->middleware('can:logistic-stockcard')->name('store');
        Route::get('/{stock_opname}/edit', [LogisticStockOpnameController::class, 'edit'])->middleware('can:logistic-stockcard')->name('edit');
        Route::put('/{stock_opname}', [LogisticStockOpnameController::class, 'update'])->middleware('can:logistic-stockcard')->name('update');
        Route::get('/{stock_opname}', [LogisticStockOpnameController::class, 'show'])->middleware('can:stock-opname-access')->name('show');
        Route::get('/{stock_opname}/assignments/{assignment}', [LogisticStockOpnameController::class, 'showAssignment'])->name('assignments.show');
        Route::post('/{stock_opname}/assignments/{assignment}/start', [LogisticStockOpnameController::class, 'startAssignment'])->name('assignments.start');
        Route::post('/{stock_opname}/assignments/{assignment}', [LogisticStockOpnameController::class, 'submitAssignment'])->name('assignments.submit');
        Route::post('/{stock_opname}/settle', [LogisticStockOpnameController::class, 'settle'])->middleware('can:logistic-master-manage')->name('settle');
    });

    // Purchase Orders
    Route::prefix('purchase-orders')->name('purchase-orders.')->group(function () {
        Route::post('{purchase_order}/advance', [PurchasingPurchaseOrderController::class, 'advance'])->middleware('can:po-ho-approve')->name('advance');
        Route::put('{purchase_order}/reject/ho', [PurchasingPurchaseOrderController::class, 'rejectHo'])->middleware('can:po-ho-approve')->name('reject.ho');
        Route::put('{purchase_order}/reject/supplier', [PurchasingPurchaseOrderController::class, 'rejectSupplier'])->middleware('can:po-manage')->name('reject.supplier');
        Route::get('{purchase_order}/print', [PurchasingPurchaseOrderController::class, 'print'])->middleware('can:po-operate')->name('print');
        Route::put('{purchase_order}/supplier-invoice', [PurchasingPurchaseOrderController::class, 'updateSupplierInvoice'])->middleware('can:po-manage')->name('supplier-invoice.update');
        Route::post('{purchase_order}/deliveries', [PurchasingPurchaseOrderController::class, 'storeDelivery'])->middleware('can:po-operate')->name('deliveries.store');
        Route::get('{purchase_order}/receivings/create', [LogisticGoodsReceiptController::class, 'create'])->middleware('can:po-operate')->name('receivings.create');
        Route::post('{purchase_order}/receivings', [LogisticGoodsReceiptController::class, 'store'])->middleware('can:po-operate')->name('receivings.store');
    });
    Route::resource('purchase-orders', PurchasingPurchaseOrderController::class)->except(['destroy'])->middleware('can:po-manage');
    Route::delete('purchase-orders/{purchase_order}', [PurchasingPurchaseOrderController::class, 'destroy'])->middleware('can:admin-manager-only-delete')->name('purchase-orders.destroy');
    Route::prefix('purchase-returns')->name('purchase-returns.')->middleware('can:po-operate')->group(function () {
        Route::get('{purchase_return}/print', [PurchasingPurchaseReturnController::class, 'print'])->name('print');
        Route::post('{purchase_return}/deliveries', [PurchasingPurchaseReturnController::class, 'storeDelivery'])->name('deliveries.store');
    });
    Route::resource('purchase-returns', PurchasingPurchaseReturnController::class)->except(['destroy'])->middleware('can:po-manage');
    Route::delete('purchase-returns/{purchase_return}', [PurchasingPurchaseReturnController::class, 'destroy'])->middleware('can:admin-manager-only-delete')->name('purchase-returns.destroy');

    // Customers
    Route::prefix('customers')->name('customers.')->middleware('can:sales-master-manage')->group(function () {
        Route::get('export', [SalesCustomerController::class, 'export'])->name('export');
        Route::post('import', [SalesCustomerController::class, 'import'])->name('import');
        Route::get('import/template', [SalesCustomerController::class, 'importTemplate'])->name('import.template');
    });
    Route::resource('customers', SalesCustomerController::class)->except(['destroy'])->middleware('can:sales-master-manage');
    Route::delete('customers/{customer}', [SalesCustomerController::class, 'destroy'])->middleware('can:admin-manager-only-delete')->name('customers.destroy');

    Route::get('/pos', [SalesPosController::class, 'index'])
        ->middleware('can:pos-operate')
        ->name('pos.index');
    Route::post('/pos', [SalesPosController::class, 'store'])
        ->middleware('can:pos-operate')
        ->name('pos.store');
    Route::get('/pos/{sales}/receipt', [SalesPosController::class, 'printReceipt'])
        ->middleware('can:pos-operate')
        ->name('pos.print.receipt');
    Route::get('/pos/{sales}/invoice', [SalesPosController::class, 'printInvoice'])
        ->middleware('can:pos-operate')
        ->name('pos.print.invoice');
    Route::get('/pos/{sales}/delivery', [SalesPosController::class, 'printDelivery'])
        ->middleware('can:pos-operate')
        ->name('pos.print.delivery');
    Route::post('/pos/open-shift', [CashierSessionController::class, 'open'])
        ->middleware('can:pos-operate')
        ->name('pos.shift.open');
    Route::post('/pos/close-shift', [CashierSessionController::class, 'close'])
        ->middleware('can:pos-operate')
        ->name('pos.shift.close');
    Route::post('/pos/customers', [SalesPosController::class, 'storeCustomer'])
        ->middleware('can:pos-operate')
        ->name('pos.customers.store');
    Route::get('/pos/products', [SalesPosProductController::class, 'index'])
        ->middleware('can:pos-operate')
        ->name('pos.products.index');
    Route::prefix('sales')->name('sales.')->middleware('can:pos-operate')->group(function () {
        Route::get('/', [SalesHistoryController::class, 'index'])->name('index');
        Route::get('/{sales}', [SalesHistoryController::class, 'show'])->name('show');
        Route::post('/{sales}/settle', [SalesHistoryController::class, 'settle'])->name('settle');
        Route::post('/{sales}/deliveries', [SalesHistoryController::class, 'createDelivery'])->name('deliveries.store');
        Route::post('/{sales}/returns', [SalesHistoryController::class, 'createReturn'])->name('returns.store');
    });
    Route::prefix('cashier-sessions')->name('cashier-sessions.')->middleware('can:pos-operate')->group(function () {
        Route::get('/', [CashHistoryController::class, 'index'])->name('index');
    });

    Route::resource('payment-methods', SalesPaymentMethodController::class)->except(['destroy'])->middleware('can:sales-master-manage');
    Route::delete('payment-methods/{payment_method}', [SalesPaymentMethodController::class, 'destroy'])->middleware('can:admin-manager-only-delete')->name('payment-methods.destroy');
    Route::resource('discounts', SalesDiscountController::class)->except(['destroy'])->middleware('can:sales-master-manage');
    Route::delete('discounts/{discount}', [SalesDiscountController::class, 'destroy'])->middleware('can:admin-manager-only-delete')->name('discounts.destroy');

    // Organization Structure
    Route::get('/organization-structure', [OrganizationStructureController::class, 'index'])
        ->name('organization-structure.index');

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

    /*
    |--------------------------------------------------------------------------
    | Employee Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('employees')->name('employees.')->middleware('can:hr-master-manage')->group(function () {
        Route::get('export', [EmployeeController::class, 'export'])
            ->name('export');

        Route::post('import', [EmployeeController::class, 'import'])
            ->name('import');

        Route::get('import/template', [EmployeeController::class, 'importTemplate'])
            ->name('import.template');
    });

    Route::resource('employees', EmployeeController::class)->except(['destroy'])->middleware('can:hr-master-manage');
    Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->middleware('can:admin-manager-only-delete')->name('employees.destroy');
    Route::resource('shifts', ShiftController::class)->except(['destroy'])->middleware('can:hr-master-manage');
    Route::delete('shifts/{shift}', [ShiftController::class, 'destroy'])->middleware('can:admin-manager-only-delete')->name('shifts.destroy');
    Route::resource('schedules', ScheduleController::class)->except(['destroy'])->middleware('can:hr-master-manage');
    Route::delete('schedules/{schedule}', [ScheduleController::class, 'destroy'])->middleware('can:admin-manager-only-delete')->name('schedules.destroy');
    Route::resource('schedule-rules', ScheduleRuleController::class)->except(['destroy'])->middleware('can:hr-master-manage');
    Route::delete('schedule-rules/{schedule_rule}', [ScheduleRuleController::class, 'destroy'])->middleware('can:admin-manager-only-delete')->name('schedule-rules.destroy');
    Route::get('/my-schedule', [SelfScheduleController::class, 'index'])
        ->middleware('can:attendance-access')
        ->name('my-schedule.index');

    /*
    |--------------------------------------------------------------------------
    | Leave Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('leaves')->name('leaves.')->middleware('can:attendance-access')->group(function () {
        Route::get('/', [SelfLeaveController::class, 'index'])->name('index');
        Route::get('/create', [SelfLeaveController::class, 'create'])->name('create');
        Route::post('/', [SelfLeaveController::class, 'store'])->name('store');
        Route::get('/{leave}/edit', [SelfLeaveController::class, 'edit'])->name('edit');
        Route::put('/{leave}', [SelfLeaveController::class, 'update'])->name('update');
        Route::delete('/{leave}', [SelfLeaveController::class, 'destroy'])->name('destroy');
    });
    Route::prefix('leaves')->name('leaves.')->middleware('can:hr-master-manage')->group(function () {
        Route::get('/manage', [LeaveController::class, 'index'])->name('manage.index');
        Route::put('/{leave}/approve', [LeaveController::class, 'approve'])->name('approve');
        Route::put('/{leave}/reject', [LeaveController::class, 'reject'])->name('reject');
    });

    /*
    |--------------------------------------------------------------------------
    | Attendance Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('absents')->name('absents.')->middleware('can:attendance-access')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])
            ->name('index');
        Route::get('/check-in', [AttendanceController::class, 'showCheckIn'])
            ->name('checkin.form');
        Route::post('/check-in', [AttendanceController::class, 'checkIn'])
            ->name('checkin');
        Route::get('/check-out', [AttendanceController::class, 'showCheckOut'])
            ->name('checkout.form');
        Route::post('/check-out', [AttendanceController::class, 'checkOut'])
            ->name('checkout');
    });

    /*
    |--------------------------------------------------------------------------
    | Report Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->name('reports.')->middleware('can:hr-report-access')->group(function () {
        Route::get('/absent', [ReportAttendanceController::class, 'index'])
            ->name('absents.index');
        Route::get('/absent/export', [ReportAttendanceController::class, 'export'])
            ->name('absents.export');
        Route::post('/attendance/{attendance}/mark-on-time', [ReportAttendanceController::class, 'markOnTime'])
            ->name('absents.mark_on_time');
    });
});
