<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ChefStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixStuckPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:fix-stuck-payments {--fix : Terapkan perubahan ke database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa dan perbaiki pesanan yang dibatalkan tetapi status pembayaran masih pending (nyantol di notifikasi)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $fix = $this->option('fix');

        // Query orders that are cancelled but payment is pending
        $orders = Order::where('order_status', OrderStatus::CANCELLED)
            ->where('payment_status', PaymentStatus::PENDING)
            ->get();

        $this->info("Ditemukan {$orders->count()} pesanan yang dibatalkan tetapi pembayaran masih PENDING.");

        if ($orders->isEmpty()) {
            $this->info('Semua aman. Tidak ada data yang nyantol.');

            return Command::SUCCESS;
        }

        foreach ($orders as $order) {
            $this->line("- Order: {$order->number} | ID: {$order->id} | Status Pesanan: {$order->order_status->value} | Pembayaran: {$order->payment_status->value}");
        }

        if (! $fix) {
            $this->warn('Gunakan opsi --fix untuk memperbarui status pembayaran menjadi FAILED.');

            return Command::SUCCESS;
        }

        $this->info('Memulai perbaikan data...');
        $updated = 0;

        foreach ($orders as $order) {
            DB::transaction(function () use ($order, &$updated) {
                // Update payment status to FAILED
                $order->update([
                    'payment_status' => PaymentStatus::FAILED,
                ]);

                // Also make sure all items are marked as cancelled
                OrderItem::where('order_id', $order->id)
                    ->where('chef_status', '!=', ChefStatus::CANCELLED)
                    ->update([
                        'chef_status' => ChefStatus::CANCELLED,
                        'chef_confirmed_at' => now(),
                    ]);

                $updated++;
            });
        }

        $this->info("Selesai. {$updated} pesanan berhasil diperbarui.");

        return Command::SUCCESS;
    }
}
