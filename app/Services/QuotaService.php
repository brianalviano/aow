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

        // We no longer query existing orders because the quota is now per-order (MOQ).
        // The frontend will add current cart items to these base values.
        return [
            'has_quota' => true,
            'min_qty' => $dropPoint->min_po_qty,
            'min_amount' => $dropPoint->min_po_amount,
            'current_qty' => 0,
            'current_amount' => 0,
            'is_fulfilled' => false,
            'percentage' => 0,
        ];
    }

    /**
     * Check PO quotas for individual orders and cancel those that do not meet the MOQ.
     * 
     * @param string $deliveryDate (format: Y-m-d)
     * @return array Array containing the number of checked orders and cancelled orders.
     */
    public function cancelUnderperformingPoOrders(string $deliveryDate): array
    {
        $validStatuses = [
            OrderStatus::PENDING->value,
            OrderStatus::CONFIRMED->value,
        ];

        // Get all orders for the delivery date that have a drop point
        $orders = Order::whereNotNull('drop_point_id')
            ->whereDate('delivery_date', $deliveryDate)
            ->whereIn('order_status', $validStatuses)
            ->with(['dropPoint', 'items'])
            ->get();

        $cancelledCount = 0;

        foreach ($orders as $order) {
            $dropPoint = $order->dropPoint;
            
            // Skip if drop point no longer exists or has no quota requirements
            if (!$dropPoint || (!$dropPoint->min_po_qty && !$dropPoint->min_po_amount)) {
                continue;
            }

            $currentQty = $order->items->sum('quantity');
            $currentAmount = $order->total_amount;

            $isQtyFulfilled = $dropPoint->min_po_qty ? $currentQty >= $dropPoint->min_po_qty : true;
            $isAmountFulfilled = $dropPoint->min_po_amount ? $currentAmount >= $dropPoint->min_po_amount : true;

            if (!$isQtyFulfilled || !$isAmountFulfilled) {
                try {
                    DB::transaction(function () use ($order, &$cancelledCount) {
                        /** @var \App\Models\Order $order */
                        $order->update([
                            'order_status' => OrderStatus::CANCELLED->value,
                            'cancellation_note' => 'Otomatis dibatalkan sistem: Pesanan Anda tidak memenuhi kuota minimum (MOQ) yang disyaratkan oleh Drop Point.',
                        ]);
                        $cancelledCount++;
                    });
                } catch (\Throwable $e) {
                    Log::error('Failed to cancel underperforming individual order', [
                        'order_id' => $order->id,
                        'delivery_date' => $deliveryDate,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return [
            'checked_orders' => $orders->count(),
            'cancelled_orders' => $cancelledCount,
        ];
    }

}
