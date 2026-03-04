<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\DropPoint;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuotaService
{
    /**
     * Calculate PO quota progress for a specific drop point and delivery date
     * 
     * @param string $dropPointId
     * @param string $deliveryDate (format: Y-m-d)
     * @return array
     */
    public function calculateDropPointQuotaProgress(string $dropPointId, string $deliveryDate): array
    {
        $dropPoint = DropPoint::find($dropPointId);

        if (!$dropPoint || (!$dropPoint->min_po_qty && !$dropPoint->min_po_amount)) {
            return [
                'has_quota' => false,
                'min_qty' => null,
                'min_amount' => null,
                'current_qty' => 0,
                'current_amount' => 0,
                'is_fulfilled' => true,
                'percentage' => 100,
            ];
        }

        // Only count active PO orders (PENDING, CONFIRMED)
        // Usually, order_status 'CONFIRMED' means it's paid and valid.
        $validStatuses = [
            OrderStatus::PENDING->value,
            OrderStatus::CONFIRMED->value,
        ];

        $orders = Order::where('drop_point_id', $dropPoint->id)
            ->whereDate('delivery_date', $deliveryDate)
            ->whereIn('order_status', $validStatuses)
            ->with('items')
            ->get();

        $currentQty = 0;
        foreach ($orders as $order) {
            $currentQty += $order->items->sum('quantity');
        }

        $currentAmount = $orders->sum('total_amount');

        $isQtyFulfilled = $dropPoint->min_po_qty ? $currentQty >= $dropPoint->min_po_qty : true;
        $isAmountFulfilled = $dropPoint->min_po_amount ? $currentAmount >= $dropPoint->min_po_amount : true;

        $isFulfilled = $isQtyFulfilled && $isAmountFulfilled;

        $qtyPercentage = 0;
        if ($dropPoint->min_po_qty) {
            $qtyPercentage = min(100, ($currentQty / $dropPoint->min_po_qty) * 100);
        }

        $amountPercentage = 0;
        if ($dropPoint->min_po_amount) {
            $amountPercentage = min(100, ($currentAmount / $dropPoint->min_po_amount) * 100);
        }

        // Use the highest relevant percentage for the progress bar
        $percentage = 100;
        if ($dropPoint->min_po_qty && $dropPoint->min_po_amount) {
            $percentage = min($qtyPercentage, $amountPercentage);
        } elseif ($dropPoint->min_po_qty) {
            $percentage = $qtyPercentage;
        } elseif ($dropPoint->min_po_amount) {
            $percentage = $amountPercentage;
        }

        return [
            'has_quota' => true,
            'min_qty' => $dropPoint->min_po_qty,
            'min_amount' => $dropPoint->min_po_amount,
            'current_qty' => $currentQty,
            'current_amount' => $currentAmount,
            'is_fulfilled' => $isFulfilled,
            'percentage' => round($percentage),
        ];
    }

    /**
     * Check PO quotas for all active drop points and cancel orders for underperforming ones.
     * 
     * @param string $deliveryDate (format: Y-m-d)
     * @return array Array containing the number of checked drop points and cancelled orders.
     */
    public function cancelUnderperformingPoOrders(string $deliveryDate): array
    {
        $dropPoints = DropPoint::where(function ($query) {
            $query->whereNotNull('min_po_qty')
                ->orWhereNotNull('min_po_amount');
        })->where('is_active', true)->get();

        $cancelledCount = 0;
        $failedDropPointsCount = 0;

        foreach ($dropPoints as $dropPoint) {
            $quotaStatus = $this->calculateDropPointQuotaProgress($dropPoint->id, $deliveryDate);

            if (!$quotaStatus['is_fulfilled']) {
                $failedDropPointsCount++;

                $validStatuses = [
                    OrderStatus::PENDING->value,
                    OrderStatus::CONFIRMED->value,
                ];

                $ordersToCancel = Order::where('drop_point_id', $dropPoint->id)
                    ->whereDate('delivery_date', $deliveryDate)
                    ->whereIn('order_status', $validStatuses)
                    ->get();

                if ($ordersToCancel->isEmpty()) {
                    continue;
                }

                try {
                    DB::transaction(function () use ($ordersToCancel, &$cancelledCount) {
                        foreach ($ordersToCancel as $order) {
                            $order->update([
                                'order_status' => OrderStatus::CANCELLED->value,
                                'cancellation_note' => 'Otomatis dibatalkan sistem: Kuota minimum kolektif Drop Point (PO) tidak terpenuhi hingga batas waktu pemesanan.',
                            ]);
                            $cancelledCount++;
                        }
                    });
                } catch (\Throwable $e) {
                    Log::error('Failed to cancel underperforming PO orders', [
                        'drop_point_id' => $dropPoint->id,
                        'delivery_date' => $deliveryDate,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            }
        }

        return [
            'checked_drop_points' => $dropPoints->count(),
            'failed_drop_points' => $failedDropPointsCount,
            'cancelled_orders' => $cancelledCount,
        ];
    }
}
