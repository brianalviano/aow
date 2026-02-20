<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Voucher\VoucherData;
use App\Models\Voucher;
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Support\Facades\{DB, Log};
use Carbon\Carbon;

final class VoucherService
{
    use RetryableTransactionsTrait;

    public function create(VoucherData $data, string $userId): Voucher
    {
        return $this->runWithRetry(function () use ($data, $userId) {
            try {
                return DB::transaction(function () use ($data) {
                    $voucher = new Voucher();
                    $voucher->code = $data->code;
                    $voucher->name = $data->name;
                    $voucher->description = $data->description;
                    $voucher->value_type = $data->valueType;
                    $voucher->value = $data->value;
                    $voucher->min_order_amount = $data->minOrderAmount ?? '0';
                    $voucher->usage_limit = $data->usageLimit;
                    $voucher->used_count = 0;
                    $voucher->max_uses_per_customer = $data->maxUsesPerCustomer;
                    $voucher->start_at = $data->startAt ? Carbon::parse($data->startAt)->toDateTimeString() : null;
                    $voucher->end_at = $data->endAt ? Carbon::parse($data->endAt)->toDateTimeString() : null;
                    $voucher->is_active = $data->isActive;
                    $voucher->save();
                    return $voucher;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('VoucherService::create failed', [
                    'user_id' => $userId,
                    'dto' => $data,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    public function update(Voucher $voucher, VoucherData $data, string $userId): Voucher
    {
        return $this->runWithRetry(function () use ($voucher, $data, $userId) {
            try {
                return DB::transaction(function () use ($voucher, $data) {
                    $voucher->code = $data->code;
                    $voucher->name = $data->name;
                    $voucher->description = $data->description;
                    $voucher->value_type = $data->valueType;
                    $voucher->value = $data->value;
                    $voucher->min_order_amount = $data->minOrderAmount ?? '0';
                    $voucher->usage_limit = $data->usageLimit;
                    $voucher->max_uses_per_customer = $data->maxUsesPerCustomer;
                    $voucher->start_at = $data->startAt ? Carbon::parse($data->startAt)->toDateTimeString() : null;
                    $voucher->end_at = $data->endAt ? Carbon::parse($data->endAt)->toDateTimeString() : null;
                    $voucher->is_active = $data->isActive;
                    $voucher->save();
                    return $voucher;
                }, 5);
            } catch (\Throwable $e) {
                Log::error('VoucherService::update failed', [
                    'user_id' => $userId,
                    'dto' => $data,
                    'voucher_id' => (string) $voucher->getKey(),
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    public function delete(Voucher $voucher, string $userId): void
    {
        $this->runWithRetry(function () use ($voucher, $userId) {
            try {
                DB::transaction(function () use ($voucher) {
                    $voucher->delete();
                }, 5);
            } catch (\Throwable $e) {
                Log::error('VoucherService::delete failed', [
                    'user_id' => $userId,
                    'voucher_id' => (string) $voucher->getKey(),
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }
}
