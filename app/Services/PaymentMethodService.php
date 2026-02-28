<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\PaymentMethod\PaymentMethodData;
use App\Models\PaymentMethod;
use App\Traits\FileHelperTrait;
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for PaymentMethod business logic.
 */
class PaymentMethodService
{
    use RetryableTransactionsTrait, FileHelperTrait;

    /**
     * Get paginated payment methods.
     */
    public function getPaginated(int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        return PaymentMethod::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'ilike', "%{$search}%");
            })
            ->orderBy('name', 'asc')
            ->paginate($perPage);
    }

    /**
     * Store a newly created payment method.
     *
     * @throws \Throwable
     */
    public function createPaymentMethod(PaymentMethodData $data): PaymentMethod
    {
        return $this->runWithRetry(function () use ($data) {
            try {
                return DB::transaction(function () use ($data) {
                    $photoPath = $this->handleFileInput($data->photo, null, 'payment_methods');

                    return PaymentMethod::create([
                        'name' => $data->name,
                        'category' => $data->category,
                        'type' => $data->type,
                        'code' => $data->code,
                        'photo' => $photoPath,
                        'is_active' => $data->isActive,
                        'account_number' => $data->accountNumber,
                        'account_name' => $data->accountName,
                        'payment_guide_id' => $data->paymentGuideId,
                        'service_fee_rate' => $data->serviceFeeRate,
                        'service_fee_fixed' => $data->serviceFeeFixed,
                    ]);
                });
            } catch (\Throwable $e) {
                Log::error('Failed to create payment method', [
                    'error' => $e->getMessage(),
                    'data' => [
                        'name' => $data->name,
                        'category' => $data->category,
                        'has_photo' => $data->photo !== null,
                        'is_active' => $data->isActive,
                    ],
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Update the specified payment method.
     *
     * @throws \Throwable
     */
    public function updatePaymentMethod(PaymentMethod $paymentMethod, PaymentMethodData $data): PaymentMethod
    {
        return $this->runWithRetry(function () use ($paymentMethod, $data) {
            try {
                return DB::transaction(function () use ($paymentMethod, $data) {
                    $photoPath = $this->handleFileInput($data->photo, $paymentMethod->photo, 'payment_methods');

                    $paymentMethod->update([
                        'name' => $data->name,
                        'category' => $data->category,
                        'type' => $data->type,
                        'code' => $data->code,
                        'photo' => $photoPath,
                        'is_active' => $data->isActive,
                        'account_number' => $data->accountNumber,
                        'account_name' => $data->accountName,
                        'payment_guide_id' => $data->paymentGuideId,
                        'service_fee_rate' => $data->serviceFeeRate,
                        'service_fee_fixed' => $data->serviceFeeFixed,
                    ]);

                    return $paymentMethod->refresh();
                });
            } catch (\Throwable $e) {
                Log::error('Failed to update payment method', [
                    'error' => $e->getMessage(),
                    'payment_method_id' => $paymentMethod->id,
                    'data' => [
                        'name' => $data->name,
                        'category' => $data->category,
                        'has_photo' => $data->photo !== null,
                        'is_active' => $data->isActive,
                    ],
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Delete the specified payment method.
     *
     * @throws \Throwable
     */
    public function deletePaymentMethod(PaymentMethod $paymentMethod): ?bool
    {
        return $this->runWithRetry(function () use ($paymentMethod) {
            try {
                return DB::transaction(function () use ($paymentMethod) {
                    if ($paymentMethod->photo) {
                        $this->deleteFile($paymentMethod->photo);
                    }
                    return $paymentMethod->delete();
                });
            } catch (\Throwable $e) {
                Log::error('Failed to delete payment method', [
                    'error' => $e->getMessage(),
                    'payment_method_id' => $paymentMethod->id,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }
}
