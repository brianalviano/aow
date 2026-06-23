<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\Midtrans;

use App\Http\Controllers\Controller;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for handling Midtrans notifications (Webhooks).
 */
class NotificationController extends Controller
{
    /**
     * @var MidtransService
     */
    protected $midtransService;

    /**
     * MidtransNotificationController constructor.
     */
    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    /**
     * Handle the incoming Midtrans notification.
     *
     * @return JsonResponse
     */
    public function handle(Request $request)
    {
        try {
            $this->midtransService->handleNotification();

            return response()->json(['message' => 'OK']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
