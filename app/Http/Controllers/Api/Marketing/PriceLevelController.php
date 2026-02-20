<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\{HandlesApiExceptions, RespondsWithJson};
use App\Models\SellingPriceLevel;
use Illuminate\Http\{JsonResponse, Request};

final class PriceLevelController extends Controller
{
    use HandlesApiExceptions;
    use RespondsWithJson;

    public function all(Request $request): JsonResponse
    {
        return $this->apiTry(function () use ($request) {
            $levels = SellingPriceLevel::query()
                ->orderBy('name')
                ->get(['id', 'name', 'percent_adjust'])
                ->map(fn($l) => [
                    'id' => (string) $l->id,
                    'name' => (string) $l->name,
                    'percent_adjust' => $l->percent_adjust !== null ? (float) $l->percent_adjust : null,
                ])
                ->all();

            return $this->apiResponse('Berhasil mengambil level harga jual', [
                'items' => $levels,
            ]);
        }, $request, [
            'user_id' => (string) ($request->user()?->getAuthIdentifier() ?? ''),
        ]);
    }
}

