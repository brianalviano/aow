<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Checkout\ProcessOrderData;
use App\Models\{DropPoint, OrderSetting};
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

    public function __construct() {}

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
}
