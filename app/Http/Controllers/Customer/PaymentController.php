<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\DTOs\Checkout\ProcessOrderData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Checkout\ProcessPaymentRequest;
use App\Models\PaymentMethod;
use App\Services\{CheckoutService, OrderService};
use App\Traits\FileHelperTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\{Inertia, Response};
use Throwable;

class PaymentController extends Controller
{
    use FileHelperTrait;

    /**
     * Create a new PaymentController instance.
     *
     * @param CheckoutService $checkoutService Service for handling checkout logic.
     * @param OrderService $orderService Service for handling order logic.
     */
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly OrderService $orderService
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
        $addressData = session('checkout_address');

        // If no checkout session, redirect home
        if (empty($cart) || (empty($dropPointData) && empty($addressData))) {
            return redirect()->to(route('home'));
        }

        $orderType = session('checkout_order_type', 'preorder');
        if ($orderType === 'instant') {
            $instantStartTime = \App\Models\OrderSetting::where('key', 'instant_order_start_time')->value('value') ?? '08:00';
            $instantEndTime = \App\Models\OrderSetting::where('key', 'instant_order_end_time')->value('value') ?? '21:00';

            $currentTime = now()->format('H:i');
            if ($currentTime < $instantStartTime || $currentTime > $instantEndTime) {
                Inertia::flash('toast', [
                    'message' => "Waktu Instant delivery telah habis. Silakan pilih tipe pesanan Pre-order.",
                    'type' => 'error'
                ]);
                return redirect()->route('customer.order-type.index', ['drop_point_id' => $dropPointData['id'] ?? null]);
            }
        }

        $paymentMethods = PaymentMethod::where('is_active', true)
            ->with('paymentGuide')
            ->get()
            ->groupBy(fn($method) => $method->category?->label() ?? 'Lainnya');

        $user = Auth::guard('customer')->user();

        $fees = $this->checkoutService->calculateFees($cart, $dropPointData['id'] ?? null, $addressData['id'] ?? null);
        $totalAmount = $fees['subtotal'] + $fees['deliveryFee'] + $fees['taxAmount'] + $fees['adminFee'];

        return Inertia::render('Domains/Customer/PaymentSummary/Index', [
            'paymentMethods' => $paymentMethods,
            'customer' => $user,
            'totalAmount' => $totalAmount,
            'dropPoint' => $dropPointData,
            'address' => $addressData,
            'delivery_date' => session('checkout_delivery_date'),
            'delivery_time' => session('checkout_delivery_time'),
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
        $address = session('checkout_address');

        if (empty($cart) || (empty($dropPoint) && empty($address))) {
            return redirect()->to(route('home'))->withErrors(['error' => 'Sesi checkout kadaluwarsa.']);
        }

        $orderType = session('checkout_order_type', 'preorder');
        if ($orderType === 'instant') {
            $instantStartTime = \App\Models\OrderSetting::where('key', 'instant_order_start_time')->value('value') ?? '08:00';
            $instantEndTime = \App\Models\OrderSetting::where('key', 'instant_order_end_time')->value('value') ?? '21:00';

            $currentTime = now()->format('H:i');
            if ($currentTime < $instantStartTime || $currentTime > $instantEndTime) {
                Inertia::flash('toast', [
                    'message' => "Waktu Instant delivery telah habis. Silakan pilih tipe pesanan Pre-order.",
                    'type' => 'error'
                ]);
                return redirect()->route('customer.order-type.index', ['drop_point_id' => $dropPoint['id'] ?? null]);
            }
        } elseif ($orderType === 'preorder') {
            $deliveryDate = session('checkout_delivery_date');
            if ($deliveryDate) {
                $cutoffTime = \App\Models\OrderSetting::where('key', 'order_cutoff_time')->value('value') ?? '20:00';
                $minDaysAhead = (int) (\App\Models\OrderSetting::where('key', 'order_min_days_ahead')->value('value') ?? 1);

                $now = now();
                $cutoffDateTime = now()->setTimeFromTimeString($cutoffTime);

                $minDate = now()->addDays($minDaysAhead)->startOfDay();
                if ($now->greaterThan($cutoffDateTime)) {
                    $minDate->addDay();
                }

                if (\Carbon\Carbon::parse($deliveryDate)->startOfDay()->lessThan($minDate)) {
                    Inertia::flash('toast', [
                        'message' => 'Tanggal pengiriman tidak valid (melewati batas waktu cut-off). Silakan atur ulang tanggal pengiriman Anda.',
                        'type' => 'error'
                    ]);
                    return back();
                }
            }
        }

        try {
            $data = ProcessOrderData::fromRequest($request, $cart, $dropPoint, $address);

            $order = $this->orderService->processOrder($data);

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
            $path = $this->handleFileInput($request->file('proof'), null, 'payment-proofs');

            $order->update([
                'payment_proof' => $path,
            ]);

            $order->loadMissing('customer');

            // Notify customer
            if ($order->customer) {
                $order->customer->notify(new \App\Notifications\PaymentProofUploadedCustomerNotification($order));
            }

            // Notify admins
            $admins = \App\Models\User::whereHas('role', function ($query) {
                $query->whereIn('name', [\App\Enums\RoleName::SuperAdmin->value, \App\Enums\RoleName::Admin->value]);
            })->get();

            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\PaymentProofUploadedAdminNotification($order));

            return redirect()->route('customer.payment.show', [
                'order' => $order->id,
                'from' => $request->query('from')
            ]);
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
