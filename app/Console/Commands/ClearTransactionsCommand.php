<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-transactions {--force : Force the operation to run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus seluruh data transaksi (orders) dan data customer beserta relasinya';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('Apakah Anda yakin ingin menghapus SELURUH data transaksi dan CUSTOMER? Tindakan ini tidak dapat dibatalkan.')) {
            $this->info('Operasi dibatalkan.');
            return 0;
        }

        $this->info('Menghapus data transaksi dan customer...');

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 1. Hapus Testimonials (yang berkaitan dengan order)
            $testimonialCount = \App\Models\Testimonial::count();
            \App\Models\Testimonial::query()->delete();
            $this->line("- Terhapus {$testimonialCount} testimonial.");

            // 2. Hapus Order Item Options
            $optionCount = \App\Models\OrderItemOption::count();
            \App\Models\OrderItemOption::query()->delete();
            $this->line("- Terhapus {$optionCount} pilihan item order.");

            // 3. Hapus Order Items
            $itemCount = \App\Models\OrderItem::count();
            \App\Models\OrderItem::query()->delete();
            $this->line("- Terhapus {$itemCount} item order.");

            // 4. Hapus Order Shippings
            $shippingCount = \App\Models\OrderShipping::count();
            \App\Models\OrderShipping::query()->delete();
            $this->line("- Terhapus {$shippingCount} data pengiriman.");

            // 5. Hapus Orders (Force delete jika menggunakan SoftDeletes)
            $orderCount = \App\Models\Order::withTrashed()->count();
            \App\Models\Order::withTrashed()->forceDelete();
            $this->line("- Terhapus {$orderCount} data order (termasuk yang di-soft delete).");

            // 6. Hapus Daily Summaries (karena data dasarnya sudah hilang)
            $summaryCount = \App\Models\DailySummary::count();
            \App\Models\DailySummary::query()->delete();
            $this->line("- Terhapus {$summaryCount} ringkasan harian.");

            // 7. Hapus Product Summaries
            $productSummaryCount = \App\Models\ProductSummary::count();
            \App\Models\ProductSummary::query()->delete();
            $this->line("- Terhapus {$productSummaryCount} ringkasan produk.");

            // 8. Hapus Customer Addresses
            $addressCount = \App\Models\CustomerAddress::withTrashed()->count();
            \App\Models\CustomerAddress::withTrashed()->forceDelete();
            $this->line("- Terhapus {$addressCount} alamat customer.");

            // 9. Hapus Feedback
            $feedbackCount = \App\Models\Feedback::count();
            \App\Models\Feedback::query()->delete();
            $this->line("- Terhapus {$feedbackCount} feedback.");

            // 10. Hapus Food Requests
            $foodRequestCount = \App\Models\FoodRequest::count();
            \App\Models\FoodRequest::query()->delete();
            $this->line("- Terhapus {$foodRequestCount} permintaan makanan.");

            // 11. Hapus Customers (Force delete karena menggunakan SoftDeletes)
            $customerCount = \App\Models\Customer::withTrashed()->count();
            \App\Models\Customer::withTrashed()->forceDelete();
            $this->line("- Terhapus {$customerCount} data customer (termasuk yang di-soft delete).");

            \Illuminate\Support\Facades\DB::commit();

            // 12. Clear Cache (Redis) - Sesuai strategi cache proyek
            \Illuminate\Support\Facades\Redis::flushall();
            $this->line("- Cache Redis telah dibersihkan.");

            $this->info('Seluruh data transaksi dan customer berhasil dihapus.');

            return 0;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->error('Gagal menghapus data: ' . $e->getMessage());
            return 1;
        }
    }
}
