<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\{Customer, Order, OrderItem, OrderItemOption, OrderSetting, PaymentMethod};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash, Log};
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class CheckoutController
 * Handles the checkout process for customers.
 */
class CheckoutController extends Controller
{
    /**
     * Display the checkout page with cart data from session.
     *
     * @param Request $request
     * @return Response|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $cart = session('checkout_cart', []);
        $dropPointData = session('checkout_drop_point');

        if (empty($cart) || empty($dropPointData)) {
            return redirect()->to(route('home'));
        }

        // Fetch DropPoint model to get delivery_fee
        $dropPoint = \App\Models\DropPoint::find($dropPointData['id']);

        // Fetch settings for fees
        $settings = OrderSetting::pluck('value', 'key')->toArray();

        // Calculate Subtotal
        $subtotal = collect($cart)->sum('totalPrice');

        // Logic for Delivery Fee
        $deliveryFeeMode = $settings['delivery_fee_mode'] ?? 'per_drop_point';
        $minOrderFreeDelivery = (int) ($settings['free_courier_min_order'] ?? 0);

        if ($subtotal >= $minOrderFreeDelivery && $minOrderFreeDelivery > 0) {
            $deliveryFee = 0;
        } else {
            $deliveryFee = match ($deliveryFeeMode) {
                'free' => 0,
                'flat' => (int) ($settings['delivery_fee_flat'] ?? 0),
                default => (int) ($dropPoint->delivery_fee ?? 0),
            };
        }

        // Logic for Admin Fee
        $adminFeeEnabled = ($settings['admin_fee_enabled'] ?? 'false') === 'true';
        $adminFee = 0;
        if ($adminFeeEnabled) {
            $adminFeeType = $settings['admin_fee_type'] ?? 'fixed';
            $adminFeeValue = (int) ($settings['admin_fee_value'] ?? 0);
            $adminFee = $adminFeeType === 'fixed' ? $adminFeeValue : (int) round($subtotal * $adminFeeValue / 100);
        }

        // Logic for Tax
        $taxEnabled = ($settings['tax_enabled'] ?? 'false') === 'true';
        $taxPercentage = (int) ($settings['tax_percentage'] ?? 0);
        $taxAmount = 0;
        if ($taxEnabled) {
            $taxAmount = (int) round($subtotal * $taxPercentage / 100);
        }

        return Inertia::render('Domains/Customer/Checkout/Index', [
            'cart' => $cart,
            'dropPoint' => $dropPointData,
            'fees' => [
                'deliveryFee' => $deliveryFee,
                'adminFee' => $adminFee,
                'taxAmount' => $taxAmount,
                'taxPercentage' => $taxPercentage,
                'taxEnabled' => $taxEnabled,
            ],
            'settings' => [
                'delivery_fee_mode' => $deliveryFeeMode,
                'free_courier_min_order' => $minOrderFreeDelivery,
                'admin_fee_enabled' => $adminFeeEnabled,
                'tax_enabled' => $taxEnabled,
            ],
        ]);
    }

    /**
     * Store checkout data in session and redirect to checkout page.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'cart' => 'required|array',
            'dropPoint' => 'required|array',
        ]);

        session([
            'checkout_cart' => $request->input('cart'),
            'checkout_drop_point' => $request->input('dropPoint'),
        ]);

        return redirect()->to(route('customer.checkout'));
    }

    /**
     * Update checkout data in session without redirecting.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSession(Request $request)
    {
        $request->validate([
            'cart' => 'required|array',
            'dropPoint' => 'required|array',
        ]);

        session([
            'checkout_cart' => $request->input('cart'),
            'checkout_drop_point' => $request->input('dropPoint'),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Display the payment page.
     *
     * @param Request $request
     * @return Response|\Illuminate\Http\RedirectResponse
     */
    public function payment(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $cart = session('checkout_cart', []);
        $dropPointData = session('checkout_drop_point');

        if (empty($cart) || empty($dropPointData)) {
            return redirect()->to(route('home'));
        }

        $paymentMethods = PaymentMethod::where('is_active', true)
            ->with('paymentGuide')
            ->get()
            ->groupBy(fn($method) => $method->category?->label() ?? 'Lainnya');

        $user = Auth::guard('customer')->user();

        // Calculate total for total amount display
        $dropPoint = \App\Models\DropPoint::find($dropPointData['id']);
        $settings = OrderSetting::pluck('value', 'key')->toArray();

        $subtotal = collect($cart)->sum('totalPrice');

        // Logic for Delivery Fee
        $deliveryFeeMode = $settings['delivery_fee_mode'] ?? 'per_drop_point';
        $minOrderFreeDelivery = (int) ($settings['free_courier_min_order'] ?? 0);

        if ($subtotal >= $minOrderFreeDelivery && $minOrderFreeDelivery > 0) {
            $deliveryFee = 0;
        } else {
            $deliveryFee = match ($deliveryFeeMode) {
                'free' => 0,
                'flat' => (int) ($settings['delivery_fee_flat'] ?? 0),
                default => (int) ($dropPoint->delivery_fee ?? 0),
            };
        }

        // Logic for Admin Fee
        $adminFeeEnabled = ($settings['admin_fee_enabled'] ?? 'false') === 'true';
        $adminFee = 0;
        if ($adminFeeEnabled) {
            $adminFeeType = $settings['admin_fee_type'] ?? 'fixed';
            $adminFeeValue = (int) ($settings['admin_fee_value'] ?? 0);
            $adminFee = $adminFeeType === 'fixed' ? $adminFeeValue : (int) round($subtotal * $adminFeeValue / 100);
        }

        // Logic for Tax
        $taxEnabled = ($settings['tax_enabled'] ?? 'false') === 'true';
        $taxPercentage = (int) ($settings['tax_percentage'] ?? 0);
        $taxAmount = 0;
        if ($taxEnabled) {
            $taxAmount = (int) round($subtotal * $taxPercentage / 100);
        }

        $totalAmount = $subtotal + $deliveryFee + $taxAmount + $adminFee;

        return Inertia::render('Domains/Customer/Checkout/Payment', [
            'paymentMethods' => $paymentMethods,
            'customer' => $user,
            'totalAmount' => $totalAmount,
        ]);
    }

