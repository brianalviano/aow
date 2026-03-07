<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\PickUpPointOfficer\PickUpPointOfficerData;
use App\Models\PickUpPointOfficer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Service for PickUpPointOfficer management.
 */
class PickUpPointOfficerService
{
    /**
     * Get paginated Pick Up Point Officers.
     */
    public function getPaginated(int $limit = 15, ?string $search = null): LengthAwarePaginator
    {
        return PickUpPointOfficer::query()
            ->with('pickUpPoint')
            ->when($search, function ($query, $search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%")
                    ->orWhereHas('pickUpPoint', function ($q) use ($search) {
                        $q->where('name', 'ilike', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate($limit)
            ->withQueryString();
    }

    /**
     * Create a new Pick Up Point Officer.
     * 
     * @throws Throwable
     */
    public function createPickUpPointOfficer(PickUpPointOfficerData $data): PickUpPointOfficer
    {
        try {
            return DB::transaction(function () use ($data) {
                return PickUpPointOfficer::create([
                    'name' => $data->name,
                    'phone' => $data->phone,
                    'email' => $data->email,
                    'password' => Hash::make($data->password),
                    'pick_up_point_id' => $data->pickUpPointId,
                    'is_active' => $data->isActive,
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Failed to create pick up point officer', [
                'error' => $e->getMessage(),
                'data' => (array) $data,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing Pick Up Point Officer.
     * 
     * @throws Throwable
     */
    public function updatePickUpPointOfficer(PickUpPointOfficer $officer, PickUpPointOfficerData $data): PickUpPointOfficer
    {
        try {
            return DB::transaction(function () use ($officer, $data) {
                $updateData = [
                    'name' => $data->name,
                    'phone' => $data->phone,
                    'email' => $data->email,
                    'pick_up_point_id' => $data->pickUpPointId,
                    'is_active' => $data->isActive,
                ];

                if (!empty($data->password)) {
                    $updateData['password'] = Hash::make($data->password);
                }

                $officer->update($updateData);

                return $officer;
            });
        } catch (Throwable $e) {
            Log::error('Failed to update pick up point officer', [
                'error' => $e->getMessage(),
                'officer_id' => $officer->id,
                'data' => (array) $data,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete a Pick Up Point Officer.
     * 
     * @throws Throwable
     */
    public function deletePickUpPointOfficer(PickUpPointOfficer $officer): bool
    {
        try {
            return DB::transaction(function () use ($officer) {
                return $officer->delete();
            });
        } catch (Throwable $e) {
            Log::error('Failed to delete pick up point officer', [
                'error' => $e->getMessage(),
                'officer_id' => $officer->id,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
