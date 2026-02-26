<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Support\Facades\{DB, Log};

class OrderService
{
    /**
     * Mark the given order as completed/delivered with photo proof.
     *
     * @param Order       $order
     * @param string|null $deliveryPhotoPath  Storage path of the delivery photo proof.
     * @return Order
     * @throws \Throwable
     */
    public function completeOrder(Order $order, ?string $deliveryPhotoPath = null): Order
    {
        try {
            return DB::transaction(function () use ($order, $deliveryPhotoPath) {
                // Ensure the order is currently shipped before marking as delivered
                if ($order->order_status !== 'shipped') {
                    throw new \Exception("Pesanan tidak dapat diselesaikan karena status saat ini adalah {$order->order_status}.");
                }

                $order->update([
                    'order_status'   => 'delivered',
                    'delivery_photo' => $deliveryPhotoPath,
                    'delivered_at'   => now(),
                ]);

                $order->load('customer');
                $order->customer->notify(new OrderStatusChangedNotification($order, 'delivered'));

                return $order->fresh();
            });
        } catch (\Throwable $e) {
            Log::error('Gagal menyelesaikan pesanan', [
                'order_id'            => $order->id,
                'customer_id'         => $order->customer_id,
                'delivery_photo_path' => $deliveryPhotoPath,
                'error'               => $e->getMessage(),
                'trace'               => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Cancel the given order.
     *
     * @param Order $order
     * @param string|null $reason
     * @return Order
     * @throws \Throwable
     */
    public function cancelOrder(Order $order, ?string $reason = null): Order
    {
        try {
            return DB::transaction(function () use ($order, $reason) {
                // Only allow cancellation if the order is still pending
                if ($order->order_status !== 'pending') {
                    throw new \Exception("Pesanan tidak dapat dibatalkan karena status saat ini adalah {$order->order_status}.");
                }

                $order->update([
                    'order_status'      => 'cancelled',
                    'cancellation_note' => $reason,
                ]);

                $order->load('customer');
                $order->customer->notify(new OrderStatusChangedNotification($order, 'cancelled'));

                return $order->fresh();
            });
        } catch (\Throwable $e) {
            Log::error('Gagal membatalkan pesanan', [
                'order_id'    => $order->id,
                'customer_id' => $order->customer_id,
                'reason'      => $reason,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Confirm the given pending order.
     *
     * @param Order $order
     * @return Order
     * @throws \Throwable
     */
    public function confirmOrder(Order $order): Order
    {
        try {
            return DB::transaction(function () use ($order) {
                if ($order->order_status !== 'pending') {
                    throw new \Exception("Pesanan tidak dapat dikonfirmasi karena status saat ini adalah {$order->order_status}.");
                }

                $order->update([
                    'order_status' => 'confirmed',
                ]);

                $order->load('customer');
                $order->customer->notify(new OrderStatusChangedNotification($order, 'confirmed'));

                return $order->fresh();
            });
        } catch (\Throwable $e) {
            Log::error('Gagal mengkonfirmasi pesanan', [
                'order_id'    => $order->id,
                'customer_id' => $order->customer_id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Mark the given confirmed order as shipped.
     *
     * @param Order $order
     * @return Order
     * @throws \Throwable
     */
    public function shipOrder(Order $order): Order
    {
        try {
            return DB::transaction(function () use ($order) {
                if ($order->order_status !== 'confirmed') {
                    throw new \Exception("Pesanan tidak dapat dikirim karena status saat ini adalah {$order->order_status}.");
                }

                $order->update([
                    'order_status' => 'shipped',
                ]);

                $order->load('customer');
                $order->customer->notify(new OrderStatusChangedNotification($order, 'shipped'));

                return $order->fresh();
            });
        } catch (\Throwable $e) {
            Log::error('Gagal mengubah status pesanan ke dikirim', [
                'order_id'    => $order->id,
                'customer_id' => $order->customer_id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get filtered and paginated orders for Admin.
     *
     * @param \App\DTOs\Order\OrderFilterDTO $dto
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFilteredOrdersForAdmin(\App\DTOs\Order\OrderFilterDTO $dto, int $perPage = 15)
    {
        $query = Order::query()
            ->with(['dropPoint', 'paymentMethod', 'customer']);

        // Filter by Status
        if ($dto->status && $dto->status !== 'all') {
            switch ($dto->status) {
                case 'unpaid':
                    $query->where('payment_status', 'pending')
                        ->where(function ($q) {
                            $q->whereDoesntHave('paymentMethod', function ($pq) {
                                $pq->where('category', 'cash');
                            });
                        });
                    break;
                case 'process':
                    $query->where(function ($q) {
                        $q->where('payment_status', '!=', 'pending')
                            ->orWhereHas('paymentMethod', function ($pq) {
                                $pq->where('category', 'cash');
                            });
                    })->whereIn('order_status', ['pending', 'confirmed']);
                    break;
                case 'shipped':
                    $query->where('order_status', 'shipped');
                    break;
                case 'completed':
                    $query->where('order_status', 'delivered');
                    break;
                case 'cancelled':
                    $query->where(function ($q) {
                        $q->where('order_status', 'cancelled')
                            ->orWhere('payment_status', 'failed');
                    });
                    break;
            }
        }

        // Filter by Search (Order Number, Customer Name, or Product Name)
        if ($dto->search) {
            $query->where(function ($q) use ($dto) {
                $q->where('number', 'like', "%{$dto->search}%")
                    ->orWhereHas('customer', function ($cq) use ($dto) {
                        $cq->where('name', 'like', "%{$dto->search}%");
                    })
                    ->orWhereHas('items.product', function ($pq) use ($dto) {
                        $pq->where('name', 'like', "%{$dto->search}%");
                    });
            });
        }

        // Filter by Date
        if ($dto->dateRange === '30_days') {
            $query->where('created_at', '>=', now()->subDays(30));
        } elseif ($dto->dateRange === '90_days') {
            $query->where('created_at', '>=', now()->subDays(90));
        } elseif ($dto->dateRange === 'custom' && $dto->startDate && $dto->endDate) {
            $query->whereBetween('created_at', [
                $dto->startDate . ' 00:00:00',
                $dto->endDate . ' 23:59:59'
            ]);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get filtered and paginated orders for a customer.
     *
     * @param string $customerId
     * @param \App\DTOs\Order\OrderFilterDTO $dto
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFilteredOrders(string $customerId, \App\DTOs\Order\OrderFilterDTO $dto, int $perPage = 15)
    {
        $query = Order::query()
            ->with(['dropPoint', 'paymentMethod'])
            ->where('customer_id', $customerId);

        // Filter by Status
        if ($dto->status && $dto->status !== 'all') {
            switch ($dto->status) {
                case 'unpaid':
                    $query->where('payment_status', 'pending')
                        ->where(function ($q) {
                            $q->whereDoesntHave('paymentMethod', function ($pq) {
                                $pq->where('category', 'cash');
                            });
                        });
                    break;
                case 'process':
                    $query->where(function ($q) {
                        $q->where('payment_status', '!=', 'pending')
                            ->orWhereHas('paymentMethod', function ($pq) {
                                $pq->where('category', 'cash');
                            });
                    })->whereIn('order_status', ['pending', 'confirmed']);
                    break;
                case 'shipped':
                    $query->where('order_status', 'shipped');
                    break;
                case 'completed':
                    $query->where('order_status', 'delivered');
                    break;
                case 'cancelled':
                    $query->where(function ($q) {
                        $q->where('order_status', 'cancelled')
                            ->orWhere('payment_status', 'failed');
                    });
                    break;
            }
        }

        // Filter by Search (Order Number or Product Name)
        if ($dto->search) {
            $query->where(function ($q) use ($dto) {
                $q->where('number', 'like', "%{$dto->search}%")
                    ->orWhereHas('items.product', function ($pq) use ($dto) {
                        $pq->where('name', 'like', "%{$dto->search}%");
                    });
            });
        }

        // Filter by Date
        if ($dto->dateRange === '30_days') {
            $query->where('created_at', '>=', now()->subDays(30));
        } elseif ($dto->dateRange === '90_days') {
            $query->where('created_at', '>=', now()->subDays(90));
        } elseif ($dto->dateRange === 'custom' && $dto->startDate && $dto->endDate) {
            $query->whereBetween('created_at', [
                $dto->startDate . ' 00:00:00',
                $dto->endDate . ' 23:59:59'
            ]);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Automatically cancel all expired unpaid orders.
     *
     * @return int Number of orders cancelled
     */
    public function cancelExpiredOrders(): int
    {
        $expiredOrders = Order::query()
            ->where('payment_status', 'pending')
            ->where('order_status', 'pending')
            ->whereNotNull('payment_expired_at')
            ->where('payment_expired_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($expiredOrders as $order) {
            try {
                $this->cancelOrder($order, 'Pembatalan otomatis oleh sistem karena melewati batas waktu pembayaran.');
                $count++;
            } catch (\Throwable $e) {
                // Individual failures are logged inside cancelOrder, we continue with others
                continue;
            }
        }

        return $count;
    }
}
