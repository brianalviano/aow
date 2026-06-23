<?php

use App\Http\Controllers\API\Midtrans\NotificationController;
use App\Http\Controllers\Webhook\BiteshipController;
use Illuminate\Support\Facades\Route;

Route::prefix('midtrans')->group(function () {
    Route::post('/payment-notification', [NotificationController::class, 'handle']);
    Route::post('/recurring-notification', [NotificationController::class, 'handle']);
    Route::post('/pay-account-notification', [NotificationController::class, 'handle']);
});

Route::post('/webhook/biteship', [BiteshipController::class, 'handle']);
