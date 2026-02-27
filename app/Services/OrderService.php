<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Checkout\ProcessOrderData;
use App\Enums\PaymentMethodType;
use App\Mail\{CustomerWelcomeMail, OrderPlacedMail};
use App\Models\{Customer, Order, OrderItem, OrderItemOption, PaymentMethod};
use App\Notifications\{OrderPlacedNotification, OrderStatusChangedNotification};
use App\Traits\{FileHelperTrait, RetryableTransactionsTrait};
use Illuminate\Support\Facades\{Auth, DB, Hash, Log, Mail};
use Throwable;

class OrderService
{
    use RetryableTransactionsTrait, FileHelperTrait;

    /**
     * Create a new OrderService instance.
     *
     * @param CheckoutService $checkoutService
     * @param MidtransService $midtransService
     */
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly MidtransService $midtransService
    ) {}
    /**
     * Mark the given order as completed/delivered with photo proof.
     *
     * @param Order       $order
     * @param \Illuminate\Http\UploadedFile|string|null $deliveryPhotoPath Storage path or UploadedFile of the delivery photo proof.
     * @return Order
     * @throws \Throwable
     */
    public function completeOrder(Order $order, $deliveryPhotoPath = null): Order
    {
        try {
            return DB::transaction(function () use ($order, $deliveryPhotoPath) {
                // Ensure the order is currently shipped before marking as delivered
                if ($order->order_status !== 'shipped') {
                    throw new \Exception("Pesanan tidak dapat diselesaikan karena status saat ini adalah {$order->order_status}.");
                }

                $photoPath = $this->handleFileInput($deliveryPhotoPath, null, 'orders/delivery');

                $order->update([
                    'order_status'   => 'delivered',
                    'delivery_photo' => $photoPath,
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
            ->with(['dropPoint', 'paymentMethod', 'customer', 'testimonial']);

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
            ->with(['dropPoint', 'paymentMethod', 'testimonial'])
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

    /**
     * Process order creation within a transaction.
     *
     * @param ProcessOrderData $data Data for creating the order.
     * @return Order The created order object.
     * @throws Throwable
     */
    public function processOrder(ProcessOrderData $data): Order
    {
        return $this->runWithRetry(function () use ($data) {
            try {
                return DB::transaction(function () use ($data) {
                    $customer = Auth::guard('customer')->user();

                    if (!$customer) {
                        $customer = Customer::where('email', $data->email)->first();

                        if (!$customer) {
                            $password = '12345678';

                            $customer = Customer::create([
                                'name'          => $data->name,
                                'phone'         => $data->phone,
                                'email'         => $data->email,
                                'password'      => Hash::make($password),
                                'school_class'  => $data->schoolClass,
                                'is_active'     => true,
                            ]);

                            // Send welcome email with credentials
                            DB::afterCommit(function () use ($customer, $password) {
                                Mail::to($customer->email)->send(new CustomerWelcomeMail($customer, $password));
                            });
                        }

                        Auth::guard('customer')->login($customer);
                    }

                    $fees = $this->checkoutService->calculateFees($data->cart, (string) data_get($data->dropPoint, 'id', ''));
                    $totalAmount = $fees['subtotal'] + $fees['deliveryFee'] + $fees['taxAmount'] + $fees['adminFee'];

                    $order = Order::create([
                        'number'             => $this->generateOrderNumber(),
                        'drop_point_id'      => data_get($data->dropPoint, 'id'),
                        'customer_id'        => $customer->id,
                        'delivery_date'      => $data->deliveryDate ?? now()->addDay()->format('Y-m-d'),
                        'delivery_time'      => $data->deliveryTime ?? '12:00',
                        'payment_method_id'  => $data->paymentMethodId,
                        'payment_status'     => 'pending',
                        'order_status'       => 'pending',
                        'total_amount'       => $totalAmount,
                        'delivery_fee'       => $fees['deliveryFee'],
                        'admin_fee'          => $fees['adminFee'],
                        'tax_amount'         => $fees['taxAmount'],
                    ]);

                    foreach ($data->cart as $item) {
                        $orderItem = OrderItem::create([
                            'order_id'   => $order->id,
                            'product_id' => data_get($item, 'product.id'),
                            'quantity'   => $item['quantity'],
                            'price'      => $item['basePrice'],
                            'subtotal'   => $item['totalPrice'],
                            'note'       => $item['notes'] ?? null,
                        ]);

                        if (isset($item['selectedOptions'])) {
                            foreach ($item['selectedOptions'] as $optionId => $selection) {
                                $itemIds = is_array($selection) ? $selection : [$selection];
                                foreach ($itemIds as $optionItemId) {
                                    $extraPrice = $this->resolveOptionExtraPrice($item, (string) $optionId, (string) $optionItemId);

                                    OrderItemOption::create([
                                        'order_item_id'          => $orderItem->id,
                                        'product_option_id'      => $optionId,
                                        'product_option_item_id' => $optionItemId,
                                        'extra_price'            => $extraPrice,
                                    ]);
                                }
                            }
                        }
                    }

                    // Midtrans Integration
                    $paymentMethod = PaymentMethod::findOrFail($data->paymentMethodId);
                    if ($paymentMethod->type === PaymentMethodType::GATEWAY) {
                        try {
                            $midtransResponse = $this->midtransService->charge($order, $paymentMethod);
                            $order->update([
                                'payment_details' => (array) $midtransResponse,
                            ]);
                        } catch (Throwable $e) {
                            Log::error('Order Creation - Midtrans Charge Failed', [
                                'order_id' => $order->id,
                                'error'    => $e->getMessage(),
                            ]);
                            throw $e;
                        }
                    }

                    // Send order confirmation email and database notification
                    DB::afterCommit(function () use ($order) {
                        Mail::to($order->customer->email)->send(new OrderPlacedMail($order));
                        $order->customer->notify(new OrderPlacedNotification($order));
                    });

                    session()->forget(['checkout_cart', 'checkout_drop_point']);

                    return $order;
                });
            } catch (Throwable $e) {
                Log::error('OrderService - Failed to process order', [
                    'error'       => $e->getMessage(),
                    'trace'       => $e->getTraceAsString(),
                    'customer_id' => Auth::guard('customer')->id(),
                    'payload'     => [
                        'email'             => $data->email,
                        'payment_method_id' => $data->paymentMethodId,
                        'cart_count'        => count($data->cart),
                        'drop_point'        => $data->dropPoint,
                        'cart_sample'       => collect($data->cart)->first(),
                    ],
                ]);
                throw $e;
            }
        });
    }

    /**
     * Generate a unique order number with format ORD/MMYYYY/XXXXXX.
     * 
     * The sequence number (XXXXXX) resets every month.
     *
     * @return string The generated order number.
     */
    private function generateOrderNumber(): string
    {
        $now = now();
        $prefix = "ORD/" . $now->format('mY') . "/";

        $lastOrder = Order::where('number', 'like', "{$prefix}%")
            ->orderBy('number', 'desc')
            ->lockForUpdate()
            ->first();

        $sequence = 1;
        if ($lastOrder) {
            $lastNumber = $lastOrder->number;
            $lastSequence = (int) substr($lastNumber, strrpos($lastNumber, '/') + 1);
            $sequence = $lastSequence + 1;
        }

        return $prefix . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Resolve the extra price for a specific product option item.
     *
     * @param array $item The cart item containing product and option data.
     * @param string $optionId The ID of the product option.
     * @param string $optionItemId The ID of the product option item.
     * @return int The extra price for the specified option item.
     */
    private function resolveOptionExtraPrice(array $item, string $optionId, string $optionItemId): int
    {
        $options = data_get($item, 'product.options', []);
        $productOptions = isset($options['data']) ? $options['data'] : $options;

        if (!is_array($productOptions)) {
            return 0;
        }

        foreach ($productOptions as $opt) {
            if ((string) data_get($opt, 'id') === $optionId) {
                $items = data_get($opt, 'items', []);
                $optionItems = isset($items['data']) ? $items['data'] : $items;

                if (!is_array($optionItems)) {
                    continue;
                }

                foreach ($optionItems as $optItem) {
                    if ((string) data_get($optItem, 'id') === $optionItemId) {
                        return (int) data_get($optItem, 'extra_price', 0);
                    }
                }
            }
        }

        return 0;
    }
}
