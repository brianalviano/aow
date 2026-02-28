<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Traits\FileHelperTrait;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\Storage;
use Inertia\{Inertia, Response};
use Throwable;

/**
 * Handles admin CRUD operations and status transitions for customer orders.
 */
class OrderController extends Controller
{
    use FileHelperTrait;

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
            'status_counts' => [
                'all'       => Order::count(),
                'unpaid'    => Order::where('payment_status', 'pending')
                    ->whereDoesntHave('paymentMethod', fn($q) => $q->where('category', 'cash'))
                    ->count(),
                'process'   => Order::where(function ($q) {
                    $q->where('payment_status', '!=', 'pending')
                        ->orWhereHas('paymentMethod', fn($pq) => $pq->where('category', 'cash'));
                })->whereIn('order_status', ['pending', 'confirmed'])->count(),
                'shipped'   => Order::where('order_status', 'shipped')->count(),
                'completed' => Order::where('order_status', 'delivered')->count(),
                'cancelled' => Order::where(function ($q) {
                    $q->where('order_status', 'cancelled')
                        ->orWhere('payment_status', 'failed');
                })->count(),
            ],
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
            'customerAddress',
            'paymentMethod',
            'testimonial',
        ]);

        return Inertia::render('Domains/Admin/Order/Show', [
            'order' => (new \App\Http\Resources\OrderResource($order))->resolve(),
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
            'delivery_photo' => $this->getFileValidationRules(true),
        ]);

        try {
            $service->completeOrder($order, $request->file('delivery_photo'));

            Inertia::flash('toast', [
                'message' => 'Pesanan berhasil diselesaikan.',
                'type'    => 'success',
            ]);

            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menyelesaikan pesanan: ' . $e->getMessage(),
                'type'    => 'error',
            ]);

            return redirect()->back();
        }
    }

    /**
     * Approve a customer testimonial.
     */
    public function approveTestimonial(\App\Models\Testimonial $testimonial): RedirectResponse
    {
        try {
            $testimonial->update(['is_approved' => true]);

            Inertia::flash('toast', [
                'message' => 'Testimoni berhasil disetujui.',
                'type'    => 'success',
            ]);

            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menyetujui testimoni: ' . $e->getMessage(),
                'type'    => 'error',
            ]);

            return redirect()->back();
        }
    }

    /**
     * Reject/Delete a customer testimonial.
     */
    public function rejectTestimonial(\App\Models\Testimonial $testimonial): RedirectResponse
    {
        try {
            if ($testimonial->photo) {
                $this->deleteFile($testimonial->photo);
            }
            $testimonial->delete();

            Inertia::flash('toast', [
                'message' => 'Testimoni berhasil dihapus.',
                'type'    => 'success',
            ]);

            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus testimoni: ' . $e->getMessage(),
                'type'    => 'error',
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()->back();
        }
    }
}
