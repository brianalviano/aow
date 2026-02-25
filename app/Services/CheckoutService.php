<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Checkout\ProcessOrderData;
use App\Enums\{PaymentMethodType};
use App\Mail\{CustomerWelcomeMail, OrderPlacedMail};
use App\Models\{Customer, Order, OrderItem, OrderItemOption, OrderSetting, DropPoint, PaymentMethod};
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Support\Facades\{Auth, DB, Hash, Log, Mail};
use Throwable;

/**
 * Service for handling checkout business logic.
 * 
 * Provides methods for fee calculation and processing customer orders.
 */
class CheckoutService
{
    use RetryableTransactionsTrait;

    /**
     * Create a new CheckoutService instance.
     *
     * @param MidtransService $midtransService
     */
    public function __construct(
        private readonly MidtransService $midtransService
    ) {}

    /**
     * Calculate checkout fees based on cart and drop point.
     *
     * @param array $cart The current items in the cart.
     * @param string $dropPointId The selected drop point ID.
     * @return array Calculated fees including delivery, admin, and tax.
     */
    public function calculateFees(array $cart, string $dropPointId): array
    {
        $settings = OrderSetting::pluck('value', 'key')->toArray();
        $subtotal = collect($cart)->sum('totalPrice');
        $dropPoint = DropPoint::find($dropPointId);

        // Logic for Delivery Fee
        $deliveryFeeMode = $settings['delivery_fee_mode'] ?? 'per_drop_point';
        $minOrderFreeDelivery = (int) ($settings['free_courier_min_order'] ?? 0);

        if ($subtotal >= $minOrderFreeDelivery && $minOrderFreeDelivery > 0) {
            $deliveryFee = 0;
        } else {
            $deliveryFee = match ($deliveryFeeMode) {
                'free' => 0,
                'flat' => (int) ($settings['delivery_fee_flat'] ?? 0),
                default => (int) ($dropPoint->delivery_fee ?? 0),
            };
        }

        // Logic for Admin Fee
        $adminFeeEnabled = ($settings['admin_fee_enabled'] ?? 'false') === 'true';
        $adminFee = 0;
        if ($adminFeeEnabled) {
            $adminFeeType = $settings['admin_fee_type'] ?? 'fixed';
            $adminFeeValue = (int) ($settings['admin_fee_value'] ?? 0);
            $adminFee = $adminFeeType === 'fixed' ? $adminFeeValue : (int) round($subtotal * $adminFeeValue / 100);
        }

        // Logic for Tax
        $taxEnabled = ($settings['tax_enabled'] ?? 'false') === 'true';
        $taxPercentage = (int) ($settings['tax_percentage'] ?? 0);
        $taxAmount = 0;
        if ($taxEnabled) {
            $taxAmount = (int) round($subtotal * $taxPercentage / 100);
        }

        return [
            'subtotal' => $subtotal,
            'deliveryFee' => $deliveryFee,
            'adminFee' => $adminFee,
            'taxAmount' => $taxAmount,
            'taxPercentage' => $taxPercentage,
            'taxEnabled' => $taxEnabled,
            'deliveryFeeMode' => $deliveryFeeMode,
            'minOrderFreeDelivery' => $minOrderFreeDelivery,
            'adminFeeEnabled' => $adminFeeEnabled,
            'baseDeliveryFee' => match ($deliveryFeeMode) {
                'free' => 0,
                'flat' => (int) ($settings['delivery_fee_flat'] ?? 0),
                default => (int) ($dropPoint->delivery_fee ?? 0),
            },
            'adminFeeType' => $settings['admin_fee_type'] ?? 'fixed',
            'adminFeeValue' => (int) ($settings['admin_fee_value'] ?? 0),
        ];
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
                                'name' => $data->name,
                                'phone' => $data->phone,
                                'email' => $data->email,
                                'password' => Hash::make($password),
                                'school_class' => $data->schoolClass,
                                'drop_point_id' => data_get($data->dropPoint, 'id'),
                                'is_active' => true,
                            ]);

                            // Send welcome email with credentials
                            DB::afterCommit(function () use ($customer, $password) {
                                Mail::to($customer->email)->send(new CustomerWelcomeMail($customer, $password));
                            });
                        }

                        Auth::guard('customer')->login($customer);
                    }

                    $fees = $this->calculateFees($data->cart, (string) data_get($data->dropPoint, 'id', ''));
                    $totalAmount = $fees['subtotal'] + $fees['deliveryFee'] + $fees['taxAmount'] + $fees['adminFee'];

                    $order = Order::create([
                        'number' => $this->generateOrderNumber(),
                        'drop_point_id' => data_get($data->dropPoint, 'id'),
                        'customer_id' => $customer->id,
                        'delivery_date' => $data->deliveryDate ?? now()->addDay()->format('Y-m-d'),
                        'delivery_time' => $data->deliveryTime ?? '12:00',
                        'payment_method_id' => $data->paymentMethodId,
                        'payment_status' => 'pending',
                        'order_status' => 'pending',
                        'total_amount' => $totalAmount,
                        'delivery_fee' => $fees['deliveryFee'],
                        'admin_fee' => $fees['adminFee'],
                        'tax_amount' => $fees['taxAmount'],
                    ]);

                    foreach ($data->cart as $item) {
                        $orderItem = OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => data_get($item, 'product.id'),
                            'quantity' => $item['quantity'],
                            'price' => $item['basePrice'],
                            'subtotal' => $item['totalPrice'],
                            'note' => $item['notes'] ?? null,
                        ]);

                        if (isset($item['selectedOptions'])) {
                            foreach ($item['selectedOptions'] as $optionId => $selection) {
                                $itemIds = is_array($selection) ? $selection : [$selection];
                                foreach ($itemIds as $optionItemId) {
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

                    // Send order confirmation email
                    DB::afterCommit(function () use ($order) {
                        Mail::to($order->customer->email)->send(new OrderPlacedMail($order));
                    });

                    session()->forget(['checkout_cart', 'checkout_drop_point']);

                    return $order;
                });
            } catch (Throwable $e) {
                Log::error('CheckoutService - Failed to process order', [
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
