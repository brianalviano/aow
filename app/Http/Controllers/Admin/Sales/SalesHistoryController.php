<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Services\SalesViewService;
use Illuminate\Http\Request;
use Inertia\{Inertia, Response};
use App\Models\{Sales, Warehouse, PaymentMethod};
use App\Enums\SalesPaymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Sales\SettleSalesPaymentRequest;
use App\Services\SalesService;
use App\DTOs\Sales\SalesPaymentData;
use Throwable;

/**
 * Halaman Riwayat Penjualan (POS).
 *
 * Menyediakan daftar transaksi penjualan dan halaman detail.
 * Tidak ada create/edit. Detail menampilkan informasi seperti invoice.
 *
 * @author PJD
 *
 * @method Response index(Request $request)
 * @method Response show(Sales $sales)
 */
final class SalesHistoryController extends Controller
{
    /**
     * Tampilkan daftar transaksi penjualan.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $q = (string) $request->string('q')->toString();
        $paymentStatus = (string) $request->string('payment_status')->toString();
        $warehouseId = (string) $request->string('warehouse_id')->toString();
        $saleDateFrom = (string) $request->string('sale_date_from')->toString();
        $saleDateTo = (string) $request->string('sale_date_to')->toString();

        $query = Sales::query()
            ->with(['warehouse', 'customer'])
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('receipt_number', 'ilike', "%{$q}%")
                        ->orWhere('invoice_number', 'ilike', "%{$q}%")
                        ->orWhere('customer_name', 'ilike', "%{$q}%");
                });
            })
            ->when($paymentStatus !== '', function ($builder) use ($paymentStatus) {
                $builder->where('payment_status', $paymentStatus);
            })
            ->when($warehouseId !== '', function ($builder) use ($warehouseId) {
                $builder->where('warehouse_id', $warehouseId);
            })
            ->when($saleDateFrom !== '', function ($builder) use ($saleDateFrom) {
                $builder->whereDate('sale_datetime', '>=', $saleDateFrom);
            })
            ->when($saleDateTo !== '', function ($builder) use ($saleDateTo) {
                $builder->whereDate('sale_datetime', '<=', $saleDateTo);
            })
            ->orderByDesc('sale_datetime')
            ->orderByDesc('created_at');

        $perPage = (int) $request->integer('per_page', 10);
        $sales = $query->paginate($perPage)->appends([
            'q' => $q,
            'payment_status' => $paymentStatus,
            'warehouse_id' => $warehouseId,
            'sale_date_from' => $saleDateFrom,
            'sale_date_to' => $saleDateTo,
        ]);

        $items = collect($sales->items())->map(function ($s) use ($request) {
            $statusValue = $s->payment_status instanceof SalesPaymentStatus
                ? $s->payment_status->value
                : (string) $s->payment_status;
            return [
                'id' => (string) $s->id,
                'receipt_number' => (string) ($s->receipt_number ?? ''),
                'invoice_number' => (string) ($s->invoice_number ?? ''),
                'sale_datetime' => $s->sale_datetime ? (string) $s->sale_datetime->toDateTimeString() : null,
                'warehouse' => $s->warehouse ? [
                    'id' => (string) $s->warehouse->id,
                    'name' => (string) $s->warehouse->name,
                ] : ['id' => null, 'name' => null],
                'customer' => $s->customer ? [
                    'id' => (string) $s->customer->id,
                    'name' => (string) $s->customer->name,
                    'phone' => $s->customer->phone !== null ? (string) $s->customer->phone : null,
                ] : ['id' => null, 'name' => null, 'phone' => null],
                'payment_status' => $statusValue,
                'payment_status_label' => SalesPaymentStatus::tryFrom($statusValue)?->label() ?? 'Tidak Diketahui',
                'grand_total' => (int) $s->grand_total,
            ];
        })->all();

        $warehouses = Warehouse::query()->orderBy('name')->get(['id', 'name']);

        $baseQuery = Sales::query()
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('receipt_number', 'ilike', "%{$q}%")
                        ->orWhere('invoice_number', 'ilike', "%{$q}%")
                        ->orWhere('customer_name', 'ilike', "%{$q}%");
                });
            })
            ->when($warehouseId !== '', function ($builder) use ($warehouseId) {
                $builder->where('warehouse_id', $warehouseId);
            })
            ->when($saleDateFrom !== '', function ($builder) use ($saleDateFrom) {
                $builder->whereDate('sale_datetime', '>=', $saleDateFrom);
            })
            ->when($saleDateTo !== '', function ($builder) use ($saleDateTo) {
                $builder->whereDate('sale_datetime', '<=', $saleDateTo);
            });

        $countRows = (clone $baseQuery)
            ->select(['payment_status', DB::raw('COUNT(*) AS aggregate')])
            ->groupBy('payment_status')
            ->get()
            ->map(fn($r) => [
                'payment_status' => $r->payment_status instanceof SalesPaymentStatus ? (string) $r->payment_status->value : (string) $r->payment_status,
                'count' => (int) $r->aggregate,
            ]);

        $statusCounters = [];
        foreach (SalesPaymentStatus::cases() as $s) {
            $statusCounters[$s->value] = 0;
        }
        foreach ($countRows as $row) {
            $statusCounters[$row['payment_status']] = $row['count'];
        }
        $statusCounters[''] = (int) (clone $baseQuery)->count();

        return Inertia::render('Domains/Admin/Sales/Sales/Index', [
            'sales' => $items,
            'meta' => [
                'current_page' => $sales->currentPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
                'last_page' => $sales->lastPage(),
            ],
            'filters' => [
                'q' => $q,
                'payment_status' => $paymentStatus,
                'warehouse_id' => $warehouseId,
                'sale_date_from' => $saleDateFrom,
                'sale_date_to' => $saleDateTo,
            ],
            'statusOptions' => array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], SalesPaymentStatus::cases()),
            'warehouses' => $warehouses->map(fn($w) => [
                'id' => (string) $w->id,
                'name' => (string) $w->name,
            ]),
            'statusCounters' => $statusCounters,
        ]);
    }

    /**
     * Tampilkan detail transaksi penjualan.
     *
     * @param Sales $sales
     * @return Response
     */
    public function show(Sales $sales, SalesViewService $viewService): Response
    {
        $paymentMethods = PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($pm) => ['id' => (string) $pm->id, 'name' => (string) $pm->name]);

