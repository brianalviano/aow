<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\{SalesPaymentStatus, SalesDeliveryStatus};
use App\Models\{Sales, SalesDelivery, User};
use Illuminate\Support\Carbon;

class SalesDashboardService
{
    public function buildStatsFor(string $role, User $user): array
    {
        $salesToday = $this->countSalesToday();
        $unpaid = $this->countByPaymentStatus(SalesPaymentStatus::Unpaid->value);
        $partiallyPaid = $this->countByPaymentStatus(SalesPaymentStatus::PartiallyPaid->value);
        $paid = $this->countByPaymentStatus(SalesPaymentStatus::Paid->value);
        $totalOutstanding = $this->sumOutstandingAmount();
        $deliveriesInDelivery = $this->countDeliveriesInDelivery();

        return [
            'summary' => [
                'sales_today' => $salesToday,
                'sales_unpaid' => $unpaid,
                'sales_partially_paid' => $partiallyPaid,
                'sales_paid' => $paid,
                'total_outstanding' => $totalOutstanding,
                'deliveries_in_delivery' => $deliveriesInDelivery,
            ],
            'lists' => [
                'recent_sales' => $this->getRecentSales(),
            ],
        ];
    }

    private function countSalesToday(): int
    {
        $today = Carbon::today();
        return (int) Sales::query()
            ->whereDate('sale_datetime', $today)
            ->count();
    }

    private function countByPaymentStatus(string $status): int
    {
        return (int) Sales::query()
            ->where('payment_status', $status)
            ->count();
    }

    private function sumOutstandingAmount(): int
    {
        return (int) Sales::query()
            ->sum('outstanding_amount');
    }

    private function countDeliveriesInDelivery(): int
    {
        return (int) SalesDelivery::query()
            ->where('status', SalesDeliveryStatus::InDelivery->value)
            ->count();
    }

    private function getRecentSales(): array
    {
        return Sales::query()
            ->with(['warehouse:id,name', 'customer:id,name,phone'])
            ->latest('sale_datetime')
            ->latest('created_at')
            ->take(5)
            ->get(['id', 'receipt_number', 'invoice_number', 'sale_datetime', 'warehouse_id', 'customer_id', 'payment_status', 'grand_total'])
            ->map(function ($s) {
                $statusValue = $s->payment_status instanceof SalesPaymentStatus ? (string) $s->payment_status->value : (string) $s->payment_status;
                return [
                    'id' => (string) $s->getKey(),
                    'number' => (string) ($s->invoice_number ?? $s->receipt_number ?? ''),
                    'sale_date' => $s->sale_datetime ? $s->sale_datetime->toDateString() : null,
                    'customer' => [
                        'id' => (string) ($s->customer_id ?? ''),
                        'name' => (string) ($s->customer?->name ?? ''),
                        'phone' => (string) ($s->customer?->phone ?? ''),
                    ],
                    'warehouse' => [
                        'id' => (string) ($s->warehouse_id ?? ''),
                        'name' => (string) ($s->warehouse?->name ?? ''),
                    ],
                    'payment_status' => $statusValue,
                    'payment_status_label' => SalesPaymentStatus::tryFrom($statusValue)?->label() ?? 'Tidak Diketahui',
                    'grand_total' => (int) $s->grand_total,
                ];
            })
            ->toArray();
    }
}
