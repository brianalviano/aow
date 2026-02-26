<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles admin CRUD operations and status transitions for customer orders.
 */
class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request, OrderService $service): Response
    {
        $dto = \App\DTOs\Order\OrderFilterDTO::fromArray($request->all());

        $orders = $service->getFilteredOrdersForAdmin($dto, perPage: 15)->withQueryString();

        return Inertia::render('Domains/Admin/Order/Index', [
            'orders'  => \App\Http\Resources\OrderResource::collection($orders),
            'filters' => $request->only(['search', 'date_range', 'start_date', 'end_date', 'status']),
        ]);
    }

    /**
     * Display the specified order detail.
     */
    public function show(Order $order): Response
    {
        $order->load([
            'items.product',
            'items.options.productOption',
            'items.options.productOptionItem',
            'customer',
            'dropPoint',
            'paymentMethod',
        ]);

        return Inertia::render('Domains/Admin/Order/Show', [
            'order' => new \App\Http\Resources\OrderResource($order),
        ]);
    }

    /**
     * Cancel a pending order (admin action).
     *
     * @throws \Throwable
     */
    public function cancel(Request $request, Order $order, OrderService $service): RedirectResponse
    {
        $request->validate([
            'cancellation_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $service->cancelOrder($order, $request->input('cancellation_note'));

            return redirect()->back()->with('success', 'Pesanan berhasil dibatalkan.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal membatalkan pesanan: ' . $e->getMessage());
        }
    }

    /**
     * Confirm a pending order (admin action).
     *
     * @throws \Throwable
     */
    public function confirm(Order $order, OrderService $service): RedirectResponse
    {
        try {
            $service->confirmOrder($order);

            return redirect()->back()->with('success', 'Pesanan berhasil dikonfirmasi.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal mengkonfirmasi pesanan: ' . $e->getMessage());
        }
    }

    /**
     * Mark a confirmed order as shipped (admin action).
     *
     * @throws \Throwable
     */
    public function ship(Order $order, OrderService $service): RedirectResponse
    {
        try {
            $service->shipOrder($order);

            return redirect()->back()->with('success', 'Status pesanan berhasil diubah ke Dikirim.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui status pengiriman: ' . $e->getMessage());
        }
    }

    /**
     * Mark a shipped order as delivered (admin action).
     *
     * @throws \Throwable
     */
    public function deliver(Order $order, OrderService $service): RedirectResponse
    {
        try {
            $service->completeOrder($order);

            return redirect()->back()->with('success', 'Pesanan berhasil diselesaikan.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menyelesaikan pesanan: ' . $e->getMessage());
        }
    }
}
