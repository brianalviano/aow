<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\{StockOpnameStatus, RoleName};
use App\Models\{Warehouse, Product, User};
use App\Services\StockOpnameService;
use Illuminate\Database\Seeder;

class StockOpnameSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name']);
        if ($warehouses->isEmpty()) {
            return;
        }

        $products = Product::query()
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(50)
            ->get(['id', 'name', 'sku']);
        if ($products->isEmpty()) {
            return;
        }

        $seedUserId = User::query()
            ->whereHas('role', function ($q) {
                $q->where('name', RoleName::StaffLogistic->value)
                    ->orWhere('name', RoleName::ManagerLogistic->value)
                    ->orWhere('name', RoleName::Marketing->value);
            })
            ->orderBy('id')
            ->value('id');
        if (!$seedUserId) {
            $seedUserId = User::query()->where('email', 'superadmin@gmail.com')->value('id');
        }
        if (!$seedUserId) {
            $seedUserId = User::query()->orderBy('id')->value('id');
        }
        if (!$seedUserId) {
            return;
        }

        $assignableUserIds = User::query()
            ->whereHas('role', function ($q) {
                $q->whereIn('name', RoleName::stockOpnameAssignable());
            })
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();
        if (empty($assignableUserIds)) {
            $assignableUserIds = [(string) $seedUserId];
        }

        $service = app(StockOpnameService::class);

        $count = 100;
        for ($i = 0; $i < $count; $i++) {
            $warehouseId = (string) $warehouses->shuffle()->first()->id;
            $pickProducts = $products->shuffle()->take(random_int(5, min(12, max(5, (int) $products->count()))));
            $productIds = $pickProducts->pluck('id')->map(fn($id) => (string) $id)->all();
            if (empty($productIds)) {
                continue;
            }

            $pickUsers = collect($assignableUserIds)->shuffle()->take(random_int(1, min(3, max(1, count($assignableUserIds)))));
            $userIds = $pickUsers->all();
            if (empty($userIds)) {
                $userIds = [(string) $seedUserId];
            }

            $rand = random_int(1, 100);
            $desiredStatus = $rand <= 30
                ? StockOpnameStatus::Completed->value
                : ($rand <= 65 ? StockOpnameStatus::Scheduled->value : StockOpnameStatus::Draft->value);
            $status = $desiredStatus === StockOpnameStatus::Completed->value
                ? StockOpnameStatus::Scheduled->value
                : $desiredStatus;
            $scheduledDate = now()->subDays(random_int(0, 60))->toDateString();

            $data = [
                'warehouse_id' => $warehouseId,
                'product_ids' => $productIds,
                'user_ids' => $userIds,
                'scheduled_date' => $scheduledDate,
                'status' => $status,
                'notes' => 'Data contoh stok opname via seeder',
            ];

            $opname = $service->createFromData($data, (string) $seedUserId);
            if ($desiredStatus === StockOpnameStatus::Completed->value) {
                $service->completeWithoutChanges($opname, (string) $seedUserId);
            }
        }
    }
}
