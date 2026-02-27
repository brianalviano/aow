<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Customer\LoginCustomerDTO;
use App\DTOs\Customer\RegisterCustomerDTO;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerAuthService
{
    /**
     * Register a new customer.
     *
     * @param RegisterCustomerDTO $dto
     * @return Customer
     * @throws \Throwable
     */
    public function register(RegisterCustomerDTO $dto): Customer
    {
        try {
            return DB::transaction(function () use ($dto) {
                $customer = Customer::create([
                    'name'          => $dto->name,
                    'username'      => $dto->username,
                    'phone'         => $dto->phone,
                    'address'       => $dto->address,
                    'email'         => $dto->email,
                    'password'      => $dto->password,
                    'school_class'  => $dto->school_class,
                ]);

                return $customer;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to register customer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => (array) $dto,
            ]);
            throw $e;
        }
    }

    /**
     * Attempt to login a customer.
     *
     * @param LoginCustomerDTO $dto
     * @return bool
     */
    public function login(LoginCustomerDTO $dto): bool
    {
        $login = $dto->login;
        $password = $dto->password;
        $remember = $dto->remember;

        $attempt = function (array $fields) use ($password, $remember): bool {
            return Auth::guard('customer')->attempt(array_merge($fields, ['password' => $password]), $remember);
        };

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            if ($attempt(['email' => $login])) {
                return true;
            }
        }

        if ($attempt(['username' => $login])) {
            return true;
        }

        $normalizedPhone = $this->normalizePhone($login);
        if ($normalizedPhone !== '' && $attempt(['phone' => $normalizedPhone])) {
            return true;
        }

        return false;
    }

    /**
     * Normalize phone number to start with '0'.
     *
     * @param string $input
     * @return string
     */
    private function normalizePhone(string $input): string
    {
        $digits = preg_replace('/\D+/', '', $input);
        if ($digits === null || $digits === '') {
            return '';
        }
        if (str_starts_with($digits, '62')) {
            return '0' . substr($digits, 2);
        }
        if (str_starts_with($digits, '0')) {
            return $digits;
        }
        return '0' . $digits;
    }
}
