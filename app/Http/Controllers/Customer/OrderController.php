<?php

namespace App\Http\Controllers\Customer;

use App\DTOs\Customer\TestimonialData;
use App\DTOs\Order\OrderFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Testimonial;
use App\Services\OrderService;
use App\Traits\FileHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    use FileHelperTrait;

    /**
     * Display a listing of the authenticated customer's orders.
     */
    public function index(Request $request, OrderService $service): Response
    {
        $dto = OrderFilterDTO::from($request->all());

        $orders = $service->getFilteredOrders(
            auth('customer')->id(),
            $dto,
            perPage: 15
        )->withQueryString();

        return Inertia::render('Domains/Customer/Order/Index', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'date_range', 'start_date', 'end_date', 'status']),
        ]);
    }

    /**
     * Display the specified order details.
     */
    public function show(Order $order): Response
    {
        // Ensure the order belongs to the authenticated customer
        if ($order->customer_id !== auth('customer')->id()) {
            abort(404);
        }

        $order->load([
            'items.product',
            'items.testimonial',
            'items.chef',
            'dropPoint',
            'customerAddress',
            'customer',
            'paymentMethod',
            'productDiscount',
            'shippingDiscount',
            'shippings.chef',
        ]);

        return Inertia::render('Domains/Customer/Order/Show', [
            'order' => (new OrderResource($order))->resolve(),
        ]);
    }

    /**
     * Complete the shipped order.
     */
    public function complete(Order $order, OrderService $service)
    {
        // Ensure the order belongs to the authenticated customer
        if ($order->customer_id !== auth('customer')->id()) {
            abort(404);
        }

        try {
            $service->completeOrder($order);

            return redirect()->back()->with('success', 'Berhasil! Pesanan telah selesai.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menyelesaikan pesanan: '.$e->getMessage());
        }
    }

    /**
     * Store a testimonial for the specified order item.
     */
    public function testimonial(OrderItem $orderItem, TestimonialData $data)
    {
        $order = $orderItem->order;

        // Ensure the order belongs to the authenticated customer
        if ($order->customer_id !== auth('customer')->id()) {
            abort(404);
        }

        // Check if the item is eligible for a testimonial
        if (! $orderItem->canBeTestimonialed()) {
            return redirect()->back()->with('error', 'Item pesanan belum dapat diberi testimoni.');
        }

        try {
            DB::transaction(function () use ($orderItem, $data) {
                $photoPath = $this->handleFileInput(request()->file('photo'), null, 'testimonials');

                Testimonial::updateOrCreate(
                    ['order_item_id' => $orderItem->id],
                    [
                        'customer_id' => $orderItem->order->customer_id,
                        'order_id' => $orderItem->order_id,
                        'rating' => $data->rating,
                        'content' => $data->content,
                        'photo' => $photoPath,
                        'is_approved' => false, // Always requires approval
                    ]
                );
            });

            return redirect()->back()->with('success', 'Terima kasih! Testimoni Anda sedang menunggu persetujuan admin.');
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan testimoni', [
                'order_item_id' => $orderItem->id,
                'customer_id' => $order->customer_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Gagal menyimpan testimoni: '.$e->getMessage());
        }
    }
}
