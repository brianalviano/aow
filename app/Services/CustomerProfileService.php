<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Customer\UpdateProfileDTO;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CustomerProfileService
{
    /**
     * Update customer profile.
     *
     * @param Customer $customer
     * @param UpdateProfileDTO $dto
     * @return bool
     * @throws \Throwable
     */
    public function updateProfile(Customer $customer, UpdateProfileDTO $dto): bool
    {
        try {
            return DB::transaction(function () use ($customer, $dto) {
                $lockedCustomer = Customer::where('id', $customer->id)->lockForUpdate()->first();

                $lockedCustomer->name = $dto->name;
                $lockedCustomer->username = $dto->username;
                $lockedCustomer->phone = $dto->phone;
                $lockedCustomer->email = $dto->email;
                $lockedCustomer->drop_point_id = $dto->drop_point_id;
                $lockedCustomer->school_class = $dto->school_class;

                if (!empty($dto->password)) {
                    $lockedCustomer->password = Hash::make($dto->password);
                }

                return $lockedCustomer->save();
            }, 3); // 3 retries for deadlocks
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui profil: ' . $e->getMessage(), [
                'customer_id' => $customer->id,
                'dto' => (array) $dto,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
