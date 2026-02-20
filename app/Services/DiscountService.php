<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Discount\DiscountData;
use App\Models\Discount;
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Support\Facades\{DB, Log};
use Carbon\Carbon;

/**
 * Layanan domain untuk manajemen Diskon.
 *
 * Mengelola pembuatan, pembaruan, dan penghapusan diskon dengan
 * boundary transaksi yang aman serta logging error terstruktur.
 */
final class DiscountService
{
    use RetryableTransactionsTrait;

    /**
     * Buat diskon baru.
     *
     * @param DiscountData $data Payload diskon.
     * @param string $userId ID user yang melakukan aksi.
     * @return Discount Diskon yang berhasil dibuat.
     *
     * @throws \Throwable
     */
    public function create(DiscountData $data, string $userId): Discount
    {
        return $this->runWithRetry(function () use ($data, $userId) {
            try {
                return DB::transaction(function () use ($data) {
                    $discount = new Discount();
                    $discount->name = $data->name;
                    $discount->type = $data->type;
                    $discount->scope = $data->scope;
                    $discount->value_type = $data->valueType;
                    $discount->value = $data->value;
                    $discount->start_at = Carbon::parse($data->startAt)->toDateTimeString();
                    $discount->end_at = Carbon::parse($data->endAt)->toDateTimeString();
                    $discount->is_active = $data->isActive;
                    $discount->save();
                    if ($data->scope === 'specific' && !empty($data->items)) {
                        foreach ($data->items as $it) {
                            $discount->items()->create([
                                'itemable_type' => $it->itemableType,
                                'itemable_id' => $it->itemableId,
                                'min_qty_buy' => $it->minQtyBuy,
                                'is_multiple' => $it->isMultiple,
                                'free_product_id' => $it->freeProductId,
                                'free_qty_get' => $it->freeQtyGet,
                                'custom_value' => $it->customValue,
                            ]);
                        }
                    }
                    return $discount;
                });
            } catch (\Throwable $e) {
                Log::error('DiscountService::create failed', [
                    'user_id' => $userId,
                    'dto' => $data,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    /**
     * Perbarui diskon.
     *
     * @param Discount $discount Diskon yang akan diperbarui.
     * @param DiscountData $data Payload pembaruan.
     * @param string $userId ID user yang melakukan aksi.
     * @return Discount Diskon setelah diperbarui.
     *
     * @throws \Throwable
     */
    public function update(Discount $discount, DiscountData $data, string $userId): Discount
    {
        return $this->runWithRetry(function () use ($discount, $data, $userId) {
            try {
                return DB::transaction(function () use ($discount, $data) {
                    $discount->name = $data->name;
                    $discount->type = $data->type;
                    $discount->scope = $data->scope;
                    $discount->value_type = $data->valueType;
                    $discount->value = $data->value;
                    $discount->start_at = Carbon::parse($data->startAt)->toDateTimeString();
                    $discount->end_at = Carbon::parse($data->endAt)->toDateTimeString();
                    $discount->is_active = $data->isActive;
                    $discount->save();
                    if ($data->scope === 'specific') {
                        $discount->items()->delete();
                        foreach ($data->items as $it) {
                            $discount->items()->create([
                                'itemable_type' => $it->itemableType,
                                'itemable_id' => $it->itemableId,
                                'min_qty_buy' => $it->minQtyBuy,
                                'is_multiple' => $it->isMultiple,
                                'free_product_id' => $it->freeProductId,
                                'free_qty_get' => $it->freeQtyGet,
                                'custom_value' => $it->customValue,
                            ]);
                        }
                    } else {
                        $discount->items()->delete();
                    }
                    return $discount;
                });
            } catch (\Throwable $e) {
                Log::error('DiscountService::update failed', [
                    'user_id' => $userId,
                    'dto' => $data,
                    'discount_id' => (string) $discount->getKey(),
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }

    /**
     * Hapus diskon beserta item terkait.
     *
     * @param Discount $discount Diskon yang akan dihapus.
     * @param string $userId ID user yang melakukan aksi.
     * @return void
     *
     * @throws \Throwable
     */
    public function delete(Discount $discount, string $userId): void
    {
        $this->runWithRetry(function () use ($discount, $userId) {
            try {
                DB::transaction(function () use ($discount) {
                    $discount->items()->delete();
                    $discount->delete();
                });
            } catch (\Throwable $e) {
                Log::error('DiscountService::delete failed', [
                    'user_id' => $userId,
                    'discount_id' => (string) $discount->getKey(),
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }, 3);
    }
}
