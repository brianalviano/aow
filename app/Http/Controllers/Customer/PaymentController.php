<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\DTOs\Checkout\ProcessOrderData;
use App\DTOs\Setting\OrderSettingsDTO;
use App\Enums\DropPointCategory;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Notifications\PaymentProofUploadedAdminNotification;
use App\Notifications\PaymentProofUploadedCustomerNotification;
use App\Services\CheckoutService;
use App\Services\MidtransService;
use App\Services\OrderService;
use App\Traits\FileHelperTrait;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PaymentController extends Controller
{
    use FileHelperTrait;

    /**
     * Create a new PaymentController instance.
     *
     * @param  CheckoutService  $checkoutService  Service for handling checkout logic.
     * @param  OrderService  $orderService  Service for handling order logic.
     */
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly OrderService $orderService,
        private readonly MidtransService $midtransService
    ) {}

    /**
     * Display the payment summary page.
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
            $settings = OrderSettingsDTO::load();
            $instantStartTime = $settings->instantOrderStartTime;
            $instantEndTime = $settings->instantOrderEndTime;

            $currentTime = now()->format('H:i');
            if ($currentTime < $instantStartTime || $currentTime > $instantEndTime) {
                Inertia::flash('toast', [
                    'message' => 'Waktu Instant delivery telah habis. Silakan pilih tipe pesanan Pre-order.',
                    'type' => 'error',
                ]);

                return redirect()->route('customer.order-type.index', ['drop_point_id' => $dropPointData['id'] ?? null]);
            }
        }

        $paymentMethods = $this->getAvailablePaymentMethods();

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
     */
    public function show(Request $request, Order $order): Response|RedirectResponse
    {
        return Inertia::render('Domains/Customer/Pay/Index', [
            'order' => $order->load(['paymentMethod.paymentGuide', 'dropPoint', 'customerAddress']),
            'from' => $request->query('from'),
            'paymentMethods' => $this->getAvailablePaymentMethods(),
        ]);
    }

    /**
     * Update payment method for an unpaid order.
     */
    public function updateMethod(Request $request, Order $order): RedirectResponse
    {
        if ($order->payment_status !== PaymentStatus::PENDING) {
            Inertia::flash('toast', [
                'message' => 'Status pembayaran sudah tidak dalam status tertunda.',
                'type' => 'error',
            ]);

            return back();
        }

        if ($order->order_status !== OrderStatus::PENDING) {
            Inertia::flash('toast', [
                'message' => 'Pesanan sudah dalam proses dan tidak dapat diubah.',
                'type' => 'error',
            ]);

            return back();
        }

        $validated = $request->validate([
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
        ]);

        $newPaymentMethod = PaymentMethod::findOrFail($validated['payment_method_id']);

        try {
            DB::transaction(function () use ($order, $newPaymentMethod) {
                // Re-calculate service fee and total amount
                $subtotal = $order->total_amount - $order->delivery_fee - $order->admin_fee - $order->service_fee - $order->tax_amount;

                $newServiceFee = (int) round($subtotal * (float) $newPaymentMethod->service_fee_rate / 100) + (int) $newPaymentMethod->service_fee_fixed;
                $newTotalAmount = $subtotal + $order->delivery_fee + $order->admin_fee + $newServiceFee + $order->tax_amount;

                $order->update([
                    'payment_method_id' => $newPaymentMethod->id,
                    'service_fee' => $newServiceFee,
                    'total_amount' => $newTotalAmount,
                    'payment_details' => null, // Reset Midtrans details if switching methods
                    'payment_proof' => null,   // Reset proof if switching
                ]);

                if ($newPaymentMethod->type === PaymentMethodType::GATEWAY) {
                    $midtransResponse = $this->midtransService->charge($order, $newPaymentMethod);
                    $order->update([
                        'payment_details' => (array) $midtransResponse,
                    ]);
                }
            });

            Inertia::flash('toast', [
                'message' => 'Metode pembayaran berhasil diubah.',
                'type' => 'success',
            ]);

            return redirect()->route('customer.payment.show', ['order' => $order->id]);
        } catch (Throwable $e) {
            Log::error('Update payment method failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            Inertia::flash('toast', [
                'message' => 'Gagal mengubah metode pembayaran: '.$e->getMessage(),
                'type' => 'error',
            ]);

            return back();
        }
    }

    /**
     * Get all active payment methods grouped by category.
     */
    private function getAvailablePaymentMethods()
    {
        return PaymentMethod::where('is_active', true)
            ->with(['paymentGuide'])
            ->get()
            ->groupBy(fn ($method) => $method->category?->label() ?? 'Lainnya');
    }

    /**
     * Process the payment and create order.
     *
     * @param  Request  $request  Handled validation.
     * @return RedirectResponse
     *
     * @throws Throwable
     */
    public function processPayment(Request $request)
    {
        $cart = session('checkout_cart', []);
        $dropPoint = session('checkout_drop_point');
        $address = session('checkout_address');

        if (empty($cart) || (empty($dropPoint) && empty($address))) {
            return redirect()->to(route('home'))->withErrors(['error' => 'Sesi checkout kadaluwarsa.']);
        }

        $orderType = session('checkout_order_type', 'preorder');
        if ($orderType === 'instant') {
            $settings = OrderSettingsDTO::load();
            $instantStartTime = $settings->instantOrderStartTime;
            $instantEndTime = $settings->instantOrderEndTime;

            $currentTime = now()->format('H:i');
            if ($currentTime < $instantStartTime || $currentTime > $instantEndTime) {
                Inertia::flash('toast', [
                    'message' => 'Waktu Instant delivery telah habis. Silakan pilih tipe pesanan Pre-order.',
                    'type' => 'error',
                ]);

                return redirect()->route('customer.order-type.index', ['drop_point_id' => $dropPoint['id'] ?? null]);
            }
        } elseif ($orderType === 'preorder') {
            $deliveryDate = session('checkout_delivery_date');
            if ($deliveryDate) {
                $settings = OrderSettingsDTO::load();
                $cutoffTime = $settings->orderCutoffTime;
                $minDaysAhead = $settings->orderMinDaysAhead;

                $now = now();
                $cutoffDateTime = now()->setTimeFromTimeString($cutoffTime);

                $minDate = now()->addDays($minDaysAhead)->startOfDay();
                if ($now->greaterThan($cutoffDateTime)) {
                    $minDate->addDay();
                }

                if (Carbon::parse($deliveryDate)->startOfDay()->lessThan($minDate)) {
                    Inertia::flash('toast', [
                        'message' => 'Tanggal pengiriman tidak valid (melewati batas waktu cut-off). Silakan atur ulang tanggal pengiriman Anda.',
                        'type' => 'error',
                    ]);

                    return back();
                }
            }
        }

        try {
            $dropPointSession = session('checkout_drop_point');
            $isSchool = $dropPointSession && ($dropPointSession['category'] ?? '') === DropPointCategory::SCHOOL->value;

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:20'],
                'email' => ['required', 'email', 'max:255'],
                'payment_method_id' => ['required', 'exists:payment_methods,id'],
                'delivery_date' => ['nullable', 'date'],
                'delivery_time' => ['nullable'],
                'school_class' => [($isSchool ? 'required' : 'nullable'), 'string', 'max:255'],
            ]);
            $data = ProcessOrderData::fromCheckout($validated, $cart, $dropPoint, $address);

            $order = $this->orderService->processOrder($data);

            return redirect()->route('customer.payment.show', ['order' => $order->id, 'from' => 'checkout']);
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memproses pesanan: '.$e->getMessage(),
                'type' => 'error',
            ]);

            return redirect()->route('customer.payment-summary')->withInput();
        }
    }

    /**
     * Upload payment proof for manual transfer.
     *
     * @return RedirectResponse
     */
    public function uploadProof(Request $request, Order $order)
    {
        $request->validate([
            'proof' => 'required|image|max:10240',
        ]);

        try {
            $path = $this->handleFileInput($request->file('proof'), null, 'payment-proofs');

            $order->update([
                'payment_proof' => $path,
            ]);

            $order->loadMissing('customer');

            // Notify customer
            if ($order->customer) {
                $order->customer->notify(new PaymentProofUploadedCustomerNotification($order));
            }

            // Notify admins
            $admins = User::whereHas('role', function ($query) {
                $query->whereIn('name', [RoleName::SuperAdmin->value, RoleName::Admin->value]);
            })->get();

            Notification::send($admins, new PaymentProofUploadedAdminNotification($order));

            return redirect()->route('customer.payment.show', [
                'order' => $order->id,
                'from' => $request->query('from'),
            ]);
        } catch (Throwable $e) {
            Log::error('Upload payment proof failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            Inertia::flash('toast', [
                'message' => 'Gagal mengunggah bukti pembayaran: '.$e->getMessage(),
                'type' => 'error',
            ]);

            return redirect()->route('customer.payment.show', [
                'order' => $order->id,
                'from' => $request->query('from'),
            ]);
        }
    }

    /**
     * Download QRIS image via backend to bypass CORS and force download.
     *
     * @return StreamedResponse|RedirectResponse
     */
    public function downloadQris(Order $order)
    {
        $details = $order->payment_details;

        if (! $details || ! isset($details['actions'])) {
            Inertia::flash('toast', [
                'message' => 'Data QRIS tidak ditemukan.',
                'type' => 'error',
            ]);

            return redirect()->route('customer.payment.show', ['order' => $order->id]);
        }

        $qrisUrl = null;
        foreach ($details['actions'] as $action) {
            if ($action['name'] === 'generate-qr-code') {
                $qrisUrl = $action['url'];
                break;
            }
        }

        if (! $qrisUrl) {
            Inertia::flash('toast', [
                'message' => 'URL QRIS tidak ditemukan.',
                'type' => 'error',
            ]);

            return redirect()->route('customer.payment.show', ['order' => $order->id]);
        }

        try {
            $response = Http::get($qrisUrl);

            if ($response->successful()) {
                $imageContent = $response->body();
                $contentType = $response->header('Content-Type') ?? 'image/png';

                // Sanitize filename to ensure no slashes or backslashes are present
                $safeOrderNumber = str_replace('/', '-', $order->number);
                $filename = 'QRIS-'.$safeOrderNumber.'.png';

                return response()->streamDownload(function () use ($imageContent) {
                    echo $imageContent;
                }, $filename, [
                    'Content-Type' => $contentType,
                ]);
            }

            throw new \Exception('Failed to fetch image from Midtrans.');
        } catch (Throwable $e) {
            Log::error('Download QRIS failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            Inertia::flash('toast', [
                'message' => 'Gagal mengunduh QRIS, silakan coba lagi.',
                'type' => 'error',
            ]);

            return redirect()->route('customer.payment.show', ['order' => $order->id]);
        }
    }
}
