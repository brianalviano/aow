<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Checkout\ProcessOrderData;
use App\DTOs\Order\OrderFilterDTO;
use App\Enums\ChefStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Jobs\SendTelegramNotificationJob;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Mail\CustomerWelcomeMail;
use App\Mail\OrderPlacedMail;
use App\Models\Chef;
use App\Models\Customer;
use App\Models\DropPoint;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\OrderShipping;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Notifications\ChefOrderAssignedNotification;
use App\Notifications\ChefStatusUpdatedNotification;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderStatusChangedNotification;
use App\Traits\FileHelperTrait;
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OrderService
{
    use FileHelperTrait, RetryableTransactionsTrait;

    /**
     * Create a new OrderService instance.
     */
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly MidtransService $midtransService
    ) {}

    /**
     * Mark the given order as completed/delivered with photo proof.
     *
     * @param  UploadedFile|string|null  $deliveryPhotoPath  Storage path or UploadedFile of the delivery photo proof.
     *
     * @throws Throwable
     */
    public function completeOrder(Order $order, $deliveryPhotoPath = null): Order
    {
        try {
            return DB::transaction(function () use ($order, $deliveryPhotoPath) {
                // Ensure the order is in a valid state to be completed
                if (in_array($order->order_status, [OrderStatus::DELIVERED, OrderStatus::CANCELLED])) {
                    throw new \Exception("Pesanan tidak dapat diselesaikan karena status saat ini adalah {$order->order_status->value}.");
                }

                $photoPath = $this->handleFileInput($deliveryPhotoPath, null, 'orders/delivery');

                $order->load('paymentMethod');

                $updateData = [
                    'order_status' => OrderStatus::DELIVERED,
                    'delivery_photo' => $photoPath,
                    'delivered_at' => now(),
                ];

                if ($order->payment_status === PaymentStatus::PENDING && $order->paymentMethod?->category === 'cash') {
                    $updateData['payment_status'] = PaymentStatus::PAID;
                }

                $order->update($updateData);

                $order->load('customer');
                $order->customer->notify(new OrderStatusChangedNotification($order, 'delivered'));

                return $order->fresh();
            });
        } catch (Throwable $e) {
            Log::error('Gagal menyelesaikan pesanan', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'delivery_photo_path' => $deliveryPhotoPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Cancel the given order.
     *
     * @throws Throwable
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
                    'order_status' => OrderStatus::CANCELLED,
                    'cancellation_note' => $reason,
                ]);

                OrderItem::where('order_id', $order->id)
                    ->where('chef_status', '!=', ChefStatus::CANCELLED->value)
                    ->update([
                        'chef_status' => ChefStatus::CANCELLED,
                        'chef_confirmed_at' => now(),
                    ]);

                OrderShipping::where('order_id', $order->id)
                    ->where(function ($query) {
                        $query->whereNull('biteship_status')
                            ->orWhere('biteship_status', '!=', 'cancelled');
                    })
                    ->update(['biteship_status' => 'cancelled']);

                $order->load('customer');
                $order->customer->notify(new OrderStatusChangedNotification($order, 'cancelled'));

                DB::afterCommit(function () use ($order, $reason) {
                    $message = "Halo {$order->customer->name},\n\nMohon maaf, pesanan Anda dengan nomor *{$order->number}* telah dibatalkan oleh Admin.\n\nAlasan: ".($reason ?: 'Tidak disebutkan');
                    dispatch(new SendWhatsAppNotificationJob($order->customer->phone, $message));
                });

                return $order->fresh();
            });
        } catch (Throwable $e) {
            Log::error('Gagal membatalkan pesanan', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'reason' => $reason,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Confirm the given pending order.
     *
     * @throws Throwable
     */
    public function confirmOrder(Order $order, ?string $pickUpPointId = null): Order
    {
        try {
            return DB::transaction(function () use ($order, $pickUpPointId) {
                if ($order->order_status !== OrderStatus::PENDING) {
                    throw new \Exception("Pesanan tidak dapat dikonfirmasi karena status saat ini adalah {$order->order_status->value}.");
                }

                $order->load('paymentMethod');

                $updateData = [
                    'order_status' => OrderStatus::CONFIRMED,
                ];

                if ($pickUpPointId) {
                    $updateData['pick_up_point_id'] = $pickUpPointId;
                }

                if ($order->payment_status === PaymentStatus::PENDING && $order->paymentMethod?->category !== 'cash') {
                    $updateData['payment_status'] = PaymentStatus::PAID;
                }

                $order->update($updateData);

                $order->load('customer', 'items.chef');

                // Notify Customer
                $order->customer->notify(new OrderStatusChangedNotification($order, 'confirmed'));

                // Notify Assigned Chefs
                $chefs = $order->items->map(fn ($item) => $item->chef)->filter()->unique('id');
                foreach ($chefs as $chef) {
                    $chef->notify(new ChefOrderAssignedNotification($order));
                }

                DB::afterCommit(function () use ($order) {
                    $message = $this->buildConfirmedWhatsAppMessage($order);
                    dispatch(new SendWhatsAppNotificationJob($order->customer->phone, $message));
                });

                return $order->fresh();
            });
        } catch (Throwable $e) {
            Log::error('Gagal mengkonfirmasi pesanan', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'pick_up_point_id' => $pickUpPointId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Chef approves specific items in an order.
     *
     * @throws Throwable
     */
    public function chefApproveItems(array $itemIds, Chef $chef): void
    {
        try {
            DB::transaction(function () use ($itemIds, $chef) {
                $items = OrderItem::whereIn('id', $itemIds)
                    ->where('chef_id', $chef->id)
                    ->get();

                foreach ($items as $item) {
                    $item->update([
                        'chef_status' => ChefStatus::ACCEPTED,
                        'chef_confirmed_at' => now(),
                    ]);
                }

                $this->notifyCustomerAboutChefStatus($items, ChefStatus::ACCEPTED);
            });
        } catch (Throwable $e) {
            Log::error('Chef failed to approve items', [
                'chef_id' => $chef->id,
                'item_ids' => $itemIds,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Chef rejects specific items in an order, which cancels the entire order.
     *
     * @throws Throwable
     */
    public function chefRejectItems(array $itemIds, Chef $chef, ?string $reason = null): void
    {
        try {
            DB::transaction(function () use ($itemIds, $chef) {
                $items = OrderItem::whereIn('id', $itemIds)
                    ->where('chef_id', $chef->id)
                    ->get();

                foreach ($items as $item) {
                    $item->update([
                        'chef_status' => ChefStatus::REJECTED,
                        'chef_confirmed_at' => now(),
                    ]);
                }

                $this->notifyCustomerAboutChefStatus($items, ChefStatus::REJECTED);
            });
        } catch (Throwable $e) {
            Log::error('Chef failed to reject items', [
                'chef_id' => $chef->id,
                'item_ids' => $itemIds,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Chef marks specific items in an order as shipped (to pickup point).
     *
     * Chef no longer auto-books Biteship. PIC handles courier booking.
     * When a chef ships, items go to the assigned pickup point.
     *
     * @throws Throwable
     */
    public function chefShipItems(array $itemIds, Chef $chef): void
    {
        try {
            DB::transaction(function () use ($itemIds, $chef) {
                $items = OrderItem::whereIn('id', $itemIds)
                    ->where('chef_id', $chef->id)
                    ->get();

                if ($items->isEmpty()) {
                    return;
                }

                $orderIdsToProcess = [];

                foreach ($items as $item) {
                    $item->update([
                        'chef_status' => ChefStatus::SHIPPED,
                        'chef_confirmed_at' => now(),
                    ]);
                    $orderIdsToProcess[$item->order_id] = true;
                }

                // For each affected order, update main order status to SHIPPED
                foreach (array_keys($orderIdsToProcess) as $orderId) {
                    /** @var Order $order */
                    $order = Order::find($orderId);
                    if ($order && $order->order_status === OrderStatus::CONFIRMED) {
                        $this->shipOrder($order);
                    }
                }

                $this->notifyCustomerAboutChefStatus($items, ChefStatus::SHIPPED);
            });
        } catch (Throwable $e) {
            Log::error('Chef failed to ship items', [
                'chef_id' => $chef->id,
                'item_ids' => $itemIds,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Chef marks specific items in an order as delivered.
     *
     * @param  UploadedFile|string|null  $deliveryPhotoPath
     *
     * @throws Throwable
     */
    public function chefDeliverItems(array $itemIds, Chef $chef, $deliveryPhotoPath = null): void
    {
        try {
            DB::transaction(function () use ($itemIds, $chef, $deliveryPhotoPath) {
                $items = OrderItem::whereIn('id', $itemIds)
                    ->where('chef_id', $chef->id)
                    ->get();

                $orderIdsToProcess = [];

                foreach ($items as $item) {
                    $item->update([
                        'chef_status' => ChefStatus::DELIVERED,
                        'chef_confirmed_at' => now(),
                    ]);
                    $orderIdsToProcess[$item->order_id] = true;
                }

                // If ALL items in an order are DELIVERED, mark the main order as DELIVERED
                foreach (array_keys($orderIdsToProcess) as $orderId) {
                    /** @var Order $order */
                    $order = Order::with('items')->find($orderId);
                    if ($order && $order->order_status === OrderStatus::SHIPPED) {
                        $allDelivered = $order->items->every(fn ($i) => $i->chef_status === ChefStatus::DELIVERED);
                        if ($allDelivered) {
                            $this->completeOrder($order, $deliveryPhotoPath);
                        }
                    }
                }

                $this->notifyCustomerAboutChefStatus($items, ChefStatus::DELIVERED);
            });
        } catch (Throwable $e) {
            Log::error('Chef failed to deliver items', [
                'chef_id' => $chef->id,
                'item_ids' => $itemIds,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reassign an order item to another chef.
     *
     * @throws Throwable
     */
    public function reassignChef(OrderItem $item, string $chefId): void
    {
        try {
            DB::transaction(function () use ($item, $chefId) {
                $item->update([
                    'chef_id' => $chefId,
                    'chef_status' => ChefStatus::PENDING,
                    'chef_confirmed_at' => null,
                ]);

                // Notify New Chef
                $item->load('chef', 'order');
                if ($item->chef) {
                    $item->chef->notify(new ChefOrderAssignedNotification($item->order));
                }
            });
        } catch (Throwable $e) {
            Log::error('Failed to reassign chef to item', [
                'item_id' => $item->id,
                'chef_id' => $chefId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mark the given confirmed order as shipped.
     *
     * @throws Throwable
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

                $order->load('customer', 'dropPoint');
                $order->customer->notify(new OrderStatusChangedNotification($order, 'shipped'));

                DB::afterCommit(function () use ($order) {
                    $dpName = $order->dropPoint ? $order->dropPoint->name : 'Custom Address';
                    $message = "<b>PESANAN MENUJU PICKUP POINT</b>\n\n"
                        ."Order: <b>{$order->number}</b>\n"
                        ."Chef telah selesai memasak dan pesanan sedang dikirim ke <b>{$dpName}</b>.\n"
                        .'Harap PIC bersiap untuk menerima pesanan.';
                    dispatch(new SendTelegramNotificationJob($message));
                });

                return $order->fresh();
            });
        } catch (Throwable $e) {
            Log::error('Gagal mengubah status pesanan ke dikirim', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get filtered and paginated orders for Admin.
     *
     * @return LengthAwarePaginator
     */
    public function getFilteredOrdersForAdmin(OrderFilterDTO $dto, int $perPage = 15)
    {
        $query = Order::query()
            ->with(['dropPoint', 'customerAddress', 'paymentMethod', 'customer', 'items.product']);

        // Filter by Status
        if ($dto->status && $dto->status !== 'all') {
            switch ($dto->status) {
                case 'unpaid':
                    $query->where('payment_status', 'pending')
                        ->where('order_status', '!=', 'cancelled')
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
                    $query->whereIn('order_status', [
                        OrderStatus::SHIPPED,
                        OrderStatus::AT_PICKUP_POINT,
                        OrderStatus::ON_DELIVERY,
                        OrderStatus::ARRIVED,
                    ]);
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
                $q->where('number', 'ilike', "%{$dto->search}%")
                    ->orWhereHas('customer', function ($cq) use ($dto) {
                        $cq->where('name', 'ilike', "%{$dto->search}%");
                    })
                    ->orWhereHas('items.product', function ($pq) use ($dto) {
                        $pq->where('name', 'ilike', "%{$dto->search}%");
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
                $dto->startDate.' 00:00:00',
                $dto->endDate.' 23:59:59',
            ]);
        }

        // Additional Admin Filters
        if ($dto->dropPointId) {
            $query->where('drop_point_id', $dto->dropPointId);
        }

        if ($dto->chefId) {
            $query->whereHas('items', fn ($q) => $q->where('chef_id', $dto->chefId));
        }

        if ($dto->deliveryDate) {
            $query->whereDate('delivery_date', $dto->deliveryDate);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get orders awaiting payment approval (unpaid, non-cash).
     */
    public function getPaymentApprovalOrders(int $perPage = 15)
    {
        return Order::query()
            ->with(['customer', 'paymentMethod', 'items.product'])
            ->where('payment_status', 'pending')
            ->where('order_status', '!=', 'cancelled')
            ->whereDoesntHave('paymentMethod', fn ($q) => $q->where('category', 'cash'))
            ->orderBy('payment_expired_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get orders currently being processed or shipped, with optional filters.
     */
    public function getProcessingOrders(OrderFilterDTO $dto, int $perPage = 15)
    {
        $query = Order::query()
            ->with(['customer', 'dropPoint', 'items.chef', 'items.product', 'paymentMethod'])
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->where('payment_status', '!=', 'pending')
                        ->orWhereHas('paymentMethod', fn ($pq) => $pq->where('category', 'cash'));
                })->whereIn('order_status', ['pending', 'confirmed', 'shipped', 'at_pickup_point', 'on_delivery', 'arrived']);
            });

        if ($dto->dropPointId) {
            $query->where('drop_point_id', $dto->dropPointId);
        }

        if ($dto->chefId) {
            $query->whereHas('items', fn ($q) => $q->where('chef_id', $dto->chefId));
        }

        if ($dto->deliveryDate) {
            $query->whereDate('delivery_date', $dto->deliveryDate);
        }

        return $query->orderBy('delivery_date', 'asc')->orderBy('created_at', 'asc')->paginate($perPage);
    }

    /**
     * Get filtered and paginated order items for a chef.
     *
     * @return LengthAwarePaginator
     */
    public function getFilteredOrderItemsForChef(string $chefId, OrderFilterDTO $dto, int $perPage = 15)
    {
        $query = OrderItem::query()
            ->with(['order.customer', 'order.dropPoint', 'order.pickUpPoint', 'product', 'order.items'])
            ->where('chef_id', $chefId);

        // Filter by Status
        if ($dto->status && $dto->status !== 'all') {
            switch ($dto->status) {
                case 'pending':
                    $query->where('chef_status', ChefStatus::PENDING);
                    break;
                case 'accepted':
                    // Accepted items that are not yet in a delivered order
                    $query->where('chef_status', ChefStatus::ACCEPTED)
                        ->whereHas('order', function ($q) {
                            $q->where('order_status', '!=', OrderStatus::DELIVERED)
                                ->where('order_status', '!=', OrderStatus::CANCELLED);
                        });
                    break;
                case 'completed':
                    // Items where the final order is delivered
                    $query->whereHas('order', function ($q) {
                        $q->where('order_status', OrderStatus::DELIVERED);
                    });
                    break;
                case 'rejected':
                    $query->where('chef_status', ChefStatus::REJECTED);
                    break;
                case 'cancelled':
                    $query->where('chef_status', ChefStatus::CANCELLED);
                    break;
            }
        }

        // Filter by Search (Order Number, Customer Name, or Product Name)
        if ($dto->search) {
            $query->where(function ($q) use ($dto) {
                $q->whereHas('order', function ($oq) use ($dto) {
                    $oq->where('number', 'ilike', "%{$dto->search}%")
                        ->orWhereHas('customer', function ($cq) use ($dto) {
                            $cq->where('name', 'ilike', "%{$dto->search}%");
                        });
                })->orWhereHas('product', function ($pq) use ($dto) {
                    $pq->where('name', 'ilike', "%{$dto->search}%");
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
                $dto->startDate.' 00:00:00',
                $dto->endDate.' 23:59:59',
            ]);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get filtered and paginated orders for a customer.
     *
     * @return LengthAwarePaginator
     */
    public function getFilteredOrders(string $customerId, OrderFilterDTO $dto, int $perPage = 15)
    {
        $query = Order::query()
            ->with(['dropPoint', 'customerAddress', 'paymentMethod'])
            ->where('customer_id', $customerId);

        // Filter by Status
        if ($dto->status && $dto->status !== 'all') {
            switch ($dto->status) {
                case 'unpaid':
                    $query->where('payment_status', 'pending')
                        ->where('order_status', '!=', 'cancelled')
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
                $q->where('number', 'ilike', "%{$dto->search}%")
                    ->orWhereHas('items.product', function ($pq) use ($dto) {
                        $pq->where('name', 'ilike', "%{$dto->search}%");
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
                $dto->startDate.' 00:00:00',
                $dto->endDate.' 23:59:59',
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
        /** @var Collection<int, Order> $expiredOrders */
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
            } catch (Throwable $e) {
                // Individual failures are logged inside cancelOrder, we continue with others
                continue;
            }
        }

        return $count;
    }

    /**
     * Process order creation within a transaction.
     *
     * @param  ProcessOrderData  $data  Data for creating the order.
     * @return Order The created order object.
     *
     * @throws Throwable
     */
    public function processOrder(ProcessOrderData $data): Order
    {
        return $this->runWithRetry(function () use ($data) {
            try {
                return DB::transaction(function () use ($data) {
                    $customer = Auth::guard('customer')->user();

                    if (! $customer) {
                        $customer = Customer::where('email', $data->email)->first();

                        if (! $customer) {
                            $password = '12345678';

                            $customer = Customer::create([
                                'name' => $data->name,
                                'phone' => $data->phone,
                                'email' => $data->email,
                                'password' => Hash::make($password),
                                'school_class' => $data->schoolClass,
                                'is_active' => true,
                            ]);

                            // Send welcome email with credentials
                            DB::afterCommit(function () use ($customer, $password) {
                                Mail::to($customer->email)->send(new CustomerWelcomeMail($customer, $password));
                            });
                        }

                        Auth::guard('customer')->login($customer);
                    }

                    $dropPoint = $data->dropPoint;
                    $address = $data->address;

                    // Automatically pick nearest drop point if not selected but address is provided
                    if (empty($dropPoint) && ! empty($address) && isset($address['latitude'], $address['longitude'])) {
                        $nearestDropPoint = DropPoint::findNearest((float) $address['latitude'], (float) $address['longitude']);
                        if ($nearestDropPoint) {
                            $dropPoint = [
                                'id' => $nearestDropPoint->id,
                                'name' => $nearestDropPoint->name,
                                'address' => $nearestDropPoint->address,
                                'latitude' => $nearestDropPoint->latitude,
                                'longitude' => $nearestDropPoint->longitude,
                            ];
                        }
                    }

                    $fees = $this->checkoutService->calculateFees(
                        $data->cart,
                        (string) data_get($dropPoint, 'id', ''),
                        (string) data_get($address, 'id', ''),
                        $data->paymentMethodId
                    );
                    $totalAmount = $fees['subtotal'] + $fees['deliveryFee'] + $fees['taxAmount'] + $fees['adminFee'] + $fees['serviceFee'];

                    $order = Order::create([
                        'number' => $this->generateOrderNumber(),
                        'drop_point_id' => data_get($dropPoint, 'id'),
                        'customer_address_id' => data_get($address, 'id'),
                        'customer_id' => $customer->id,
                        'delivery_date' => $data->deliveryDate ?? now()->addDay()->format('Y-m-d'),
                        'delivery_time' => $data->deliveryTime ?? '12:00',
                        'payment_method_id' => $data->paymentMethodId,
                        'payment_status' => 'pending',
                        'order_status' => 'pending',
                        'total_amount' => $totalAmount,
                        'delivery_fee' => $fees['deliveryFee'],
                        'admin_fee' => $fees['adminFee'],
                        'service_fee' => $fees['serviceFee'],
                        'tax_amount' => $fees['taxAmount'],
                    ]);

                    $firstChef = null;

                    foreach ($data->cart as $item) {
                        $product = Product::find(data_get($item, 'product.id'));
                        $chef = $product?->chefs->first();

                        if (! $firstChef && $chef) {
                            $firstChef = $chef;
                        }

                        $orderItem = OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'quantity' => $item['quantity'],
                            'price' => $item['basePrice'],
                            'subtotal' => $item['totalPrice'],
                            'note' => $item['notes'] ?? null,
                            'chef_id' => $chef?->id,
                            'chef_status' => $chef ? ChefStatus::PENDING : null,
                            'chef_confirmed_at' => null,
                        ]);

                        if (isset($item['selectedOptions'])) {
                            foreach ($item['selectedOptions'] as $optionId => $selection) {
                                $itemIds = is_array($selection) ? $selection : [$selection];
                                foreach ($itemIds as $optionItemId) {
                                    if (blank($optionItemId)) {
                                        continue;
                                    }
                                    $extraPrice = $this->resolveOptionExtraPrice($item, (string) $optionId, (string) $optionItemId);

                                    OrderItemOption::create([
                                        'order_item_id' => $orderItem->id,
                                        'product_option_id' => $optionId,
                                        'product_option_item_id' => $optionItemId,
                                        'extra_price' => $extraPrice,
                                    ]);
                                }
                            }
                        }
                    }

                    // Assign nearest pickup point based on first chef's location
                    if ($firstChef && $firstChef->latitude && $firstChef->longitude) {
                        $nearestPickup = PicOrderService::findNearestPickUpPoint(
                            $firstChef->latitude,
                            $firstChef->longitude
                        );
                        if ($nearestPickup) {
                            $order->update(['pick_up_point_id' => $nearestPickup->id]);
                        }
                    }

                    // Create per-chef shipping records (Biteship)
                    $shippingBreakdown = $fees['shippingBreakdown'] ?? [];
                    foreach ($shippingBreakdown as $shipping) {
                        if (! $shipping['success']) {
                            continue;
                        }
                        OrderShipping::create([
                            'order_id' => $order->id,
                            'chef_id' => $shipping['chef_id'],
                            'courier_company' => $shipping['courier_company'] ?? 'unknown',
                            'courier_type' => $shipping['courier_type'] ?? 'instant',
                            'courier_name' => $shipping['courier_name'] ?? 'Kurir Instant',
                            'shipping_fee' => $shipping['fee'],
                            'origin_address' => $shipping['origin_address'] ?? null,
                            'origin_latitude' => $shipping['origin_latitude'] ?? null,
                            'origin_longitude' => $shipping['origin_longitude'] ?? null,
                            'destination_latitude' => $shipping['destination_latitude'] ?? null,
                            'destination_longitude' => $shipping['destination_longitude'] ?? null,
                        ]);
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
                                'error' => $e->getMessage(),
                            ]);
                            throw $e;
                        }
                    }

                    // Send order confirmation email and database notification
                    DB::afterCommit(function () use ($order) {
                        Mail::to($order->customer->email)->send(new OrderPlacedMail($order));
                        $order->customer->notify(new OrderPlacedNotification($order));

                        // 1. Notify Admin via Telegram
                        $message = "<b>PESANAN BARU MASUK!</b>\n\n"
                            ."Order: <b>{$order->number}</b>\n"
                            ."Customer: {$order->customer->name}\n"
                            .'Total: Rp '.number_format($order->total_amount, 0, ',', '.')."\n"
                            .'Harap segera cek dashboard admin.';
                        dispatch(new SendTelegramNotificationJob($message));
                    });

                    session()->forget(['checkout_cart', 'checkout_drop_point', 'checkout_address']);

                    return $order;
                });
            } catch (Throwable $e) {
                Log::error('OrderService - Failed to process order', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'customer_id' => Auth::guard('customer')->id(),
                    'payload' => [
                        'email' => $data->email,
                        'payment_method_id' => $data->paymentMethodId,
                        'cart_count' => count($data->cart),
                        'drop_point' => $data->dropPoint,
                        'cart_sample' => collect($data->cart)->first(),
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
        $prefix = 'ORD/'.$now->format('mY').'/';

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

        return $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Build the WhatsApp confirmation message for customer.
     */
    private function buildConfirmedWhatsAppMessage(Order $order): string
    {
        $order->loadMissing(['customer', 'items.product', 'dropPoint', 'pickUpPoint', 'customerAddress']);

        $customerName = $order->customer?->name ?? 'Pelanggan';
        $orderNumber = $order->number;
        $deliveryDate = $order->delivery_date ? $order->delivery_date->translatedFormat('d M Y') : '-';
        $deliveryTime = $order->delivery_time ? $order->delivery_time->format('H:i') : '-';

        $transitPoint = '-';
        if ($order->pickUpPoint) {
            $transitPoint = 'Pickup Point: '.$order->pickUpPoint->name;
        } elseif ($order->dropPoint) {
            $transitPoint = 'Drop Point: '.$order->dropPoint->name;
        } elseif ($order->customerAddress) {
            $transitPoint = 'Alamat Customer: '.$order->customerAddress->address;
        }

        $menuList = '';
        foreach ($order->items as $item) {
            $menuList .= "\n- ".($item->product?->name ?? 'Menu').' x'.$item->quantity;
        }

        return "*PESANAN DIKONFIRMASI* 🎉\n\n"
            ."Halo *{$customerName}*,\n"
            ."Nomor order *#{$orderNumber}* telah dikonfirmasi dan sedang diproses oleh dapur kami. 🧑‍🍳🍳\n\n"
            ."*Detail Pesanan:*\n"
            ."📅 Tanggal Kirim: {$deliveryDate}\n"
            ."⏰ Waktu/Jam: {$deliveryTime} WIB\n"
            ."📍 Titik Transit: {$transitPoint}\n"
            ."🍔 Menu:{$menuList}\n\n"
            .'Terima kasih telah memesan di Aowenak! Kami akan mengirimkan update berikutnya ketika pesanan Anda siap dikirim. 🚀';
    }

    /**
     * Notify customer about chef status updates for their items.
     */
    private function notifyCustomerAboutChefStatus(Collection $items, ChefStatus $newStatus): void
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
                    $order->customer->notify(new ChefStatusUpdatedNotification($order, $orderItems, $newStatus));
                }
            }
        });
    }

    /**
     * Resolve the extra price for a specific product option item.
     *
     * @param  array  $item  The cart item containing product and option data.
     * @param  string  $optionId  The ID of the product option.
     * @param  string  $optionItemId  The ID of the product option item.
     * @return int The extra price for the specified option item.
     */
    private function resolveOptionExtraPrice(array $item, string $optionId, string $optionItemId): int
    {
        $options = data_get($item, 'product.options', []);
        $productOptions = isset($options['data']) ? $options['data'] : $options;

        if (! is_array($productOptions)) {
            return 0;
        }

        foreach ($productOptions as $opt) {
            if ((string) data_get($opt, 'id') === $optionId) {
                $items = data_get($opt, 'items', []);
                $optionItems = isset($items['data']) ? $items['data'] : $items;

                if (! is_array($optionItems)) {
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

    /**
     * Update status pesanan berdasarkan webhook dari Biteship.
     *
     * In the new PIC flow, Biteship orders are created by PIC (from pickup point to customer).
     * When status is 'delivered', we mark the order as DELIVERED.
     */
    public function updateStatusFromBiteship(string $biteshipOrderId, string $status, array $payload = []): void
    {
        try {
            DB::transaction(function () use ($biteshipOrderId, $status) {
                $shipping = OrderShipping::where('biteship_order_id', $biteshipOrderId)->first();

                if (! $shipping) {
                    Log::warning('Biteship order_id tidak ditemukan di database kita', ['biteship_order_id' => $biteshipOrderId]);

                    return;
                }

                $shipping->update([
                    'biteship_status' => $status,
                ]);

                // When courier has delivered, mark the order as DELIVERED
                if ($status === 'delivered') {
                    /** @var Order $order */
                    $order = Order::find($shipping->order_id);

                    if (! $order) {
                        return;
                    }

                    // PIC flow: Biteship delivers from pickup point to customer
                    // Change to ARRIVED so customer must confirm receipt
                    if ($order->order_status === OrderStatus::ON_DELIVERY) {
                        $order->update([
                            'order_status' => OrderStatus::ARRIVED,
                            'arrived_at' => now(),
                        ]);
                    }

                    $order->load('customer');
                    if ($order->customer) {
                        $order->customer->notify(new OrderStatusChangedNotification($order, 'delivered'));

                        DB::afterCommit(function () use ($order) {
                            $message = "Halo {$order->customer->name},\n\n"
                                ."Pesanan Anda dengan nomor *{$order->number}* telah TIBA di tujuan!\n\n"
                                .'Silakan periksa pesanan Anda dan jangan lupa konfirmasi penerimaan di aplikasi. Selamat menikmati!';
                            dispatch(new SendWhatsAppNotificationJob($order->customer->phone, $message));
                        });
                    }
                }
            });
        } catch (Throwable $e) {
            Log::error('Gagal memproses update status dari Biteship', [
                'biteship_order_id' => $biteshipOrderId,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Automatically complete orders that have been in ARRIVED status for more than 6 hours.
     *
     * @return int Number of orders auto-completed.
     */
    public function autoCompleteArrivedOrders(): int
    {
        $expiredOrders = Order::query()
            ->where('order_status', OrderStatus::ARRIVED)
            ->where('arrived_at', '<=', now()->subHours(6))
            ->get();

        $count = 0;
        foreach ($expiredOrders as $order) {
            /** @var Order $order */
            try {
                $this->completeOrder($order);
                $count++;
            } catch (Throwable $e) {
                Log::error('Gagal menyelesaikan pesanan otomatis (timeout 6 jam)', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Resend order notifications based on target.
     */
    public function resendOrderNotifications(Order $order, string $target): void
    {
        $order->loadMissing(['customer', 'dropPoint', 'pickUpPoint', 'items.chef']);

        if ($target === 'customer') {
            // 1. WhatsApp to customer based on current status
            $waMessage = null;
            switch ($order->order_status) {
                case OrderStatus::PENDING:
                    $waMessage = "Halo {$order->customer->name},\n\n"
                        ."Pesanan Anda dengan nomor *{$order->number}* telah berhasil dibuat dan sedang menunggu konfirmasi pembayaran/admin.\n\n"
                        .'Total Tagihan: Rp '.number_format($order->total_amount, 0, ',', '.')."\n\n"
                        .'Terima kasih telah memesan!';
                    break;
                case OrderStatus::CONFIRMED:
                    $waMessage = $this->buildConfirmedWhatsAppMessage($order);
                    break;
                case OrderStatus::AT_PICKUP_POINT:
                    if ($order->pickUpPoint) {
                        $waMessage = "Halo {$order->customer->name},\n\n"
                            ."Kabar baik! Pesanan Anda dengan nomor *{$order->number}* telah tiba di titik transit kami (*{$order->pickUpPoint->name}*).\n\n"
                            .'Tim kami akan segera mengirimkannya ke alamat Anda. Mohon ditunggu ya!';
                    }
                    break;
                case OrderStatus::ON_DELIVERY:
                    $waMessage = "Halo {$order->customer->name},\n\n"
                        ."Pesanan Anda dengan nomor *{$order->number}* sedang dalam perjalanan menuju alamat Anda!\n\n"
                        .'Mohon standby untuk menerima pesanan Anda ya. Terima kasih.';
                    break;
                case OrderStatus::ARRIVED:
                    $waMessage = "Halo {$order->customer->name},\n\n"
                        ."Pesanan Anda dengan nomor *{$order->number}* telah TIBA di tujuan!\n\n"
                        .'Silakan periksa pesanan Anda dan jangan lupa konfirmasi penerimaan di aplikasi. Selamat menikmati!';
                    break;
                case OrderStatus::DELIVERED:
                    $waMessage = "Halo {$order->customer->name},\n\n"
                        ."Pesanan Anda dengan nomor *{$order->number}* telah BERHASIL dikirim dan diselesaikan!\n\n"
                        .'Terima kasih telah berbelanja bersama kami. Selamat menikmati hidangan Anda!';
                    break;
                case OrderStatus::CANCELLED:
                    $waMessage = "Halo {$order->customer->name},\n\n"
                        ."Mohon maaf, pesanan Anda dengan nomor *{$order->number}* telah dibatalkan oleh Admin.\n\n"
                        .'Alasan: '.($order->cancellation_note ?: 'Tidak disebutkan');
                    break;
            }

            if ($waMessage) {
                dispatch(new SendWhatsAppNotificationJob($order->customer->phone, $waMessage));
            }

            // Also resend status change email notification to customer
            if ($order->order_status !== OrderStatus::PENDING) {
                $order->customer->notify(new OrderStatusChangedNotification($order, $order->order_status->value));
            } else {
                // If pending, send the initial placed order mail
                Mail::to($order->customer->email)->send(new OrderPlacedMail($order));
            }
        } elseif ($target === 'chef') {
            // 2. Resend assignment notification to all chefs of items in this order
            $chefs = $order->items->map(fn ($item) => $item->chef)->filter()->unique('id');
            if ($chefs->isEmpty()) {
                throw new \Exception('Tidak ada chef yang ditugaskan pada pesanan ini.');
            }
            foreach ($chefs as $chef) {
                $chef->notify(new ChefOrderAssignedNotification($order));
            }
        } elseif ($target === 'admin') {
            // 3. Telegram to admin based on current status
            $tgMessage = null;
            if ($order->order_status === OrderStatus::PENDING) {
                $tgMessage = "<b>PESANAN BARU MASUK! (Kirim Ulang)</b>\n\n"
                    ."Order: <b>{$order->number}</b>\n"
                    ."Customer: {$order->customer->name}\n"
                    .'Total: Rp '.number_format($order->total_amount, 0, ',', '.')."\n"
                    .'Harap segera cek dashboard admin.';
            } elseif ($order->order_status === OrderStatus::SHIPPED) {
                $dpName = $order->dropPoint ? $order->dropPoint->name : 'Custom Address';
                $tgMessage = "<b>PESANAN MENUJU PICKUP POINT (Kirim Ulang)</b>\n\n"
                    ."Order: <b>{$order->number}</b>\n"
                    ."Chef telah selesai memasak dan pesanan sedang dikirim ke <b>{$dpName}</b>.\n"
                    .'Harap PIC bersiap untuk menerima pesanan.';
            } else {
                $statusLabel = $order->order_status->label();
                $tgMessage = "<b>UPDATE PESANAN (Kirim Ulang)</b>\n\n"
                    ."Order: <b>{$order->number}</b>\n"
                    ."Customer: {$order->customer->name}\n"
                    .'Status Saat Ini: <b>'.strtoupper($statusLabel)."</b>\n"
                    .'Total: Rp '.number_format($order->total_amount, 0, ',', '.').'.';
            }

            if ($tgMessage) {
                dispatch(new SendTelegramNotificationJob($tgMessage));
            }
        } else {
            throw new \InvalidArgumentException("Target notifikasi '{$target}' tidak valid.");
        }
    }
}
