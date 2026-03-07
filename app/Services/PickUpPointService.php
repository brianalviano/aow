<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\PickUpPoint\PickUpPointData;
use App\Models\PickUpPoint;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Service for PickUpPoint management.
 */
class PickUpPointService
{
    /**
     * Get paginated pick up points.
     */
    public function getPaginated(int $limit = 15, ?string $search = null): LengthAwarePaginator
    {
        return PickUpPoint::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('address', 'ilike', "%{$search}%");
            })
            ->latest()
            ->paginate($limit)
            ->withQueryString();
    }

    /**
     * Create a new pick up point.
     * 
     * @throws Throwable
     */
    public function createPickUpPoint(PickUpPointData $data): PickUpPoint
    {
        try {
            return DB::transaction(function () use ($data) {
                $pickUpPoint = PickUpPoint::create([
                    'name' => $data->name,
                    'address' => $data->address,
                    'latitude' => $data->latitude,
                    'longitude' => $data->longitude,
                    'description' => $data->description,
                    'is_active' => $data->isActive,
                ]);

                if (!empty($data->officerIds)) {
                    \App\Models\PickUpPointOfficer::whereIn('id', $data->officerIds)
                        ->update(['pick_up_point_id' => $pickUpPoint->id]);
                }

                return $pickUpPoint;
            });
        } catch (Throwable $e) {
            Log::error('Failed to create pick up point', [
                'error' => $e->getMessage(),
                'data' => (array) $data,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing pick up point.
     * 
     * @throws Throwable
     */
    public function updatePickUpPoint(PickUpPoint $pickUpPoint, PickUpPointData $data): PickUpPoint
    {
        try {
            return DB::transaction(function () use ($pickUpPoint, $data) {
                $pickUpPoint->update([
                    'name' => $data->name,
                    'address' => $data->address,
                    'latitude' => $data->latitude,
                    'longitude' => $data->longitude,
                    'description' => $data->description,
                    'is_active' => $data->isActive,
                ]);

                // Sync officers
                // 1. Remove assignments for officers no longer selected
                \App\Models\PickUpPointOfficer::where('pick_up_point_id', $pickUpPoint->id)
                    ->when(!empty($data->officerIds), function ($query) use ($data) {
                        $query->whereNotIn('id', $data->officerIds);
                    })
                    ->update(['pick_up_point_id' => null]);

                // 2. Add assignments for newly selected officers
                if (!empty($data->officerIds)) {
                    \App\Models\PickUpPointOfficer::whereIn('id', $data->officerIds)
                        ->update(['pick_up_point_id' => $pickUpPoint->id]);
                }

                return $pickUpPoint;
            });
        } catch (Throwable $e) {
            Log::error('Failed to update pick up point', [
                'error' => $e->getMessage(),
                'pick_up_point_id' => $pickUpPoint->id,
                'data' => (array) $data,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete a pick up point.
     * 
     * @throws Throwable
     */
    public function deletePickUpPoint(PickUpPoint $pickUpPoint): bool
    {
        try {
            return DB::transaction(function () use ($pickUpPoint) {
                return $pickUpPoint->delete();
            });
        } catch (Throwable $e) {
            Log::error('Failed to delete pick up point', [
                'error' => $e->getMessage(),
                'pick_up_point_id' => $pickUpPoint->id,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
