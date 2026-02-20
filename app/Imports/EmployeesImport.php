<?php

namespace App\Imports;

use App\Services\EmployeeService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeesImport implements ToCollection, WithHeadingRow
{
    private EmployeeService $service;

    public function __construct(EmployeeService $service)
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
                'role_name' => isset($row['role_name']) ? (string) $row['role_name'] : null,
                'phone_number' => isset($row['phone_number']) ? (string) $row['phone_number'] : null,
                'join_date' => isset($row['join_date']) ? (string) $row['join_date'] : null,
                'address' => isset($row['address']) ? (string) $row['address'] : null,
                'birth_date' => isset($row['birth_date']) ? (string) $row['birth_date'] : null,
                'gender' => isset($row['gender']) ? (string) $row['gender'] : null,
            ];
        }

        $this->service->importBatch($normalized);
    }
}
