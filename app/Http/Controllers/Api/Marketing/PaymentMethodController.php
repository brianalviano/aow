<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\{HandlesApiExceptions, RespondsWithJson};
use App\Models\PaymentMethod;
use Illuminate\Http\{JsonResponse, Request};

final class PaymentMethodController extends Controller
{
    use HandlesApiExceptions;
    use RespondsWithJson;

    public function all(Request $request): JsonResponse
    {
        return $this->apiTry(function () use ($request) {
            $items = PaymentMethod::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($pm) => [
                    'id' => (string) $pm->id,
                    'name' => (string) $pm->name,
                ])
                ->all();

            return $this->apiResponse('Berhasil mengambil metode pembayaran', [
                'items' => $items,
            ]);
        }, $request, [
            'user_id' => (string) ($request->user()?->getAuthIdentifier() ?? ''),
        ]);
    }
}

