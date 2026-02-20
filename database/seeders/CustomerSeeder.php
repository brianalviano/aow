<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\{CustomerSource, RoleName};
use App\Models\User;
use App\Services\CustomerService;
use App\DTOs\Customer\CustomerData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $createdById = User::query()
            ->whereHas('role', function ($q) {
                $q->where('name', RoleName::Marketing->value);
            })
            ->orderBy('id')
            ->value('id');
        if (!$createdById) {
            $createdById = User::query()->where('email', 'superadmin@gmail.com')->value('id');
        }
        if (!$createdById) {
            $createdById = User::query()->orderBy('id')->value('id');
        }
        if (!$createdById) {
            return;
        }

        $service = app(CustomerService::class);
        $customers = [
            [
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@example.com',
                'phone' => '081234567801',
                'address' => 'Jl. Merdeka No. 10, Jakarta',
            ],
            [
                'name' => 'Siti Rahma',
                'email' => 'siti.rahma@example.com',
                'phone' => '081234567802',
                'address' => 'Jl. Sudirman No. 21, Bandung',
            ],
            [
                'name' => 'Andi Prasetyo',
                'email' => 'andi.prasetyo@example.com',
                'phone' => '081234567803',
                'address' => 'Jl. Diponegoro No. 5, Surabaya',
            ],
        ];

        foreach ($customers as $data) {
            try {
                $service->create(new CustomerData(
                    name: (string) $data['name'],
                    email: (string) $data['email'],
                    phone: $data['phone'] ? (string) $data['phone'] : null,
                    address: $data['address'] ? (string) $data['address'] : null,
                    latitude: null,
                    longitude: null,
                    isActive: true,
                    source: CustomerSource::Marketing->value,
                    createdById: (string) $createdById,
                    isVisibleInPos: true,
                    isVisibleInMarketing: true,
                    marketerIds: []
                ));
            } catch (\Throwable $e) {
                Log::error('CustomerSeeder failed', [
                    'email' => (string) $data['email'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }
    }
}
