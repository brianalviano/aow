<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Supplier\SupplierData;
use App\Enums\Gender;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\{NormalizationTrait, RetryableTransactionsTrait, FileHelperTrait};

class SupplierService
{
    use RetryableTransactionsTrait, NormalizationTrait, FileHelperTrait;

    public function create(SupplierData $data): Supplier
    {
        return $this->runWithRetry(function () use ($data) {
            return DB::transaction(function () use ($data) {
                $supplier = new Supplier();
                $supplier->name = $data->name;
                $supplier->email = $data->email;
                $supplier->phone = $data->phone;
                $supplier->address = $data->address;
                $supplier->birth_date = $data->birthDate;
                $g = $this->normalizeGender($data->gender);
                $supplier->gender = $g ? Gender::tryFrom($g) : null;
                $supplier->is_active = $data->isActive;

                if ($data->photo) {
                    $stored = $this->handleFileUpload($data->photo, null, 'supplier_photos');
                    $supplier->photo = str_starts_with($stored, '/storage/') ? ltrim(substr($stored, 9), '/') : $stored;
                }

                $supplier->save();
                return $supplier;
            }, 5);
        }, 3);
    }

    public function update(Supplier $supplier, SupplierData $data): Supplier
    {
        return $this->runWithRetry(function () use ($supplier, $data) {
            return DB::transaction(function () use ($supplier, $data) {
                $supplier->name = $data->name;
                $supplier->email = $data->email;
                $supplier->phone = $data->phone;
                $supplier->address = $data->address;
                $supplier->birth_date = $data->birthDate;
                $g = $this->normalizeGender($data->gender);
                $supplier->gender = $g ? Gender::tryFrom($g) : null;
                $supplier->is_active = $data->isActive;

                if ($data->photo) {
                    $existingPublicPath = $supplier->photo ? '/storage/' . ltrim((string) $supplier->photo, '/') : null;
                    $stored = $this->handleFileUpload($data->photo, $existingPublicPath, 'supplier_photos');
                    $supplier->photo = str_starts_with($stored, '/storage/') ? ltrim(substr($stored, 9), '/') : $stored;
                    $supplier->save();
                } else {
                    $supplier->save();
                }

                return $supplier;
            }, 5);
        }, 3);
    }

    public function delete(Supplier $supplier): void
    {
        $this->runWithRetry(function () use ($supplier) {
            return DB::transaction(function () use ($supplier) {
                $old = $supplier->photo;
                $supplier->delete();
                if ($old) {
                    $this->deleteFile('/storage/' . ltrim((string) $old, '/'));
                }
                return null;
            }, 5);
        }, 3);
    }

    public function importBatch(array $rows): array
    {
        return $this->runWithRetry(function () use ($rows) {
            return DB::transaction(function () use ($rows) {
                $now = now();
                $validRows = [];
                foreach ($rows as $r) {
                    $name = isset($r['name']) ? trim((string) $r['name']) : '';
                    $email = isset($r['email']) ? trim(Str::lower((string) $r['email'])) : '';
                    if ($name === '' || $email === '') {
                        continue;
                    }
                    $phone = isset($r['phone']) ? trim((string) $r['phone']) : null;
                    $address = isset($r['address']) ? trim((string) $r['address']) : null;
                    $birthDate = isset($r['birth_date']) ? trim((string) $r['birth_date']) : null;
                    $gender = isset($r['gender']) ? trim(Str::lower((string) $r['gender'])) : null;
                    $gender = $this->normalizeGender($gender);
                    $isActive = isset($r['is_active']) ? in_array(Str::lower((string) $r['is_active']), ['1', 'true', 'yes', 'aktif'], true) : true;
                    $validRows[] = [
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'address' => $address,
                        'birth_date' => $birthDate,
                        'gender' => $gender,
                        'is_active' => $isActive,
                    ];
                }

                if (count($validRows) === 0) {
                    return ['created' => 0, 'updated' => 0, 'failed' => count($rows)];
                }

                $emails = array_map(fn($x) => $x['email'], $validRows);
                $existing = Supplier::query()->whereIn('email', $emails)->get(['id', 'email'])->keyBy(function ($s) {
                    return Str::lower((string) $s->email);
                });

                foreach ($validRows as $r) {
                    $joinBirth = $this->normalizeDate($r['birth_date']);
                    Supplier::query()->updateOrCreate(
                        ['email' => $r['email']],
                        [
                            'name' => $r['name'],
                            'phone' => $r['phone'],
                            'address' => $r['address'],
                            'birth_date' => $joinBirth,
                            'gender' => $r['gender'],
                            'is_active' => $r['is_active'],
                        ],
                    );
                }

                $created = 0;
                $updated = 0;
                foreach ($validRows as $r) {
                    if ($existing->has($r['email'])) {
                        $updated++;
                    } else {
                        $created++;
                    }
                }
                $failed = count($rows) - count($validRows);

                return ['created' => $created, 'updated' => $updated, 'failed' => $failed];
            }, 5);
        }, 3);
    }
}
