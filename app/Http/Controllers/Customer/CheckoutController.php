<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\{Inertia, Response};

class CheckoutController extends Controller
{
    /**
     * Create a new CheckoutController instance.
     *
     * @param CheckoutService $checkoutService Service for handling checkout logic.
     */
    public function __construct(
        private readonly CheckoutService $checkoutService
    ) {}

    /**
     * Display the checkout page with cart data from session.
     *
     * @return Response|RedirectResponse
     */
    public function index(): Response|RedirectResponse
    {
        $cart = session('checkout_cart', []);
        $dropPointData = session('checkout_drop_point');
        $addressData = session('checkout_address');

        if (empty($cart) || (empty($dropPointData) && empty($addressData))) {
            return redirect()->to(route('home'));
        }

        $fees = $this->checkoutService->calculateFees($cart, $dropPointData['id'] ?? null, $addressData['id'] ?? null);

        return Inertia::render('Domains/Customer/Checkout/Index', [
            'cart' => $cart,
            'dropPoint' => $dropPointData,
            'address' => $addressData,
            'fees' => [
                'deliveryFee' => $fees['deliveryFee'],
                'baseDeliveryFee' => $fees['baseDeliveryFee'],
                'adminFee' => $fees['adminFee'],
                'taxAmount' => $fees['taxAmount'],
                'taxPercentage' => $fees['taxPercentage'],
                'taxEnabled' => $fees['taxEnabled'],
            ],
            'settings' => [
                'delivery_fee_mode' => $fees['deliveryFeeMode'],
                'free_courier_min_order' => $fees['minOrderFreeDelivery'],
                'admin_fee_enabled' => $fees['adminFeeEnabled'],
                'admin_fee_type' => $fees['adminFeeType'],
                'admin_fee_value' => $fees['adminFeeValue'],
                'tax_enabled' => $fees['taxEnabled'],
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
            'dropPoint' => 'nullable|array',
            'address' => 'nullable|array',
        ]);

        session([
            'checkout_cart' => $request->input('cart'),
            'checkout_drop_point' => $request->input('dropPoint'),
            'checkout_address' => $request->input('address'),
        ]);

        return redirect()->to(route('customer.checkout'));
    }

    /**
     * Update checkout data in session without redirecting.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $request->validate([
            'cart' => 'required|array',
            'dropPoint' => 'nullable|array',
            'address' => 'nullable|array',
        ]);

        session([
            'checkout_cart' => $request->input('cart'),
            'checkout_drop_point' => $request->input('dropPoint'),
            'checkout_address' => $request->input('address'),
        ]);

        return response()->json(['success' => true]);
    }
}
