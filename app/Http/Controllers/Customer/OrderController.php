<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Display a listing of the authenticated customer's orders.
     */
    public function index(Request $request): Response
    {
        $orders = Order::with(['dropPoint', 'paymentMethod'])
            ->where('customer_id', auth('customer')->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Domains/Customer/Order/Index', [
            'orders' => $orders,
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
            'shippingDiscount'
        ]);

        return Inertia::render('Domains/Customer/Order/Show', [
            'order' => $order,
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
}
