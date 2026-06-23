<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Command;

class ForceCompleteOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:force-complete {number : Nomor pesanan yang ingin diselesaikan secara manual}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Paksa selesaikan pesanan (ubah status menjadi DELIVERED) secara manual dari terminal';

    /**
     * Execute the console command.
     */
    public function handle(OrderService $service): int
    {
        $number = $this->argument('number');

        $order = Order::where('number', $number)->first();

        if (! $order) {
            $this->error("Pesanan dengan nomor {$number} tidak ditemukan.");

            return Command::FAILURE;
        }

        $this->info("Menyelesaikan pesanan {$order->number} (Status saat ini: {$order->order_status->value})...");

        try {
            $service->completeOrder($order, null);
            $this->info("Pesanan {$order->number} berhasil diselesaikan (status: DELIVERED).");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal menyelesaikan pesanan: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
