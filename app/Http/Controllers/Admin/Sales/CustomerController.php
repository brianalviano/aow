<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\{Customer, Sales, User, Setting};
use App\Enums\CustomerSource;
use App\Http\Resources\CustomerResource;
use App\Http\Requests\Customer\{StoreCustomerRequest, UpdateCustomerRequest, ImportCustomerRequest};
use App\DTOs\Customer\CustomerData;
use App\Services\CustomerService;
use App\Services\SalesViewService;
use App\Exports\{CustomersExport, CustomersImportTemplateExport};
use App\Enums\RoleName;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->string('q')->toString();
        $isActive = (string) $request->string('is_active')->toString();
        $lastFrom = (string) $request->string('last_transaction_from')->toString();
        $lastTo = (string) $request->string('last_transaction_to')->toString();

        $query = Customer::query()
            ->with('marketers')
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%")
                        ->orWhere('email', 'ilike', "%{$q}%")
                        ->orWhere('phone', 'ilike', "%{$q}%")
                        ->orWhere('address', 'ilike', "%{$q}%");
                });
            })
            ->when($isActive !== '', function ($builder) use ($isActive) {
                $builder->where('is_active', $isActive === '1' || $isActive === 'true');
            })
            ->when($lastFrom !== '', function ($builder) use ($lastFrom) {
                $builder->whereDate('last_transaction_at', '>=', $lastFrom);
            })
            ->when($lastTo !== '', function ($builder) use ($lastTo) {
                $builder->whereDate('last_transaction_at', '<=', $lastTo);
            })
            ->orderBy('name');

        $perPage = (int) $request->integer('per_page', 10);
        $customers = $query->paginate($perPage)->appends([
            'q' => $q,
            'is_active' => $isActive,
            'last_transaction_from' => $lastFrom,
            'last_transaction_to' => $lastTo,
        ]);
        $items = array_map(
            fn($c) => CustomerResource::make($c)->toArray($request),
            $customers->items(),
        );

        return Inertia::render('Domains/Admin/Sales/Customers/Index', [
            'customers' => $items,
            'meta' => [
                'current_page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'last_page' => $customers->lastPage(),
            ],
            'filters' => [
                'q' => $q,
                'is_active' => $isActive,
                'last_transaction_from' => $lastFrom,
                'last_transaction_to' => $lastTo,
            ],
        ]);
    }

    public function create(): Response
    {
        $gf = (array) config('tomtom.geofence', []);
        $s = Setting::query()->first();
        $cfg = [
            'center_lat' => $s ? (float) $s->latitude : (float) ($gf['center_lat'] ?? 0),
            'center_long' => $s ? (float) $s->longitude : (float) ($gf['center_long'] ?? 0),
            'radius_m' => (int) ($gf['radius_m'] ?? 100),
            'tomtom_key' => (string) config('tomtom.api_key', ''),
            'tomtom_sdk_base' => (string) config('tomtom.sdk_cdn_base', 'https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.5.0'),
        ];
        $marketers = User::query()
            ->whereHas('role', function ($q) {
                $q->where('name', RoleName::Marketing->value);
            })
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($u) => ['id' => (string) $u->getKey(), 'name' => (string) $u->name])
            ->toArray();

        return Inertia::render('Domains/Admin/Sales/Customers/Form', [
            'customer' => null,
            'marketers' => $marketers,
            'geofence' => $cfg,
        ]);
    }

    public function store(StoreCustomerRequest $request, CustomerService $service): RedirectResponse
    {
        try {
            $service->create(CustomerData::fromStoreRequest(
                $request,
                (string) $request->user()->id,
                CustomerSource::Marketing->value
            ));
            Inertia::flash('toast', [
                'message' => 'Customer berhasil dibuat',
                'type' => 'success',
            ]);
            return redirect()->route('customers.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat customer: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function show(Customer $customer, Request $request, SalesViewService $viewService): Response
    {
        $paymentStatus = (string) $request->string('payment_status')->toString();
        $saleDateFrom = (string) $request->string('sale_date_from')->toString();
        $saleDateTo = (string) $request->string('sale_date_to')->toString();

        $query = Sales::query()
            ->where('customer_id', (string) $customer->id)
            ->when($paymentStatus !== '', function ($builder) use ($paymentStatus) {
                $builder->where('payment_status', $paymentStatus);
            })
            ->when($saleDateFrom !== '', function ($builder) use ($saleDateFrom) {
                $builder->whereDate('sale_datetime', '>=', $saleDateFrom);
            })
            ->when($saleDateTo !== '', function ($builder) use ($saleDateTo) {
                $builder->whereDate('sale_datetime', '<=', $saleDateTo);
            })
            ->orderByDesc('sale_datetime')
            ->orderByDesc('created_at');

        $baseQuery = Sales::query()
            ->where('customer_id', (string) $customer->id)
            ->when($paymentStatus !== '', function ($builder) use ($paymentStatus) {
                $builder->where('payment_status', $paymentStatus);
            })
            ->when($saleDateFrom !== '', function ($builder) use ($saleDateFrom) {
                $builder->whereDate('sale_datetime', '>=', $saleDateFrom);
            })
            ->when($saleDateTo !== '', function ($builder) use ($saleDateTo) {
                $builder->whereDate('sale_datetime', '<=', $saleDateTo);
            });

        $perPage = (int) $request->integer('per_page', 10);
        $salesPaginated = $query->paginate($perPage)->appends([
            'payment_status' => $paymentStatus,
            'sale_date_from' => $saleDateFrom,
            'sale_date_to' => $saleDateTo,
        ]);
        $items = collect($salesPaginated->items())->map(function ($s) use ($viewService) {
            $statusValue = $s->payment_status instanceof \App\Enums\SalesPaymentStatus
                ? $s->payment_status->value
                : (string) $s->payment_status;
            $paymentsInfo = $viewService->getPaymentsInfo((string) $s->id, (int) $s->grand_total);
            return [
                'id' => (string) $s->id,
                'receipt_number' => (string) ($s->receipt_number ?? ''),
                'invoice_number' => (string) ($s->invoice_number ?? ''),
                'sale_datetime' => $s->sale_datetime ? (string) $s->sale_datetime->toDateTimeString() : null,
                'grand_total' => (int) $s->grand_total,
                'outstanding_amount' => (int) $s->outstanding_amount,
                'payment_status' => $statusValue,
                'payment_status_label' => \App\Enums\SalesPaymentStatus::tryFrom($statusValue)?->label() ?? 'Tidak Diketahui',
                'payment_total' => (int) $paymentsInfo['payment_total'],
                'shortage_amount' => (int) $paymentsInfo['shortage_amount'],
            ];
        })->all();
        $totalOutstanding = (int) (clone $baseQuery)->sum('outstanding_amount');

        $customer->load(['marketers', 'createdBy']);

        return Inertia::render('Domains/Admin/Sales/Customers/Show', [
            'customer' => CustomerResource::make($customer)->toArray(request()),
            'sales' => $items,
            'sales_total_outstanding' => $totalOutstanding,
            'meta' => [
                'current_page' => $salesPaginated->currentPage(),
                'per_page' => $salesPaginated->perPage(),
                'total' => $salesPaginated->total(),
                'last_page' => $salesPaginated->lastPage(),
            ],
            'filters' => [
                'payment_status' => $paymentStatus,
                'sale_date_from' => $saleDateFrom,
                'sale_date_to' => $saleDateTo,
                'per_page' => $perPage,
            ],
            'statusOptions' => array_map(
                fn($s) => ['value' => $s->value, 'label' => $s->label()],
                \App\Enums\SalesPaymentStatus::cases()
            ),
        ]);
    }

    public function edit(Customer $customer): Response
    {
        $gf = (array) config('tomtom.geofence', []);
        $s = Setting::query()->first();
        $cfg = [
            'center_lat' => $s ? (float) $s->latitude : (float) ($gf['center_lat'] ?? 0),
            'center_long' => $s ? (float) $s->longitude : (float) ($gf['center_long'] ?? 0),
            'radius_m' => (int) ($gf['radius_m'] ?? 100),
            'tomtom_key' => (string) config('tomtom.api_key', ''),
            'tomtom_sdk_base' => (string) config('tomtom.sdk_cdn_base', 'https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.5.0'),
        ];
        $customer->load('marketers');
        $marketers = User::query()
            ->whereHas('role', function ($q) {
                $q->where('name', RoleName::Marketing->value);
            })
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($u) => ['id' => (string) $u->getKey(), 'name' => (string) $u->name])
            ->toArray();

        return Inertia::render('Domains/Admin/Sales/Customers/Form', [
            'customer' => CustomerResource::make($customer)->toArray(request()),
            'marketers' => $marketers,
            'geofence' => $cfg,
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer, CustomerService $service): RedirectResponse
    {
        try {
            $service->update($customer, CustomerData::fromUpdateRequest(
                $request,
                (string) $request->user()->id,
                $customer->source instanceof \App\Enums\CustomerSource ? $customer->source->value : (string) $customer->source
            ));
            Inertia::flash('toast', [
                'message' => 'Customer berhasil diperbarui',
                'type' => 'success',
            ]);
            return redirect()->route('customers.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui customer: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function destroy(Customer $customer, CustomerService $service): RedirectResponse
    {
        try {
            $service->delete($customer);
            Inertia::flash('toast', [
                'message' => 'Customer berhasil dihapus',
                'type' => 'success',
            ]);
            return redirect()->route('customers.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus customer: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return redirect()->route('customers.index');
        }
    }

    public function export(Request $request): BinaryFileResponse
    {
        $q = (string) $request->string('q')->toString();
        return Excel::download(new CustomersExport($q), 'customers.xlsx');
    }

    public function import(ImportCustomerRequest $request, CustomerService $service): RedirectResponse
    {
        $file = $request->file('file');
        Excel::import(new \App\Imports\CustomersImport($service, (string) $request->user()->id), $file);
        return redirect()->route('customers.index');
    }

    public function importTemplate(): BinaryFileResponse
    {
        return Excel::download(new CustomersImportTemplateExport(), 'customers_import_template.xlsx');
    }
}
