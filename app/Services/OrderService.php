<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Mark the given order as completed/delivered.
     *
     * @param Order $order
     * @return Order
     * @throws \Throwable
     */
    public function completeOrder(Order $order): Order
    {
        try {
            return DB::transaction(function () use ($order) {
                // Ensure the order is currently shipped before marking as delivered
                if ($order->order_status !== 'shipped') {
                    throw new \Exception("Pesanan tidak dapat diselesaikan karena status saat ini adalah {$order->order_status}.");
                }

                $order->update([
                    'order_status' => 'delivered',
                ]);

                return $order->fresh();
            });
        } catch (\Throwable $e) {
            Log::error('Gagal menyelesaikan pesanan', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
