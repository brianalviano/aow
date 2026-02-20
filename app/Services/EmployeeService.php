<?php

namespace App\Services;

use App\DTOs\Employee\EmployeeData;
use App\Enums\RoleName;
use App\Models\{User, Role};
use Illuminate\Support\Facades\{DB, Hash, Log};
use Illuminate\Support\Str;
use App\Traits\{NormalizationTrait, RetryableTransactionsTrait, FileHelperTrait};

class EmployeeService
{
    use RetryableTransactionsTrait, NormalizationTrait, FileHelperTrait;

    /**
     * Create a new employee.
     *
     * @param EmployeeData $data
     * @return User
     */
    public function create(EmployeeData $data): User
    {
        return $this->runWithRetry(function () use ($data) {
            return DB::transaction(function () use ($data) {
                $user = new User();
                $user->name = $data->name;
                $user->email = $data->email;
                $user->username = $data->username;
                $user->password = Hash::make((string) $data->password);
                $user->role_id = $data->roleId;
                $user->join_date = $data->joinDate;
                $user->phone_number = $data->phoneNumber;
                $user->address = $data->address;
                $user->birth_date = $data->birthDate;
                $user->gender = $data->gender;

                if ($data->ktpPhoto) {
                    $stored = $this->handleFileUpload($data->ktpPhoto, null, 'photos');
                    $user->photo = str_starts_with($stored, '/storage/') ? ltrim(substr($stored, 9), '/') : $stored;
                }

                $user->save();
                return $user;
            }, 5);
        }, 3);
    }

    /**
     * Update an employee.
     *
     * @param User $user
     * @param EmployeeData $data
     * @return User
     */
    public function update(User $user, EmployeeData $data): User
    {
        return $this->runWithRetry(function () use ($user, $data) {
            return DB::transaction(function () use ($user, $data) {
                $user->name = $data->name;
                $user->email = $data->email;
                $user->username = $data->username;
                if (!empty($data->password)) {
                    $user->password = Hash::make((string) $data->password);
                }
                $user->role_id = $data->roleId;
                $user->join_date = $data->joinDate;
                $user->phone_number = $data->phoneNumber;
                $user->address = $data->address;
                $user->birth_date = $data->birthDate;
                $user->gender = $data->gender;

                if ($data->ktpPhoto) {
                    $existingPublicPath = $user->photo ? '/storage/' . ltrim((string) $user->photo, '/') : null;
                    $stored = $this->handleFileUpload($data->ktpPhoto, $existingPublicPath, 'photos');
                    $user->photo = str_starts_with($stored, '/storage/') ? ltrim(substr($stored, 9), '/') : $stored;
                    $user->save();
                } else {
                    $user->save();
                }

                return $user;
            }, 5);
        }, 3);
    }

    /**
     * Update user location (latitude/longitude).
     *
     * @param User $user
     * @param float|null $latitude
     * @param float|null $longitude
     * @return User
     *
     * @throws \Throwable
     */
    public function updateLocation(User $user, ?float $latitude, ?float $longitude): User
    {
        try {
            return $this->runWithRetry(function () use ($user, $latitude, $longitude) {
                return DB::transaction(function () use ($user, $latitude, $longitude) {
                    $user->latitude = $latitude;
                    $user->longitude = $longitude;
                    $user->save();
                    return $user;
                }, 5);
            }, 3);
        } catch (\Throwable $e) {
            Log::error('employee_update_location_failed', [
                'user_id' => (string) $user->getKey(),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete an employee.
     *
     * @param User $user
     * @return void
     */
    public function delete(User $user): void
    {
        $this->runWithRetry(function () use ($user) {
            return DB::transaction(function () use ($user) {
                $old = $user->photo;
                $user->delete();
                if ($old) {
                    $this->deleteFile('/storage/' . ltrim((string) $old, '/'));
                }
                return null;
            }, 5);
        }, 3);
    }

    /**
     * Import employees in batch.
     *
     * @param array $rows
     * @return array
     */
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
                    $roleName = isset($r['role_name']) ? trim((string) $r['role_name']) : null;
                    $phone = isset($r['phone_number']) ? trim((string) $r['phone_number']) : null;
                    $joinDate = isset($r['join_date']) ? trim((string) $r['join_date']) : null;
                    $address = isset($r['address']) ? trim((string) $r['address']) : null;
                    $birthDate = isset($r['birth_date']) ? trim((string) $r['birth_date']) : null;
                    $gender = isset($r['gender']) ? trim(Str::lower((string) $r['gender'])) : null;
                    $gender = $this->normalizeGender($gender);
                    $validRows[] = [
                        'name' => $name,
                        'email' => $email,
                        'role_name' => $roleName,
                        'phone_number' => $phone,
                        'join_date' => $joinDate,
                        'address' => $address,
                        'birth_date' => $birthDate,
                        'gender' => $gender,
                    ];
                }

                if (count($validRows) === 0) {
                    return ['created' => 0, 'updated' => 0, 'failed' => count($rows)];
                }

                $emails = array_map(fn($x) => $x['email'], $validRows);
                $existing = User::query()->whereIn('email', $emails)->get(['id', 'email'])->keyBy(function ($u) {
                    return Str::lower((string) $u->email);
                });

                $roleNames = array_values(array_unique(array_filter(array_map(function ($x) {
                    $v = $x['role_name'] ?? null;
                    return $v !== null ? Str::lower(trim((string) $v)) : null;
                }, $validRows))));
                $roles = Role::query()
                    ->get(['id', 'name'])
                    ->keyBy(function ($r) {
                        return Str::lower((string) $r->name);
                    });
                $defaultRole = Role::query()->firstOrCreate(['name' => RoleName::StaffLogistic->value]);
                $defaultRoleId = (string) $defaultRole->getKey();

                $created = 0;
                $updated = 0;
                foreach ($validRows as $r) {
                    $roleId = null;
                    if ($r['role_name']) {
                        $key = Str::lower(trim((string) $r['role_name']));
                        if ($roles->has($key)) {
                            $roleId = (string) $roles->get($key)->id;
                        }
                    }
                    if ($roleId === null) {
                        $roleId = $defaultRoleId;
                    }

                    $joinDate = $this->normalizeDate($r['join_date']);
                    $birthDate = $this->normalizeDate($r['birth_date']);

                    $user = User::query()->where('email', $r['email'])->first();
                    if ($user) {
                        $user->name = $r['name'];
                        $user->role_id = $roleId;
                        $user->phone_number = $r['phone_number'];
                        $user->join_date = $joinDate;
                        $user->address = $r['address'];
                        $user->birth_date = $birthDate;
                        $user->gender = $r['gender'];
                        $user->save();
                        $updated++;
                    } else {
                        User::create([
                            'name' => $r['name'],
                            'email' => $r['email'],
                            'username' => Str::lower(Str::before((string) $r['email'], '@')),
                            'password' => Hash::make('12345678'),
                            'role_id' => $roleId,
                            'phone_number' => $r['phone_number'],
                            'join_date' => $joinDate,
                            'address' => $r['address'],
                            'birth_date' => $birthDate,
                            'gender' => $r['gender'],
                        ]);
                        $created++;
                    }
                }

                // recompute counts against original rows
                $existingEmails = $existing->keys()->all();
                $updated = count(array_intersect($emails, $existingEmails));
                $created = count($emails) - $updated;
                $failed = count($rows) - count($validRows);

                return ['created' => $created, 'updated' => $updated, 'failed' => $failed];
            }, 5);
        }, 3);
    }
}
