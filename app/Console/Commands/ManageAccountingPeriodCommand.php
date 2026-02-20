<?php
declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Schema};
use Carbon\Carbon;
use App\Models\AccountingPeriod;

/**
 * Console command sederhana untuk mengelola AccountingPeriod per bulan.
 *
 * - Default: membuat periode bulan berjalan berstatus open jika belum ada.
 * - Opsi --month=YYYY-MM: targetkan bulan tertentu.
 * - Opsi --close-previous: menutup periode bulan sebelumnya jika ada dan masih open.
 */
class ManageAccountingPeriodCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'accounting:period {--month=} {--close-previous}';

    /**
     * @var string
     */
    protected $description = 'Membuat periode akuntansi untuk bulan yang ditentukan dan opsional menutup bulan sebelumnya';

    /**
     * Entry point command.
     *
     * @return int
     */
    public function handle(): int
    {
        if (!Schema::hasTable('accounting_periods')) {
            $this->components->error('Tabel accounting_periods tidak tersedia.');
            return self::FAILURE;
        }

        $monthOpt = (string) ($this->option('month') ?? '');
        $target = $this->parseMonth($monthOpt);
        $code = $target->format('Y-m');

        DB::transaction(function () use ($code, $target) {
            $period = AccountingPeriod::query()->where('code', $code)->first();
            if (!$period) {
                AccountingPeriod::query()->create([
                    'code' => $code,
                    'name' => 'Periode ' . $code,
                    'start_date' => $target->copy()->startOfMonth()->toDateString(),
                    'end_date' => $target->copy()->endOfMonth()->toDateString(),
                    'status' => 'open',
                    'closed_at' => null,
                    'closed_by_id' => null,
                ]);
                $this->components->info("Membuat periode {$code} (open).");
            } else {
                $this->components->info("Periode {$code} sudah ada, tidak diubah.");
            }

            if ($this->option('close-previous')) {
                $prev = $target->copy()->subMonth();
                $prevCode = $prev->format('Y-m');
                $prevRow = AccountingPeriod::query()->where('code', $prevCode)->first();
                if ($prevRow && (string) ($prevRow->status ?? '') === 'open') {
                    $prevRow->update([
                        'status' => 'closed',
                        'closed_at' => now(),
                    ]);
                    $this->components->info("Menutup periode {$prevCode} (closed).");
                } else {
                    $this->components->info("Periode {$prevCode} tidak ada atau sudah closed, tidak diubah.");
                }
            }
        }, 1);

        return self::SUCCESS;
    }

    /**
     * Parse teks bulan menjadi Carbon (awal bulan).
     *
     * @param string $monthText
     * @return Carbon
     */
    private function parseMonth(string $monthText): Carbon
    {
        if ($monthText !== '') {
            try {
                return Carbon::createFromFormat('Y-m', $monthText)->startOfMonth();
            } catch (\Throwable $e) {
                // Jika format tidak valid, fallback ke bulan berjalan
            }
        }
        return Carbon::now()->startOfMonth();
    }
}
