<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Warehouse\WarehouseData;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\RetryableTransactionsTrait;

class WarehouseService
{
    use RetryableTransactionsTrait;

    public function create(WarehouseData $data): Warehouse
    {
        return $this->runWithRetry(function () use ($data) {
            return DB::transaction(function () use ($data) {
                $w = new Warehouse();
                $w->name = $data->name;
                $w->code = $data->code;
                $w->address = $data->address;
                $w->is_central = $data->isCentral;
                $w->phone = $data->phone;
                $w->is_active = $data->isActive;
                $w->save();
                return $w;
            }, 5);
        }, 3);
    }

    public function update(Warehouse $w, WarehouseData $data): Warehouse
    {
        return $this->runWithRetry(function () use ($w, $data) {
            return DB::transaction(function () use ($w, $data) {
                if ($w->is_central && $data->name !== (string) $w->name) {
                    throw new \DomainException('Nama gudang pusat tidak boleh diubah');
                }
                $w->name = $data->name;
                $w->code = $data->code;
                $w->address = $data->address;
                $w->is_central = $data->isCentral;
                $w->phone = $data->phone;
                $w->is_active = $data->isActive;
                $w->save();
                return $w;
            }, 5);
        }, 3);
    }

    public function delete(Warehouse $w): void
    {
        $this->runWithRetry(function () use ($w) {
            return DB::transaction(function () use ($w) {
                if ($w->is_central) {
                    throw new \DomainException('Gudang pusat tidak boleh dihapus');
                }
                $w->delete();
                return null;
            }, 5);
        }, 3);
    }

    public function importBatch(array $rows): array
    {
        return $this->runWithRetry(function () use ($rows) {
            return DB::transaction(function () use ($rows) {
                $now = now();
                $valid = [];
                foreach ($rows as $r) {
                    $name = isset($r['name']) ? trim((string) $r['name']) : '';
                    $code = isset($r['code']) ? trim(strtoupper((string) $r['code'])) : '';
                    if ($name === '' || $code === '') {
                        continue;
                    }
                    $address = isset($r['address']) ? trim((string) $r['address']) : null;
                    $isCentral = self::normalizeBool($r['is_central'] ?? null);
                    $phone = isset($r['phone']) ? trim((string) $r['phone']) : null;
                    $isActive = self::normalizeBool($r['is_active'] ?? null, true);
                    $valid[] = [
                        'name' => $name,
                        'code' => $code,
                        'address' => $address,
                        'is_central' => $isCentral,
                        'phone' => $phone,
                        'is_active' => $isActive,
                    ];
                }

                if (count($valid) === 0) {
                    return ['created' => 0, 'updated' => 0, 'failed' => count($rows)];
                }

                $codes = array_map(fn($x) => $x['code'], $valid);
                $existing = Warehouse::query()
                    ->whereIn('code', $codes)
                    ->get(['id', 'code'])
                    ->keyBy(function ($w) {
                        return Str::upper((string) $w->code);
                    });

                foreach ($valid as $r) {
                    Warehouse::query()->updateOrCreate(
                        ['code' => $r['code']],
                        [
                            'name' => $r['name'],
                            'address' => $r['address'],
                            'is_central' => $r['is_central'],
                            'phone' => $r['phone'],
                            'is_active' => $r['is_active'],
                        ],
                    );
                }

                $created = 0;
                $updated = 0;
                foreach ($valid as $r) {
                    if ($existing->has($r['code'])) {
                        $updated++;
                    } else {
                        $created++;
                    }
                }
                $failed = count($rows) - count($valid);
                return ['created' => $created, 'updated' => $updated, 'failed' => $failed];
            }, 5);
        }, 3);
    }

    private static function normalizeBool($value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }
        $v = Str::lower(trim((string) $value));
        if (in_array($v, ['1', 'true', 'yes', 'y', 'on', 'aktif', 'active'], true)) {
            return true;
        }
        if (in_array($v, ['0', 'false', 'no', 'n', 'off', 'nonaktif', 'inactive'], true)) {
            return false;
        }
        return $default;
    }
}
