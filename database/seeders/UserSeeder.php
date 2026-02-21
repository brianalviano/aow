<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\{User, Role};
use App\Enums\RoleName;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $roles = RoleName::values();
            $defaults = [
                RoleName::SuperAdmin->value => ['name' => 'Super Admin', 'email' => 'superadmin@gmail.com'],
                RoleName::Admin->value => ['name' => 'Admin', 'email' => 'admin@gmail.com'],
            ];
            $userRecords = [];
            foreach ($roles as $roleName) {
                if (isset($defaults[$roleName])) {
                    $userRecords[] = [
                        'role' => $roleName,
                        'name' => $defaults[$roleName]['name'],
                        'email' => $defaults[$roleName]['email'],
                    ];
                    continue;
                }
                $local = strtolower(str_replace(' ', '.', $roleName));
                $userRecords[] = [
                    'role' => $roleName,
                    'name' => $roleName,
                    'email' => $local . '@gmail.com',
                ];
            }

            $userRoleNames = array_values(array_unique(array_map(
                static fn(array $u): string => $u['role'],
                $userRecords
            )));

            $roleIdsByName = Role::query()
                ->whereIn('name', $userRoleNames)
                ->get(['id', 'name'])
                ->pluck('id', 'name')
                ->all();

            foreach ($userRecords as $u) {
                $roleName = $u['role'];
                if (!isset($roleIdsByName[$roleName])) {
                    continue;
                }

                $username = Str::lower(Str::before((string) $u['email'], '@'));
                $userData = [
                    'name' => $u['name'],
                    'email' => $u['email'],
                    'username' => $username,
                    'password' => Hash::make('12345678'),
                    'role_id' => $roleIdsByName[$roleName],
                ];

                $user = User::query()->updateOrCreate(
                    ['email' => $userData['email']],
                    $userData
                );

                $existingCount = DatabaseNotification::query()
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', $user->getKey())
                    ->count();

                if ($existingCount === 0) {
                    DatabaseNotification::query()->create([
                        'id' => (string) Str::uuid(),
                        'type' => 'system',
                        'notifiable_type' => User::class,
                        'notifiable_id' => $user->getKey(),
                        'data' => [
                            'priority' => 'medium',
                            'title' => 'Selamat datang',
                            'message' => 'Hai ' . (string) $user->name . ', akun kamu telah dibuat.',
                            'url' => '/notifications',
                        ],
                        'read_at' => null,
                    ]);
                }
            }
        });
    }
}
