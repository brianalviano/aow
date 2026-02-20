<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Purchasing;

use App\DTOs\Supplier\SupplierData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Requests\Supplier\ImportSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\{Supplier, PurchaseOrder, PurchaseReturn};
use App\Services\SupplierService;
use App\Exports\SuppliersExport;
use App\Exports\SuppliersImportTemplateExport;
use App\Enums\{PurchaseOrderStatus, PurchaseReturnStatus};
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;
use Maatwebsite\Excel\Facades\Excel;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->string('q')->toString();

        $query = Supplier::query()
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%")
                        ->orWhere('email', 'ilike', "%{$q}%")
                        ->orWhere('phone', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('name');

        $perPage = (int) $request->integer('per_page', 10);
        $suppliers = $query->paginate($perPage)->appends([
            'q' => $q,
        ]);
        $items = array_map(
            fn($s) => SupplierResource::make($s)->toArray($request),
            $suppliers->items(),
        );

        return Inertia::render('Domains/Admin/Purchasing/Suppliers/Index', [
            'suppliers' => $items,
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
                'last_page' => $suppliers->lastPage(),
            ],
            'filters' => [
                'q' => $q,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Domains/Admin/Purchasing/Suppliers/Form', [
            'supplier' => null,
        ]);
    }

    public function store(StoreSupplierRequest $request, SupplierService $service): RedirectResponse
    {
        try {
            $service->create(SupplierData::fromStoreRequest($request));
            Inertia::flash('toast', [
                'message' => 'Supplier berhasil dibuat',
                'type' => 'success',
            ]);
            return redirect()->route('suppliers.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat supplier: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    /**
     * Tampilkan detail Supplier beserta ringkasan riwayat PO dan Retur Pembelian.
     *
     * @param Supplier $supplier Supplier yang akan ditampilkan
     * @return Response Halaman detail supplier dengan riwayat transaksi terkait
     */
    public function show(Supplier $supplier): Response
    {
        $purchaseOrders = PurchaseOrder::query()
            ->where('supplier_id', $supplier->getKey())
            ->orderByDesc('order_date')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'number', 'order_date', 'status', 'grand_total']);
        $poItems = $purchaseOrders->map(function (PurchaseOrder $po): array {
            $statusValue = $po->status instanceof PurchaseOrderStatus
                ? (string) $po->status->value
                : (string) $po->status;
            $statusLabel = $po->status instanceof PurchaseOrderStatus
                ? $po->status->label()
                : PurchaseOrderStatus::from($statusValue)->label();
            return [
                'id' => (string) $po->id,
                'number' => (string) ($po->number ?? ''),
                'order_date' => $po->order_date ? (string) $po->order_date->format('Y-m-d') : null,
                'status' => $statusValue,
                'status_label' => $statusLabel,
                'grand_total' => (int) ($po->grand_total ?? 0),
            ];
        });

        $purchaseReturns = PurchaseReturn::query()
            ->with(['purchaseOrder:id,number'])
            ->where('supplier_id', $supplier->getKey())
            ->orderByDesc('return_date')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'number', 'purchase_order_id', 'return_date', 'status', 'credit_amount', 'refund_amount']);
        $prItems = $purchaseReturns->map(function (PurchaseReturn $pr): array {
            $statusValue = $pr->status instanceof PurchaseReturnStatus
                ? (string) $pr->status->value
                : (string) $pr->status;
            $statusLabel = $pr->status instanceof PurchaseReturnStatus
                ? $pr->status->label()
                : PurchaseReturnStatus::from($statusValue)->label();
            return [
                'id' => (string) $pr->id,
                'number' => (string) ($pr->number ?? ''),
                'purchase_order' => [
                    'id' => (string) ($pr->purchase_order_id ?? ''),
                    'number' => (string) ($pr->purchaseOrder?->number ?? ''),
                ],
                'return_date' => $pr->return_date ? (string) $pr->return_date->format('Y-m-d') : null,
                'status' => $statusValue,
                'status_label' => $statusLabel,
                'credit_amount' => (int) ($pr->credit_amount ?? 0),
                'refund_amount' => (int) ($pr->refund_amount ?? 0),
            ];
        });

        return Inertia::render('Domains/Admin/Purchasing/Suppliers/Show', [
            'supplier' => SupplierResource::make($supplier)->toArray(request()),
            'purchase_orders' => $poItems,
            'purchase_returns' => $prItems,
        ]);
    }

    public function edit(Supplier $supplier): Response
    {
        return Inertia::render('Domains/Admin/Purchasing/Suppliers/Form', [
            'supplier' => SupplierResource::make($supplier)->toArray(request()),
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier, SupplierService $service): RedirectResponse
    {
        try {
            $service->update($supplier, SupplierData::fromUpdateRequest($request));
            Inertia::flash('toast', [
                'message' => 'Supplier berhasil diperbarui',
                'type' => 'success',
            ]);
            return redirect()->route('suppliers.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui supplier: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function destroy(Supplier $supplier, SupplierService $service): RedirectResponse
    {
        try {
            $service->delete($supplier);
            Inertia::flash('toast', [
                'message' => 'Supplier berhasil dihapus',
                'type' => 'success',
            ]);
            return redirect()->route('suppliers.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus supplier: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return redirect()->route('suppliers.index');
        }
    }

    public function export(Request $request): BinaryFileResponse
    {
        $q = (string) $request->string('q')->toString();
        return Excel::download(new SuppliersExport($q), 'suppliers.xlsx');
    }

    public function import(ImportSupplierRequest $request, SupplierService $service): RedirectResponse
    {
        $file = $request->file('file');
        Excel::import(new \App\Imports\SuppliersImport($service), $file);
        return redirect()->route('suppliers.index');
    }

    public function importTemplate(): BinaryFileResponse
    {
        return Excel::download(new SuppliersImportTemplateExport(), 'suppliers_import_template.xlsx');
    }
}
