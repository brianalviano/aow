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
        $dto = \App\DTOs\Order\OrderFilterDTO::from($request->all());

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
            'items.chef',
            'items.testimonial',
            'items.options.productOption',
            'items.options.productOptionItem',
            'customer',
            'dropPoint',
            'pickUpPoint',
            'customerAddress',
            'paymentMethod',
            'shippings.chef',
        ]);

        return Inertia::render('Domains/Admin/Order/Show', [
            'order' => (new \App\Http\Resources\OrderResource($order))->resolve(),
            'chefs' => \App\Models\Chef::where('is_active', true)->get(['id', 'name']),
            'pickUpPoints' => \App\Models\PickUpPoint::where('is_active', true)->get(['id', 'name', 'address']),
            'canChangePickUpPoint' => $order->canChangePickUpPoint(),
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

    /**
     * Reassign an order item to a new chef.
     */
    public function reassignItemChef(\App\Models\OrderItem $order_item, \App\Services\OrderService $service): \Illuminate\Http\RedirectResponse
    {
        $data = request()->validate([
            'chef_id' => 'required|exists:chefs,id',
        ]);

        try {
            $service->reassignChef($order_item, $data['chef_id']);

            \Inertia\Inertia::flash('toast', [
                'message' => 'Chef berhasil diperbarui.',
                'type'    => 'success',
            ]);

            return redirect()->back();
        } catch (\Throwable $e) {
            \Inertia\Inertia::flash('toast', [
                'message' => 'Gagal memperbarui chef: ' . $e->getMessage(),
                'type'    => 'error',
            ]);

            return redirect()->back();
        }
    }

    /**
     * Update the pickup point for an order (admin action).
     *
     * Only allowed when not all chef items have been shipped yet.
     *
     * @param Request $request
     * @param Order $order
     * @return RedirectResponse
     */
    public function updatePickUpPoint(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'pick_up_point_id' => ['required', 'exists:pick_up_points,id'],
        ]);

        try {
            $order->load('items');

            if (!$order->canChangePickUpPoint()) {
                Inertia::flash('toast', [
                    'message' => 'Tidak dapat mengubah pickup point karena semua item sudah dikirim.',
                    'type' => 'error',
                ]);
                return redirect()->back();
            }

            $order->update([
                'pick_up_point_id' => $request->input('pick_up_point_id'),
            ]);

            Inertia::flash('toast', [
                'message' => 'Pickup point berhasil diperbarui.',
                'type' => 'success',
            ]);

            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui pickup point: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return redirect()->back();
        }
    }
}
