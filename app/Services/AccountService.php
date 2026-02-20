<?php

namespace App\Services;

use App\DTOs\Account\AccountData;
use App\Models\User;
use App\Traits\{RetryableTransactionsTrait, FileHelperTrait};
use Illuminate\Support\Facades\{DB, Hash};

class AccountService
{
    use RetryableTransactionsTrait, FileHelperTrait;

    public function update(User $user, AccountData $data): User
    {
        return $this->runWithRetry(function () use ($user, $data) {
            return DB::transaction(function () use ($user, $data) {
                $user->email = $data->email;
                if (!empty($data->password)) {
                    $user->password = Hash::make((string) $data->password);
                }
                if (!empty($data->pin)) {
                    $user->pin = Hash::make((string) $data->pin);
                }
                $user->phone_number = $data->phoneNumber;
                $user->address = $data->address;
                $user->birth_date = $data->birthDate;
                $user->gender = $data->gender;

                if ($data->ktpPhoto) {
                    $existingPublicPath = $user->photo ? '/storage/' . ltrim((string) $user->photo, '/') : null;
                    $stored = $this->handleFileUpload($data->ktpPhoto, $existingPublicPath, 'photos');
                    $user->photo = str_starts_with($stored, '/storage/') ? ltrim(substr($stored, 9), '/') : $stored;
                    $user->save();
                } else {
                    $user->save();
                }

                return $user;
            }, 5);
        }, 3);
    }
}
