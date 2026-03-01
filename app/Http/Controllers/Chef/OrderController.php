<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chef;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for managing chef orders.
 */
class OrderController extends Controller
{
    /**
     * Display a listing of orders assigned to the chef.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\OrderService $service
     * @return \Inertia\Response
     */
    public function index(Request $request, \App\Services\OrderService $service): Response
    {
        $chef = Auth::guard('chef')->user();
        $dto = \App\DTOs\Order\OrderFilterDTO::fromArray($request->all());

        $items = $service->getFilteredOrderItemsForChef(
            $chef->id,
            $dto,
            perPage: 15
        )->withQueryString();

        return Inertia::render('Domains/Chef/Orders/Index', [
            'items'   => $items,
            'filters' => $request->only(['search', 'date_range', 'start_date', 'end_date', 'status']),
        ]);
    }
}
