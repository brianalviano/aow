<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\DTOs\Checkout\ProcessOrderData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Checkout\ProcessPaymentRequest;
use App\Models\PaymentMethod;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\{Inertia, Response};
use Throwable;

class PaymentController extends Controller
{
    /**
     * Create a new PaymentController instance.
     *
     * @param CheckoutService $checkoutService Service for handling checkout logic.
     */
    public function __construct(
        private readonly CheckoutService $checkoutService
    ) {}

    /**
     * Display the payment summary page.
     *
     * @return Response|RedirectResponse
     */
    public function index(): Response|RedirectResponse
    {
        $cart = session('checkout_cart', []);
        $dropPointData = session('checkout_drop_point');

        // If no checkout session, redirect home
        if (empty($cart) || empty($dropPointData)) {
            return redirect()->to(route('home'));
        }

        $paymentMethods = PaymentMethod::where('is_active', true)
            ->with('paymentGuide')
            ->get()
            ->groupBy(fn($method) => $method->category?->label() ?? 'Lainnya');

        $user = Auth::guard('customer')->user();

        $fees = $this->checkoutService->calculateFees($cart, $dropPointData['id']);
        $totalAmount = $fees['subtotal'] + $fees['deliveryFee'] + $fees['taxAmount'] + $fees['adminFee'];

        return Inertia::render('Domains/Customer/PaymentSummary/Index', [
            'paymentMethods' => $paymentMethods,
            'customer' => $user,
            'totalAmount' => $totalAmount,
        ]);
    }

    /**
     * Display the payment page for an order.
     *
     * @param \App\Models\Order $order
     * @return Response|RedirectResponse
     */
    public function show(\Illuminate\Http\Request $request, \App\Models\Order $order): Response|RedirectResponse
    {
        return Inertia::render('Domains/Customer/Pay/Index', [
            'order' => $order->load('paymentMethod.paymentGuide'),
            'from' => $request->query('from'),
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

            $order = $this->checkoutService->processOrder($data);

            return redirect()->route('customer.payment.show', ['order' => $order->id, 'from' => 'checkout']);
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memproses pesanan: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back()->withInput();
        }
    }

    /**
     * Upload payment proof for manual transfer.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Order $order
     * @return \Illuminate\Http\RedirectResponse
     */
    public function uploadProof(\Illuminate\Http\Request $request, \App\Models\Order $order)
    {
        $request->validate([
            'proof' => 'required|image|max:2048',
        ]);

        try {
            $path = $request->file('proof')->store('payment-proofs', 'public');

            $order->update([
                'payment_proof' => $path,
            ]);

            return redirect()->route('customer.payment.show', $order->id);
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Upload payment proof failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            Inertia::flash('toast', [
                'message' => 'Gagal mengunggah bukti pembayaran: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back();
        }
    }
}
