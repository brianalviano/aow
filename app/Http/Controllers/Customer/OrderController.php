<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\FileHelperTrait;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    use FileHelperTrait;

    /**
     * Display a listing of the authenticated customer's orders.
     */
    public function index(Request $request, \App\Services\OrderService $service): Response
    {
        $dto = \App\DTOs\Order\OrderFilterDTO::fromArray($request->all());

        $orders = $service->getFilteredOrders(
            auth('customer')->id(),
            $dto,
            perPage: 15
        )->withQueryString();

        return Inertia::render('Domains/Customer/Order/Index', [
            'orders'  => $orders,
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
            'dropPoint',
            'customer',
            'paymentMethod',
            'productDiscount',
            'shippingDiscount',
            'testimonial'
        ]);

        return Inertia::render('Domains/Customer/Order/Show', [
            'order' => (new \App\Http\Resources\OrderResource($order))->resolve(),
        ]);
    }

    /**
     * Complete the shipped order.
     */
    public function complete(Order $order, \App\Services\OrderService $service)
    {
        // Ensure the order belongs to the authenticated customer
        if ($order->customer_id !== auth('customer')->id()) {
            abort(404);
        }

        try {
            $service->completeOrder($order);

            return redirect()->back()->with('success', 'Berhasil! Pesanan telah selesai.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menyelesaikan pesanan: ' . $e->getMessage());
        }
    }

    /**
     * Store a testimonial for the specified order.
     */
    public function testimonial(Order $order, \App\Http\Requests\Customer\TestimonialRequest $request)
    {
        // Ensure the order belongs to the authenticated customer
        if ($order->customer_id !== auth('customer')->id()) {
            abort(404);
        }

        // Check if the order is eligible for a testimonial
        if (!$order->canBeTestimonialed()) {
            return redirect()->back()->with('error', 'Pesanan belum dapat diberi testimoni. Mohon tunggu 30 menit setelah diterima.');
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($order, $request) {
                $photoPath = $this->handleFileInput($request->file('photo'), null, 'testimonials');

                \App\Models\Testimonial::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'customer_id' => $order->customer_id,
                        'rating'      => $request->rating,
                        'content'     => $request->content,
                        'photo'       => $photoPath,
                    ]
                );
            });

            return redirect()->back()->with('success', 'Terima kasih atas testimoni Anda!');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal menyimpan testimoni', [
                'order_id'    => $order->id,
                'customer_id' => $order->customer_id,
                'error'       => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Gagal menyimpan testimoni: ' . $e->getMessage());
        }
    }
}
