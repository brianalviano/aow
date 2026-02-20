<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\{HandlesApiExceptions, RespondsWithJson};
use App\Http\Resources\DiscountResource;
use App\Models\Discount;
use Illuminate\Http\{JsonResponse, Request};

final class DiscountController extends Controller
{
    use HandlesApiExceptions;
    use RespondsWithJson;

    public function all(Request $request): JsonResponse
    {
        return $this->apiTry(function () use ($request) {
            $now = now()->format('Y-m-d H:i:s');
            $discounts = Discount::query()
                ->with(['items.itemable'])
                ->where('is_active', true)
                ->where(function ($q) use ($now) {
                    $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
                })
                ->orderBy('name')
                ->get();

            $items = array_map(
                fn($d) => DiscountResource::make($d)->toArray($request),
                $discounts->all(),
            );

            return $this->apiResponse('Berhasil mengambil daftar diskon', [
                'items' => $items,
            ]);
        }, $request, [
            'user_id' => (string) ($request->user()?->getAuthIdentifier() ?? ''),
        ]);
    }
}

