<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Customer\CustomerData;
use App\Models\Customer;
use App\Enums\CustomerSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Traits\{NormalizationTrait, RetryableTransactionsTrait};

class CustomerService
{
    use RetryableTransactionsTrait, NormalizationTrait;

    public function create(CustomerData $data): Customer
    {
        try {
            return $this->runWithRetry(function () use ($data) {
                return DB::transaction(function () use ($data) {
                    $marketerIds = !empty($data->marketerIds)
                        ? $data->marketerIds
                        : ($data->source === CustomerSource::Marketing->value ? [$data->createdById] : []);
                    $existing = null;
                    if ($data->email !== '') {
                        $existing = Customer::query()
                            ->whereRaw('LOWER(email) = ?', [strtolower($data->email)])
                            ->first();
                    }
                    if (!$existing && $data->phone) {
                        $existing = Customer::query()
                            ->where('phone', $data->phone)
                            ->first();
                    }
                    if ($existing) {
                        $existing->is_visible_in_pos = $existing->is_visible_in_pos || $data->isVisibleInPos;
                        $existing->is_visible_in_marketing = $existing->is_visible_in_marketing || $data->isVisibleInMarketing;
                        $existing->save();
                        if (!empty($marketerIds)) {
                            $existing->marketers()->syncWithoutDetaching($marketerIds);
                        }
                        return $existing;
                    }
                    $c = new Customer();
                    $c->name = $data->name;
                    $c->email = $data->email;
                    $c->phone = $data->phone;
                    $c->address = $data->address;
                    $c->latitude = $data->latitude;
                    $c->longitude = $data->longitude;
                    $c->is_active = $data->isActive;
                    $c->source = $data->source;
                    $c->created_by_id = $data->createdById;
                    $c->is_visible_in_pos = $data->isVisibleInPos;
                    $c->is_visible_in_marketing = $data->isVisibleInMarketing;
                    $c->save();
                    if (!empty($marketerIds)) {
                        $c->marketers()->syncWithoutDetaching($marketerIds);
                    }
                    return $c;
                }, 5);
            }, 3);
        } catch (\Throwable $e) {
            Log::error('CustomerService::create failed', [
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'source' => $data->source,
                'created_by_id' => $data->createdById,
                'is_visible_in_pos' => $data->isVisibleInPos,
                'is_visible_in_marketing' => $data->isVisibleInMarketing,
                'marketer_ids' => $data->marketerIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function update(Customer $customer, CustomerData $data): Customer
    {
        try {
            return $this->runWithRetry(function () use ($customer, $data) {
                return DB::transaction(function () use ($customer, $data) {
                    $customer->name = $data->name;
                    $customer->email = $data->email;
                    $customer->phone = $data->phone;
                    $customer->address = $data->address;
                    $customer->latitude = $data->latitude;
                    $customer->longitude = $data->longitude;
                    $customer->is_active = $data->isActive;
                    $customer->is_visible_in_pos = $data->isVisibleInPos;
                    $customer->is_visible_in_marketing = $data->isVisibleInMarketing;
                    $customer->save();
                    if (!empty($data->marketerIds)) {
                        $customer->marketers()->sync($data->marketerIds);
                    }
                    return $customer;
                }, 5);
            }, 3);
        } catch (\Throwable $e) {
            Log::error('CustomerService::update failed', [
                'customer_id' => (string) $customer->id,
                'email' => $data->email,
                'is_visible_in_pos' => $data->isVisibleInPos,
                'is_visible_in_marketing' => $data->isVisibleInMarketing,
                'marketer_ids' => $data->marketerIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function delete(Customer $customer): void
    {
        try {
            $this->runWithRetry(function () use ($customer) {
                return DB::transaction(function () use ($customer) {
                    $customer->delete();
                    return null;
                }, 5);
            }, 3);
        } catch (\Throwable $e) {
            Log::error('CustomerService::delete failed', [
                'customer_id' => (string) $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function importBatch(array $rows, ?string $createdById = null): array
    {
        try {
            return $this->runWithRetry(function () use ($rows, $createdById) {
                return DB::transaction(function () use ($rows, $createdById) {
                    $validRows = [];
                    foreach ($rows as $r) {
                        $name = isset($r['name']) ? trim((string) $r['name']) : '';
                        $email = isset($r['email']) ? trim(strtolower((string) $r['email'])) : '';
                        if ($name === '' || $email === '') {
                            continue;
                        }
                        $phone = isset($r['phone']) ? trim((string) $r['phone']) : null;
                        $address = isset($r['address']) ? trim((string) $r['address']) : null;
                        $isActive = isset($r['is_active']) ? (bool) $r['is_active'] : true;
                        $validRows[] = [
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone,
                            'address' => $address,
                            'is_active' => $isActive,
                        ];
                    }

                    if (count($validRows) === 0) {
                        return ['created' => 0, 'updated' => 0, 'failed' => count($rows)];
                    }

                    $emails = array_map(fn($x) => $x['email'], $validRows);
                    $existing = Customer::query()
                        ->whereIn('email', $emails)
                        ->get(['id', 'email'])
                        ->keyBy(function ($c) {
                            return strtolower((string) $c->email);
                        });

                    $created = 0;
                    $updated = 0;
                    foreach ($validRows as $r) {
                        $emailKey = strtolower($r['email']);
                        if ($existing->has($emailKey)) {
                            Customer::query()
                                ->where('email', $r['email'])
                                ->update([
                                    'name' => $r['name'],
                                    'phone' => $r['phone'],
                                    'address' => $r['address'],
                                    'is_active' => (bool) $r['is_active'],
                                ]);
                            $updated++;
                        } else {
                            $c = new Customer();
                            $c->name = $r['name'];
                            $c->email = $r['email'];
                            $c->phone = $r['phone'];
                            $c->address = $r['address'];
                            $c->is_active = (bool) $r['is_active'];
                            $c->source = CustomerSource::Marketing->value;
                            $c->created_by_id = $createdById;
                            $c->save();
                            $created++;
                        }
                    }
                    $failed = count($rows) - count($validRows);

                    return ['created' => $created, 'updated' => $updated, 'failed' => $failed];
                }, 5);
            }, 3);
        } catch (\Throwable $e) {
            Log::error('CustomerService::importBatch failed', [
                'rows_count' => count($rows),
                'created_by_id' => $createdById,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
