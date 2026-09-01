<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\Order\OrderFilterDTO;
use App\DTOs\Setting\OrderSettingsDTO;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\CompanyProfile;
use App\Models\DropPoint;
use App\Models\Order;
use App\Models\Testimonial;
use App\Services\OrderService;
use App\Traits\FileHelperTrait;
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
            'filters' => $request->only(['search', 'date_range', 'start_date', 'end_date', 'status', 'drop_point_id', 'delivery_date']),
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
                'shipped' => Order::whereIn('order_status', ['on_delivery', 'arrived'])->count(),
                'completed' => Order::where('order_status', 'delivered')->count(),
                'cancelled' => Order::where(function ($q) {
                    $q->where('order_status', 'cancelled')
                        ->orWhere('payment_status', 'failed');
                })->count(),
            ],
            'dropPoints' => DropPoint::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    /**
     * Display a summary of orders per menu per drop point on a given delivery date.
     */
    public function resume(Request $request): Response
    {
        $todayStr = now()->toDateString();

        if ($request->filled('delivery_date')) {
            $deliveryDate = (string) $request->query('delivery_date');
        } else {
            $hasToday = Order::where('delivery_date', $todayStr)
                ->where('order_status', '!=', OrderStatus::CANCELLED->value)
                ->exists();

            if ($hasToday) {
                $deliveryDate = $todayStr;
            } else {
                $nextDate = Order::where('delivery_date', '>=', $todayStr)
                    ->where('order_status', '!=', OrderStatus::CANCELLED->value)
                    ->orderBy('delivery_date', 'asc')
                    ->value('delivery_date');

                if ($nextDate) {
                    $deliveryDate = is_string($nextDate) ? $nextDate : $nextDate->format('Y-m-d');
                } else {
                    $latestDate = Order::where('order_status', '!=', OrderStatus::CANCELLED->value)
                        ->orderBy('delivery_date', 'desc')
                        ->value('delivery_date');
                    $deliveryDate = $latestDate ? (is_string($latestDate) ? $latestDate : $latestDate->format('Y-m-d')) : $todayStr;
                }
            }
        }

        $paymentFilter = $request->query('payment_filter', 'all');

        $ordersQuery = Order::query()
            ->with([
                'items.product',
                'items.options.productOption',
                'items.options.productOptionItem',
                'dropPoint',
                'paymentMethod',
            ])
            ->where('delivery_date', $deliveryDate)
            ->where('order_status', '!=', OrderStatus::CANCELLED->value);

        if ($paymentFilter === 'paid_only') {
            $ordersQuery->where(function ($q) {
                $q->where('payment_status', PaymentStatus::PAID->value)
                    ->orWhereHas('paymentMethod', fn ($pq) => $pq->where('category', 'cash'));
            });
        }

        $orders = $ordersQuery->get();

        $resumeData = [];

        foreach ($orders as $order) {
            $dropPointName = $order->dropPoint ? $order->dropPoint->name : 'Alamat Kustom / Lainnya';
            $dropPointId = $order->dropPoint ? $order->dropPoint->id : 'custom';
            $isPaid = $order->payment_status === PaymentStatus::PAID || $order->payment_status === 'paid' || ($order->paymentMethod && $order->paymentMethod->category === 'cash');

            if (! isset($resumeData[$dropPointId])) {
                $resumeData[$dropPointId] = [
                    'drop_point_name' => $dropPointName,
                    'items' => [],
                ];
            }

            foreach ($order->items as $item) {
                $optionsParts = [];
                $sizeOption = null;
                $spicyOption = null;
                $otherOptions = [];

                foreach ($item->options as $opt) {
                    if ($opt->productOption && $opt->productOptionItem) {
                        $optName = $opt->productOption->name;
                        $optItem = $opt->productOptionItem->name;
                        $optionsParts[] = $optName.': '.$optItem;

                        if (stripos($optName, 'ukuran') !== false || stripos($optName, 'size') !== false) {
                            $sizeOption = $optItem;
                        } elseif (stripos($optName, 'pedas') !== false || stripos($optName, 'level') !== false || stripos($optName, 'spicy') !== false) {
                            $spicyOption = $optItem;
                        } else {
                            $otherOptions[] = $optName.': '.$optItem;
                        }
                    }
                }
                sort($optionsParts);
                $optionsLabel = implode(', ', $optionsParts);
                $productId = $item->product_id;
                $productName = $item->product ? $item->product->name : 'Produk Tidak Dikenal';

                $groupKey = $productId.'_'.md5($optionsLabel);

                if (! isset($resumeData[$dropPointId]['items'][$groupKey])) {
                    $resumeData[$dropPointId]['items'][$groupKey] = [
                        'product_name' => $productName,
                        'options_label' => $optionsLabel ?: null,
                        'size_option' => $sizeOption,
                        'spicy_option' => $spicyOption,
                        'other_options' => $otherOptions,
                        'quantity' => 0,
                        'paid_quantity' => 0,
                        'unpaid_quantity' => 0,
                    ];
                }

                $resumeData[$dropPointId]['items'][$groupKey]['quantity'] += $item->quantity;
                if ($isPaid) {
                    $resumeData[$dropPointId]['items'][$groupKey]['paid_quantity'] += $item->quantity;
                } else {
                    $resumeData[$dropPointId]['items'][$groupKey]['unpaid_quantity'] += $item->quantity;
                }
            }
        }

        foreach ($resumeData as $dropPointId => $data) {
            $resumeData[$dropPointId]['items'] = array_values($data['items']);
        }

        $resumeData = array_values($resumeData);

        $activeDates = Order::query()
            ->where('order_status', '!=', OrderStatus::CANCELLED->value)
            ->whereNotNull('delivery_date')
            ->selectRaw('delivery_date, count(*) as total_orders')
            ->groupBy('delivery_date')
            ->orderBy('delivery_date', 'asc')
            ->get()
            ->map(fn ($row) => [
                'date' => is_string($row->delivery_date) ? $row->delivery_date : $row->delivery_date->format('Y-m-d'),
                'total_orders' => (int) $row->total_orders,
            ]);

        return Inertia::render('Domains/Admin/Order/Resume', [
            'resumeData' => $resumeData,
            'filters' => [
                'delivery_date' => $deliveryDate,
                'payment_filter' => $paymentFilter,
            ],
            'activeDates' => $activeDates,
        ]);
    }

    /**
     * Display the specified order detail.
     */
    public function show(Order $order): Response
    {
        $order->load([
            'items.product',
            'items.testimonial',
            'items.options.productOption',
            'items.options.productOptionItem',
            'customer',
            'dropPoint',
            'customerAddress',
            'paymentMethod',
            'shippings',
        ]);

        return Inertia::render('Domains/Admin/Order/Show', [
            'order' => (new OrderResource($order))->resolve(),
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
    public function confirm(Order $order, OrderService $service): RedirectResponse
    {
        try {
            $service->confirmOrder($order);

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
     * Mark an order as cooking / in preparation (admin action).
     *
     * @throws Throwable
     */
    public function cook(Order $order, OrderService $service): RedirectResponse
    {
        try {
            $service->cookOrder($order);

            Inertia::flash('toast', [
                'message' => 'Status pesanan berhasil diubah menjadi Sedang Dimasak.',
                'type' => 'success',
            ]);

            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memproses masak pesanan: '.$e->getMessage(),
                'type' => 'error',
            ]);

            return redirect()->back();
        }
    }

    /**
     * Mark an order as shipped / on delivery (admin action).
     *
     * @throws Throwable
     */
    public function ship(Order $order, OrderService $service): RedirectResponse
    {
        try {
            $service->shipOrder($order);

            Inertia::flash('toast', [
                'message' => 'Pesanan berhasil dikirim.',
                'type' => 'success',
            ]);

            return redirect()->back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal mengirim pesanan: '.$e->getMessage(),
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
            'delivery_photo' => ['nullable', 'image', 'max:5120'], // Max 5MB, opsional
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
        $perPage = ($view === 'list') ? 15 : 200;

        $orders = $service->getProcessingOrders($dto, perPage: $perPage)->withQueryString();

        return Inertia::render('Domains/Admin/Order/Processing', [
            'orders' => OrderResource::collection($orders),
            'filters' => array_merge(
                $request->only(['drop_point_id', 'delivery_date']),
                ['view' => $view]
            ),
            'dropPoints' => DropPoint::where('is_active', true)->get(['id', 'name']),
        ]);
    }
}
