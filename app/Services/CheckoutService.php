<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Checkout\ProcessOrderData;
use App\Models\{Customer, Order, OrderItem, OrderItemOption, OrderSetting, DropPoint};
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Support\Facades\{Auth, DB, Log};
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
     * Calculate checkout fees based on cart and drop point.
     *
     * @param array $cart The current items in the cart.
     * @param int $dropPointId The selected drop point ID.
     * @return array Calculated fees including delivery, admin, and tax.
     */
    public function calculateFees(array $cart, int $dropPointId): array
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
                            $customer = Customer::create([
                                'name' => $data->name,
                                'phone' => $data->phone,
                                'email' => $data->email,
                                'username' => $data->email,
                                'password' => '12345678',
                                'school_class' => $data->schoolClass,
                                'drop_point_id' => $data->dropPoint['id'],
                                'is_active' => true,
                            ]);

                            // TODO: Send email with credentials via event
                        }

                        Auth::guard('customer')->login($customer);
                    }

                    $fees = $this->calculateFees($data->cart, (int) $data->dropPoint['id']);
                    $totalAmount = $fees['subtotal'] + $fees['deliveryFee'] + $fees['taxAmount'] + $fees['adminFee'];

                    $order = Order::create([
                        'number' => 'ORD-' . strtoupper(uniqid()),
                        'drop_point_id' => $data->dropPoint['id'],
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
                            'product_id' => $item['product']['id'],
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

                    session()->forget(['checkout_cart', 'checkout_drop_point']);

                    return $order;
                });
            } catch (Throwable $e) {
                Log::error('Failed to process order', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'customer_id' => Auth::guard('customer')->id(),
                    'payload' => [
                        'email' => $data->email,
                        'payment_method_id' => $data->paymentMethodId,
                        'cart_count' => count($data->cart),
                    ],
                ]);
                throw $e;
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
        $options = $item['product']['options'] ?? [];
        $productOptions = is_array($options) ? $options : ($options['data'] ?? []);

        foreach ($productOptions as $opt) {
            if ((string) $opt['id'] === $optionId) {
                foreach ($opt['items'] as $optItem) {
                    if ((string) $optItem['id'] === $optionItemId) {
                        return (int) ($optItem['extra_price'] ?? 0);
                    }
                }
            }
        }

        return 0;
    }
}
