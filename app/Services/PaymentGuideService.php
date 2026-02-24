<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PaymentGuide;
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for PaymentGuide business logic.
 */
class PaymentGuideService
{
    use RetryableTransactionsTrait;

    /**
     * Get paginated payment guides.
     */
    public function getPaginated(int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        return PaymentGuide::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'ilike', "%{$search}%");
            })
            ->orderBy('name', 'asc')
            ->paginate($perPage);
    }

    /**
     * Store a newly created payment guide.
     */
    public function createPaymentGuide(array $data): PaymentGuide
    {
        return $this->runWithRetry(function () use ($data) {
            try {
                return DB::transaction(function () use ($data) {
                    return PaymentGuide::create($data);
                });
            } catch (\Throwable $e) {
                Log::error('Failed to create payment guide', [
                    'error' => $e->getMessage(),
                    'data' => $data,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Update the specified payment guide.
     */
    public function updatePaymentGuide(PaymentGuide $paymentGuide, array $data): PaymentGuide
    {
        return $this->runWithRetry(function () use ($paymentGuide, $data) {
            try {
                return DB::transaction(function () use ($paymentGuide, $data) {
                    $paymentGuide->update($data);
                    return $paymentGuide->refresh();
                });
            } catch (\Throwable $e) {
                Log::error('Failed to update payment guide', [
                    'error' => $e->getMessage(),
                    'payment_guide_id' => $paymentGuide->id,
                    'data' => $data,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Delete the specified payment guide.
     */
    public function deletePaymentGuide(PaymentGuide $paymentGuide): ?bool
    {
        return $this->runWithRetry(function () use ($paymentGuide) {
            try {
                return DB::transaction(function () use ($paymentGuide) {
                    return $paymentGuide->delete();
                });
            } catch (\Throwable $e) {
                Log::error('Failed to delete payment guide', [
                    'error' => $e->getMessage(),
                    'payment_guide_id' => $paymentGuide->id,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }
}
