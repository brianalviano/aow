<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Checkout\ProcessOrderData;
use App\Enums\{OrderStatus, PaymentMethodType};
use App\Mail\{CustomerWelcomeMail, OrderPlacedMail};
use App\Models\{Customer, Order, OrderItem, OrderItemOption, PaymentMethod, Product};
use App\Notifications\{OrderPlacedNotification, OrderStatusChangedNotification};
use App\Traits\{FileHelperTrait, RetryableTransactionsTrait};
use Illuminate\Support\Facades\{Auth, DB, Hash, Log, Mail};
use Illuminate\Support\Collection;
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
                if ($order->order_status !== OrderStatus::SHIPPED) {
                    throw new \Exception("Pesanan tidak dapat diselesaikan karena status saat ini adalah {$order->order_status->value}.");
                }

                $photoPath = $this->handleFileInput($deliveryPhotoPath, null, 'orders/delivery');

                $order->load('paymentMethod');

                $updateData = [
                    'order_status'   => OrderStatus::DELIVERED,
                    'delivery_photo' => $photoPath,
                    'delivered_at'   => now(),
                ];

                if ($order->payment_status === \App\Enums\PaymentStatus::PENDING && $order->paymentMethod?->category === 'cash') {
                    $updateData['payment_status'] = \App\Enums\PaymentStatus::PAID;
                }

                $order->update($updateData);

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
                if ($order->order_status !== OrderStatus::PENDING) {
                    throw new \Exception("Pesanan tidak dapat dibatalkan karena status saat ini adalah {$order->order_status->value}.");
                }

                $order->update([
                    'order_status'      => OrderStatus::CANCELLED,
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
                if ($order->order_status !== OrderStatus::PENDING) {
                    throw new \Exception("Pesanan tidak dapat dikonfirmasi karena status saat ini adalah {$order->order_status->value}.");
                }

                $order->load('paymentMethod');

                $updateData = [
                    'order_status' => OrderStatus::CONFIRMED,
                ];

                if ($order->payment_status === \App\Enums\PaymentStatus::PENDING && $order->paymentMethod?->category !== 'cash') {
                    $updateData['payment_status'] = \App\Enums\PaymentStatus::PAID;
                }

                $order->update($updateData);

                $order->load('customer', 'items.chef');

                // Notify Customer
                $order->customer->notify(new OrderStatusChangedNotification($order, 'confirmed'));

                // Notify Assigned Chefs
                $chefs = $order->items->map(fn($item) => $item->chef)->filter()->unique('id');
                foreach ($chefs as $chef) {
                    $chef->notify(new \App\Notifications\ChefOrderAssignedNotification($order));
                }

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
     * Chef approves specific items in an order.
     *
     * @param array $itemIds
     * @param \App\Models\Chef $chef
     * @return void
     * @throws \Throwable
     */
    public function chefApproveItems(array $itemIds, \App\Models\Chef $chef): void
    {
        try {
            DB::transaction(function () use ($itemIds, $chef) {
                $items = OrderItem::whereIn('id', $itemIds)
                    ->where('chef_id', $chef->id)
                    ->get();

                foreach ($items as $item) {
                    $item->update([
                        'chef_status'       => \App\Enums\ChefStatus::ACCEPTED,
                        'chef_confirmed_at' => now(),
                    ]);
                }

                $this->notifyCustomerAboutChefStatus($items, \App\Enums\ChefStatus::ACCEPTED);
            });
        } catch (\Throwable $e) {
            Log::error('Chef failed to approve items', [
                'chef_id'  => $chef->id,
                'item_ids' => $itemIds,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Chef rejects specific items in an order, which cancels the entire order.
     *
     * @param array $itemIds
     * @param \App\Models\Chef $chef
     * @param string|null $reason
     * @return void
     * @throws \Throwable
     */
    public function chefRejectItems(array $itemIds, \App\Models\Chef $chef, ?string $reason = null): void
    {
        try {
            DB::transaction(function () use ($itemIds, $chef, $reason) {
                $items = OrderItem::whereIn('id', $itemIds)
                    ->where('chef_id', $chef->id)
                    ->get();

                foreach ($items as $item) {
                    $item->update([
                        'chef_status'       => \App\Enums\ChefStatus::REJECTED,
                        'chef_confirmed_at' => now(),
                    ]);
                }

                $this->notifyCustomerAboutChefStatus($items, \App\Enums\ChefStatus::REJECTED);
            });
        } catch (\Throwable $e) {
            Log::error('Chef failed to reject items', [
                'chef_id'  => $chef->id,
                'item_ids' => $itemIds,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Chef marks specific items in an order as shipped.
     *
     * @param array $itemIds
     * @param \App\Models\Chef $chef
     * @return void
     * @throws \Throwable
     */
    public function chefShipItems(array $itemIds, \App\Models\Chef $chef): void
    {
        try {
            DB::transaction(function () use ($itemIds, $chef) {
                $items = OrderItem::whereIn('id', $itemIds)
                    ->where('chef_id', $chef->id)
                    ->get();

                $orderIdsToProcess = [];

                foreach ($items as $item) {
                    $item->update([
                        'chef_status'       => \App\Enums\ChefStatus::SHIPPED,
                        'chef_confirmed_at' => now(),
                    ]);
                    $orderIdsToProcess[$item->order_id] = true;
                }

                // For each affected order, check if we need to update the main order status to shipped
                foreach (array_keys($orderIdsToProcess) as $orderId) {
                    $order = Order::find($orderId);
                    // If one item is shipped, and the main order is Confirmed, move it to Shipped
                    if ($order && $order->order_status === OrderStatus::CONFIRMED) {
                        $this->shipOrder($order);
                    }
                }

                $this->notifyCustomerAboutChefStatus($items, \App\Enums\ChefStatus::SHIPPED);
            });
        } catch (\Throwable $e) {
            Log::error('Chef failed to ship items', [
                'chef_id'  => $chef->id,
                'item_ids' => $itemIds,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Chef marks specific items in an order as delivered.
     *
     * @param array $itemIds
     * @param \App\Models\Chef $chef
     * @param \Illuminate\Http\UploadedFile|string|null $deliveryPhotoPath 
     * @return void
     * @throws \Throwable
     */
    public function chefDeliverItems(array $itemIds, \App\Models\Chef $chef, $deliveryPhotoPath = null): void
    {
        try {
            DB::transaction(function () use ($itemIds, $chef, $deliveryPhotoPath) {
                $items = OrderItem::whereIn('id', $itemIds)
                    ->where('chef_id', $chef->id)
                    ->get();

                $orderIdsToProcess = [];

                foreach ($items as $item) {
                    $item->update([
                        'chef_status'       => \App\Enums\ChefStatus::DELIVERED,
                        'chef_confirmed_at' => now(),
                    ]);
                    $orderIdsToProcess[$item->order_id] = true;
                }

                // If ALL items in an order are DELIVERED, mark the main order as DELIVERED
                foreach (array_keys($orderIdsToProcess) as $orderId) {
                    $order = Order::with('items')->find($orderId);
                    if ($order && $order->order_status === OrderStatus::SHIPPED) {
                        $allDelivered = $order->items->every(fn($i) => $i->chef_status === \App\Enums\ChefStatus::DELIVERED);
                        if ($allDelivered) {
                            $this->completeOrder($order, $deliveryPhotoPath);
                        }
                    }
                }

                $this->notifyCustomerAboutChefStatus($items, \App\Enums\ChefStatus::DELIVERED);
            });
        } catch (\Throwable $e) {
            Log::error('Chef failed to deliver items', [
                'chef_id'  => $chef->id,
                'item_ids' => $itemIds,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reassign an order item to another chef.
     *
     * @param \App\Models\OrderItem $item
     * @param string $chefId
     * @return void
     * @throws \Throwable
     */
    public function reassignChef(\App\Models\OrderItem $item, string $chefId): void
    {
        try {
            DB::transaction(function () use ($item, $chefId) {
                $item->update([
                    'chef_id'           => $chefId,
                    'chef_status'       => \App\Enums\ChefStatus::PENDING,
                    'chef_confirmed_at' => null,
                ]);

                // Notify New Chef
                $item->load('chef', 'order');
                if ($item->chef) {
                    $item->chef->notify(new \App\Notifications\ChefOrderAssignedNotification($item->order));
                }
            });
        } catch (\Throwable $e) {
            Log::error('Failed to reassign chef to item', [
                'item_id' => $item->id,
                'chef_id' => $chefId,
                'error'   => $e->getMessage(),
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
                if ($order->order_status !== OrderStatus::CONFIRMED) {
                    throw new \Exception("Pesanan tidak dapat dikirim karena status saat ini adalah {$order->order_status->value}.");
                }

                $order->update([
                    'order_status' => OrderStatus::SHIPPED,
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
            ->with(['dropPoint', 'customerAddress', 'paymentMethod', 'customer', 'items']);

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
     * Get filtered and paginated order items for a chef.
     *
     * @param string $chefId
     * @param \App\DTOs\Order\OrderFilterDTO $dto
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFilteredOrderItemsForChef(string $chefId, \App\DTOs\Order\OrderFilterDTO $dto, int $perPage = 15)
    {
        $query = OrderItem::query()
            ->with(['order.customer', 'order.dropPoint', 'product', 'order.items'])
            ->where('chef_id', $chefId);

        // Filter by Status
        if ($dto->status && $dto->status !== 'all') {
            switch ($dto->status) {
                case 'pending':
                    $query->where('chef_status', \App\Enums\ChefStatus::PENDING);
                    break;
                case 'accepted':
                    // Accepted items that are not yet in a delivered order
                    $query->where('chef_status', \App\Enums\ChefStatus::ACCEPTED)
                        ->whereHas('order', function ($q) {
                            $q->where('order_status', '!=', \App\Enums\OrderStatus::DELIVERED)
                                ->where('order_status', '!=', \App\Enums\OrderStatus::CANCELLED);
                        });
                    break;
                case 'completed':
                    // Items where the final order is delivered
                    $query->whereHas('order', function ($q) {
                        $q->where('order_status', \App\Enums\OrderStatus::DELIVERED);
                    });
                    break;
                case 'rejected':
                    $query->where('chef_status', \App\Enums\ChefStatus::REJECTED);
                    break;
            }
        }

        // Filter by Search (Order Number, Customer Name, or Product Name)
        if ($dto->search) {
            $query->where(function ($q) use ($dto) {
                $q->whereHas('order', function ($oq) use ($dto) {
                    $oq->where('number', 'like', "%{$dto->search}%")
                        ->orWhereHas('customer', function ($cq) use ($dto) {
                            $cq->where('name', 'like', "%{$dto->search}%");
                        });
                })->orWhereHas('product', function ($pq) use ($dto) {
                    $pq->where('name', 'like', "%{$dto->search}%");
                });
            });
        }

        // Filter by Date (Created At)
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
            ->with(['dropPoint', 'customerAddress', 'paymentMethod'])
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

                    $fees = $this->checkoutService->calculateFees(
                        $data->cart,
                        (string) data_get($data->dropPoint, 'id', ''),
                        (string) data_get($data->address, 'id', ''),
                        $data->paymentMethodId
                    );
                    $totalAmount = $fees['subtotal'] + $fees['deliveryFee'] + $fees['taxAmount'] + $fees['adminFee'] + $fees['serviceFee'];

                    $order = Order::create([
                        'number'             => $this->generateOrderNumber(),
                        'drop_point_id'      => data_get($data->dropPoint, 'id'),
                        'customer_address_id' => data_get($data->address, 'id'),
                        'customer_id'        => $customer->id,
                        'delivery_date'      => $data->deliveryDate ?? now()->addDay()->format('Y-m-d'),
                        'delivery_time'      => $data->deliveryTime ?? '12:00',
                        'payment_method_id'  => $data->paymentMethodId,
                        'payment_status'     => 'pending',
                        'order_status'       => 'pending',
                        'total_amount'       => $totalAmount,
                        'delivery_fee'       => $fees['deliveryFee'],
                        'admin_fee'          => $fees['adminFee'],
                        'service_fee'        => $fees['serviceFee'],
                        'tax_amount'         => $fees['taxAmount'],
                    ]);

                    foreach ($data->cart as $item) {
                        $product = Product::find(data_get($item, 'product.id'));
                        $chef = $product?->chefs->first();

                        $orderItem = OrderItem::create([
                            'order_id'          => $order->id,
                            'product_id'        => $product->id,
                            'quantity'          => $item['quantity'],
                            'price'             => $item['basePrice'],
                            'subtotal'          => $item['totalPrice'],
                            'note'              => $item['notes'] ?? null,
                            'chef_id'           => $chef?->id,
                            'chef_status'       => $chef ? \App\Enums\ChefStatus::PENDING : null,
                            'chef_confirmed_at' => null,
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

                    session()->forget(['checkout_cart', 'checkout_drop_point', 'checkout_address']);

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
     * Notify customer about chef status updates for their items.
     *
     * @param Collection $items
     * @param \App\Enums\ChefStatus $newStatus
     * @return void
     */
    private function notifyCustomerAboutChefStatus(Collection $items, \App\Enums\ChefStatus $newStatus): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $items->loadMissing(['order.customer', 'product']);

        $groupedByOrder = $items->groupBy('order_id');

        DB::afterCommit(function () use ($groupedByOrder, $newStatus) {
            foreach ($groupedByOrder as $orderId => $orderItems) {
                $order = $orderItems->first()->order;
                if ($order && $order->customer) {
                    $order->customer->notify(new \App\Notifications\ChefStatusUpdatedNotification($order, $orderItems, $newStatus));
                }
            }
        });
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
