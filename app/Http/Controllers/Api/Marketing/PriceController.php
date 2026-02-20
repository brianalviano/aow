<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\{HandlesApiExceptions, RespondsWithJson};
use App\Services\ProductPriceService;
use Illuminate\Http\{JsonResponse, Request};

final class PriceController extends Controller
{
    use HandlesApiExceptions;
    use RespondsWithJson;

    public function selling(Request $request, ProductPriceService $service): JsonResponse
    {
        return $this->apiTry(function () use ($request, $service) {
            $ids = $request->input('product_ids', []);
            if (is_string($ids)) {
                $ids = array_filter(array_map('trim', explode(',', $ids)), fn($v) => $v !== '');
            }
            if (!is_array($ids)) {
                $ids = [];
            }
            $productIds = array_values(array_unique(array_map(fn($id) => (string) $id, $ids)));

            $sellingPriceMainMap = $service->getSellingPriceMainMap($productIds);
            $sellingPriceMap = $service->getSellingPriceMap($productIds);

            return $this->apiResponse('Berhasil mengambil harga jual', [
                'sellingPriceMainMap' => $sellingPriceMainMap,
                'sellingPriceMap' => $sellingPriceMap,
            ]);
        }, $request, [
            'user_id' => (string) ($request->user()?->getAuthIdentifier() ?? ''),
            'product_ids' => (array) $request->input('product_ids', []),
        ]);
    }
}

