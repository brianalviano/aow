<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chef;

use App\Enums\ChefStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for chef dashboard.
 */
class DashboardController extends Controller
{
    /**
     * Show the chef dashboard.
     */
    public function index(): Response
    {
        $chef = Auth::guard('chef')->user();

        $items = OrderItem::query()
            ->with(['order.customer', 'order.dropPoint', 'order.pickUpPoint', 'order.items', 'product'])
            ->where('chef_id', $chef->id)
            ->whereHas('order', function ($query) {
                $query->where('order_status', OrderStatus::CONFIRMED);
            })
            ->whereIn('chef_status', [
                ChefStatus::PENDING,
                ChefStatus::ACCEPTED,
            ])
            ->latest()
            ->get();

        return Inertia::render('Domains/Chef/Dashboard/Index', [
            'items' => $items,
        ]);
    }
}
