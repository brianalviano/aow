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
                RoleName::Director->value => ['name' => 'Roni', 'email' => 'roni@gmail.com'],
                RoleName::Marketing->value => ['name' => 'Jonathan', 'email' => 'jonathan@gmail.com'],
                RoleName::ManagerHR->value => ['name' => 'Siti', 'email' => 'siti@gmail.com'],
                RoleName::ManagerLogistic->value => ['name' => 'Budi', 'email' => 'budi@gmail.com'],
                RoleName::StaffLogistic->value => ['name' => 'Andi', 'email' => 'andi@gmail.com'],
                RoleName::ManagerSales->value => ['name' => 'Dewi', 'email' => 'dewi@gmail.com'],
                RoleName::Cashier->value => ['name' => 'Rita', 'email' => 'rita@gmail.com'],
                RoleName::ManagerFinance->value => ['name' => 'Agus', 'email' => 'agus@gmail.com'],
                RoleName::StaffFinance->value => ['name' => 'Nina', 'email' => 'nina@gmail.com'],
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
                    'pin' => Hash::make('123456'),
                    'password' => Hash::make('12345678'),
                    'role_id' => $roleIdsByName[$roleName],
                    'join_date' => now()->toDateString(),
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

                    DatabaseNotification::query()->create([
                        'id' => (string) Str::uuid(),
                        'type' => 'system',
                        'notifiable_type' => User::class,
                        'notifiable_id' => $user->getKey(),
                        'data' => [
                            'priority' => 'high',
                            'title' => 'Keamanan Akun',
                            'message' => 'Ubah PIN default dan perbarui password kamu.',
                            'url' => '/account/settings',
                        ],
                        'read_at' => now(),
                    ]);
                }
            }
        });
    }
}
