<?php

declare(strict_types=1);

namespace App\Imports;

use App\Services\SupplierService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SuppliersImport implements ToCollection, WithHeadingRow
{
    private SupplierService $service;

    public function __construct(SupplierService $service)
    {
        $this->service = $service;
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
                'birth_date' => isset($row['birth_date']) ? (string) $row['birth_date'] : null,
                'gender' => isset($row['gender']) ? (string) $row['gender'] : null,
                'is_active' => isset($row['is_active']) ? (string) $row['is_active'] : null,
            ];
        }

        $this->service->importBatch($normalized);
    }
}
