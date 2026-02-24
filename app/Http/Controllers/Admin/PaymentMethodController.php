<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\PaymentMethod\PaymentMethodData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentMethod\{StorePaymentMethodRequest, UpdatePaymentMethodRequest};
use App\Http\Resources\PaymentMethodResource;
use App\Models\{PaymentGuide, PaymentMethod};
use App\Services\PaymentMethodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Handles admin CRUD operations for payment methods.
 */
class PaymentMethodController extends Controller
{
    public function __construct(
        private readonly PaymentMethodService $paymentMethodService
    ) {}

    /**
     * Display a listing of payment methods.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $limit = (int) $request->query('limit', 15);

        $paymentMethods = $this->paymentMethodService->getPaginated($limit, $search);

        return Inertia::render('Domains/Admin/PaymentMethod/Index', [
            'paymentMethods' => PaymentMethodResource::collection($paymentMethods),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new payment method.
     */
    public function create(): Response
    {
        return Inertia::render('Domains/Admin/PaymentMethod/Form', [
            'paymentGuides' => PaymentGuide::all(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created payment method.
     */
    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        try {
            $data = PaymentMethodData::fromStoreRequest($request);

            $this->paymentMethodService->createPaymentMethod($data);

            Inertia::flash('toast', [
                'message' => 'Metode Pembayaran berhasil dibuat',
                'type' => 'success',
            ]);

            return redirect()->route('admin.payment-methods.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat Metode Pembayaran: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back()->withInput();
        }
    }

    /**
     * Show the form for editing the specified payment method.
     */
    public function edit(PaymentMethod $paymentMethod): Response
    {
        return Inertia::render('Domains/Admin/PaymentMethod/Form', [
            'paymentMethod' => new PaymentMethodResource($paymentMethod),
            'paymentGuides' => PaymentGuide::all(['id', 'name']),
        ]);
    }

    /**
     * Update the specified payment method.
     */
    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        try {
            $data = PaymentMethodData::fromUpdateRequest($request);

            $this->paymentMethodService->updatePaymentMethod($paymentMethod, $data);

            Inertia::flash('toast', [
                'message' => 'Metode Pembayaran berhasil diperbarui',
                'type' => 'success',
            ]);

            return redirect()->route('admin.payment-methods.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui Metode Pembayaran: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back()->withInput();
        }
    }

    /**
     * Remove the specified payment method.
     */
    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        try {
            $this->paymentMethodService->deletePaymentMethod($paymentMethod);

            Inertia::flash('toast', [
                'message' => 'Metode Pembayaran berhasil dihapus',
                'type' => 'success',
            ]);

            return redirect()->route('admin.payment-methods.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus Metode Pembayaran: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back();
        }
    }
}
