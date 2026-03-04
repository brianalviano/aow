<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckPoQuotas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-po-quotas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check PO quotas for all active drop points and cancel orders for underperforming ones.';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\QuotaService $quotaService)
    {
        // Identify the delivery date we are checking for.
        // If this runs at cutoff time (e.g. 20:00), we are checking for orders to be delivered tomorrow (or based on min_days_ahead).
        // To be safe, let's target all active preorder orders. Usually, we just check tomorrow's date if it's H-1.
        $orderSettings = \App\DTOs\Setting\OrderSettingsDTO::load();
        $minDays = $orderSettings->orderMinDaysAhead ?: 1;
        $targetDeliveryDate = now()->addDays($minDays)->format('Y-m-d');

        $this->info("Checking PO Quotas for delivery date: {$targetDeliveryDate}");

        $result = $quotaService->cancelUnderperformingPoOrders($targetDeliveryDate);

        $this->info("PO Quota check completed.");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Drop Points Evaluated', $result['checked_drop_points']],
                ['Drop Points Failed Quota', $result['failed_drop_points']],
                ['Orders Cancelled', $result['cancelled_orders']],
            ]
        );
    }
}
