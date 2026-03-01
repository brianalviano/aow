<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chef;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for chef dashboard.
 */
class DashboardController extends Controller
{
    /**
     * Show the chef dashboard.
     *
     * @return \Inertia\Response
     */
    public function index(): Response
    {
        $chef = Auth::guard('chef')->user();

        $items = OrderItem::query()
            ->with(['order.customer', 'order.dropPoint', 'product'])
            ->where('chef_id', $chef->id)
            ->whereHas('order', function ($query) {
                $query->where('order_status', \App\Enums\OrderStatus::CONFIRMED);
            })
            ->where('chef_status', \App\Enums\ChefStatus::PENDING)
            ->latest()
            ->get();

        return Inertia::render('Domains/Chef/Dashboard/Index', [
            'items' => $items,
        ]);
    }
}
