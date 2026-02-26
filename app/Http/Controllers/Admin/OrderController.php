<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles admin CRUD operations for customer orders.
 */
class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request, \App\Services\OrderService $service): Response
    {
        $dto = \App\DTOs\Order\OrderFilterDTO::fromArray($request->all());

        $orders = $service->getFilteredOrdersForAdmin($dto, perPage: 15)->withQueryString();

        return Inertia::render('Domains/Admin/Order/Index', [
            'orders' => \App\Http\Resources\OrderResource::collection($orders),
            'filters' => $request->only(['search', 'date_range', 'start_date', 'end_date', 'status']),
        ]);
    }

    /**
     * Display the specified order detail.
     */
    public function show(\App\Models\Order $order): Response
    {
        $order->load([
            'items.product',
            'items.options.productOption',
            'items.options.productOptionItem',
            'customer',
            'dropPoint',
            'paymentMethod'
        ]);

        return Inertia::render('Domains/Admin/Order/Show', [
            'order' => new \App\Http\Resources\OrderResource($order),
        ]);
    }
}
