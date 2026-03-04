<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\DropPoint\DropPointData;
use App\Models\DropPoint;
use App\Traits\FileHelperTrait;
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for DropPoint business logic.
 */
class DropPointService
{
    use RetryableTransactionsTrait, FileHelperTrait;

    /**
     * Get paginated drop points.
     */
    public function getPaginated(int $perPage = 10, ?string $search = null)
    {
        return DropPoint::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('address', 'ilike', "%{$search}%")
                    ->orWhere('pic_name', 'ilike', "%{$search}%");
            })
            ->orderBy('name', 'asc')
            ->paginate($perPage);
    }

    /**
     * Store a newly created drop point.
     *
     * @throws \Throwable
     */
    public function createDropPoint(DropPointData $data): DropPoint
    {
        return $this->runWithRetry(function () use ($data) {
            try {
                return DB::transaction(function () use ($data) {
                    $photoPath = $this->handleFileInput($data->photo, null, 'drop_points');

                    return DropPoint::create([
                        'name' => $data->name,
                        'photo' => $photoPath,
                        'address' => $data->address,
                        'phone' => $data->phone,
                        'latitude' => $data->latitude,
                        'longitude' => $data->longitude,
                        'pic_name' => $data->picName,
                        'pic_phone' => $data->picPhone,
                        'is_active' => $data->isActive,
                        'delivery_fee' => $data->deliveryFee,
                        'min_po_qty' => $data->minPoQty,
                        'min_po_amount' => $data->minPoAmount,
                    ]);
                });
            } catch (\Throwable $e) {
                Log::error('Failed to create drop point', [
                    'error' => $e->getMessage(),
                    'data' => [
                        'name' => $data->name,
                        'has_photo' => $data->photo !== null,
                        'address' => $data->address,
                        'phone' => $data->phone,
                        'latitude' => $data->latitude,
                        'longitude' => $data->longitude,
                        'pic_name' => $data->picName,
                        'pic_phone' => $data->picPhone,
                        'is_active' => $data->isActive,
                        'delivery_fee' => $data->deliveryFee,
                        'min_po_qty' => $data->minPoQty,
                        'min_po_amount' => $data->minPoAmount,
                    ],
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Update the specified drop point.
     *
     * @throws \Throwable
     */
    public function updateDropPoint(DropPoint $dropPoint, DropPointData $data): DropPoint
    {
        return $this->runWithRetry(function () use ($dropPoint, $data) {
            try {
                return DB::transaction(function () use ($dropPoint, $data) {
                    $photoPath = $this->handleFileInput($data->photo, $dropPoint->photo, 'drop_points');

                    $dropPoint->update([
                        'name' => $data->name,
                        'photo' => $photoPath,
                        'address' => $data->address,
                        'phone' => $data->phone,
                        'latitude' => $data->latitude,
                        'longitude' => $data->longitude,
                        'pic_name' => $data->picName,
                        'pic_phone' => $data->picPhone,
                        'is_active' => $data->isActive,
                        'delivery_fee' => $data->deliveryFee,
                        'min_po_qty' => $data->minPoQty,
                        'min_po_amount' => $data->minPoAmount,
                    ]);

                    return $dropPoint->refresh();
                });
            } catch (\Throwable $e) {
                Log::error('Failed to update drop point', [
                    'error' => $e->getMessage(),
                    'drop_point_id' => $dropPoint->id,
                    'data' => [
                        'name' => $data->name,
                        'has_photo' => $data->photo !== null,
                        'address' => $data->address,
                        'phone' => $data->phone,
                        'latitude' => $data->latitude,
                        'longitude' => $data->longitude,
                        'pic_name' => $data->picName,
                        'pic_phone' => $data->picPhone,
                        'is_active' => $data->isActive,
                        'delivery_fee' => $data->deliveryFee,
                        'min_po_qty' => $data->minPoQty,
                        'min_po_amount' => $data->minPoAmount,
                    ],
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Delete the specified drop point.
     *
     * @throws \Throwable
     */
    public function deleteDropPoint(DropPoint $dropPoint): ?bool
    {
        return $this->runWithRetry(function () use ($dropPoint) {
            try {
                return DB::transaction(function () use ($dropPoint) {
                    if ($dropPoint->photo) {
                        $this->deleteFile($dropPoint->photo);
                    }
                    return $dropPoint->delete();
                });
            } catch (\Throwable $e) {
                Log::error('Failed to delete drop point', [
                    'error' => $e->getMessage(),
                    'drop_point_id' => $dropPoint->id,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }
}
