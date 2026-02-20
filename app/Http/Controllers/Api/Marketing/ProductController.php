<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\{HandlesApiExceptions, RespondsWithJson};
use App\Models\{Product, ProductPurchasePrice, Stock, Warehouse};
use App\Services\ProductPriceService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final class ProductController extends Controller
{
    use HandlesApiExceptions;
    use RespondsWithJson;

    public function all(Request $request, ProductPriceService $priceService): JsonResponse
    {
        return $this->apiTry(function () use ($request, $priceService) {
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

            $stockMap = [];
            $ownerUserId = (string) ($request->user()?->getAuthIdentifier() ?? '');
            if (!empty($productIds) && $ownerUserId !== '') {
                $stockRows = Stock::query()
                    ->whereIn('product_id', $productIds)
                    ->where('owner_user_id', $ownerUserId)
                    ->select(['product_id', DB::raw('SUM(quantity) as quantity')])
                    ->groupBy(['product_id'])
                    ->get();
                foreach ($stockRows as $row) {
                    $stockMap[(string) $row->product_id] = (int) $row->quantity;
                }
            }

            $items = $products->map(function ($p) use ($selling, $purchase, $stockMap) {
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
                    'stock_quantity' => (int) ($stockMap[$pid] ?? 0),
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

            return $this->apiResponse('Berhasil mengambil daftar produk', [
                'items' => $items,
                'categories' => $categories,
            ]);
        }, $request, [
            'user_id' => (string) ($request->user()?->getAuthIdentifier() ?? ''),
            'q' => (string) $request->query('q', ''),
        ]);
    }
}
