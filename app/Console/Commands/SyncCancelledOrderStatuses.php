<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderShipping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Synchronize historical cancelled orders so shipping statuses are cancelled too.
 */
class SyncCancelledOrderStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:sync-cancelled-statuses
        {--dry-run : Tampilkan jumlah data yang akan diperbaiki tanpa mengubah database}
        {--chunk=100 : Jumlah order yang diproses per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronkan status pengiriman untuk order lama yang sudah dibatalkan';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $query = Order::query()
            ->where('order_status', OrderStatus::CANCELLED)
            ->whereHas('shippings', function ($shippingQuery) {
                $shippingQuery->whereNull('biteship_status')
                    ->orWhere('biteship_status', '!=', 'cancelled');
            });

        $orderCount = (clone $query)->count();
        $shippingCount = OrderShipping::query()
            ->whereHas('order', fn ($orderQuery) => $orderQuery->where('order_status', OrderStatus::CANCELLED))
            ->where(function ($shippingQuery) {
                $shippingQuery->whereNull('biteship_status')
                    ->orWhere('biteship_status', '!=', 'cancelled');
            })
            ->count();

        $this->info("Order perlu sinkronisasi: {$orderCount}");
        $this->info("Shipping perlu dibatalkan: {$shippingCount}");

        if ($dryRun) {
            $this->warn('Dry-run aktif. Tidak ada data yang diubah.');

            return Command::SUCCESS;
        }

        $processedOrders = 0;
        $updatedShippings = 0;

        $query->select('id')->chunkById($chunkSize, function ($orders) use (&$processedOrders, &$updatedShippings) {
            foreach ($orders as $order) {
                DB::transaction(function () use ($order, &$processedOrders, &$updatedShippings) {
                    $updatedShippings += OrderShipping::where('order_id', $order->id)
                        ->where(function ($shippingQuery) {
                            $shippingQuery->whereNull('biteship_status')
                                ->orWhere('biteship_status', '!=', 'cancelled');
                        })
                        ->update(['biteship_status' => 'cancelled']);

                    $processedOrders++;
                });
            }
        });

        $this->info("Selesai. {$processedOrders} order diproses dan {$updatedShippings} shipping diperbarui.");

        return Command::SUCCESS;
    }
}
