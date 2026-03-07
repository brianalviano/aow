<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chef;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

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
        $dto = \App\DTOs\Order\OrderFilterDTO::from($request->all());

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

    /**
     * Approve selected order items.
     */
    public function approve(Request $request, \App\Services\OrderService $orderService): RedirectResponse
    {
        $request->validate([
            'item_ids' => ['required', 'array'],
            'item_ids.*' => ['required', 'exists:order_items,id'],
        ]);

        try {
            $orderService->chefApproveItems($request->input('item_ids'), Auth::guard('chef')->user());

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Item berhasil diterima.',
            ]);

            return redirect()->back();
        } catch (\Throwable $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Gagal menerima item: ' . $e->getMessage(),
            ]);

            return redirect()->back();
        }
    }

    /**
     * Reject selected order items (cancels the entire order).
     */
    public function reject(Request $request, \App\Services\OrderService $orderService): RedirectResponse
    {
        $request->validate([
            'item_ids' => ['required', 'array'],
            'item_ids.*' => ['required', 'exists:order_items,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $orderService->chefRejectItems(
                $request->input('item_ids'),
                Auth::guard('chef')->user(),
                $request->input('reason')
            );

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Item ditolak dan pesanan telah dibatalkan.',
            ]);

            return redirect()->back();
        } catch (\Throwable $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Gagal menolak item: ' . $e->getMessage(),
            ]);

            return redirect()->back();
        }
    }

    /**
     * Mark selected order items as shipped.
     */
    public function ship(Request $request, \App\Services\OrderService $orderService): RedirectResponse
    {
        $request->validate([
            'item_ids' => ['required', 'array'],
            'item_ids.*' => ['required', 'exists:order_items,id'],
        ]);

        try {
            $orderService->chefShipItems($request->input('item_ids'), Auth::guard('chef')->user());

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Item berhasil ditandai sebagai dikirim.',
            ]);

            return redirect()->back();
        } catch (\Throwable $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Gagal menandai item sebagai dikirim: ' . $e->getMessage(),
            ]);

            return redirect()->back();
        }
    }
}
