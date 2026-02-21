<?php

namespace App\Services;

use App\DTOs\User\UserData;
use App\Models\User;
use App\Traits\{RetryableTransactionsTrait, FileHelperTrait};
use Illuminate\Support\Facades\{DB, Hash};

class UserService
{
    use RetryableTransactionsTrait, FileHelperTrait;

    public function update(User $user, UserData $data): User
    {
        return $this->runWithRetry(function () use ($user, $data) {
            return DB::transaction(function () use ($user, $data) {
                $user->email = $data->email;
                if (!empty($data->password)) {
                    $user->password = Hash::make((string) $data->password);
                }
                $user->phone_number = $data->phoneNumber;
                $user->save();
                return $user;
            }, 5);
        }, 3);
    }
}
