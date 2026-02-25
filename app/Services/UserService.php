<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\User\UserData;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\{DB, Hash, Log};
use Throwable;

/**
 * Service class for managing administrative users.
 */
class UserService
{
    /**
     * Get paginated users with optional search and role filtering.
     *
     * @param int $limit
     * @param string|null $search
     * @param string|null $roleId
     * @return LengthAwarePaginator
     */
    public function getPaginated(int $limit = 15, ?string $search = null, ?string $roleId = null): LengthAwarePaginator
    {
        return User::query()
            ->with('role')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when($roleId, function ($query, $roleId) {
                $query->where('role_id', $roleId);
            })
            ->orderBy('name', 'asc')
            ->paginate($limit)
            ->withQueryString();
    }

    /**
     * Create a new administrative user.
     *
     * @param UserData $data
     * @return User
     * @throws Throwable
     */
    public function createUser(UserData $data): User
    {
        try {
            return DB::transaction(function () use ($data) {
                $user = User::create([
                    'name' => $data->name,
                    'username' => $data->username,
                    'email' => $data->email,
                    'role_id' => $data->roleId,
                    'password' => Hash::make($data->password),
                    'phone' => $data->phone,
                ]);

                return $user;
            });
        } catch (Throwable $e) {
            Log::error('Gagal membuat User: ' . $e->getMessage(), [
                'data' => [
                    'name' => $data->name,
                    'username' => $data->username,
                    'email' => $data->email,
                    'role_id' => $data->roleId,
                ],
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing administrative user.
     *
     * @param User $user
     * @param UserData $data
     * @return User
     * @throws Throwable
     */
    public function updateUser(User $user, UserData $data): User
    {
        try {
            return DB::transaction(function () use ($user, $data) {
                $updateData = [
                    'name' => $data->name,
                    'username' => $data->username,
                    'email' => $data->email,
                    'role_id' => $data->roleId,
                    'phone' => $data->phone,
                ];

                if ($data->password) {
                    $updateData['password'] = Hash::make($data->password);
                }

                $user->update($updateData);

                return $user;
            });
        } catch (Throwable $e) {
            Log::error('Gagal memperbarui User: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'data' => [
                    'name' => $data->name,
                    'username' => $data->username,
                    'email' => $data->email,
                    'role_id' => $data->roleId,
                ],
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete an administrative user.
     *
     * @param User $user
     * @return bool
     * @throws Throwable
     */
    public function deleteUser(User $user): bool
    {
        try {
            return DB::transaction(function () use ($user) {
                return (bool) $user->delete();
            });
        } catch (Throwable $e) {
            Log::error('Gagal menghapus User: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
