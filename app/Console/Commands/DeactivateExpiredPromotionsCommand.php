<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Log};
use Carbon\Carbon;
use App\Models\{Discount, Voucher};

final class DeactivateExpiredPromotionsCommand extends Command
{
    protected $signature = 'promotions:deactivate-expired';
    protected $description = 'Nonaktifkan diskon dan voucher yang melewati end_at';

    public function handle(): int
    {
        $now = Carbon::now()->toDateTimeString();
        try {
            return DB::transaction(function () use ($now) {
                $discounts = Discount::query()
                    ->where('is_active', true)
                    ->whereNotNull('end_at')
                    ->where('end_at', '<=', $now)
                    ->update(['is_active' => false]);

                $vouchers = Voucher::query()
                    ->where('is_active', true)
                    ->whereNotNull('end_at')
                    ->where('end_at', '<=', $now)
                    ->update(['is_active' => false]);

                $this->info('Deactivated discounts: ' . $discounts . ', vouchers: ' . $vouchers);
                return self::SUCCESS;
            }, 5);
        } catch (\Throwable $e) {
            Log::error('promotions_deactivate_expired_failed', [
                'now' => $now,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
