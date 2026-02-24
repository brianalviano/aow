<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentGuide\{StorePaymentGuideRequest, UpdatePaymentGuideRequest};
use App\Models\PaymentGuide;
use App\Services\PaymentGuideService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PaymentGuideController extends Controller
{
    public function __construct(
        private readonly PaymentGuideService $paymentGuideService
    ) {}

    /**
     * Display a listing of payment guides.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $limit = (int) $request->query('limit', 15);

        $paymentGuides = $this->paymentGuideService->getPaginated($limit, $search);

        return Inertia::render('Domains/Admin/PaymentGuide/Index', [
            'paymentGuides' => $paymentGuides,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new payment guide.
     */
    public function create(): Response
    {
        return Inertia::render('Domains/Admin/PaymentGuide/Form');
    }

    /**
     * Store a newly created payment guide.
     */
    public function store(StorePaymentGuideRequest $request): RedirectResponse
    {
        try {
            $this->paymentGuideService->createPaymentGuide($request->validated());

            Inertia::flash('toast', [
                'message' => 'Panduan Pembayaran berhasil dibuat',
                'type' => 'success',
            ]);

            return redirect()->route('admin.payment-guides.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat Panduan Pembayaran: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back()->withInput();
        }
    }

    /**
     * Show the form for editing the specified payment guide.
     */
    public function edit(PaymentGuide $paymentGuide): Response
    {
        return Inertia::render('Domains/Admin/PaymentGuide/Form', [
            'paymentGuide' => $paymentGuide,
        ]);
    }

    /**
     * Update the specified payment guide.
     */
    public function update(UpdatePaymentGuideRequest $request, PaymentGuide $paymentGuide): RedirectResponse
    {
        try {
            $this->paymentGuideService->updatePaymentGuide($paymentGuide, $request->validated());

            Inertia::flash('toast', [
                'message' => 'Panduan Pembayaran berhasil diperbarui',
                'type' => 'success',
            ]);

            return redirect()->route('admin.payment-guides.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui Panduan Pembayaran: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back()->withInput();
        }
    }

    /**
     * Remove the specified payment guide.
     */
    public function destroy(PaymentGuide $paymentGuide): RedirectResponse
    {
        try {
            $this->paymentGuideService->deletePaymentGuide($paymentGuide);

            Inertia::flash('toast', [
                'message' => 'Panduan Pembayaran berhasil dihapus',
                'type' => 'success',
            ]);

            return redirect()->route('admin.payment-guides.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus Panduan Pembayaran: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back();
        }
    }
}
