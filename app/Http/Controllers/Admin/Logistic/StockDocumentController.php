<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Logistic;

use App\Http\Controllers\Controller;
use App\Models\{StockDocument, Warehouse, User, Product, Stock};
use App\Enums\{StockDocumentType, StockDocumentReason, StockBucket, StockDocumentStatus};
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\StockDocumentService;
use App\DTOs\StockDocument\{StockDocumentData, StockDocumentItemData};
use Throwable;
use App\Http\Requests\StockDocument\{StoreStockDocumentRequest, UpdateStockDocumentRequest};
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Http\Requests\StockDocument\ImportStockDocumentRequest;
use App\Exports\StockDocumentsExport;
use App\Exports\StockDocumentsInImportTemplateExport;
use App\Exports\StockDocumentsOutImportTemplateExport;
use App\Imports\StockDocumentsImport;

/**
 * Listing Dokumen Stok (IN/OUT) dengan filter via TAB.
 *
 * @author PJD
 *
 * @throws \Throwable Tidak melempar pada operasi baca ini; validasi dilakukan sederhana.
 */
class StockDocumentController extends Controller
{
    /**
     * Tampilkan daftar dokumen stok dengan filter tab: in/out.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $tab = (string) $request->string('tab')->toString();
        if (!in_array($tab, ['in', 'out'], true)) {
            $tab = 'in';
        }
        $type = $tab === 'in' ? StockDocumentType::In->value : StockDocumentType::Out->value;

        $q = (string) $request->string('q')->toString();
        $warehouseId = (string) $request->string('warehouse_id')->toString();
        $dateFrom = (string) $request->string('date_from')->toString();
        $dateTo = (string) $request->string('date_to')->toString();
        $bucket = (string) $request->string('bucket')->toString();
        $reason = (string) $request->string('reason')->toString();
        $perPage = (int) $request->integer('per_page', 25);

        $base = StockDocument::query()
            ->with(['warehouse:id,name', 'user:id,name'])
            ->where('type', $type)
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('number', 'ilike', "%{$q}%")
                        ->orWhere('notes', 'ilike', "%{$q}%");
                });
            })
            ->when($warehouseId !== '', fn($b) => $b->where('warehouse_id', $warehouseId))
            ->when($bucket !== '', fn($b) => $b->where('bucket', $bucket))
            ->when($reason !== '', fn($b) => $b->where('reason', $reason))
            ->when($dateFrom !== '', fn($b) => $b->where('document_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn($b) => $b->where('document_date', '<=', $dateTo))
            ->orderByDesc('document_date')
            ->orderByDesc('created_at');

        $documents = $base->paginate($perPage)->appends([
            'q' => $q,
            'tab' => $tab,
            'warehouse_id' => $warehouseId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'bucket' => $bucket,
            'reason' => $reason,
            'per_page' => $perPage,
        ]);

        $items = collect($documents->items())->map(function (StockDocument $doc) {
            return [
                'id' => (string) $doc->getKey(),
                'number' => (string) $doc->number,
                'document_date' => $doc->document_date ? (string) $doc->document_date->format('Y-m-d') : null,
                'type' => (string) (is_string($doc->type) ? $doc->type : $doc->type->value),
                'reason' => (string) (is_string($doc->reason) ? $doc->reason : $doc->reason->value),
                'status' => (string) (is_string($doc->status) ? $doc->status : $doc->status->value),
                'status_label' => (string) \App\Enums\StockDocumentStatus::from(
                    (string) (is_string($doc->status) ? $doc->status : $doc->status->value)
                )->label(),
                'bucket' => $doc->bucket instanceof StockBucket ? (string) $doc->bucket->value : ((string) ($doc->bucket ?? '')),
                'warehouse' => [
                    'id' => (string) ($doc->warehouse?->getKey() ?? ''),
                    'name' => (string) ($doc->warehouse?->name ?? ''),
                ],
                'user' => [
                    'id' => (string) ($doc->user?->getKey() ?? ''),
                    'name' => (string) ($doc->user?->name ?? ''),
                ],
                'notes' => $doc->notes ? (string) $doc->notes : null,
            ];
        })->toArray();

        $countIn = (clone $base)->withoutGlobalScopes()->where('type', StockDocumentType::In->value)->count();
        $countOut = (clone $base)->withoutGlobalScopes()->where('type', StockDocumentType::Out->value)->count();

        $warehouses = Warehouse::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($w) => ['id' => (string) $w->getKey(), 'name' => (string) $w->name])
            ->toArray();

        $reasonOptions = StockDocumentReason::values();
        $bucketOptions = StockBucket::values();

        return Inertia::render('Domains/Admin/Logistic/StockDocuments/Index', [
            'documents' => $items,
            'meta' => [
                'current_page' => $documents->currentPage(),
                'per_page' => $documents->PerPage(),
                'total' => $documents->total(),
                'last_page' => $documents->lastPage(),
            ],
            'filters' => [
                'tab' => $tab,
                'q' => $q,
                'warehouse_id' => $warehouseId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'bucket' => $bucket,
                'reason' => $reason,
                'per_page' => $perPage,
            ],
            'tabCounters' => [
                'in' => (int) $countIn,
                'out' => (int) $countOut,
            ],
            'warehouses' => $warehouses,
            'reasonOptions' => $reasonOptions,
            'bucketOptions' => $bucketOptions,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $tab = (string) $request->string('tab')->toString();
        if (!in_array($tab, ['in', 'out'], true)) {
            $tab = 'in';
        }
        $q = (string) $request->string('q')->toString();
        $warehouseId = (string) $request->string('warehouse_id')->toString();
        $dateFrom = (string) $request->string('date_from')->toString();
        $dateTo = (string) $request->string('date_to')->toString();
        $bucket = (string) $request->string('bucket')->toString();
        $reason = (string) $request->string('reason')->toString();
        return Excel::download(
            new StockDocumentsExport($tab, $q, $warehouseId, $dateFrom, $dateTo, $bucket, $reason),
            'stock_documents.xlsx'
        );
    }

    public function import(ImportStockDocumentRequest $request, StockDocumentService $service): RedirectResponse
    {
        try {
            $file = $request->file('file');
            $type = (string) $request->input('type');
            $reason = (string) $request->input('reason');
            $bucket = (string) $request->input('bucket', '');
            $notes = (string) $request->input('notes', '');
            $warehouseId = (string) $request->input('warehouse_id');
            Excel::import(
                new StockDocumentsImport($service, (string) $request->user()?->id, $type, $reason, $bucket, $notes, $warehouseId),
                $file
            );
            Inertia::flash('toast', [
                'message' => 'Import dokumen stok berhasil diproses',
                'type' => 'success',
            ]);
            return redirect()->route('stock-documents.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal import dokumen stok: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function importInTemplate(): BinaryFileResponse
    {
        return Excel::download(new StockDocumentsInImportTemplateExport(), 'stock_documents_in_import_template.xlsx');
    }

    public function importOutTemplate(): BinaryFileResponse
    {
        return Excel::download(new StockDocumentsOutImportTemplateExport(), 'stock_documents_out_import_template.xlsx');
    }

    /**
     * Form pembuatan Dokumen Stok manual.
     *
     * @return Response
     */
    public function create(): Response
    {
        $warehouses = Warehouse::query()->orderBy('name')->get(['id', 'name', 'address', 'phone']);
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);
        $stockRows = Stock::query()
            ->select(['warehouse_id', 'product_id', DB::raw('SUM(quantity) as quantity')])
            ->groupBy(['warehouse_id', 'product_id'])
            ->get();
        return Inertia::render('Domains/Admin/Logistic/StockDocuments/Form', [
            'stock_document' => null,
            'warehouses' => $warehouses->map(fn($w) => [
                'id' => (string) $w->id,
                'name' => (string) $w->name,
                'address' => (string) ($w->address ?? ''),
                'phone' => (string) ($w->phone ?? ''),
            ]),
            'products' => $products->map(fn($p) => ['id' => (string) $p->id, 'name' => (string) $p->name, 'sku' => (string) ($p->sku ?? '')]),
            'stocks' => $stockRows->map(fn($s) => [
                'warehouse_id' => (string) $s->warehouse_id,
                'product_id' => (string) $s->product_id,
                'quantity' => (int) $s->quantity,
            ]),
            'typeOptions' => array_map(
                fn($t) => [
                    'value' => $t->value,
                    'label' => $t->label(),
                ],
                StockDocumentType::cases()
            ),
            'reasonOptions' => array_map(
                fn($r) => [
                    'value' => $r->value,
                    'label' => $r->label(),
                ],
                StockDocumentReason::cases()
            ),
            'reasonOptionsByType' => [
                'in' => array_map(
                    fn($r) => ['value' => $r->value, 'label' => $r->label()],
                    StockDocumentReason::forType(StockDocumentType::In)
                ),
                'out' => array_map(
                    fn($r) => ['value' => $r->value, 'label' => $r->label()],
                    StockDocumentReason::forType(StockDocumentType::Out)
                ),
            ],
            'bucketOptions' => array_map(fn($b) => ['value' => $b->value, 'label' => $b->label()], StockBucket::cases()),
            'default_document_date' => now()->toDateString(),
        ]);
    }

    /**
     * Simpan Dokumen Stok manual (menulis stok dan kartu stok).
     *
     * @param StoreStockDocumentRequest $request
     * @param StockDocumentService $service
     * @return RedirectResponse
     */
    public function store(StoreStockDocumentRequest $request, StockDocumentService $service): RedirectResponse
    {
        try {
            $p = $request->validated();
            $dto = new StockDocumentData(
                warehouseId: (string) $p['warehouse_id'],
                type: StockDocumentType::from((string) $p['type']),
                reason: StockDocumentReason::from((string) $p['reason']),
                userId: (string) $request->user()?->getAuthIdentifier(),
                documentDate: (string) $p['document_date'],
                number: isset($p['number']) && $p['number'] !== '' ? (string) $p['number'] : null,
                sourceableType: isset($p['sourceable_type']) && $p['sourceable_type'] !== '' ? (string) $p['sourceable_type'] : null,
                sourceableId: isset($p['sourceable_id']) && $p['sourceable_id'] !== '' ? (string) $p['sourceable_id'] : null,
                bucket: isset($p['bucket']) && $p['bucket'] !== '' ? StockBucket::from((string) $p['bucket']) : null,
                notes: isset($p['notes']) && $p['notes'] !== '' ? (string) $p['notes'] : null,
                status: isset($p['status']) && $p['status'] !== '' ? StockDocumentStatus::from((string) $p['status']) : null,
            );
            $items = array_map(function (array $row) {
                return new StockDocumentItemData(
                    productId: (string) $row['product_id'],
                    quantity: (int) $row['quantity'],
                    unitPrice: isset($row['unit_price']) ? (int) $row['unit_price'] : null,
                    notes: isset($row['notes']) ? (string) $row['notes'] : null,
                    productDivisionId: isset($row['product_division_id']) && $row['product_division_id'] !== '' ? (string) $row['product_division_id'] : null,
                    productRackId: isset($row['product_rack_id']) && $row['product_rack_id'] !== '' ? (string) $row['product_rack_id'] : null,
                    ownerUserId: isset($row['owner_user_id']) && $row['owner_user_id'] !== '' ? (string) $row['owner_user_id'] : null,
                );
            }, (array) $p['items']);
            $doc = $service->createManualStockDocument($dto, $items);
            Inertia::flash('toast', [
                'message' => 'Dokumen Stok dibuat: ' . ($doc->number ?? ''),
                'type' => 'success',
            ]);
            return redirect()->route('stock-documents.show', $doc->getKey());
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat Dokumen Stok: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    /**
     * Tampilkan detail Dokumen Stok.
     *
     * @param StockDocument $stockDocument
     * @return Response
     */
    public function show(StockDocument $stockDocument): Response
    {
        $stockDocument->load(['warehouse', 'user', 'items.product']);
        $typeValue = is_string($stockDocument->type) ? (string) $stockDocument->type : (string) $stockDocument->type->value;
        $reasonValue = is_string($stockDocument->reason) ? (string) $stockDocument->reason : (string) $stockDocument->reason->value;
        $typeLabel = \App\Enums\StockDocumentType::from($typeValue)->label();
        $reasonLabel = \App\Enums\StockDocumentReason::from($reasonValue)->label();

        $warehouseId = (string) $stockDocument->warehouse_id;
        $productIds = $stockDocument->items->pluck('product_id')->unique()->values()->all();
        $stockRows = \App\Models\Stock::query()
            ->select(['warehouse_id', 'product_id', DB::raw('SUM(quantity) as quantity')])
            ->where('warehouse_id', $warehouseId)
            ->when(count($productIds) > 0, fn($b) => $b->whereIn('product_id', $productIds))
            ->groupBy(['warehouse_id', 'product_id'])
            ->get();

        return Inertia::render('Domains/Admin/Logistic/StockDocuments/Show', [
            'stock_document' => [
                'id' => (string) $stockDocument->getKey(),
                'number' => (string) $stockDocument->number,
                'document_date' => $stockDocument->document_date ? (string) $stockDocument->document_date->format('Y-m-d') : null,
                'type' => $typeValue,
                'type_label' => $typeLabel,
                'reason' => $reasonValue,
                'reason_label' => $reasonLabel,
                'status' => (string) (is_string($stockDocument->status) ? $stockDocument->status : $stockDocument->status->value),
                'status_label' => (string) \App\Enums\StockDocumentStatus::from((string) (is_string($stockDocument->status) ? $stockDocument->status : $stockDocument->status->value))->label(),
                'bucket' => $stockDocument->bucket instanceof StockBucket ? (string) $stockDocument->bucket->value : ((string) ($stockDocument->bucket ?? '')),
                'bucket_label' => (function () use ($stockDocument): string {
                    $val = $stockDocument->bucket instanceof StockBucket ? $stockDocument->bucket->value : ($stockDocument->bucket ?? '');
                    $valStr = is_string($val) ? $val : '';
                    return $valStr !== '' && StockBucket::tryFrom($valStr) ? StockBucket::from($valStr)->label() : '-';
                })(),
                'warehouse' => [
                    'id' => (string) ($stockDocument->warehouse?->getKey() ?? ''),
                    'name' => (string) ($stockDocument->warehouse?->name ?? ''),
                    'address' => (string) ($stockDocument->warehouse?->address ?? ''),
                    'phone' => (string) ($stockDocument->warehouse?->phone ?? ''),
                ],
                'user' => [
                    'id' => (string) ($stockDocument->user?->getKey() ?? ''),
                    'name' => (string) ($stockDocument->user?->name ?? ''),
                ],
                'notes' => $stockDocument->notes ? (string) $stockDocument->notes : null,
                'items' => $stockDocument->items->map(fn($it) => [
                    'id' => (string) $it->getKey(),
                    'product' => [
                        'id' => (string) $it->product_id,
                        'name' => (string) ($it->product?->name ?? ''),
                        'sku' => (string) ($it->product?->sku ?? ''),
                    ],
                    'quantity' => (int) $it->quantity,
                    'unit_price' => (int) $it->unit_price,
                    'subtotal' => (int) $it->subtotal,
                    'owner_user_id' => $it->owner_user_id ? (string) $it->owner_user_id : null,
                    'notes' => $it->notes ? (string) $it->notes : null,
                ]),
            ],
            'stocks' => $stockRows->map(fn($s) => [
                'warehouse_id' => (string) $s->warehouse_id,
                'product_id' => (string) $s->product_id,
                'quantity' => (int) $s->quantity,
            ]),
        ]);
    }

    /**
     * Form edit header Dokumen Stok (metadata saja).
     *
     * @param StockDocument $stockDocument
     * @return Response
     */
    public function edit(StockDocument $stockDocument): Response
    {
        $stockDocument->load(['warehouse', 'user', 'items.product']);
        $warehouses = Warehouse::query()->orderBy('name')->get(['id', 'name', 'address', 'phone']);
        $stockRows = Stock::query()
            ->select(['warehouse_id', 'product_id', DB::raw('SUM(quantity) as quantity')])
            ->groupBy(['warehouse_id', 'product_id'])
            ->get();
        return Inertia::render('Domains/Admin/Logistic/StockDocuments/Form', [
            'stock_document' => [
                'id' => (string) $stockDocument->getKey(),
                'number' => (string) ($stockDocument->number ?? ''),
                'document_date' => $stockDocument->document_date ? (string) $stockDocument->document_date->format('Y-m-d') : null,
                'type' => (string) (is_string($stockDocument->type) ? $stockDocument->type : $stockDocument->type->value),
                'reason' => (string) (is_string($stockDocument->reason) ? $stockDocument->reason : $stockDocument->reason->value),
                'bucket' => $stockDocument->bucket instanceof StockBucket ? (string) $stockDocument->bucket->value : ((string) ($stockDocument->bucket ?? '')),
                'warehouse' => [
                    'id' => (string) ($stockDocument->warehouse?->getKey() ?? ''),
                    'name' => (string) ($stockDocument->warehouse?->name ?? ''),
                    'address' => (string) ($stockDocument->warehouse?->address ?? ''),
                    'phone' => (string) ($stockDocument->warehouse?->phone ?? ''),
                ],
                'notes' => $stockDocument->notes ? (string) $stockDocument->notes : null,
                'items' => $stockDocument->items->map(fn($it) => [
                    'id' => (string) $it->getKey(),
                    'product_id' => (string) $it->product_id,
                    'quantity' => (int) $it->quantity,
                    'unit_price' => (int) $it->unit_price,
                    'owner_user_id' => $it->owner_user_id ? (string) $it->owner_user_id : null,
                    'notes' => $it->notes ? (string) $it->notes : null,
                ]),
            ],
            'warehouses' => $warehouses->map(fn($w) => [
                'id' => (string) $w->id,
                'name' => (string) $w->name,
                'address' => (string) ($w->address ?? ''),
                'phone' => (string) ($w->phone ?? ''),
            ]),
            'stocks' => $stockRows->map(fn($s) => [
                'warehouse_id' => (string) $s->warehouse_id,
                'product_id' => (string) $s->product_id,
                'quantity' => (int) $s->quantity,
            ]),
            'typeOptions' => array_map(
                fn($t) => [
                    'value' => $t->value,
                    'label' => $t->label(),
                ],
                StockDocumentType::cases()
            ),
            'reasonOptions' => array_map(
                fn($r) => [
                    'value' => $r->value,
                    'label' => $r->label(),
                ],
                StockDocumentReason::cases()
            ),
            'reasonOptionsByType' => [
                'in' => array_map(
                    fn($r) => ['value' => $r->value, 'label' => $r->label()],
                    StockDocumentReason::forType(StockDocumentType::In)
                ),
                'out' => array_map(
                    fn($r) => ['value' => $r->value, 'label' => $r->label()],
                    StockDocumentReason::forType(StockDocumentType::Out)
                ),
            ],
            'bucketOptions' => array_map(fn($b) => ['value' => $b->value, 'label' => $b->label()], StockBucket::cases()),
        ]);
    }

    /**
     * Update metadata Dokumen Stok (tidak mengubah pergerakan stok).
     *
     * @param UpdateStockDocumentRequest $request
     * @param StockDocument $stockDocument
     * @return RedirectResponse
     */
    public function update(UpdateStockDocumentRequest $request, StockDocument $stockDocument): RedirectResponse
    {
        try {
            $p = $request->validated();
            $updates = [
                'number' => isset($p['number']) && $p['number'] !== '' ? (string) $p['number'] : $stockDocument->number,
                'document_date' => (string) $p['document_date'],
                'notes' => isset($p['notes']) && $p['notes'] !== '' ? (string) $p['notes'] : null,
            ];
            $stockDocument->update($updates);
            Inertia::flash('toast', [
                'message' => 'Dokumen Stok diperbarui',
                'type' => 'success',
            ]);
            return redirect()->route('stock-documents.show', $stockDocument->getKey());
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui Dokumen Stok: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    /**
     * Cetak Dokumen Stok.
     *
     * @param StockDocument $stockDocument
     * @return Response
     */
    public function print(StockDocument $stockDocument): Response
    {
        $stockDocument->load(['warehouse', 'user', 'items.product']);
        $typeValue = is_string($stockDocument->type) ? (string) $stockDocument->type : (string) $stockDocument->type->value;
        $reasonValue = is_string($stockDocument->reason) ? (string) $stockDocument->reason : (string) $stockDocument->reason->value;
        $typeLabel = \App\Enums\StockDocumentType::from($typeValue)->label();
        $reasonLabel = \App\Enums\StockDocumentReason::from($reasonValue)->label();
        $warehouseId = (string) $stockDocument->warehouse_id;
        $productIds = $stockDocument->items->pluck('product_id')->unique()->values()->all();
        $stockRows = \App\Models\Stock::query()
            ->select(['warehouse_id', 'product_id', DB::raw('SUM(quantity) as quantity')])
            ->where('warehouse_id', $warehouseId)
            ->when(count($productIds) > 0, fn($b) => $b->whereIn('product_id', $productIds))
            ->groupBy(['warehouse_id', 'product_id'])
            ->get();
        return Inertia::render('Domains/Admin/Logistic/StockDocuments/Print', [
            'stock_document' => [
                'id' => (string) $stockDocument->getKey(),
                'number' => (string) $stockDocument->number,
                'document_date' => $stockDocument->document_date ? (string) $stockDocument->document_date->format('Y-m-d') : null,
                'type' => $typeValue,
                'type_label' => $typeLabel,
                'reason' => $reasonValue,
                'reason_label' => $reasonLabel,
                'status' => (string) (is_string($stockDocument->status) ? $stockDocument->status : $stockDocument->status->value),
                'status_label' => (string) \App\Enums\StockDocumentStatus::from((string) (is_string($stockDocument->status) ? $stockDocument->status : $stockDocument->status->value))->label(),
                'bucket' => $stockDocument->bucket instanceof StockBucket ? (string) $stockDocument->bucket->value : ((string) ($stockDocument->bucket ?? '')),
                'bucket_label' => (function () use ($stockDocument): string {
                    $val = $stockDocument->bucket instanceof StockBucket ? $stockDocument->bucket->value : ($stockDocument->bucket ?? '');
                    $valStr = is_string($val) ? $val : '';
                    return $valStr !== '' && StockBucket::tryFrom($valStr) ? StockBucket::from($valStr)->label() : '-';
                })(),
                'warehouse' => [
                    'id' => (string) ($stockDocument->warehouse?->getKey() ?? ''),
                    'name' => (string) ($stockDocument->warehouse?->name ?? ''),
                    'address' => (string) ($stockDocument->warehouse?->address ?? ''),
                    'phone' => (string) ($stockDocument->warehouse?->phone ?? ''),
                ],
                'user' => [
                    'id' => (string) ($stockDocument->user?->getKey() ?? ''),
                    'name' => (string) ($stockDocument->user?->name ?? ''),
                ],
                'notes' => $stockDocument->notes ? (string) $stockDocument->notes : null,
                'items' => $stockDocument->items->map(fn($it) => [
                    'product' => [
                        'id' => (string) $it->product_id,
                        'name' => (string) ($it->product?->name ?? ''),
                        'sku' => (string) ($it->product?->sku ?? ''),
                    ],
                    'quantity' => (int) $it->quantity,
                    'unit_price' => (int) $it->unit_price,
                    'subtotal' => (int) $it->subtotal,
                ]),
            ],
            'stocks' => $stockRows->map(fn($s) => [
                'warehouse_id' => (string) $s->warehouse_id,
                'product_id' => (string) $s->product_id,
                'quantity' => (int) $s->quantity,
            ]),
        ]);
    }

    /**
     * Lanjutkan status Dokumen Stok.
     *
     * - Draft → PendingHoApproval (tanpa efek stok).
     * - PendingHoApproval → Completed (pergerakan stok dieksekusi).
     *
     * @param Request $request
     * @param StockDocument $stockDocument
     * @param StockDocumentService $service
     * @return RedirectResponse
     */
    public function advance(Request $request, StockDocument $stockDocument, StockDocumentService $service): RedirectResponse
    {
        try {
            $current = $stockDocument->status instanceof StockDocumentStatus
                ? $stockDocument->status
                : StockDocumentStatus::from((string) $stockDocument->status);
            $next = $service->computeNextStockDocumentStatus($current);
            if ($next === StockDocumentStatus::Completed) {
                $roleName = (string) ($request->user()?->role?->name ?? '');
                if (!in_array($roleName, ['Super Admin', 'Director'], true)) {
                    Inertia::flash('toast', [
                        'message' => 'Hanya role tertinggi yang diperbolehkan menyetujui HO',
                        'type' => 'error',
                    ]);
                    return back();
                }
            }
            $service->advanceStockDocumentStatus($stockDocument, (string) $request->user()?->getAuthIdentifier());
            $stockDocument->refresh();
            $label = $stockDocument->status instanceof StockDocumentStatus
                ? $stockDocument->status->label()
                : StockDocumentStatus::from((string) $stockDocument->status)->label();
            Inertia::flash('toast', [
                'message' => 'Status Dokumen Stok diperbarui: ' . $label,
                'type' => 'success',
            ]);
            return back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui status: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    /**
     * Tolak Dokumen Stok pada tahap HO.
     *
     * @param Request $request
     * @param StockDocument $stockDocument
     * @param StockDocumentService $service
     * @return RedirectResponse
     */
    public function rejectHo(Request $request, StockDocument $stockDocument, StockDocumentService $service): RedirectResponse
    {
        try {
            $roleName = (string) ($request->user()?->role?->name ?? '');
            if (!in_array($roleName, ['Super Admin', 'Director'], true)) {
                Inertia::flash('toast', [
                    'message' => 'Hanya role tertinggi yang diperbolehkan menolak pada tahap HO',
                    'type' => 'error',
                ]);
                return back();
            }
            $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
            $service->rejectStockDocumentByHo($stockDocument, (string) $validated['reason'], (string) $request->user()?->getAuthIdentifier());
            Inertia::flash('toast', [
                'message' => 'Dokumen Stok ditolak oleh HO',
                'type' => 'success',
            ]);
            return back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menolak Dokumen Stok: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    /**
     * Penghapusan Dokumen Stok dinonaktifkan karena mempengaruhi saldo stok.
     *
     * @param StockDocument $stockDocument
     * @return RedirectResponse
     */
    public function destroy(StockDocument $stockDocument): RedirectResponse
    {
        Inertia::flash('toast', [
            'message' => 'Dokumen Stok tidak dapat dihapus karena mempengaruhi pergerakan stok.',
            'type' => 'error',
        ]);
        return redirect()->route('stock-documents.index');
    }
}
