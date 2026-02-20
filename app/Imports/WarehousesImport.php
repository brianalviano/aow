<?php
declare(strict_types=1);

namespace App\Imports;

use App\Services\WarehouseService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WarehousesImport implements ToCollection, WithHeadingRow
{
    private WarehouseService $service;

    public function __construct(WarehouseService $service)
    {
        $this->service = $service;
    }

    public function collection(Collection $rows)
    {
        $normalized = [];
        foreach ($rows as $row) {
            $normalized[] = [
                'name' => isset($row['name']) ? (string) $row['name'] : '',
                'code' => isset($row['code']) ? (string) $row['code'] : '',
                'address' => isset($row['address']) ? (string) $row['address'] : null,
                'is_central' => isset($row['is_central']) ? (string) $row['is_central'] : null,
                'phone' => isset($row['phone']) ? (string) $row['phone'] : null,
                'is_active' => isset($row['is_active']) ? (string) $row['is_active'] : null,
            ];
        }

        $this->service->importBatch($normalized);
    }
}

