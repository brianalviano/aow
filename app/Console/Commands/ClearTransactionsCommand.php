<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\DailySummary;
use App\Models\Feedback;
use App\Models\FoodRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\OrderShipping;
use App\Models\ProductSummary;
use App\Models\Testimonial;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

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
        if (! $this->option('force') && ! $this->confirm('Apakah Anda yakin ingin menghapus SELURUH data transaksi dan CUSTOMER? Tindakan ini tidak dapat dibatalkan.')) {
            $this->info('Operasi dibatalkan.');

            return 0;
        }

        $this->info('Menghapus data transaksi dan customer...');

        try {
            DB::beginTransaction();

            // 1. Hapus Testimonials (yang berkaitan dengan order)
            $testimonialCount = Testimonial::count();
            Testimonial::query()->delete();
            $this->line("- Terhapus {$testimonialCount} testimonial.");

            // 2. Hapus Order Item Options
            $optionCount = OrderItemOption::count();
            OrderItemOption::query()->delete();
            $this->line("- Terhapus {$optionCount} pilihan item order.");

            // 3. Hapus Order Items
            $itemCount = OrderItem::count();
            OrderItem::query()->delete();
            $this->line("- Terhapus {$itemCount} item order.");

            // 4. Hapus Order Shippings
            $shippingCount = OrderShipping::count();
            OrderShipping::query()->delete();
            $this->line("- Terhapus {$shippingCount} data pengiriman.");

            // 5. Hapus Orders (Force delete jika menggunakan SoftDeletes)
            $orderCount = Order::withTrashed()->count();
            Order::withTrashed()->forceDelete();
            $this->line("- Terhapus {$orderCount} data order (termasuk yang di-soft delete).");

            // 6. Hapus Daily Summaries (karena data dasarnya sudah hilang)
            $summaryCount = DailySummary::count();
            DailySummary::query()->delete();
            $this->line("- Terhapus {$summaryCount} ringkasan harian.");

            // 7. Hapus Product Summaries
            $productSummaryCount = ProductSummary::count();
            ProductSummary::query()->delete();
            $this->line("- Terhapus {$productSummaryCount} ringkasan produk.");

            // 8. Hapus Customer Addresses
            $addressCount = CustomerAddress::withTrashed()->count();
            CustomerAddress::withTrashed()->forceDelete();
            $this->line("- Terhapus {$addressCount} alamat customer.");

            // 9. Hapus Feedback
            $feedbackCount = Feedback::count();
            Feedback::query()->delete();
            $this->line("- Terhapus {$feedbackCount} feedback.");

            // 10. Hapus Food Requests
            $foodRequestCount = FoodRequest::count();
            FoodRequest::query()->delete();
            $this->line("- Terhapus {$foodRequestCount} permintaan makanan.");

            // 11. Hapus Customers (Force delete karena menggunakan SoftDeletes)
            $customerCount = Customer::withTrashed()->count();
            Customer::withTrashed()->forceDelete();
            $this->line("- Terhapus {$customerCount} data customer (termasuk yang di-soft delete).");

            DB::commit();

            // 12. Clear Cache (Redis) - Sesuai strategi cache proyek
            Redis::flushall();
            $this->line('- Cache Redis telah dibersihkan.');

            $this->info('Seluruh data transaksi dan customer berhasil dihapus.');

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Gagal menghapus data: '.$e->getMessage());

            return 1;
        }
    }
}
