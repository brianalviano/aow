<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DailySummaryService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

/**
 * Artisan command to generate daily order summaries per drop point.
 *
 * Runs automatically every day at 23:55 via the scheduler. Can also be triggered
 * manually with an optional --date option for backfilling historical data.
 *
 * @example php artisan app:generate-daily-summary
 * @example php artisan app:generate-daily-summary --date=2025-01-15
 */
class GenerateDailySummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-daily-summary
                            {--date= : Tanggal tertentu untuk di-generate (format: Y-m-d), default: hari ini}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate ringkasan pesanan harian per drop point dan simpan ke tabel daily_summaries';

    /**
     * Execute the console command.
     *
     * @param  DailySummaryService $service
     * @return int
     */
    public function handle(DailySummaryService $service): int
    {
        $rawDate = $this->option('date');
        $date    = $rawDate ? Carbon::createFromFormat('Y-m-d', $rawDate) : Carbon::today();

        if (!$date) {
            $this->error("Format tanggal tidak valid. Gunakan format Y-m-d (contoh: 2025-01-15).");
            return Command::FAILURE;
        }

        $this->info("Membuat ringkasan harian untuk tanggal: {$date->toDateString()}...");

        try {
            $dropPointCount = $service->generateForDate($date);
            $this->info("  ✓ DailySummary   : {$dropPointCount} drop point diproses.");

            $productCount = $service->generateProductSummaryForDate($date);
            $this->info("  ✓ ProductSummary : {$productCount} produk diproses.");

            $this->info("Selesai.");
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Gagal membuat ringkasan harian: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
