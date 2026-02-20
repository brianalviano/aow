<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Services\SalesViewService;
use App\Http\Requests\Sales\StoreSalesRequest;
use App\Services\SalesService;
use App\Services\ProductPriceService;
use App\DTOs\Sales\SalesData;
use App\Services\CustomerService;
use App\DTOs\Customer\CustomerData;
use App\Http\Requests\Customer\StoreCustomerRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Throwable;
use App\Models\{Product, ValueAddedTax, SellingPriceLevel, Customer, PaymentMethod, Warehouse, ProductPurchasePrice, CashierSession, Sales, Discount, PaymentAllocation, Voucher};
use Inertia\{Inertia, Response};
use App\Enums\CashSessionStatus;
use App\Enums\CustomerSource;

class PosController extends Controller
{
    public function index(Request $request, ProductPriceService $priceService): Response
    {
        $q = trim((string) $request->query('q', ''));
        $products = Product::query()
            ->with(['productCategory'])
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%")
                        ->orWhere('sku', 'ilike', "%{$q}%");
                });
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name', 'product_category_id', 'product_sub_category_id', 'sku']);

        $productIds = $products->pluck('id')->map(fn($id) => (string) $id)->all();
        $selling = $priceService->getSellingPriceMainMap($productIds);
        $purchase = ProductPurchasePrice::query()
            ->whereIn('product_id', $productIds)
            ->get(['product_id', 'price'])
            ->reduce(function ($carry, $row) {
                $carry[(string) $row->product_id] = (int) $row->price;
                return $carry;
            }, []);
        $sellingPriceMap = $priceService->getSellingPriceMap($productIds);

        $items = $products->map(function ($p) use ($selling, $purchase) {
            $pid = (string) $p->id;
            $price = $selling[$pid] ?? ($purchase[$pid] ?? 0);
            $label = $p->productCategory?->name ? (string) $p->productCategory?->name : 'Umum';
            $slug = Str::slug($label, '-');
            return [
                'id' => $pid,
                'name' => (string) $p->name,
                'category' => $slug,
                'category_label' => $label,
                'price' => (int) $price,
                'product_category_id' => $p->product_category_id ? (string) $p->product_category_id : null,
                'product_sub_category_id' => $p->product_sub_category_id ? (string) $p->product_sub_category_id : null,
                'sku' => $p->sku !== null ? (string) $p->sku : null,
            ];
        })->all();

        $categories = collect($items)
            ->pluck('category_label', 'category')
            ->map(fn($label, $slug) => ['id' => (string) $slug, 'label' => (string) $label])
            ->values()
            ->all();

        $vat = ValueAddedTax::query()->where('is_active', true)->orderByDesc('percentage')->first();
        $vatOptions = ValueAddedTax::query()
            ->orderByDesc('percentage')
            ->get(['percentage'])
            ->map(fn($v) => (float) $v->percentage)
            ->all();
        $levels = SellingPriceLevel::query()
            ->orderBy('name')
            ->get(['id', 'name', 'percent_adjust'])
            ->map(fn($l) => [
                'id' => (string) $l->id,
                'name' => (string) $l->name,
                'percent_adjust' => $l->percent_adjust !== null ? (float) $l->percent_adjust : null,
            ])
            ->all();

        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name', 'phone', 'address'])
            ->map(fn($c) => [
                'id' => (string) $c->id,
                'name' => (string) $c->name,
                'phone' => $c->phone !== null ? (string) $c->phone : null,
                'address' => (string) $c->address,
            ])
            ->all();

        $paymentMethods = PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($pm) => [
                'id' => (string) $pm->id,
                'name' => (string) $pm->name,
            ])
            ->all();

        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->where('is_central', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($w) => [
                'id' => (string) $w->id,
                'name' => (string) $w->name,
            ])
            ->all();
        $activeShift = CashierSession::query()
            ->where('user_id', (string) $request->user()->getAuthIdentifier())
            ->whereNull('closed_at')
            ->where('status', CashSessionStatus::Open->value)
            ->latest('opened_at')
            ->first();
        $activeShiftPayload = null;
        if ($activeShift) {
            $totalSales = (int) Sales::query()
                ->where('cashier_session_id', (string) $activeShift->id)
                ->toBase()
                ->sum('grand_total');
            $totalCashIn = (int) PaymentAllocation::query()
                ->join('sales', 'payment_allocations.referencable_id', '=', 'sales.id')
                ->where('payment_allocations.referencable_type', Sales::class)
                ->where('sales.cashier_session_id', (string) $activeShift->id)
                ->whereNull('payment_allocations.voided_at')
                ->toBase()
                ->sum('payment_allocations.amount');
            $activeShiftPayload = [
                'id' => (string) $activeShift->id,
                'number' => '',
                'opened_at' => (string) $activeShift->opened_at,
                'opening_balance' => (int) $activeShift->starting_cash,
                'expected_closing_balance' => (int) ((int) $activeShift->starting_cash + (int) $totalCashIn),
                'total_sales' => (int) $totalSales,
                'total_cash_in' => (int) $totalCashIn,
                'cash_register' => null,
            ];
        }

        $now = now()->format('Y-m-d H:i:s');
        $canOpenShiftToday = !CashierSession::query()
            ->where('user_id', (string) $request->user()->getAuthIdentifier())
            ->whereNotNull('closed_at')
            ->whereDate('closed_at', now()->toDateString())
            ->exists();
        $closedToday = CashierSession::query()
            ->where('user_id', (string) $request->user()->getAuthIdentifier())
            ->whereNotNull('closed_at')
            ->whereDate('closed_at', now()->toDateString())
            ->latest('closed_at')
            ->first();
        $discounts = Discount::query()
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->with(['items' => function ($q) {
                $q->select([
                    'id',
                    'discount_id',
                    'itemable_type',
                    'itemable_id',
                    'min_qty_buy',
                    'free_product_id',
                    'free_qty_get',
                    'custom_value',
                    'is_multiple',
                ]);
            }])
            ->get(['id', 'name', 'type', 'scope', 'value_type', 'value'])
            ->map(function ($d) {
                return [
                    'id' => (string) $d->id,
                    'name' => (string) $d->name,
                    'type' => (string) $d->type,
                    'scope' => (string) $d->scope,
                    'value_type' => (string) $d->value_type,
                    'value' => $d->value !== null ? (float) $d->value : null,
                    'items' => $d->items->map(function ($it) {
                        return [
                            'itemable_type' => (string) $it->itemable_type,
                            'itemable_id' => (string) $it->itemable_id,
                            'min_qty_buy' => $it->min_qty_buy !== null ? (int) $it->min_qty_buy : null,
                            'free_product_id' => $it->free_product_id ? (string) $it->free_product_id : null,
                            'free_qty_get' => $it->free_qty_get !== null ? (int) $it->free_qty_get : null,
                            'custom_value' => $it->custom_value !== null ? (float) $it->custom_value : null,
                            'is_multiple' => (bool) ($it->is_multiple ?? false),
                        ];
                    })->all(),
                ];
            })
            ->all();

        $vouchers = Voucher::query()
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->orderBy('code')
            ->get([
                'id',
                'code',
                'name',
                'value_type',
                'value',
                'min_order_amount',
                'usage_limit',
                'used_count',
                'max_uses_per_customer',
                'start_at',
                'end_at',
            ])
            ->map(function ($v) {
                return [
                    'id' => (string) $v->id,
                    'code' => (string) $v->code,
                    'name' => (string) $v->name,
                    'value_type' => (string) $v->value_type,
                    'value' => $v->value !== null ? (float) $v->value : null,
                    'min_order_amount' => (int) $v->min_order_amount,
                    'usage_limit' => (int) $v->usage_limit,
                    'used_count' => (int) $v->used_count,
                    'max_uses_per_customer' => (int) $v->max_uses_per_customer,
                    'start_at' => $v->start_at ? (string) $v->start_at : null,
                    'end_at' => $v->end_at ? (string) $v->end_at : null,
                ];
            })
            ->all();

        return Inertia::render('Domains/Admin/Sales/POS/Index', [
            'products' => $items,
            'categories' => $categories,
            'vat_percent' => $vat ? (float) $vat->percentage : 0.0,
            'vat_options' => $vatOptions,
            'levels' => $levels,
            'sellingPriceMainMap' => $selling,
            'sellingPriceMap' => $sellingPriceMap,
            'customers' => $customers,
            'payment_methods' => $paymentMethods,
            'warehouses' => $warehouses,
            'active_shift' => $activeShiftPayload,
            'can_open_shift_today' => (bool) $canOpenShiftToday,
            'discounts' => $discounts,
            'vouchers' => $vouchers,
        ]);
    }

    public function storeCustomer(StoreCustomerRequest $request, CustomerService $service): RedirectResponse
    {
        try {
            $customer = $service->create(CustomerData::fromStoreRequest(
                $request,
                (string) $request->user()->getAuthIdentifier(),
                CustomerSource::Pos->value
            ));
            Inertia::flash('toast', [
                'message' => 'Customer berhasil dibuat',
                'type' => 'success',
            ]);
            Inertia::flash('created_customer_id', (string) $customer->id);
            Inertia::flash('created_customer', [
                'id' => (string) $customer->id,
                'name' => (string) $customer->name,
                'phone' => $customer->phone !== null ? (string) $customer->phone : null,
                'address' => (string) $customer->address,
            ]);
            return redirect()->route('pos.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat customer: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function store(StoreSalesRequest $request, SalesService $service, SalesViewService $viewService): RedirectResponse
    {
        try {
            $sale = $service->create(SalesData::fromStoreRequest($request, (string) $request->user()->getAuthIdentifier()));
            Inertia::flash('toast', [
                'message' => 'Transaksi berhasil: ' . (string) ($sale->receipt_number ?? ''),
                'type' => 'success',
            ]);
            Inertia::flash('sale_completed', $viewService->buildSalePayload($sale));
            return redirect()->route('pos.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menyimpan transaksi: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function printReceipt(Sales $sales, SalesViewService $viewService): Response
    {
        return Inertia::render('Domains/Admin/Sales/POS/ReceiptPrint', [
            'sale' => $viewService->buildSalePayload($sales),
        ]);
    }

    public function printInvoice(Sales $sales, SalesViewService $viewService): Response
    {
        return Inertia::render('Domains/Admin/Sales/POS/InvoicePrint', [
            'sale' => $viewService->buildSalePayload($sales),
        ]);
    }

    public function printDelivery(Sales $sales, SalesViewService $viewService): Response
    {
        return Inertia::render('Domains/Admin/Sales/POS/DeliveryPrint', [
            'sale' => $viewService->buildSalePayload($sales, true),
        ]);
    }
}
