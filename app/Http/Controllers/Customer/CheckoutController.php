<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\DTOs\Checkout\ProcessOrderData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Checkout\ProcessPaymentRequest;
use App\Models\{OrderSetting, PaymentMethod};
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\{Inertia, Response};
use Throwable;

/**
 * Class CheckoutController
 * Handles the checkout process for customers.
 */
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

        $fees = $this->checkoutService->calculateFees($cart, (int) $dropPointData['id']);

        return Inertia::render('Domains/Customer/Checkout/Index', [
            'cart' => $cart,
            'dropPoint' => $dropPointData,
            'fees' => [
                'deliveryFee' => $fees['deliveryFee'],
                'adminFee' => $fees['adminFee'],
                'taxAmount' => $fees['taxAmount'],
                'taxPercentage' => $fees['taxPercentage'],
                'taxEnabled' => $fees['taxEnabled'],
            ],
            'settings' => [
                'delivery_fee_mode' => $fees['deliveryFeeMode'],
                'free_courier_min_order' => $fees['minOrderFreeDelivery'],
                'admin_fee_enabled' => $fees['adminFeeEnabled'],
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

        $fees = $this->checkoutService->calculateFees($cart, (int) $dropPointData['id']);
        $totalAmount = $fees['subtotal'] + $fees['deliveryFee'] + $fees['taxAmount'] + $fees['adminFee'];

        return Inertia::render('Domains/Customer/Checkout/Payment', [
            'paymentMethods' => $paymentMethods,
            'customer' => $user,
            'totalAmount' => $totalAmount,
        ]);
    }

    /**
     * Process the payment and create order.
     *
     * @param ProcessPaymentRequest $request Handled validation.
     * @return \Illuminate\Http\RedirectResponse
     * @throws Throwable
     */
    public function processPayment(ProcessPaymentRequest $request)
    {
        $cart = session('checkout_cart', []);
        $dropPoint = session('checkout_drop_point');

        if (empty($cart) || empty($dropPoint)) {
            return redirect()->to(route('home'))->withErrors(['error' => 'Sesi checkout kadaluwarsa.']);
        }

        try {
            $data = ProcessOrderData::fromRequest($request, $cart, $dropPoint);

            $this->checkoutService->processOrder($data);

            Inertia::flash('toast', [
                'message' => 'Pesanan berhasil dibuat',
                'type' => 'success',
            ]);

            return redirect()->route('home');
        } catch (Throwable $e) {
            // Logging is handled within the service
            Inertia::flash('toast', [
                'message' => 'Gagal memproses pesanan: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back()->withInput();
        }
    }
}
