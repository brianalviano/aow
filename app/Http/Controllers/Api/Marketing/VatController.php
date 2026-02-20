<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\{HandlesApiExceptions, RespondsWithJson};
use App\Models\ValueAddedTax;
use Illuminate\Http\{JsonResponse, Request};

final class VatController extends Controller
{
    use HandlesApiExceptions;
    use RespondsWithJson;

    public function index(Request $request): JsonResponse
    {
        return $this->apiTry(function () use ($request) {
            $vat = ValueAddedTax::query()->where('is_active', true)->orderByDesc('percentage')->first();
            $vatOptions = ValueAddedTax::query()
                ->orderByDesc('percentage')
                ->get(['percentage'])
                ->map(fn($v) => (float) $v->percentage)
                ->all();

            return $this->apiResponse('Berhasil mengambil VAT', [
                'vat_percent' => $vat ? (float) $vat->percentage : 0.0,
                'vat_options' => $vatOptions,
            ]);
        }, $request, [
            'user_id' => (string) ($request->user()?->getAuthIdentifier() ?? ''),
        ]);
    }
}