    /**
     * Process the payment and create order.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'school_class' => 'required|string|max:50',
            'payment_method_id' => 'required|exists:payment_methods,id',
        ]);

        $cart = session('checkout_cart', []);
        $dropPoint = session('checkout_drop_point');

        if (empty($cart) || empty($dropPoint)) {
            return redirect()->to(route('home'))->withErrors(['error' => 'Sesi checkout kadaluwarsa.']);
        }

        try {
            return DB::transaction(function () use ($request, $cart, $dropPoint) {
                $customer = Auth::guard('customer')->user();

                if (!$customer) {
                    // Check if customer with this email already exists
                    $customer = Customer::where('email', $request->email)->first();

                    if (!$customer) {
                        // Create new customer account
                        $customer = Customer::create([
                            'name' => $request->name,
                            'phone' => $request->phone,
                            'email' => $request->email,
                            'username' => $request->email, // Use email as username for simplicity
                            'password' => '12345678', // Default password as requested
                            'school_class' => $request->school_class,
                            'drop_point_id' => $dropPoint['id'],
                            'is_active' => true,
                        ]);

                        // TODO: Send email with credentials "12345678"
                    }

                    Auth::guard('customer')->login($customer);
                }

                // Calculate costs
                $settings = OrderSetting::pluck('value', 'key')->toArray();
                $subtotal = collect($cart)->sum('totalPrice');

                $dropPointModel = \App\Models\DropPoint::find($dropPoint['id']);

                // Logic for Delivery Fee
                $deliveryFeeMode = $settings['delivery_fee_mode'] ?? 'per_drop_point';
                $minOrderFreeDelivery = (int) ($settings['free_courier_min_order'] ?? 0);

                if ($subtotal >= $minOrderFreeDelivery && $minOrderFreeDelivery > 0) {
                    $deliveryFee = 0;
                } else {
                    $deliveryFee = match ($deliveryFeeMode) {
                        'free' => 0,
                        'flat' => (int) ($settings['delivery_fee_flat'] ?? 0),
                        default => (int) ($dropPointModel->delivery_fee ?? 0),
                    };
                }

                // Logic for Admin Fee
                $adminFeeEnabled = ($settings['admin_fee_enabled'] ?? 'false') === 'true';
                $adminFee = 0;
                if ($adminFeeEnabled) {
                    $adminFeeType = $settings['admin_fee_type'] ?? 'fixed';
                    $adminFeeValue = (int) ($settings['admin_fee_value'] ?? 0);
                    $adminFee = $adminFeeType === 'fixed' ? $adminFeeValue : (int) round($subtotal * $adminFeeValue / 100);
                }

                // Logic for Tax
                $taxEnabled = ($settings['tax_enabled'] ?? 'false') === 'true';
                $taxPercentage = (int) ($settings['tax_percentage'] ?? 0);
                $taxAmount = 0;
                if ($taxEnabled) {
                    $taxAmount = (int) round($subtotal * $taxPercentage / 100);
                }

                $totalAmount = $subtotal + $deliveryFee + $taxAmount + $adminFee;

                // Create Order
                $order = Order::create([
                    'number' => 'ORD-' . strtoupper(uniqid()),
                    'drop_point_id' => $dropPoint['id'],
                    'customer_id' => $customer->id,
                    'delivery_date' => $request->delivery_date ?? now()->addDay()->format('Y-m-d'),
                    'delivery_time' => $request->delivery_time ?? '12:00',
                    'payment_method_id' => $request->payment_method_id,
                    'payment_status' => 'pending',
                    'order_status' => 'pending',
                    'total_amount' => $totalAmount,
                    'delivery_fee' => $deliveryFee,
                    'admin_fee' => $adminFee,
                    'tax_amount' => $taxAmount,
                ]);

                // Create Order Items
                foreach ($cart as $item) {
                    $orderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product']['id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['basePrice'],
                        'subtotal' => $item['totalPrice'],
                        'note' => $item['notes'] ?? null,
                    ]);

                    // Create Order Item Options
                    if (isset($item['selectedOptions'])) {
                        foreach ($item['selectedOptions'] as $optionId => $selection) {
                            $itemIds = is_array($selection) ? $selection : [$selection];
                            foreach ($itemIds as $optionItemId) {
                                // Find extra price for this option item
                                // This is a bit inefficient inside loop, but cart structure has it
                                $extraPrice = 0;
                                $options = $item['product']['options'] ?? [];
                                $productOptions = is_array($options) ? $options : ($options['data'] ?? []);

                                foreach ($productOptions as $opt) {
                                    if ($opt['id'] === $optionId) {
                                        foreach ($opt['items'] as $optItem) {
                                            if ($optItem['id'] === $optionItemId) {
                                                $extraPrice = $optItem['extra_price'] ?? 0;
                                                break 2;
                                            }
                                        }
                                    }
                                }

                                OrderItemOption::create([
                                    'order_item_id' => $orderItem->id,
                                    'product_option_id' => $optionId,
                                    'product_option_item_id' => $optionItemId,
                                    'extra_price' => $extraPrice,
                                ]);
                            }
                        }
                    }
                }

                // Clear checkout session
                session()->forget(['checkout_cart', 'checkout_drop_point']);

                Inertia::flash('toast', [
                    'message' => 'Pesanan berhasil dibuat',
                    'type' => 'success',
                ]);

                return redirect()->route('home');
            });
        } catch (\Throwable $e) {
            Log::error('Failed to process payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::guard('customer')->id(),
            ]);
            throw $e;
        }
    }
}
