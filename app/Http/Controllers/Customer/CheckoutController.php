<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use App\Services\QuotaService;
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
        private readonly CheckoutService $checkoutService,
        private readonly QuotaService $quotaService
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

        $orderType = session('checkout_order_type', 'preorder');
        $deliveryDate = session('checkout_delivery_date');
        $deliveryTime = session('checkout_delivery_time');
        $notes = session('checkout_notes');

        $quotaProgress = null;
        if ($dropPointData && $orderType === 'preorder') {
            $quotaDate = $deliveryDate ?: now()->addDay()->format('Y-m-d');
            $quotaProgress = $this->quotaService->calculateDropPointQuotaProgress($dropPointData['id'], $quotaDate);
        }

        return Inertia::render('Domains/Customer/Checkout/Index', [
            'cart' => (object) $cart,
            'dropPoint' => $dropPointData,
            'address' => $addressData,
            'orderType' => $orderType,
            'delivery_date' => $deliveryDate,
            'delivery_time' => $deliveryTime,
            'notes' => $notes,
            'fees' => [
                'deliveryFee' => $fees['deliveryFee'],
                'baseDeliveryFee' => $fees['baseDeliveryFee'],
                'adminFee' => $fees['adminFee'],
                'taxAmount' => $fees['taxAmount'],
                'taxPercentage' => $fees['taxPercentage'],
                'taxEnabled' => $fees['taxEnabled'],
                'shippingBreakdown' => $fees['shippingBreakdown'],
                'useBiteship' => $fees['useBiteship'],
            ],
            'settings' => [
                'delivery_fee_mode' => $fees['deliveryFeeMode'],
                'free_courier_min_order' => $fees['minOrderFreeDelivery'],
                'admin_fee_enabled' => $fees['adminFeeEnabled'],
                'admin_fee_type' => $fees['adminFeeType'],
                'admin_fee_value' => $fees['adminFeeValue'],
                'tax_enabled' => $fees['taxEnabled'],
                'order_cutoff_time' => \App\DTOs\Setting\OrderSettingsDTO::load()->orderCutoffTime,
                'order_min_days_ahead' => \App\DTOs\Setting\OrderSettingsDTO::load()->orderMinDaysAhead,
            ],
            'quotaProgress' => $quotaProgress,
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
            'checkout_delivery_date' => $request->input('delivery_date'),
            'checkout_delivery_time' => $request->input('delivery_time'),
            'checkout_notes' => $request->input('notes'),
            'checkout_redirect_after_selection' => $request->boolean('redirect_to_checkout'),
        ]);

        if (empty($request->input('dropPoint')) && empty($request->input('address')) && $request->boolean('redirect_to_checkout')) {
            return redirect()->route('home');
        }

        return redirect()->to(route('customer.checkout'));
    }

    /**
     * Update checkout data in session without redirecting.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
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
            'checkout_delivery_date' => $request->input('delivery_date'),
            'checkout_delivery_time' => $request->input('delivery_time'),
            'checkout_notes' => $request->input('notes'),
            'checkout_order_type' => $request->input('order_type', session('checkout_order_type', 'preorder')),
        ]);

        return back();
    }
}
