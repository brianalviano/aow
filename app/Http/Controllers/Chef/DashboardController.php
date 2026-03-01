<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chef;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

use App\Models\OrderItem;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    /**
     * Approve selected order items.
     */
    public function approve(Request $request, OrderService $orderService): RedirectResponse
    {
        $request->validate([
            'item_ids' => ['required', 'array'],
            'item_ids.*' => ['required', 'exists:order_items,id'],
        ]);

        try {
            $orderService->chefApproveItems($request->input('item_ids'), Auth::guard('chef')->user());

            return back()->with('toast', [
                'type' => 'success',
                'message' => 'Item berhasil diterima.',
            ]);
        } catch (\Throwable $e) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'Gagal menerima item: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Reject selected order items (cancels the entire order).
     */
    public function reject(Request $request, OrderService $orderService): RedirectResponse
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

            return back()->with('toast', [
                'type' => 'success',
                'message' => 'Item ditolak dan pesanan telah dibatalkan.',
            ]);
        } catch (\Throwable $e) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'Gagal menolak item: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Mark selected order items as shipped.
     */
    public function ship(Request $request, OrderService $orderService): RedirectResponse
    {
        $request->validate([
            'item_ids' => ['required', 'array'],
            'item_ids.*' => ['required', 'exists:order_items,id'],
        ]);

        try {
            $orderService->chefShipItems($request->input('item_ids'), Auth::guard('chef')->user());

            return back()->with('toast', [
                'type' => 'success',
                'message' => 'Item berhasil ditandai sebagai dikirim.',
            ]);
        } catch (\Throwable $e) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'Gagal menandai item sebagai dikirim: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Mark selected order items as delivered.
     */
    public function deliver(Request $request, OrderService $orderService): RedirectResponse
    {
        $request->validate([
            'item_ids' => ['required', 'array'],
            'item_ids.*' => ['required', 'exists:order_items,id'],
            'delivery_photo' => ['nullable', 'image', 'max:5120'], // max 5mb
        ]);

        try {
            $orderService->chefDeliverItems(
                $request->input('item_ids'),
                Auth::guard('chef')->user(),
                $request->file('delivery_photo')
            );

            return back()->with('toast', [
                'type' => 'success',
                'message' => 'Item berhasil ditandai sebagai diterima/selesai.',
            ]);
        } catch (\Throwable $e) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'Gagal menandai item sebagai selesai: ' . $e->getMessage(),
            ]);
        }
    }
}
