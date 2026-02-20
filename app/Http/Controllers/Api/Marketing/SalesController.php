<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\HandlesApiExceptions;
use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Enums\{SalesPaymentStatus, SalesStatus, SalesDeliveryStatus};
use App\Models\{Sales, SalesItem};
use App\Services\SalesViewService;
use App\Services\SalesService;
use App\DTOs\Sales\{SalesData};
use App\Http\Requests\Sales\{StoreSalesRequest, UpdateSalesRequest};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesController extends Controller
{
    use HandlesApiExceptions;
    use RespondsWithJson;

    public function index(Request $request): JsonResponse
    {
        return $this->apiTry(function () use ($request) {
            $p = $request->validate([
                'q' => ['nullable', 'string', 'max:255'],
                'state' => ['nullable', 'string', 'in:,paid,deposit,draft,canceled'],
                'shipping_status' => ['nullable', 'string', 'in:,not_shipped,shipped'],
                'date_from' => ['nullable', 'date_format:Y-m-d'],
                'date_to' => ['nullable', 'date_format:Y-m-d'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
                'period' => ['nullable', 'string', 'in:,monthly,weekly,daily'],
            ]);

            $q = isset($p['q']) ? (string) $p['q'] : '';
            $state = isset($p['state']) ? (string) $p['state'] : '';
            $shipping = isset($p['shipping_status']) ? (string) $p['shipping_status'] : '';
            $dateFrom = isset($p['date_from']) ? (string) $p['date_from'] : '';
            $dateTo = isset($p['date_to']) ? (string) $p['date_to'] : '';
            $perPage = isset($p['per_page']) ? (int) $p['per_page'] : 10;
            $period = isset($p['period']) ? (string) $p['period'] : '';

            if ($dateFrom === '' && $dateTo === '' && $period !== '') {
                if ($period === 'monthly') {
                    $dateFrom = Carbon::now()->startOfMonth()->toDateString();
                    $dateTo = Carbon::now()->endOfMonth()->toDateString();
                } elseif ($period === 'weekly') {
                    $dateFrom = Carbon::now()->startOfMonth()->toDateString();
                    $dateTo = Carbon::now()->endOfMonth()->toDateString();
                } elseif ($period === 'daily') {
                    $dateFrom = Carbon::now()->startOfMonth()->toDateString();
                    $dateTo = Carbon::now()->endOfMonth()->toDateString();
                }
            }

            $query = Sales::query()
                ->with(['customer'])
                ->where(function ($w) use ($request) {
                    $uid = (string) $request->user()->getKey();
                    $w->where('created_by_id', $uid)
                        ->orWhere(function ($ww) use ($uid) {
                            $ww->whereNotNull('customer_id')
                                ->whereHas('customer.marketers', function ($q) use ($uid) {
                                    $q->where('customer_marketers.user_id', $uid);
                                });
                        });
                })
                ->when($q !== '', function ($builder) use ($q) {
                    $builder->where(function ($w) use ($q) {
                        $w->where('receipt_number', 'ilike', "%{$q}%")
                            ->orWhere('invoice_number', 'ilike', "%{$q}%")
                            ->orWhere('customer_name', 'ilike', "%{$q}%");
                    });
                })
                ->when($state !== '', function ($builder) use ($state) {
                    if ($state === 'paid') {
                        $builder->where('payment_status', SalesPaymentStatus::Paid->value);
                    } elseif ($state === 'deposit') {
                        $builder->where('payment_status', SalesPaymentStatus::PartiallyPaid->value);
                    } elseif ($state === 'draft') {
                        $builder->where('status', SalesStatus::Draft->value);
                    } elseif ($state === 'canceled') {
                        $builder->where('status', SalesStatus::Canceled->value);
                    }
                })
                ->when($shipping !== '', function ($builder) use ($shipping) {
                    if ($shipping === 'not_shipped') {
                        $builder->where('requires_delivery', true)
                            ->whereNotExists(function ($q) {
                                $q->select(DB::raw('1'))
                                    ->from('sales_deliveries')
                                    ->whereColumn('sales_deliveries.sales_id', 'sales.id')
                                    ->where('sales_deliveries.status', SalesDeliveryStatus::Completed->value);
                            });
                    } elseif ($shipping === 'shipped') {
                        $builder->where('requires_delivery', true)
                            ->whereExists(function ($q) {
                                $q->select(DB::raw('1'))
                                    ->from('sales_deliveries')
                                    ->whereColumn('sales_deliveries.sales_id', 'sales.id')
                                    ->where('sales_deliveries.status', SalesDeliveryStatus::Completed->value);
                            });
                    }
                })
                ->when($dateFrom !== '', function ($builder) use ($dateFrom) {
                    $builder->whereDate('sale_datetime', '>=', $dateFrom);
                })
                ->when($dateTo !== '', function ($builder) use ($dateTo) {
                    $builder->whereDate('sale_datetime', '<=', $dateTo);
                })
                ->orderByDesc('sale_datetime')
                ->orderByDesc('created_at');

            $paginated = $query->paginate($perPage)->appends([
                'q' => $q,
                'state' => $state,
                'shipping_status' => $shipping,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'period' => $period,
            ]);

            $rows = collect($paginated->items());
            $ids = $rows->map(fn($s) => (string) $s->id)->all();

            $deliveryCompletedIds = collect(
                DB::table('sales_deliveries')
                    ->select('sales_id')
                    ->whereIn('sales_id', $ids)
                    ->where('status', SalesDeliveryStatus::Completed->value)
                    ->distinct()
                    ->pluck('sales_id')
            )->map(fn($id) => (string) $id)->values()->all();

            $itemsGrouped = SalesItem::query()
                ->whereIn('sales_id', $ids)
                ->with(['product:id,name'])
                ->get(['sales_id', 'product_id', 'product_name', 'quantity'])
                ->groupBy(fn($si) => (string) $si->sales_id);

            $items = $rows->map(function ($s) use ($itemsGrouped, $deliveryCompletedIds) {
                $statusValue = $s->payment_status instanceof SalesPaymentStatus
                    ? (string) $s->payment_status->value
                    : (string) $s->payment_status;
                $docStatusValue = $s->status instanceof SalesStatus
                    ? (string) $s->status->value
                    : (string) $s->status;

                $salesStateValue = '';
                if ($statusValue === 'paid') {
                    $salesStateValue = 'paid';
                } elseif ($statusValue === 'partially_paid') {
                    $salesStateValue = 'deposit';
                } elseif ($docStatusValue === 'draft') {
                    $salesStateValue = 'draft';
                } elseif ($docStatusValue === 'canceled') {
                    $salesStateValue = 'canceled';
                } else {
                    $salesStateValue = 'unpaid';
                }
                $salesStateLabel = $salesStateValue === 'paid' ? 'Paid'
                    : ($salesStateValue === 'deposit' ? 'Deposit'
                        : ($salesStateValue === 'draft' ? 'Draft'
                            : ($salesStateValue === 'canceled' ? 'Canceled' : 'Unpaid')));

                $requiresDelivery = (bool) ($s->requires_delivery ?? false);
                $shippingStatusValue = $requiresDelivery
                    ? (in_array((string) $s->id, $deliveryCompletedIds, true) ? 'shipped' : 'not_shipped')
                    : 'not_applicable';
                $shippingStatusLabel = $shippingStatusValue === 'shipped' ? 'Shipped'
                    : ($shippingStatusValue === 'not_shipped' ? 'Not Shipped' : 'Not Applicable');

                $paymentStatusLabelEn = $statusValue === 'paid' ? 'Paid'
                    : ($statusValue === 'partially_paid' ? 'Deposit' : 'Unpaid');

                $products = ($itemsGrouped[(string) $s->id] ?? collect())->map(function ($si) {
                    return [
                        'product_name' => (string) ($si->product?->name ?? ($si->product_name ?? '')),
                        'quantity' => (int) ($si->quantity ?? 0),
                    ];
                })->values()->all();
                return [
                    'id' => (string) $s->id,
                    'receipt_number' => (string) ($s->receipt_number ?? ''),
                    'invoice_number' => (string) ($s->invoice_number ?? ''),
                    'sale_datetime' => $s->sale_datetime ? (string) $s->sale_datetime->toDateTimeString() : null,
                    'customer' => [
                        'id' => $s->customer_id ? (string) $s->customer_id : null,
                        'name' => $s->customer ? (string) ($s->customer->name ?? '') : null,
                        'phone' => $s->customer ? ($s->customer->phone !== null ? (string) $s->customer->phone : null) : null,
                    ],
                    'payment_status' => $statusValue,
                    'payment_status_label' => SalesPaymentStatus::tryFrom($statusValue)?->label() ?? 'Tidak Diketahui',
                    'payment_status_label_en' => $paymentStatusLabelEn,
                    'sales_state' => $salesStateValue,
                    'sales_state_label' => $salesStateLabel,
                    'requires_delivery' => $requiresDelivery,
                    'shipping_status' => $shippingStatusValue,
                    'shipping_status_label' => $shippingStatusLabel,
                    'grand_total' => (int) ($s->grand_total ?? 0),
                    'items' => $products,
                ];
            })->values()->all();

            return $this->apiResponse('Daftar penjualan', [
                'sales' => $items,
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'last_page' => $paginated->lastPage(),
                ],
            ]);
        }, $request, [
            'q' => (string) $request->input('q', ''),
            'state' => (string) $request->input('state', ''),
            'shipping_status' => (string) $request->input('shipping_status', ''),
        ]);
    }

    public function store(StoreSalesRequest $request, SalesService $service, SalesViewService $viewService): JsonResponse
    {
        return $this->apiTry(function () use ($request, $service, $viewService) {
            $sale = $service->create(SalesData::fromStoreRequest($request, (string) $request->user()->getAuthIdentifier()));
            return $this->apiResponse('Transaksi berhasil', [
                'sale' => $viewService->buildSalePayload($sale, true),
            ]);
        }, $request, [
            'user_id' => (string) $request->user()->getAuthIdentifier(),
        ]);
    }

    public function show(Request $request, Sales $sales, SalesViewService $viewService): JsonResponse
    {
        return $this->apiTry(function () use ($request, $sales, $viewService) {
            $uid = (string) $request->user()->getKey();
            $allowed = Sales::query()
                ->where('id', (string) $sales->id)
                ->where(function ($w) use ($uid) {
                    $w->where('created_by_id', $uid)
                        ->orWhere(function ($ww) use ($uid) {
                            $ww->whereNotNull('customer_id')
                                ->whereHas('customer.marketers', function ($q) use ($uid) {
                                    $q->where('customer_marketers.user_id', $uid);
                                });
                        });
                })
                ->exists();
            if (!$allowed) {
                throw new AuthorizationException('Tidak diizinkan melihat transaksi ini');
            }
            $payload = $viewService->buildSalePayload($sales, true);
            return $this->apiResponse('Detail penjualan', [
                'sale' => $payload,
            ]);
        }, $request, [
            'sales_id' => (string) $sales->id,
        ]);
    }

    public function update(UpdateSalesRequest $request, Sales $sales, SalesService $service, SalesViewService $viewService): JsonResponse
    {
        return $this->apiTry(function () use ($request, $sales, $service, $viewService) {
            $userId = (string) $request->user()->getAuthIdentifier();
            $updated = $service->update($sales, (array) $request->validated(), $userId);
            return $this->apiResponse('Transaksi berhasil diperbarui', [
                'sale' => $viewService->buildSalePayload($updated, true),
            ]);
        }, $request, [
            'sales_id' => (string) $sales->id,
            'user_id' => (string) $request->user()->getAuthIdentifier(),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        return $this->apiTry(function () use ($request) {
            $p = $request->validate([
                'q' => ['nullable', 'string', 'max:255'],
                'date_from' => ['nullable', 'date_format:Y-m-d'],
                'date_to' => ['nullable', 'date_format:Y-m-d'],
                'group' => ['nullable', 'string', 'in:,monthly,weekly,daily'],
            ]);
            $q = isset($p['q']) ? (string) $p['q'] : '';
            $dateFrom = isset($p['date_from']) ? (string) $p['date_from'] : '';
            $dateTo = isset($p['date_to']) ? (string) $p['date_to'] : '';
            $group = isset($p['group']) ? (string) $p['group'] : 'monthly';
            $group = in_array($group, ['monthly', 'weekly', 'daily'], true) ? $group : 'monthly';

            $base = Sales::query()
                ->where(function ($w) use ($request) {
                    $uid = (string) $request->user()->getKey();
                    $w->where('created_by_id', $uid)
                        ->orWhere(function ($ww) use ($uid) {
                            $ww->whereNotNull('customer_id')
                                ->whereHas('customer.marketers', function ($q) use ($uid) {
                                    $q->where('customer_marketers.user_id', $uid);
                                });
                        });
                })
                ->when($q !== '', function ($builder) use ($q) {
                    $builder->where(function ($w) use ($q) {
                        $w->where('receipt_number', 'ilike', "%{$q}%")
                            ->orWhere('invoice_number', 'ilike', "%{$q}%")
                            ->orWhere('customer_name', 'ilike', "%{$q}%");
                    });
                })
                ->when($dateFrom !== '', function ($builder) use ($dateFrom) {
                    $builder->whereDate('sale_datetime', '>=', $dateFrom);
                })
                ->when($dateTo !== '', function ($builder) use ($dateTo) {
                    $builder->whereDate('sale_datetime', '<=', $dateTo);
                });

            $totalTransactions = (int) (clone $base)->count();
            $totalOmzet = (int) (clone $base)->sum('grand_total');

            $counters = [
                'all' => $totalTransactions,
                'paid' => (int) (clone $base)->where('payment_status', SalesPaymentStatus::Paid->value)->count(),
                'deposit' => (int) (clone $base)->where('payment_status', SalesPaymentStatus::PartiallyPaid->value)->count(),
                'draft' => (int) (clone $base)->where('status', SalesStatus::Draft->value)->count(),
                'canceled' => (int) (clone $base)->where('status', SalesStatus::Canceled->value)->count(),
            ];

            $shippingCounters = [
                'not_shipped' => (int) (clone $base)
                    ->where('requires_delivery', true)
                    ->whereNotExists(function ($q2) {
                        $q2->select(DB::raw('1'))
                            ->from('sales_deliveries')
                            ->whereColumn('sales_deliveries.sales_id', 'sales.id')
                            ->where('sales_deliveries.status', SalesDeliveryStatus::Completed->value);
                    })
                    ->count(),
                'shipped' => (int) (clone $base)
                    ->where('requires_delivery', true)
                    ->whereExists(function ($q2) {
                        $q2->select(DB::raw('1'))
                            ->from('sales_deliveries')
                            ->whereColumn('sales_deliveries.sales_id', 'sales.id')
                            ->where('sales_deliveries.status', SalesDeliveryStatus::Completed->value);
                    })
                    ->count(),
            ];

            $chart = [
                'group' => $group,
                'labels' => [],
                'values' => [],
                'range' => ['from' => '', 'to' => ''],
            ];
            if ($group === 'monthly') {
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                $chart['range'] = ['from' => $start->toDateString(), 'to' => $end->toDateString()];
                $keyMap = [];
                for ($i = 0; $i < 12; $i++) {
                    $m = (clone $start)->addMonths($i);
                    $key = $m->format('Y-m');
                    $keyMap[$key] = 0;
                    $chart['labels'][] = $m->format('M');
                }
                $rows = (clone $base)
                    ->whereDate('sale_datetime', '>=', $start->toDateString())
                    ->whereDate('sale_datetime', '<=', $end->toDateString())
                    ->select(
                        DB::raw("to_char(date_trunc('month', sale_datetime), 'YYYY-MM') AS period"),
                        DB::raw('SUM(grand_total) AS total')
                    )
                    ->groupBy('period')
                    ->orderBy('period')
                    ->get();
                foreach ($rows as $r) {
                    $k = (string) ($r->period ?? '');
                    if ($k !== '' && array_key_exists($k, $keyMap)) {
                        $keyMap[$k] = (int) ($r->total ?? 0);
                    }
                }
                $chart['values'] = array_values($keyMap);
            } elseif ($group === 'weekly') {
                $startMonth = Carbon::now()->startOfMonth();
                $endMonth = Carbon::now()->endOfMonth();
                $chart['range'] = ['from' => $startMonth->toDateString(), 'to' => $endMonth->toDateString()];
                $keyMap = [];
                $weekStart = (clone $startMonth)->startOfWeek();
                $weekNo = 1;
                while ($weekStart <= $endMonth) {
                    $key = $weekStart->format('o') . '-' . $weekStart->format('W');
                    $keyMap[$key] = 0;
                    $chart['labels'][] = 'M' . $weekNo;
                    $weekStart = $weekStart->addWeek();
                    $weekNo++;
                }
                $rows = (clone $base)
                    ->whereDate('sale_datetime', '>=', $startMonth->toDateString())
                    ->whereDate('sale_datetime', '<=', $endMonth->toDateString())
                    ->select(
                        DB::raw("to_char(date_trunc('week', sale_datetime), 'IYYY-IW') AS period"),
                        DB::raw('SUM(grand_total) AS total')
                    )
                    ->groupBy('period')
                    ->orderBy('period')
                    ->get();
                foreach ($rows as $r) {
                    $k = (string) ($r->period ?? '');
                    if ($k !== '' && array_key_exists($k, $keyMap)) {
                        $keyMap[$k] = (int) ($r->total ?? 0);
                    }
                }
                $chart['values'] = array_values($keyMap);
            } else {
                $startMonth = Carbon::now()->startOfMonth();
                $endMonth = Carbon::now()->endOfMonth();
                $chart['range'] = ['from' => $startMonth->toDateString(), 'to' => $endMonth->toDateString()];
                $keyMap = [];
                $d = (clone $startMonth)->startOfDay();
                while ($d <= $endMonth) {
                    $key = $d->format('Y-m-d');
                    $keyMap[$key] = 0;
                    $chart['labels'][] = $d->format('d M');
                    $d = $d->addDay();
                }
                $rows = (clone $base)
                    ->whereDate('sale_datetime', '>=', $startMonth->toDateString())
                    ->whereDate('sale_datetime', '<=', $endMonth->toDateString())
                    ->select(
                        DB::raw("to_char(date_trunc('day', sale_datetime), 'YYYY-MM-DD') AS period"),
                        DB::raw('SUM(grand_total) AS total')
                    )
                    ->groupBy('period')
                    ->orderBy('period')
                    ->get();
                foreach ($rows as $r) {
                    $k = (string) ($r->period ?? '');
                    if ($k !== '' && array_key_exists($k, $keyMap)) {
                        $keyMap[$k] = (int) ($r->total ?? 0);
                    }
                }
                $chart['values'] = array_values($keyMap);
            }

            return $this->apiResponse('Ringkasan penjualan', [
                'total_omzet' => $totalOmzet,
                'total_transaksi' => $totalTransactions,
                'status_counters' => $counters,
                'shipping_counters' => $shippingCounters,
                'chart' => $chart,
            ]);
        }, $request, [
            'q' => (string) $request->input('q', ''),
        ]);
    }
}
