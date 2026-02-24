<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Midtrans\NotificationController;

Route::prefix('midtrans')->group(function () {
    Route::post('/payment-notification', [NotificationController::class, 'handle']);
    Route::post('/recurring-notification', [NotificationController::class, 'handle']);
    Route::post('/pay-account-notification', [NotificationController::class, 'handle']);
});
