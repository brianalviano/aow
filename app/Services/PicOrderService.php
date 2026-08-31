<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Setting\OrderSettingsDTO;
use App\Enums\ChefStatus;
use App\Enums\OrderStatus;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipping;
use App\Models\PickUpPoint;
use App\Models\PickUpPointOfficer;
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Service for Pickup Point Officer (PIC) order operations.
 *
 * Handles the PIC workflow: incoming orders → approve arrival → send to customer → mark delivered.
 * Pre-order: PIC delivers manually to drop point → marks completed.
 * Instant: PIC creates Biteship order (Grab/Gojek) → webhook completes.
 */
class PicOrderService
{
    use RetryableTransactionsTrait;

    /**
     * @param  BiteshipService  $biteshipService  Service for creating courier orders.
     */
    public function __construct(
        private readonly BiteshipService $biteshipService,
    ) {}

    /**
     * Get orders headed to this pickup point (chef has shipped, not yet arrived).
     */
    public function getIncomingOrders(string $pickUpPointId): LengthAwarePaginator
    {
        return Order::query()
            ->where('pick_up_point_id', $pickUpPointId)
            ->where('order_status', OrderStatus::SHIPPED)
            ->with(['items.product', 'customer', 'dropPoint', 'customerAddress', 'pickUpPoint'])
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Get orders that are at the pickup point, ready for PIC to send out.
     */
    public function getAtPickupOrders(string $pickUpPointId): LengthAwarePaginator
    {
        return Order::query()
            ->where('pick_up_point_id', $pickUpPointId)
            ->where('order_status', OrderStatus::AT_PICKUP_POINT)
            ->with(['items.product', 'customer', 'dropPoint', 'customerAddress', 'pickUpPoint', 'shippings'])
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Get orders currently being delivered by PIC (on their way to customer).
     */
    public function getOnDeliveryOrders(string $pickUpPointId): LengthAwarePaginator
    {
        return Order::query()
            ->where('pick_up_point_id', $pickUpPointId)
            ->whereIn('order_status', [OrderStatus::ON_DELIVERY, OrderStatus::ARRIVED])
            ->with(['items.product', 'customer', 'dropPoint', 'customerAddress', 'pickUpPoint', 'shippings'])
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Get completed orders for this pickup point.
     */
    public function getCompletedOrders(string $pickUpPointId): LengthAwarePaginator
    {
        return Order::query()
            ->where('pick_up_point_id', $pickUpPointId)
            ->where('order_status', OrderStatus::DELIVERED)
            ->with(['items.product', 'customer', 'dropPoint', 'customerAddress', 'pickUpPoint', 'shippings'])
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * PIC approves that all food for this order has arrived at the pickup point.
     *
     * Updates all chef item statuses to DELIVERED and order status to AT_PICKUP_POINT.
     *
     * @throws Throwable
     */
    public function approveArrival(Order $order, PickUpPointOfficer $officer): Order
    {
        try {
            return DB::transaction(function () use ($order, $officer) {
                // Mark all chef items as DELIVERED (arrived at pickup point)
                OrderItem::where('order_id', $order->id)
                    ->whereIn('chef_status', [ChefStatus::SHIPPED->value, ChefStatus::ACCEPTED->value])
                    ->update([
                        'chef_status' => ChefStatus::DELIVERED,
                        'chef_confirmed_at' => now(),
                    ]);

                $order->update([
                    'order_status' => OrderStatus::AT_PICKUP_POINT,
                ]);

                Log::info('PIC approved order arrival at pickup point', [
                    'order_id' => $order->id,
                    'officer_id' => $officer->id,
                    'pick_up_point_id' => $officer->pick_up_point_id,
                ]);

                DB::afterCommit(function () use ($order) {
                    $settings = OrderSettingsDTO::load();
                    if ($settings->whatsappEnabled && ! empty($order->customer?->phone)) {
                        $order->load(['customer', 'pickUpPoint']);
                        $message = "Halo {$order->customer->name},\n\n"
                            ."Kabar baik! Pesanan Anda dengan nomor *{$order->number}* telah tiba di titik transit kami (*{$order->pickUpPoint->name}*).\n\n"
                            .'Tim kami akan segera mengirimkannya ke alamat Anda. Mohon ditunggu ya!';
                        dispatch(new SendWhatsAppNotificationJob($order->customer->phone, $message));
                    }
                });

                return $order->fresh();
            });
        } catch (Throwable $e) {
            Log::error('PIC failed to approve order arrival', [
                'order_id' => $order->id,
                'officer_id' => $officer->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * PIC sends the order to the customer.
     *
     * Pre-order: Updates status to ON_DELIVERY (PIC delivers manually to drop point).
     * Instant: Creates Biteship order from pickup point to customer address.
     *
     * @throws Throwable
     */
    public function sendToCustomer(Order $order, PickUpPointOfficer $officer): Order
    {
        try {
            return DB::transaction(function () use ($order, $officer) {
                $order->load(['pickUpPoint', 'customerAddress', 'items.product']);

                if ($order->isInstant()) {
                    // Create Biteship order from pickup point to customer address
                    $pickUpPoint = $order->pickUpPoint;
                    $customerAddress = $order->customerAddress;

                    if (! $pickUpPoint || ! $customerAddress) {
                        throw new \RuntimeException('Pickup point atau alamat customer tidak ditemukan.');
                    }

                    // Create or update shipping record from pickup point to customer
                    $shipping = OrderShipping::updateOrCreate(
                        [
                            'order_id' => $order->id,
                            'chef_id' => null, // PIC shipment, not chef
                        ],
                        [
                            'origin_address' => $pickUpPoint->address,
                            'origin_latitude' => $pickUpPoint->latitude,
                            'origin_longitude' => $pickUpPoint->longitude,
                            'destination_latitude' => $customerAddress->latitude,
                            'destination_longitude' => $customerAddress->longitude,
                        ]
                    );

                    // Book Biteship courier
                    $result = $this->biteshipService->createOrder($shipping, $order->items);

                    if ($result['success']) {
                        $shipping->update([
                            'biteship_order_id' => $result['order_id'],
                            'biteship_tracking_id' => $result['tracking_id'],
                            'biteship_waybill_id' => $result['waybill_id'],
                            'biteship_status' => $result['status'],
                            'courier_company' => $result['courier_company'] ?? null,
                            'courier_type' => $result['courier_type'] ?? null,
                        ]);
                    } else {
                        throw new \RuntimeException('Gagal memesan kurir: '.($result['error'] ?? 'Unknown error'));
                    }
                }

                $order->update([
                    'order_status' => OrderStatus::ON_DELIVERY,
                ]);

                Log::info('PIC sent order to customer', [
                    'order_id' => $order->id,
                    'officer_id' => $officer->id,
                    'type' => $order->isInstant() ? 'instant' : 'preorder',
                ]);

                DB::afterCommit(function () use ($order) {
                    $settings = OrderSettingsDTO::load();
                    if ($settings->whatsappEnabled && ! empty($order->customer?->phone)) {
                        $order->load('customer');
                        $message = "Halo {$order->customer->name},\n\n"
                            ."Pesanan Anda dengan nomor *{$order->number}* sedang dalam perjalanan menuju alamat Anda!\n\n"
                            .'Mohon standby untuk menerima pesanan Anda ya. Terima kasih.';
                        dispatch(new SendWhatsAppNotificationJob($order->customer->phone, $message));
                    }
                });

                return $order->fresh();
            });
        } catch (Throwable $e) {
            Log::error('PIC failed to send order to customer', [
                'order_id' => $order->id,
                'officer_id' => $officer->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * PIC marks pre-order as delivered/completed.
     *
     * Only applicable for pre-orders (manual delivery by PIC).
     * Instant orders are completed via Biteship webhook.
     *
     * @throws Throwable
     */
    public function markDelivered(Order $order, PickUpPointOfficer $officer): Order
    {
        try {
            return DB::transaction(function () use ($order, $officer) {
                $order->update([
                    'order_status' => OrderStatus::DELIVERED,
                    'delivered_at' => now(),
                ]);

                Log::info('PIC marked order as delivered', [
                    'order_id' => $order->id,
                    'officer_id' => $officer->id,
                ]);

                DB::afterCommit(function () use ($order) {
                    $settings = OrderSettingsDTO::load();
                    if ($settings->whatsappEnabled && ! empty($order->customer?->phone)) {
                        $order->load('customer');
                        $message = "Halo {$order->customer->name},\n\n"
                            ."Pesanan Anda dengan nomor *{$order->number}* telah BERHASIL dikirim dan diselesaikan!\n\n"
                            .'Terima kasih telah berbelanja bersama kami. Selamat menikmati hidangan Anda!';
                        dispatch(new SendWhatsAppNotificationJob($order->customer->phone, $message));
                    }
                });

                return $order->fresh();
            });
        } catch (Throwable $e) {
            Log::error('PIC failed to mark order as delivered', [
                'order_id' => $order->id,
                'officer_id' => $officer->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Find the nearest active pickup point to the given coordinates using Haversine formula.
     */
    public static function findNearestPickUpPoint(float $latitude, float $longitude): ?PickUpPoint
    {
        return PickUpPoint::query()
            ->where('is_active', true)
            ->selectRaw('
                *,
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance_km
            ', [$latitude, $longitude, $latitude])
            ->orderBy('distance_km')
            ->first();
    }
}
