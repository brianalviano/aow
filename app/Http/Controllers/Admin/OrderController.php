<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

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

            Inertia::flash('toast', [
                'message' => 'Pesanan berhasil dibatalkan.',
                'type'    => 'success',
            ]);

            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membatalkan pesanan: ' . $e->getMessage(),
                'type'    => 'error',
            ]);

            return redirect()->back();
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

            Inertia::flash('toast', [
                'message' => 'Pesanan berhasil dikonfirmasi.',
                'type'    => 'success',
            ]);

            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal mengkonfirmasi pesanan: ' . $e->getMessage(),
                'type'    => 'error',
            ]);

            return redirect()->back();
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

            Inertia::flash('toast', [
                'message' => 'Status pesanan berhasil diubah ke Dikirim.',
                'type'    => 'success',
            ]);

            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui status pengiriman: ' . $e->getMessage(),
                'type'    => 'error',
            ]);

            return redirect()->back();
        }
    }

    /**
     * Mark a shipped order as delivered with photo proof (admin action).
     *
     * @throws \Throwable
     */
    public function deliver(Request $request, Order $order, OrderService $service): RedirectResponse
    {
        $request->validate([
            'delivery_photo' => ['required', 'image', 'max:5120'],
        ], [
            'delivery_photo.required' => 'Foto bukti penerimaan wajib diunggah.',
            'delivery_photo.image'    => 'File harus berupa gambar (jpg, png, webp, dll).',
            'delivery_photo.max'      => 'Ukuran foto maksimal 5 MB.',
        ]);

        $path = $request->file('delivery_photo')->store('orders/delivery', 'public');

        try {
            $service->completeOrder($order, $path);

            Inertia::flash('toast', [
                'message' => 'Pesanan berhasil diselesaikan.',
                'type'    => 'success',
            ]);

            return redirect()->back();
        } catch (Throwable $e) {
            // Clean up the uploaded photo if order completion fails
            Storage::disk('public')->delete($path);

            Inertia::flash('toast', [
                'message' => 'Gagal menyelesaikan pesanan: ' . $e->getMessage(),
                'type'    => 'error',
            ]);

            return redirect()->back();
        }
    }
}
