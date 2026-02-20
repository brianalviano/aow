<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\DTOs\StockTransfer\{StockTransferData, StockTransferItemData};
use App\Enums\{StockTransferStatus, RoleName};
use App\Models\{Warehouse, Product, User};
use App\Services\{StockTransferService, StockService};
use Illuminate\Database\Seeder;

/**
 * Seeder untuk membuat data contoh Mutasi Stok (Stock Transfer).
 *
 * Membuat 100 transaksi mutasi antar gudang dengan variasi tanggal, tujuan pemilik stok (marketing),
 * dan daftar item. Menggunakan StockTransferService agar pembuatan mengikuti aturan domain
 * (penomoran, sinkronisasi item).
 */
class StockTransferSeeder extends Seeder
{
    /**
     * Jalankan seeder.
     *
     * @return void
     */
    public function run(): void
    {
        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name', 'is_central']);
        if ($warehouses->count() < 2) {
            return;
        }

        $products = Product::query()
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(20)
            ->get(['id', 'name']);
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

        $marketingIds = User::query()
            ->whereHas('role', function ($q) {
                $q->where('name', RoleName::Marketing->value);
            })
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $service = app(StockTransferService::class);

        $count = 100;
        for ($i = 0; $i < $count; $i++) {
            $from = (string) $warehouses->shuffle()->first()->id;
            $to = (string) $warehouses->shuffle()->first()->id;
            if ($to === $from) {
                $to = (string) $warehouses->where('id', '!=', $from)->shuffle()->first()->id;
            }

            $pick = $products->shuffle()->take(random_int(2, min(4, max(2, (int) $products->count()))));
            $items = [];
            foreach ($pick as $p) {
                $qty = random_int(1, 3);
                $items[] = new StockTransferItemData(
                    productId: (string) $p->id,
                    quantity: (int) $qty,
                    fromOwnerUserId: null,
                    toOwnerUserId: null,
                );
            }
            if (empty($items)) {
                continue;
            }

            $toOwnerUserId = !empty($marketingIds) && random_int(0, 1) === 1
                ? $marketingIds[array_rand($marketingIds)]
                : null;
            $transferDate = now()->subDays(random_int(0, 90))->toDateString();
            $rand = random_int(1, 100);
            $desiredStatus = $rand <= 30
                ? StockTransferStatus::Received->value
                : ($rand <= 65 ? StockTransferStatus::InTransit->value : StockTransferStatus::Draft->value);
            $initialStatus = $desiredStatus === StockTransferStatus::Received->value
                ? StockTransferStatus::InTransit->value
                : $desiredStatus;

            $data = new StockTransferData(
                fromWarehouseId: $from,
                toWarehouseId: $to,
                toOwnerUserId: $toOwnerUserId,
                transferDate: $transferDate,
                status: $initialStatus,
                notes: 'Data contoh mutasi stok via seeder',
                createdById: (string) $seedUserId,
                items: $items
            );

            $transfer = $service->create($data);
            if ($desiredStatus === StockTransferStatus::Received->value) {
                $service->advanceStatus($transfer, (string) $seedUserId, app(StockService::class));
            }
        }
    }
}
