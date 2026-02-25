<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentMethod;
use Midtrans\Config;
use Midtrans\CoreApi;
use Throwable;
use Illuminate\Support\Facades\Log;

/**
 * Service for interacting with Midtrans Core API (VT-Direct).
 */
class MidtransService
{
    /**
     * MidtransService constructor.
     */
    public function __construct()
    {
        $this->configureMidtrans();
    }

    /**
     * Configure Midtrans settings.
     */
    private function configureMidtrans(): void
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;

        if (!Config::$isProduction) {
            Config::$curlOptions = [
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_HTTPHEADER => [],
            ];
        }
    }

    /**
     * Charge an order via Midtrans Core API.
     *
     * @param Order $order
     * @param PaymentMethod $paymentMethod
     * @return object Midtrans response
     * @throws Throwable
     */
    public function charge(Order $order, PaymentMethod $paymentMethod): object
    {
        // Ensure all necessary relationships are loaded for mapping
        $order->load(['customer', 'items.product', 'items.options', 'paymentMethod', 'dropPoint']);

        $params = [
            'transaction_details' => [
                'order_id' => $order->number,
                'gross_amount' => (int) $order->total_amount,
            ],
            'customer_details' => [
                'first_name' => $order->customer->name,
                'email' => $order->customer->email,
                'phone' => $order->customer->phone,
                'shipping_address' => [
                    'first_name' => $order->dropPoint->name,
                    'address' => $order->dropPoint->address,
                    'phone' => $order->dropPoint->pic_phone ?? $order->dropPoint->phone,
                    'country_code' => 'IDN',
                ],
            ],
            'item_details' => $this->mapItems($order),
        ];

        // Expiry duration from settings (default 60 minutes)
        $expiryDuration = (int) \App\Models\OrderSetting::where('key', 'payment_expired_duration')->value('value') ?: 60;
        $params['expiry'] = [
            'unit' => 'minute',
            'duration' => $expiryDuration,
        ];

        // Specific payment type handling
        $params = array_merge($params, $this->getPaymentParams($paymentMethod));

        try {
            return CoreApi::charge($params);
        } catch (Throwable $e) {
            Log::error('Midtrans Charge Failed', [
                'order_id' => $order->number,
                'error' => $e->getMessage(),
                'params' => $params,
            ]);
            throw $e;
        }
    }

    /**
     * Map order items to Midtrans format.
     */
    private function mapItems(Order $order): array
    {
        $items = [];

        // 1. Order Items (Products + Options)
        foreach ($order->items as $item) {
            // We use subtotal divided by quantity to get the effective unit price (includes options)
            // This ensures (unitPrice * quantity) matches the item subtotal exactly.
            $unitPrice = (int) ($item->subtotal / $item->quantity);

            $items[] = [
                'id' => 'ITEM-' . $item->id,
                'price' => $unitPrice,
                'quantity' => (int) $item->quantity,
                'name' => data_get($item, 'product.name', 'Produk'),
            ];

            // Item-level discount handling (if stored separately as negative amount)
            // But usually subtotal already accounts for this.
            // If subtotal is final, we don't need to add another discount item here.
        }

        // 2. Shipping Cost (use final_delivery_fee if available)
        $shippingCost = $order->final_delivery_fee ?? $order->delivery_fee;
        if ($shippingCost > 0) {
            $items[] = [
                'id' => 'FEE-SHIPPING',
                'price' => (int) $shippingCost,
                'quantity' => 1,
                'name' => 'Biaya Ongkir',
            ];
        }

        // 3. Admin Fee
        if ($order->admin_fee > 0) {
            $items[] = [
                'id' => 'FEE-ADMIN',
                'price' => (int) $order->admin_fee,
                'quantity' => 1,
                'name' => 'Biaya Admin',
            ];
        }

        // 4. Tax (PPN)
        if ($order->tax_amount > 0) {
            $items[] = [
                'id' => 'TAX-PPN',
                'price' => (int) $order->tax_amount,
                'quantity' => 1,
                'name' => 'PPN',
            ];
        }

        // 5. Global/Coupon Discount
        if ($order->discount_amount > 0) {
            $items[] = [
                'id' => 'DISC-GLOBAL',
                'price' => -(int) $order->discount_amount,
                'quantity' => 1,
                'name' => 'Diskon Pesanan',
            ];
        }

        return $items;
    }

    /**
     * Handle Midtrans notification and update order status.
     *
     * @return array
     * @throws Throwable
     */
    public function handleNotification(): array
    {
        try {
            $notification = new \Midtrans\Notification();

            $orderId = (string) $notification->order_id;
            $statusCode = (string) $notification->status_code;
            $grossAmount = (string) $notification->gross_amount;
            $signatureKey = (string) $notification->signature_key;
            $serverKey = config('services.midtrans.server_key');

            // Security check: Validate signature key
            $calculatedHash = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

            if ($calculatedHash !== $signatureKey) {
                Log::warning('Midtrans Webhook: Invalid Signature', [
                    'order_id' => $orderId,
                    'provided_signature' => $signatureKey,
                    'calculated_signature' => $calculatedHash,
                ]);
                throw new \Exception('Invalid signature');
            }

            $transactionStatus = $notification->transaction_status;
            $fraudStatus = $notification->fraud_status;

            $order = Order::where('number', $orderId)->first();

            if (!$order) {
                throw new \Exception('Order not found: ' . $orderId);
            }

            $paymentStatus = 'pending';

            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'challenge') {
                    $paymentStatus = 'challenge';
                } else if ($fraudStatus == 'accept') {
                    $paymentStatus = 'paid';
                }
            } else if ($transactionStatus == 'settlement') {
                $paymentStatus = 'paid';
            } else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
                $paymentStatus = 'failed';
            } else if ($transactionStatus == 'pending') {
                $paymentStatus = 'pending';
            }

            $order->update([
                'payment_status' => $paymentStatus,
                'order_status' => $paymentStatus === 'paid' ? 'processing' : $order->order_status,
            ]);

            return [
                'order_id' => $orderId,
                'status' => $paymentStatus,
            ];
        } catch (Throwable $e) {
            Log::error('Midtrans Webhook Processing Failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get payment specific parameters based on payment method code.
     */
    private function getPaymentParams(PaymentMethod $paymentMethod): array
    {
        $code = $paymentMethod->code;

        // VA Banks
        if (in_array($code, ['bca', 'bni', 'bri', 'permata'])) {
            return [
                'payment_type' => 'bank_transfer',
                'bank_transfer' => [
                    'bank' => $code,
                ],
            ];
        }

        // Mandiri Bill (Special VA)
        if ($code === 'mandiri') {
            return [
                'payment_type' => 'echannel',
                'echannel' => [
                    'bill_info1' => 'Payment For:',
                    'bill_info2' => 'Order ' . uniqid(),
                ],
            ];
        }

        // GoPay / QRIS
        if ($code === 'gopay' || $code === 'qris') {
            return [
                'payment_type' => 'gopay', // GOPAY response usually includes QRIS
                'gopay' => [
                    'enable_callback' => true,
                    'callback_url' => url('/'),
                ],
            ];
        }

        throw new \Exception('Metode pembayaran Midtrans tidak didukung: ' . $code);
    }
}
