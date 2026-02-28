<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Chef\{ChefData, ChefTransferData};
use App\Models\{Chef, ChefTransfer, OrderItem};
use App\Traits\{FileHelperTrait, RetryableTransactionsTrait};
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\{DB, Log};

/**
 * Service for Chef business logic.
 *
 * Handles CRUD operations, product assignment sync, sales computation,
 * and transfer recording for chef partners.
 */
class ChefService
{
    use RetryableTransactionsTrait, FileHelperTrait;

    /**
     * Get paginated chefs with optional search.
     *
     * @param int $perPage Jumlah item per halaman
     * @param string|null $search Kata kunci pencarian (nama/email/phone)
     * @return LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return Chef::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%");
            })
            ->withCount('products')
            ->orderBy('name', 'asc')
            ->paginate($perPage);
    }

    /**
     * Create a new chef and sync assigned products.
     *
     * @param ChefData $data Validated chef data
     * @return Chef
     *
     * @throws \Throwable
     */
    public function createChef(ChefData $data): Chef
    {
        return $this->runWithRetry(function () use ($data) {
            try {
                return DB::transaction(function () use ($data) {
                    $chef = Chef::create([
                        'name'           => $data->name,
                        'email'          => $data->email,
                        'password'       => $data->password,
                        'phone'          => $data->phone,
                        'bank_name'      => $data->bankName,
                        'account_number' => $data->accountNumber,
                        'account_name'   => $data->accountName,
                        'note'           => $data->note,
                        'fee_percentage' => $data->feePercentage,
                        'address'        => $data->address,
                        'longitude'      => $data->longitude,
                        'latitude'       => $data->latitude,
                        'is_active'      => $data->isActive,
                        'order_types'    => $data->orderTypes,
                    ]);

                    if (!empty($data->productIds)) {
                        $chef->products()->sync($data->productIds);
                    }

                    return $chef->load('products');
                });
            } catch (\Throwable $e) {
                Log::error('Failed to create chef', [
                    'error' => $e->getMessage(),
                    'data'  => [
                        'name'  => $data->name,
                        'email' => $data->email,
                    ],
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Update an existing chef and sync assigned products.
     *
     * @param Chef $chef Chef model to update
     * @param ChefData $data Validated chef data
     * @return Chef
     *
     * @throws \Throwable
     */
    public function updateChef(Chef $chef, ChefData $data): Chef
    {
        return $this->runWithRetry(function () use ($chef, $data) {
            try {
                return DB::transaction(function () use ($chef, $data) {
                    $updateData = [
                        'name'           => $data->name,
                        'email'          => $data->email,
                        'phone'          => $data->phone,
                        'bank_name'      => $data->bankName,
                        'account_number' => $data->accountNumber,
                        'account_name'   => $data->accountName,
                        'note'           => $data->note,
                        'fee_percentage' => $data->feePercentage,
                        'address'        => $data->address,
                        'longitude'      => $data->longitude,
                        'latitude'       => $data->latitude,
                        'is_active'      => $data->isActive,
                        'order_types'    => $data->orderTypes,
                    ];

                    if ($data->password !== null) {
                        $updateData['password'] = $data->password;
                    }

                    $chef->update($updateData);
                    $chef->products()->sync($data->productIds);

                    return $chef->refresh()->load('products');
                });
            } catch (\Throwable $e) {
                Log::error('Failed to update chef', [
                    'error'   => $e->getMessage(),
                    'chef_id' => $chef->id,
                    'data'    => [
                        'name'  => $data->name,
                        'email' => $data->email,
                    ],
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Soft-delete a chef.
     *
     * @param Chef $chef Chef model to delete
     * @return bool|null
     *
     * @throws \Throwable
     */
    public function deleteChef(Chef $chef): ?bool
    {
        return $this->runWithRetry(function () use ($chef) {
            try {
                return DB::transaction(function () use ($chef) {
                    $chef->products()->detach();

                    return $chef->delete();
                });
            } catch (\Throwable $e) {
                Log::error('Failed to delete chef', [
                    'error'   => $e->getMessage(),
                    'chef_id' => $chef->id,
                    'trace'   => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Record a new transfer to a chef.
     *
     * Fee is computed from the chef's current `fee_percentage` and snapshot-ed
     * at the time of transfer for audit trail.
     *
     * @param Chef $chef Target chef
     * @param ChefTransferData $data Validated transfer data
     * @return ChefTransfer
     *
     * @throws \Throwable
     */
    public function createTransfer(Chef $chef, ChefTransferData $data): ChefTransfer
    {
        return $this->runWithRetry(function () use ($chef, $data) {
            try {
                return DB::transaction(function () use ($chef, $data) {
                    $feePercentage = $chef->fee_percentage;
                    $grossAmount   = $data->grossAmount;
                    $feeAmount     = (int) round($grossAmount * $feePercentage / 100);
                    $netAmount     = $grossAmount - $feeAmount;

                    $proofPath = $this->handleFileInput($data->transferProof, null, 'chef_transfers');

                    return $chef->transfers()->create([
                        'amount'         => $netAmount,
                        'fee_percentage' => $feePercentage,
                        'fee_amount'     => $feeAmount,
                        'gross_amount'   => $grossAmount,
                        'note'           => $data->note,
                        'transfer_proof' => $proofPath,
                        'transferred_at' => $data->transferredAt,
                    ]);
                });
            } catch (\Throwable $e) {
                Log::error('Failed to create chef transfer', [
                    'error'   => $e->getMessage(),
                    'chef_id' => $chef->id,
                    'data'    => [
                        'gross_amount'   => $data->grossAmount,
                        'transferred_at' => $data->transferredAt,
                    ],
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Enrich a Chef model with computed sales data attributes.
     *
     * Calculates total_sales, total_fee_amount, net_sales,
     * total_transferred, and outstanding_balance based on delivered/paid orders
     * linked through the chef's assigned products.
     *
     * @param Chef $chef Chef model to enrich
     * @return Chef Chef model with dynamically set attributes
     */
    public function enrichWithSalesData(Chef $chef): Chef
    {
        $productIds = $chef->products()->pluck('products.id');

        // Total penjualan dari order items yang produknya di-assign ke chef ini
        // Hanya order yang sudah dibayar dan tidak dibatalkan
        $totalSales = (int) OrderItem::query()
            ->whereIn('product_id', $productIds)
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 'paid')
                    ->where('order_status', '!=', 'cancelled');
            })
            ->sum('subtotal');

        $feePercentage    = $chef->fee_percentage;
        $totalFeeAmount   = (int) round($totalSales * $feePercentage / 100);
        $netSales         = $totalSales - $totalFeeAmount;
        $totalTransferred = (int) $chef->transfers()->sum('amount');

        $chef->total_sales         = $totalSales;
        $chef->total_fee_amount    = $totalFeeAmount;
        $chef->net_sales           = $netSales;
        $chef->total_transferred   = $totalTransferred;
        $chef->outstanding_balance = $netSales - $totalTransferred;

        return $chef;
    }
}
