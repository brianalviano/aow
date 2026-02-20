<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Logistic;

use App\DTOs\Warehouse\WarehouseData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseRequest;
use App\Http\Requests\Warehouse\ImportWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use App\Exports\WarehousesExport;
use App\Exports\WarehousesImportTemplateExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->string('q')->toString();
        $isActive = (string) $request->string('is_active')->toString();

        $query = Warehouse::query()
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%")
                        ->orWhere('code', 'ilike', "%{$q}%")
                        ->orWhere('address', 'ilike', "%{$q}%")
                        ->orWhere('phone', 'ilike', "%{$q}%");
                });
            })
            ->when($isActive !== '', function ($builder) use ($isActive) {
                $builder->where('is_active', $isActive === '1' || $isActive === 'true');
            })
            ->orderBy('name');

        $perPage = (int) $request->integer('per_page', 10);
        $warehouses = $query->paginate($perPage)->appends([
            'q' => $q,
            'is_active' => $isActive,
        ]);
        $items = array_map(
            fn($w) => WarehouseResource::make($w)->toArray($request),
            $warehouses->items(),
        );

        return Inertia::render('Domains/Admin/Logistic/Warehouses/Index', [
            'warehouses' => $items,
            'meta' => [
                'current_page' => $warehouses->currentPage(),
                'per_page' => $warehouses->perPage(),
                'total' => $warehouses->total(),
                'last_page' => $warehouses->lastPage(),
            ],
            'filters' => [
                'q' => $q,
                'is_active' => $isActive,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Domains/Admin/Logistic/Warehouses/Form', [
            'warehouse' => null,
        ]);
    }

    public function store(StoreWarehouseRequest $request, WarehouseService $service): RedirectResponse
    {
        try {
            $service->create(WarehouseData::fromStoreRequest($request));
            Inertia::flash('toast', [
                'message' => 'Gudang berhasil dibuat',
                'type' => 'success',
            ]);
            return redirect()->route('warehouses.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat gudang: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function show(Warehouse $warehouse): Response
    {
        return Inertia::render('Domains/Admin/Logistic/Warehouses/Show', [
            'warehouse' => WarehouseResource::make($warehouse)->toArray(request()),
        ]);
    }

    public function edit(Warehouse $warehouse): Response
    {
        return Inertia::render('Domains/Admin/Logistic/Warehouses/Form', [
            'warehouse' => WarehouseResource::make($warehouse)->toArray(request()),
        ]);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse, WarehouseService $service): RedirectResponse
    {
        try {
            $service->update($warehouse, WarehouseData::fromUpdateRequest($request));
            Inertia::flash('toast', [
                'message' => 'Gudang berhasil diperbarui',
                'type' => 'success',
            ]);
            return redirect()->route('warehouses.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui gudang: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function destroy(Warehouse $warehouse, WarehouseService $service): RedirectResponse
    {
        try {
            $service->delete($warehouse);
            Inertia::flash('toast', [
                'message' => 'Gudang berhasil dihapus',
                'type' => 'success',
            ]);
            return redirect()->route('warehouses.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus gudang: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return redirect()->route('warehouses.index');
        }
    }

    public function export(Request $request): BinaryFileResponse
    {
        $q = (string) $request->string('q')->toString();
        $isActive = (string) $request->string('is_active')->toString();
        return Excel::download(new WarehousesExport($q, $isActive), 'warehouses.xlsx');
    }

    public function import(ImportWarehouseRequest $request, WarehouseService $service): RedirectResponse
    {
        $file = $request->file('file');
        Excel::import(new \App\Imports\WarehousesImport($service), $file);
        return redirect()->route('warehouses.index');
    }

    public function importTemplate(): BinaryFileResponse
    {
        return Excel::download(new WarehousesImportTemplateExport(), 'warehouses_import_template.xlsx');
    }
}
