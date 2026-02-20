<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\{HandlesApiExceptions, RespondsWithJson};
use App\Http\Resources\VoucherResource;
use App\Models\Voucher;
use Illuminate\Http\{JsonResponse, Request};

final class VoucherController extends Controller
{
    use HandlesApiExceptions;
    use RespondsWithJson;

    public function all(Request $request): JsonResponse
    {
        return $this->apiTry(function () use ($request) {
            $now = now()->format('Y-m-d H:i:s');
            $vouchers = Voucher::query()
                ->where('is_active', true)
                ->where(function ($q) use ($now) {
                    $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
                })
                ->orderBy('code')
                ->get();

            $items = array_map(
                fn($v) => VoucherResource::make($v)->toArray($request),
                $vouchers->all(),
            );

            return $this->apiResponse('Berhasil mengambil daftar voucher', [
                'items' => $items,
            ]);
        }, $request, [
            'user_id' => (string) ($request->user()?->getAuthIdentifier() ?? ''),
        ]);
    }
}
