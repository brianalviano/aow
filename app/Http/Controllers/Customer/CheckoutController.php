<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\OrderSetting;
use Illuminate\Http\Request;
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
     * @return Response
     */
    public function index(Request $request): Response
    {
        $cart = session('checkout_cart', []);
        $dropPoint = session('checkout_drop_point');

        // If session is empty, we might want to redirect back to products
        // But for now, let's allow it to render empty if they just visited the URL directly.

        // Fetch settings for fees
        $settings = OrderSetting::pluck('value', 'key');

        $adminFee = (int) ($settings['admin_fee'] ?? 1000);
        // Delivery fee can be from drop point or settings
        $deliveryFee = (int) ($settings['delivery_fee'] ?? 5000);

        return Inertia::render('Domains/Customer/Checkout/Index', [
            'cart' => $cart,
            'dropPoint' => $dropPoint,
            'adminFee' => $adminFee,
            'deliveryFee' => $deliveryFee,
            'taxRate' => 0.11, // 11% PPN
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
}
