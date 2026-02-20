<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Logistic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\{Auth, Gate};
use App\Models\{StockOpname, StockOpnameAssignment, StockOpnameItem, Warehouse, Product, User};
use App\Services\{StockOpnameService, StockService};
use App\Http\Requests\StockOpname\{StoreStockOpnameRequest, SubmitStockOpnameCountRequest};
use App\Enums\{StockOpnameStatus, RoleName};
use Throwable;

class StockOpnameController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->string('q')->toString();
        $status = (string) $request->string('status')->toString();
        $warehouseId = (string) $request->string('warehouse_id')->toString();
        $dateFrom = (string) $request->string('scheduled_date_from')->toString();
        $dateTo = (string) $request->string('scheduled_date_to')->toString();
        $perPage = (int) $request->integer('per_page', 15);

        $base = StockOpname::query()
            ->with(['warehouse'])
            ->when($q !== '', function ($qBuilder) use ($q) {
                $qBuilder->where(function ($inner) use ($q) {
                    $inner->where('number', 'like', '%' . $q . '%');
                });
            })
            ->when($warehouseId !== '', fn($qb) => $qb->where('warehouse_id', $warehouseId))
            ->when($dateFrom !== '', fn($qb) => $qb->whereDate('scheduled_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn($qb) => $qb->whereDate('scheduled_date', '<=', $dateTo));

        $query = (clone $base)
            ->when($status !== '', fn($qb) => $qb->where('status', $status))
            ->orderByDesc('created_at');

        $opnames = $query->paginate($perPage)->appends([
            'q' => $q,
            'status' => $status,
            'warehouse_id' => $warehouseId,
            'scheduled_date_from' => $dateFrom,
            'scheduled_date_to' => $dateTo,
            'per_page' => $perPage,
        ]);

        $assignmentMap = [];
        $user = Auth::user();
        if ($user) {
            $ids = array_map(static fn($o) => (string) $o->getKey(), $opnames->items());
            if (!empty($ids)) {
                $assignmentMap = StockOpnameAssignment::query()
                    ->whereIn('stock_opname_id', $ids)
                    ->where('user_id', (string) $user->getKey())
                    ->pluck('id', 'stock_opname_id')
                    ->map(fn($id) => (string) $id)
                    ->toArray();
            }
        }

        $items = array_map(function ($o) use ($assignmentMap) {
            $statusEnum = $o->status;
            return [
                'id' => (string) $o->getKey(),
                'number' => (string) $o->number,
                'scheduled_date' => $o->scheduled_date ? (string) $o->scheduled_date->format('Y-m-d') : null,
                'status' => (string) $statusEnum->value,
                'status_label' => (string) $statusEnum->label(),
                'warehouse' => [
                    'id' => (string) $o->warehouse?->getKey(),
                    'name' => (string) ($o->warehouse?->name ?? ''),
                ],
                'my_assignment_id' => $assignmentMap[(string) $o->getKey()] ?? null,
            ];
        }, $opnames->items());

        $statusOptions = array_map(
            static fn(StockOpnameStatus $s) => ['value' => $s->value, 'label' => $s->label()],
            StockOpnameStatus::cases()
        );
        $groupedRows = (clone $base)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->get();
        $grouped = [];
        foreach ($groupedRows as $r) {
            $key = $r->status instanceof StockOpnameStatus ? (string) $r->status->value : (string) $r->status;
            $grouped[$key] = (int) $r->aggregate;
        }
        $totalCount = (clone $base)->count();
        $statusCounters = ['' => $totalCount];
        foreach (StockOpnameStatus::values() as $v) {
            $statusCounters[$v] = (int) ($grouped[$v] ?? 0);
        }

        $warehouses = Warehouse::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($w) => ['id' => (string) $w->getKey(), 'name' => (string) $w->name])
            ->toArray();

        return Inertia::render('Domains/Admin/Logistic/StockOpnames/Index', [
            'opnames' => $items,
            'filters' => [
                'q' => $q,
                'status' => $status,
                'warehouse_id' => $warehouseId,
                'scheduled_date_from' => $dateFrom,
                'scheduled_date_to' => $dateTo,
                'per_page' => $perPage,
            ],
            'meta' => [
                'current_page' => $opnames->currentPage(),
                'per_page' => $opnames->perPage(),
                'total' => $opnames->total(),
                'last_page' => $opnames->lastPage(),
            ],
            'statusOptions' => $statusOptions,
            'statusCounters' => $statusCounters,
            'warehouses' => $warehouses,
            'can_settle' => Gate::allows('logistic-master-manage'),
        ]);
    }

    public function create(Request $request): Response
    {
        $warehouses = Warehouse::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($w) => ['id' => (string) $w->getKey(), 'name' => (string) $w->name])
            ->toArray();

        $products = Product::query()
            ->select(['id', 'name', 'sku'])
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id' => (string) $p->getKey(),
                'name' => (string) $p->name,
                'sku' => $p->sku ? (string) $p->sku : null,
            ])
            ->toArray();

        $users = User::query()
            ->whereHas('role', function ($q) {
                $q->whereIn('name', RoleName::stockOpnameAssignable());
            })
            ->select(['id', 'name', 'email'])
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id' => (string) $u->getKey(),
                'name' => (string) $u->name,
                'email' => (string) $u->email,
            ])
            ->toArray();

        return Inertia::render('Domains/Admin/Logistic/StockOpnames/Form', [
            'warehouses' => $warehouses,
            'products' => $products,
            'users' => $users,
        ]);
    }

    public function store(StoreStockOpnameRequest $request, StockOpnameService $service): RedirectResponse
    {
        try {
            $createdById = (string) Auth::id();
            $opname = $service->createFromRequest($request, $createdById);
            Inertia::flash('toast', [
                'message' => 'Stock Opname berhasil dibuat: ' . $opname->number,
                'type' => 'success',
            ]);
            return redirect()->route('stock-opnames.show', ['stock_opname' => (string) $opname->getKey()]);
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat Stock Opname: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function show(StockOpname $stock_opname): Response
    {
        $stock_opname->load(['warehouse']);

        $assignments = StockOpnameAssignment::query()
            ->where('stock_opname_id', (string) $stock_opname->getKey())
            ->with(['user'])
            ->get()
            ->map(fn($a) => [
                'id' => (string) $a->getKey(),
                'status' => (string) $a->status->value,
                'status_label' => (string) $a->status->label(),
                'user' => [
                    'id' => (string) $a->user?->getKey(),
                    'name' => (string) ($a->user?->name ?? ''),
                    'email' => (string) ($a->user?->email ?? ''),
                ],
            ])
            ->toArray();

        $itemsByProduct = StockOpnameItem::query()
            ->where('stock_opname_id', (string) $stock_opname->getKey())
            ->with(['product', 'stockOpnameAssignment.user'])
            ->get()
            ->groupBy('product_id')
            ->map(function ($rows) {
                $any = $rows->first();
                $actualTotal = (int) $rows->sum('actual_quantity');
                $systemSnapshot = (int) ($any?->system_quantity ?? 0);
                $diff = $actualTotal - $systemSnapshot;
                $assignments = $rows->map(function ($r) {
                    return [
                        'assignment_id' => (string) $r->stock_opname_assignment_id,
                        'user' => [
                            'id' => (string) ($r->stockOpnameAssignment?->user?->getKey() ?? ''),
                            'name' => (string) ($r->stockOpnameAssignment?->user?->name ?? ''),
                        ],
                        'actual_quantity' => (int) $r->actual_quantity,
                        'counted_at' => $r->counted_at ? (string) $r->counted_at->toDateTimeString() : null,
                    ];
                })->values()->toArray();
                return [
                    'product' => [
                        'id' => (string) $any?->product_id,
                        'name' => (string) ($any?->product?->name ?? ''),
                        'sku' => $any?->product?->sku ? (string) $any->product->sku : null,
                    ],
                    'system_quantity' => $systemSnapshot,
                    'actual_total' => $actualTotal,
                    'difference' => $diff,
                    'hpp' => (int) ($any?->hpp ?? 0),
                    'subtotal' => (int) $rows->sum('subtotal'),
                    'assignments' => $assignments,
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Domains/Admin/Logistic/StockOpnames/Show', [
            'opname' => [
                'id' => (string) $stock_opname->getKey(),
                'number' => (string) $stock_opname->number,
                'scheduled_date' => $stock_opname->scheduled_date ? (string) $stock_opname->scheduled_date->format('Y-m-d') : null,
                'status' => (string) $stock_opname->status->value,
                'status_label' => (string) $stock_opname->status->label(),
                'grand_total' => (int) $stock_opname->grand_total,
                'warehouse' => [
                    'id' => (string) $stock_opname->warehouse?->getKey(),
                    'name' => (string) ($stock_opname->warehouse?->name ?? ''),
                ],
                'notes' => $stock_opname->notes ? (string) $stock_opname->notes : null,
            ],
            'assignments' => $assignments,
            'items_by_product' => $itemsByProduct,
            'can_settle' => Gate::allows('logistic-master-manage'),
        ]);
    }

    public function showAssignment(StockOpname $stock_opname, StockOpnameAssignment $assignment): Response
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }
        if ((string) $assignment->stock_opname_id !== (string) $stock_opname->getKey()) {
            abort(404);
        }
        if ((string) $assignment->user_id !== (string) $user->getKey() && !Gate::allows('logistic-master-manage')) {
            abort(403);
        }

        $items = StockOpnameItem::query()
            ->where('stock_opname_id', (string) $stock_opname->getKey())
            ->where('stock_opname_assignment_id', (string) $assignment->getKey())
            ->with(['product'])
            ->get()
            ->map(fn($it) => [
                'id' => (string) $it->getKey(),
                'product' => [
                    'id' => (string) $it->product_id,
                    'name' => (string) ($it->product?->name ?? ''),
                    'sku' => $it->product?->sku ? (string) $it->product->sku : null,
                ],
                'actual_quantity' => (int) $it->actual_quantity,
                'status' => (string) $it->status->value,
                'notes' => $it->notes ? (string) $it->notes : null,
            ])
            ->toArray();

        return Inertia::render('Domains/Admin/Logistic/StockOpnames/Assignment', [
            'opname' => [
                'id' => (string) $stock_opname->getKey(),
                'number' => (string) $stock_opname->number,
                'status' => (string) $stock_opname->status->value,
                'status_label' => (string) $stock_opname->status->label(),
            ],
            'assignment' => [
                'id' => (string) $assignment->getKey(),
                'status' => (string) $assignment->status->value,
                'status_label' => (string) $assignment->status->label(),
            ],
            'items' => $items,
        ]);
    }

    public function startAssignment(StockOpname $stock_opname, StockOpnameAssignment $assignment, StockOpnameService $service): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }
        if ((string) $assignment->stock_opname_id !== (string) $stock_opname->getKey()) {
            abort(404);
        }
        if ((string) $assignment->user_id !== (string) $user->getKey() && !Gate::allows('logistic-master-manage')) {
            abort(403);
        }
        try {
            $service->startOnAssignmentEntry($stock_opname, $assignment, (string) $user->getKey());
            return back();
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memulai penugasan: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function edit(StockOpname $stock_opname): Response|RedirectResponse
    {
        $current = $stock_opname->status instanceof StockOpnameStatus
            ? $stock_opname->status
            : StockOpnameStatus::from((string) $stock_opname->status);
        if ($current !== StockOpnameStatus::Draft) {
            Inertia::flash('toast', [
                'message' => 'Edit Stock Opname hanya diperbolehkan pada status Draft',
                'type' => 'error',
            ]);
            return redirect()->route('stock-opnames.show', (string) $stock_opname->getKey());
        }
        $stock_opname->load(['warehouse']);
        $warehouses = Warehouse::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($w) => ['id' => (string) $w->getKey(), 'name' => (string) $w->name])
            ->toArray();
        $products = Product::query()
            ->select(['id', 'name', 'sku'])
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id' => (string) $p->getKey(),
                'name' => (string) $p->name,
                'sku' => $p->sku ? (string) $p->sku : null,
            ])
            ->toArray();
        $users = User::query()
            ->whereHas('role', function ($q) {
                $q->whereIn('name', RoleName::stockOpnameAssignable());
            })
            ->select(['id', 'name', 'email'])
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id' => (string) $u->getKey(),
                'name' => (string) $u->name,
                'email' => (string) $u->email,
            ])
            ->toArray();
        $assignedUserIds = StockOpnameAssignment::query()
            ->where('stock_opname_id', (string) $stock_opname->getKey())
            ->pluck('user_id')
            ->map(fn($id) => (string) $id)
            ->toArray();
        $productIds = StockOpnameItem::query()
            ->where('stock_opname_id', (string) $stock_opname->getKey())
            ->select('product_id')
            ->groupBy('product_id')
            ->pluck('product_id')
            ->map(fn($id) => (string) $id)
            ->toArray();
        return Inertia::render('Domains/Admin/Logistic/StockOpnames/Form', [
            'opname' => [
                'id' => (string) $stock_opname->getKey(),
                'number' => (string) $stock_opname->number,
                'scheduled_date' => $stock_opname->scheduled_date ? (string) $stock_opname->scheduled_date->format('Y-m-d') : null,
                'status' => (string) $stock_opname->status->value,
                'status_label' => (string) $stock_opname->status->label(),
                'warehouse' => [
                    'id' => (string) $stock_opname->warehouse?->getKey(),
                    'name' => (string) ($stock_opname->warehouse?->name ?? ''),
                ],
                'notes' => $stock_opname->notes ? (string) $stock_opname->notes : null,
                'user_ids' => $assignedUserIds,
                'product_ids' => $productIds,
            ],
            'warehouses' => $warehouses,
            'products' => $products,
            'users' => $users,
        ]);
    }

    public function update(StoreStockOpnameRequest $request, StockOpname $stock_opname, StockOpnameService $service): RedirectResponse
    {
        $current = $stock_opname->status instanceof StockOpnameStatus
            ? $stock_opname->status
            : StockOpnameStatus::from((string) $stock_opname->status);
        if ($current !== StockOpnameStatus::Draft) {
            Inertia::flash('toast', [
                'message' => 'Tidak dapat mengedit Stock Opname pada status saat ini',
                'type' => 'error',
            ]);
            return back();
        }
        try {
            $updatedById = (string) Auth::id();
            $service->updateFromRequest($stock_opname, $request, $updatedById);
            Inertia::flash('toast', [
                'message' => 'Stock Opname berhasil diperbarui',
                'type' => 'success',
            ]);
            return redirect()->route('stock-opnames.show', (string) $stock_opname->getKey());
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui Stock Opname: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function submitAssignment(SubmitStockOpnameCountRequest $request, StockOpname $stock_opname, StockOpnameAssignment $assignment, StockOpnameService $service): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }
        if ((string) $assignment->stock_opname_id !== (string) $stock_opname->getKey()) {
            abort(404);
        }
        if ((string) $assignment->user_id !== (string) $user->getKey() && !Gate::allows('logistic-master-manage')) {
            abort(403);
        }
        try {
            $service->submitCountsFromRequest($assignment, $request, (string) $user->getKey());
            Inertia::flash('toast', [
                'message' => 'Perhitungan Stock Opname berhasil disubmit',
                'type' => 'success',
            ]);
            return redirect()->route('stock-opnames.show', ['stock_opname' => (string) $stock_opname->getKey()]);
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menyimpan perhitungan Stock Opname: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function settle(StockOpname $stock_opname, StockOpnameService $service): RedirectResponse
    {
        $current = $stock_opname->status instanceof StockOpnameStatus
            ? $stock_opname->status
            : StockOpnameStatus::from((string) $stock_opname->status);
        if ($current !== StockOpnameStatus::Scheduled) {
            Inertia::flash('toast', [
                'message' => 'Hanya dapat menyelesaikan dokumen dengan status Dijadwalkan',
                'type' => 'error',
            ]);
            return back();
        }
        try {
            $performedById = (string) Auth::id();
            $service->completeWithoutChanges($stock_opname, $performedById);
            Inertia::flash('toast', [
                'message' => 'Stock Opname diselesaikan tanpa mengubah stok',
                'type' => 'success',
            ]);
            return redirect()->route('stock-opnames.show', (string) $stock_opname->getKey());
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menyelesaikan Stock Opname: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }
}
