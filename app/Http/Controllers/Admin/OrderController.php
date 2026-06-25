<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\Order\OrderFilterDTO;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Chef;
use App\Models\CompanyProfile;
use App\Models\DropPoint;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PickUpPoint;
use App\Models\Testimonial;
use App\Services\OrderService;
use App\Traits\FileHelperTrait;
use App\DTOs\Setting\OrderSettingsDTO;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
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
        $dto = OrderFilterDTO::from($request);

        $orders = $service->getFilteredOrdersForAdmin($dto, perPage: 15)->withQueryString();

        return Inertia::render('Domains/Admin/Order/Index', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only(['search', 'date_range', 'start_date', 'end_date', 'status', 'drop_point_id', 'chef_id', 'delivery_date']),
            'status_counts' => [
                'all' => Order::count(),
                'unpaid' => Order::where('payment_status', 'pending')
                    ->where('order_status', '!=', 'cancelled')
                    ->whereDoesntHave('paymentMethod', fn ($q) => $q->where('category', 'cash'))
                    ->count(),
                'process' => Order::where(function ($q) {
                    $q->where('payment_status', '!=', 'pending')
                        ->orWhereHas('paymentMethod', fn ($pq) => $pq->where('category', 'cash'));
                })->whereIn('order_status', ['pending', 'confirmed'])->count(),
                'shipped' => Order::whereIn('order_status', ['shipped', 'at_pickup_point', 'on_delivery', 'arrived'])->count(),
                'completed' => Order::where('order_status', 'delivered')->count(),
                'cancelled' => Order::where(function ($q) {
                    $q->where('order_status', 'cancelled')
                        ->orWhere('payment_status', 'failed');
                })->count(),
            ],
            'dropPoints' => DropPoint::where('is_active', true)->get(['id', 'name']),
            'chefs' => Chef::where('is_active', true)->get(['id', 'name']),
            'pickUpPoints' => PickUpPoint::where('is_active', true)->get(['id', 'name', 'address', 'latitude', 'longitude']),
        ]);
    }

    /**
     * Display a summary of orders per menu per drop point on a given delivery date.
     */
    public function resume(Request $request): Response
    {
        $deliveryDate = $request->query('delivery_date', now()->toDateString());

        // Query orders on delivery_date
        $orders = Order::query()
            ->with([
                'items.product',
                'items.options.productOption',
                'items.options.productOptionItem',
                'dropPoint',
            ])
            ->where('delivery_date', $deliveryDate)
            ->where('order_status', '!=', OrderStatus::CANCELLED->value)
            ->where(function ($q) {
                $q->where('payment_status', PaymentStatus::PAID->value)
                    ->orWhereHas('paymentMethod', fn ($pq) => $pq->where('category', 'cash'));
            })
            ->get();

        $resumeData = [];

        foreach ($orders as $order) {
            $dropPointName = $order->dropPoint ? $order->dropPoint->name : 'Alamat Kustom / Lainnya';
            $dropPointId = $order->dropPoint ? $order->dropPoint->id : 'custom';

            if (! isset($resumeData[$dropPointId])) {
                $resumeData[$dropPointId] = [
                    'drop_point_name' => $dropPointName,
                    'items' => [],
                ];
            }

            foreach ($order->items as $item) {
                // Format options label
                $optionsParts = [];
                foreach ($item->options as $opt) {
                    if ($opt->productOption && $opt->productOptionItem) {
                        $optionsParts[] = $opt->productOption->name.': '.$opt->productOptionItem->name;
                    }
                }
                sort($optionsParts);
                $optionsLabel = implode(', ', $optionsParts);
                $productId = $item->product_id;
                $productName = $item->product ? $item->product->name : 'Produk Tidak Dikenal';

                // Group by product_id + options_label
                $groupKey = $productId.'_'.md5($optionsLabel);

                if (! isset($resumeData[$dropPointId]['items'][$groupKey])) {
                    $resumeData[$dropPointId]['items'][$groupKey] = [
                        'product_name' => $productName,
                        'options_label' => $optionsLabel ?: null,
                        'quantity' => 0,
                    ];
                }

                $resumeData[$dropPointId]['items'][$groupKey]['quantity'] += $item->quantity;
            }
        }

        // Convert the inner items grouping back to simple sequential array
        foreach ($resumeData as $dropPointId => $data) {
            $resumeData[$dropPointId]['items'] = array_values($data['items']);
        }

        $resumeData = array_values($resumeData);

        return Inertia::render('Domains/Admin/Order/Resume', [
            'resumeData' => $resumeData,
            'filters' => [
                'delivery_date' => $deliveryDate,
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
            'order' => (new OrderResource($order))->resolve(),
            'chefs' => Chef::where('is_active', true)->get(['id', 'name']),
            'pickUpPoints' => PickUpPoint::where('is_active', true)->get(['id', 'name', 'address', 'latitude', 'longitude']),
            'canChangePickUpPoint' => $order->canChangePickUpPoint(),
            'free_courier_min_order' => OrderSettingsDTO::load()->freeCourierMinOrder,
        ]);
    }

    /**
     * Print the specified order receipt.
     */
    public function printReceipt(Order $order)
    {
        $order->load([
            'items.product',
            'items.options.productOption',
            'items.options.productOptionItem',
            'customer',
            'dropPoint',
            'customerAddress',
            'paymentMethod',
        ]);

        return view('admin.orders.print', [
            'order' => $order,
            'settings' => CompanyProfile::first(),
        ]);
    }

    /**
     * Cancel a pending order (admin action).
     *
     * @throws Throwable
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
                'type' => 'success',
            ]);

            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membatalkan pesanan: '.$e->getMessage(),
                'type' => 'error',
            ]);

            return redirect()->back();
        }
    }

    /**
     * Resend order notifications (admin action).
     *
     * @throws Throwable
     */
    public function resendNotifications(Order $order, string $target, OrderService $service): RedirectResponse
    {
        try {
            $service->resendOrderNotifications($order, $target);

            Inertia::flash('toast', [
                'message' => 'Notifikasi berhasil dikirim ulang.',
                'type' => 'success',
            ]);

            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal mengirim ulang notifikasi: '.$e->getMessage(),
                'type' => 'error',
            ]);

            return redirect()->back();
        }
    }

    /**
     * Confirm a pending order (admin action).
     *
     * @throws Throwable
     */
    public function confirm(Request $request, Order $order, OrderService $service): RedirectResponse
    {
        $request->validate([
            'pick_up_point_id' => ['required', 'exists:pick_up_points,id'],
        ]);

        try {
            $service->confirmOrder($order, $request->input('pick_up_point_id'));

            Inertia::flash('toast', [
                'message' => 'Pesanan berhasil dikonfirmasi.',
                'type' => 'success',
            ]);

            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal mengkonfirmasi pesanan: '.$e->getMessage(),
                'type' => 'error',
            ]);

            return redirect()->back();
        }
    }

    /**
     * Mark an order as delivered/completed (admin action).
     *
     * @throws Throwable
     */
    public function deliver(Request $request, Order $order, OrderService $service): RedirectResponse
    {
        $request->validate([
            'delivery_photo' => ['required', 'image', 'max:5120'], // Max 5MB
        ]);

        try {
            $service->completeOrder($order, $request->file('delivery_photo'));

            Inertia::flash('toast', [
                'message' => 'Pesanan berhasil diselesaikan.',
                'type' => 'success',
            ]);

            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menyelesaikan pesanan: '.$e->getMessage(),
                'type' => 'error',
            ]);

            return redirect()->back();
        }
    }

    /**
     * Approve a customer testimonial.
     */
    public function approveTestimonial(Testimonial $testimonial): RedirectResponse
    {
        try {
            $testimonial->update(['is_approved' => true]);

            Inertia::flash('toast', [
                'message' => 'Testimoni berhasil disetujui.',
                'type' => 'success',
            ]);

            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menyetujui testimoni: '.$e->getMessage(),
                'type' => 'error',
            ]);

            return redirect()->back();
        }
    }

    /**
     * Reject/Delete a customer testimonial.
     */
    public function rejectTestimonial(Testimonial $testimonial): RedirectResponse
    {
        try {
            if ($testimonial->photo) {
                $this->deleteFile($testimonial->photo);
            }
            $testimonial->delete();

            Inertia::flash('toast', [
                'message' => 'Testimoni berhasil dihapus.',
                'type' => 'success',
            ]);

            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus testimoni: '.$e->getMessage(),
                'type' => 'error',
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back();
        }
    }

    /**
     * Reassign an order item to a new chef.
     */
    public function reassignItemChef(OrderItem $order_item, OrderService $service): RedirectResponse
    {
        $data = request()->validate([
            'chef_id' => 'required|exists:chefs,id',
        ]);

        try {
            $service->reassignChef($order_item, $data['chef_id']);

            Inertia::flash('toast', [
                'message' => 'Chef berhasil diperbarui.',
                'type' => 'success',
            ]);

            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui chef: '.$e->getMessage(),
                'type' => 'error',
            ]);

            return redirect()->back();
        }
    }

    /**
     * Display orders awaiting payment approval.
     */
    public function payments(OrderService $service): Response
    {
        $orders = $service->getPaymentApprovalOrders(perPage: 15)->withQueryString();

        return Inertia::render('Domains/Admin/Order/Payments', [
            'orders' => OrderResource::collection($orders),
        ]);
    }

    /**
     * Display orders currently being processed or shipped.
     */
    public function processing(Request $request, OrderService $service): Response
    {
        $dto = OrderFilterDTO::from($request);
        $view = $request->query('view', 'list');
        $perPage = ($view === 'list') ? 15 : 200; // Larger limit for grouped views to allow complete overview

        $orders = $service->getProcessingOrders($dto, perPage: $perPage)->withQueryString();

        return Inertia::render('Domains/Admin/Order/Processing', [
            'orders' => OrderResource::collection($orders),
            'filters' => array_merge(
                $request->only(['drop_point_id', 'chef_id', 'delivery_date']),
                ['view' => $view]
            ),
            'dropPoints' => DropPoint::where('is_active', true)->get(['id', 'name']),
            'chefs' => Chef::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    /**
     * Update the pickup point for an order (admin action).
     *
     * Only allowed when not all chef items have been shipped yet.
     */
    public function updatePickUpPoint(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'pick_up_point_id' => ['required', 'exists:pick_up_points,id'],
        ]);

        try {
            $order->load('items');

            if (! $order->canChangePickUpPoint()) {
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
                'message' => 'Gagal memperbarui pickup point: '.$e->getMessage(),
                'type' => 'error',
            ]);

            return redirect()->back();
        }
    }
}
