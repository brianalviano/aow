<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Sales;

use App\DTOs\PaymentMethod\PaymentMethodData;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Requests\PaymentMethod\UpdatePaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Services\PaymentMethodService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class PaymentMethodController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->string('q')->toString();
        $isActive = (string) $request->string('is_active')->toString();

        $query = PaymentMethod::query()
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%");
                });
            })
            ->when($isActive !== '', function ($builder) use ($isActive) {
                $builder->where('is_active', $isActive === '1' || $isActive === 'true');
            })
            ->orderBy('name');

        $perPage = (int) $request->integer('per_page', 10);
        $paymentMethods = $query->paginate($perPage)->appends([
            'q' => $q,
            'is_active' => $isActive,
        ]);

        $items = array_map(
            fn($pm) => PaymentMethodResource::make($pm)->toArray($request),
            $paymentMethods->items(),
        );

        return Inertia::render('Domains/Admin/Sales/PaymentMethods/Index', [
            'payment_methods' => $items,
            'meta' => [
                'current_page' => $paymentMethods->currentPage(),
                'per_page' => $paymentMethods->perPage(),
                'total' => $paymentMethods->total(),
                'last_page' => $paymentMethods->lastPage(),
            ],
            'filters' => [
                'q' => $q,
                'is_active' => $isActive,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Domains/Admin/Sales/PaymentMethods/Form', [
            'payment_method' => null,
        ]);
    }

    public function store(StorePaymentMethodRequest $request, PaymentMethodService $service): RedirectResponse
    {
        try {
            $service->create(PaymentMethodData::fromStoreRequest($request));
            Inertia::flash('toast', [
                'message' => 'Metode pembayaran berhasil dibuat',
                'type' => 'success',
            ]);
            return redirect()->route('payment-methods.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat metode pembayaran: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function show(PaymentMethod $paymentMethod): Response
    {
        return Inertia::render('Domains/Admin/Sales/PaymentMethods/Show', [
            'payment_method' => PaymentMethodResource::make($paymentMethod)->toArray(request()),
        ]);
    }

    public function edit(PaymentMethod $paymentMethod): Response
    {
        return Inertia::render('Domains/Admin/Sales/PaymentMethods/Form', [
            'payment_method' => PaymentMethodResource::make($paymentMethod)->toArray(request()),
        ]);
    }

    public function update(
        UpdatePaymentMethodRequest $request,
        PaymentMethod $paymentMethod,
        PaymentMethodService $service
    ): RedirectResponse {
        try {
            $service->update($paymentMethod, PaymentMethodData::fromUpdateRequest($request));
            Inertia::flash('toast', [
                'message' => 'Metode pembayaran berhasil diperbarui',
                'type' => 'success',
            ]);
            return redirect()->route('payment-methods.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui metode pembayaran: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function destroy(PaymentMethod $paymentMethod, PaymentMethodService $service): RedirectResponse
    {
        try {
            $service->delete($paymentMethod);
            Inertia::flash('toast', [
                'message' => 'Metode pembayaran berhasil dihapus',
                'type' => 'success',
            ]);
            return redirect()->route('payment-methods.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus metode pembayaran: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return redirect()->route('payment-methods.index');
        }
    }
}
