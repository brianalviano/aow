<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\OrderService;
use Illuminate\Console\Command;

/**
 * Console command to automatically cancel unpaid orders that have reached their expiration time.
 */
class CancelUnpaidOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cancel-unpaid-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Batalkan pesanan yang belum dibayar secara otomatis setelah melewati batas waktu';

    /**
     * Execute the console command.
     *
     * @param OrderService $orderService
     * @return int
     */
    public function handle(OrderService $orderService): int
    {
        $this->info('Memulai pembatalan pesanan kadaluarsa...');

        $cancelledCount = $orderService->cancelExpiredOrders();

        $this->info("Selesai. {$cancelledCount} pesanan telah dibatalkan.");

        return Command::SUCCESS;
    }
}
