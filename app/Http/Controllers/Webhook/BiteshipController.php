<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller for handling Biteship Webhooks.
 */
class BiteshipController extends Controller
{
    /**
     * Handle incoming webhook from Biteship.
     * 
     * @param Request $request
     * @param OrderService $orderService
     * @return JsonResponse
     */
    public function handle(Request $request, OrderService $orderService): JsonResponse
    {
        $payload = $request->all();
        $event = $request->input('event');
        $biteshipOrderId = $request->input('order_id');
        $status = $request->input('status');

        if (!$biteshipOrderId) {
            return response()->json(['message' => 'Missing order_id'], 400);
        }

        try {
            $orderService->updateStatusFromBiteship($biteshipOrderId, $status, $payload);

            return response()->json(['message' => 'Webhook processed successfully']);
        } catch (\Throwable $e) {
            Log::error('Biteship Webhook Processing Failed', [
                'error' => $e->getMessage(),
                'biteship_order_id' => $biteshipOrderId,
            ]);

            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }
}