        return Inertia::render('Domains/Admin/Sales/Sales/Show', [
            'sale' => $viewService->buildSalePayload($sales, true),
            'payment_methods' => $paymentMethods,
            'returnReasonOptions' => array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], \App\Enums\SalesReturnReason::cases()),
            'returnResolutionOptions' => array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], \App\Enums\SalesReturnResolution::cases()),
        ]);
    }

    public function createDelivery(Sales $sales, Request $request, SalesService $service): RedirectResponse
    {
        try {
            $p = $request->validate([
                'recipient_name' => ['nullable', 'string', 'max:255'],
                'recipient_phone' => ['nullable', 'string', 'max:50'],
                'delivery_address' => ['nullable', 'string', 'max:1000'],
                'shipping_note' => ['nullable', 'string', 'max:1000'],
                'shipping_amount' => ['nullable', 'integer', 'min:0'],
            ]);
            $service->createManualDelivery(
                $sales,
                (string) ($p['recipient_name'] ?? ''),
                (string) ($p['recipient_phone'] ?? ''),
                (string) ($p['delivery_address'] ?? ''),
                (string) ($p['shipping_note'] ?? ''),
                isset($p['shipping_amount']) ? (int) $p['shipping_amount'] : null,
                (string) $request->user()->getKey(),
            );
            Inertia::flash('toast', [
                'message' => 'Surat Jalan berhasil dibuat',
                'type' => 'success',
            ]);
            return redirect()->route('sales.show', ['sales' => (string) $sales->id]);
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat Surat Jalan: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function settle(Sales $sales, SettleSalesPaymentRequest $request, SalesService $service): RedirectResponse
    {
        try {
            $rows = (array) ($request->validated()['payments'] ?? []);
            $payments = [];
            foreach ($rows as $row) {
                $dto = SalesPaymentData::fromArray($row);
                if ($dto !== null) {
                    $payments[] = $dto;
                }
            }
            if (!empty($payments)) {
                $service->receivePayments($sales, $payments, (string) $request->user()->getKey());
            }
            Inertia::flash('toast', [
                'message' => 'Pembayaran berhasil dicatat',
                'type' => 'success',
            ]);
            return redirect()->route('sales.show', ['sales' => (string) $sales->id]);
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal mencatat pembayaran: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function createReturn(Sales $sales, Request $request, SalesService $service): RedirectResponse
    {
        try {
            $p = $request->validate([
                'reason' => ['required', 'string', 'in:' . implode(',', \App\Enums\SalesReturnReason::values())],
                'resolution' => ['required', 'string', 'in:' . implode(',', \App\Enums\SalesReturnResolution::values())],
                'notes' => ['nullable', 'string', 'max:1000'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.sales_item_id' => ['required', 'string'],
                'items.*.quantity' => ['required', 'integer', 'min:1'],
                'items.*.notes' => ['nullable', 'string', 'max:1000'],
            ]);
            $items = (array) ($p['items'] ?? []);
            if (empty($items)) {
                throw new \InvalidArgumentException('Items required');
            }
            $service->createReturn(
                $sales,
                $items,
                (string) $p['reason'],
                (string) $p['resolution'],
                isset($p['notes']) ? (string) $p['notes'] : null,
                (string) $request->user()->getAuthIdentifier()
            );
            Inertia::flash('toast', [
                'message' => 'Retur berhasil dibuat',
                'type' => 'success',
            ]);
            return redirect()->route('sales.show', ['sales' => (string) $sales->id]);
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat retur: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }
}
