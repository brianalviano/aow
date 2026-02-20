<?php
declare(strict_types=1);

namespace App\Imports;

use App\Services\CustomerService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomersImport implements ToCollection, WithHeadingRow
{
    private CustomerService $service;
    private ?string $userId;

    public function __construct(CustomerService $service, ?string $userId = null)
    {
        $this->service = $service;
        $this->userId = $userId;
    }

    public function collection(Collection $rows)
    {
        $normalized = [];
        foreach ($rows as $row) {
            $normalized[] = [
                'name' => isset($row['name']) ? (string) $row['name'] : '',
                'email' => isset($row['email']) ? (string) $row['email'] : '',
                'phone' => isset($row['phone']) ? (string) $row['phone'] : null,
                'address' => isset($row['address']) ? (string) $row['address'] : null,
                'is_active' => isset($row['is_active']) ? (bool) $row['is_active'] : true,
            ];
        }

        $this->service->importBatch($normalized, $this->userId);
    }
}
