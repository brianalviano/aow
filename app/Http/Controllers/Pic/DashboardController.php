<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pic;

use App\Http\Controllers\Controller;
use App\Services\PicOrderService;
use Illuminate\Support\Facades\Auth;
use Inertia\{Inertia, Response};

/**
 * Dashboard controller for PIC (Pickup Point Officer).
 *
 * Displays tabbed view of incoming, at-pickup, and on-delivery orders.
 */
class DashboardController extends Controller
{
    /**
     * Display PIC dashboard with orders across all statuses.
     *
     * @param PicOrderService $service
     * @return Response
     */
    public function index(PicOrderService $service): Response
    {
        $officer = Auth::guard('pickup_officer')->user();
        $pickUpPointId = $officer->pick_up_point_id;

        return Inertia::render('Domains/Pic/Dashboard/Index', [
            'officer' => $officer->only('id', 'name', 'email'),
            'pickUpPoint' => $officer->pickUpPoint?->only('id', 'name', 'address'),
            'incomingOrders' => $service->getIncomingOrders($pickUpPointId),
            'atPickupOrders' => $service->getAtPickupOrders($pickUpPointId),
            'onDeliveryOrders' => $service->getOnDeliveryOrders($pickUpPointId),
            'completedOrders' => $service->getCompletedOrders($pickUpPointId),
        ]);
    }
}
