<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Checkout\ProcessOrderData;
use App\Models\{Chef, CustomerAddress, DropPoint, OrderSetting};
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Support\Facades\{Auth, DB, Log};
use Throwable;

/**
 * Service for handling checkout business logic.
 *
 * Provides methods for fee calculation including per-chef dynamic shipping
 * via Biteship API (Grab/Gojek instant couriers) for custom address delivery.
 */
class CheckoutService
{
    use RetryableTransactionsTrait;

    /**
     * Create a new CheckoutService instance.
     *
     * @param BiteshipService $biteshipService Service for fetching shipping rates.
     */
    public function __construct(
        private readonly BiteshipService $biteshipService,
    ) {}

    /**
     * Calculate checkout fees based on cart and drop point or custom address.
     *
     * Untuk custom address, ongkir dihitung dinamis per-chef via Biteship.
     * Untuk drop point, ongkir tetap flat fee seperti sebelumnya.
     *
     * @param array $cart The current items in the cart.
     * @param string|null $dropPointId The selected drop point ID.
     * @param string|null $addressId The selected custom address ID.
     * @param string|null $paymentMethodId The selected payment method ID.
     * @return array Calculated fees including delivery, admin, tax, and per-chef shipping breakdown.
     */
    public function calculateFees(
        array $cart,
        ?string $dropPointId = null,
        ?string $addressId = null,
        ?string $paymentMethodId = null,
    ): array {
        $settings = OrderSetting::pluck('value', 'key')->toArray();
        $subtotal = collect($cart)->sum('totalPrice');
        $dropPoint = $dropPointId ? DropPoint::find($dropPointId) : null;
        $address = $addressId ? CustomerAddress::find($addressId) : null;

        // Logic for Delivery Fee
        $deliveryFeeMode = $settings['delivery_fee_mode'] ?? 'per_drop_point';
        $minOrderFreeDelivery = (int) ($settings['free_courier_min_order'] ?? 0);

        // Get unique chefs from cart
        $uniqueChefIds = collect($cart)->map(function ($item) {
            $chefs = data_get($item, 'product.chefs', []);
            return collect($chefs)->pluck('id');
        })->flatten()->unique()->filter();

        $chefCount = max(1, $uniqueChefIds->count());

        // Per-chef shipping breakdown (Biteship) — only for custom address
        $shippingBreakdown = [];
        $useBiteship = false;

        if ($address && $address->latitude && $address->longitude) {
            // Custom address → use Biteship for per-chef dynamic shipping
            $useBiteship = true;

            if ($subtotal >= $minOrderFreeDelivery && $minOrderFreeDelivery > 0) {
                $deliveryFee = 0;
            } else {
                $deliveryFee = 0;
                $chefs = Chef::whereIn('id', $uniqueChefIds)->get()->keyBy('id');

                foreach ($uniqueChefIds as $chefId) {
                    $chef = $chefs->get($chefId);
                    if (!$chef || !$chef->latitude || !$chef->longitude) {
                        continue;
                    }

                    $rateResult = $this->biteshipService->getCheapestRate(
                        originLat: $chef->latitude,
                        originLng: $chef->longitude,
                        destLat: $address->latitude,
                        destLng: $address->longitude,
                    );

                    $shippingBreakdown[] = [
                        'chef_id' => $chefId,
                        'chef_name' => $chef->business_name ?: $chef->name,
                        'courier_company' => $rateResult['courier_company'],
                        'courier_type' => $rateResult['courier_type'],
                        'courier_name' => $rateResult['courier_name'],
                        'fee' => $rateResult['fee'],
                        'success' => $rateResult['success'],
                        'error' => $rateResult['error'],
                        'origin_address' => $chef->address,
                        'origin_latitude' => $chef->latitude,
                        'origin_longitude' => $chef->longitude,
                        'destination_latitude' => $address->latitude,
                        'destination_longitude' => $address->longitude,
                    ];

                    $deliveryFee += $rateResult['fee'];
                }
            }
        } else {
            // Drop point → flat fee logic (unchanged)
            if ($subtotal >= $minOrderFreeDelivery && $minOrderFreeDelivery > 0) {
                $deliveryFee = 0;
            } else {
                $baseDeliveryFee = match ($deliveryFeeMode) {
                    'free' => 0,
                    'flat' => (int) ($settings['delivery_fee_flat'] ?? 0),
                    default => (int) ($dropPoint?->delivery_fee ?? $settings['delivery_fee_flat'] ?? 0),
                };
                $deliveryFee = $baseDeliveryFee * $chefCount;
            }
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

        // Logic for Payment Method Service Fee
        $serviceFee = 0;
        if ($paymentMethodId) {
            $paymentMethod = \App\Models\PaymentMethod::find($paymentMethodId);
            if ($paymentMethod) {
                $serviceFee = (int) round($subtotal * (float) $paymentMethod->service_fee_rate / 100) + (int) $paymentMethod->service_fee_fixed;
            }
        }

        return [
            'subtotal' => $subtotal,
            'deliveryFee' => $deliveryFee,
            'adminFee' => $adminFee,
            'serviceFee' => $serviceFee,
            'taxAmount' => $taxAmount,
            'taxPercentage' => $taxPercentage,
            'taxEnabled' => $taxEnabled,
            'deliveryFeeMode' => $deliveryFeeMode,
            'minOrderFreeDelivery' => $minOrderFreeDelivery,
            'adminFeeEnabled' => $adminFeeEnabled,
            'baseDeliveryFee' => $useBiteship ? 0 : match ($deliveryFeeMode) {
                'free' => 0,
                'flat' => (int) ($settings['delivery_fee_flat'] ?? 0),
                default => (int) ($dropPoint?->delivery_fee ?? $settings['delivery_fee_flat'] ?? 0),
            },
            'adminFeeType' => $settings['admin_fee_type'] ?? 'fixed',
            'adminFeeValue' => (int) ($settings['admin_fee_value'] ?? 0),
            'shippingBreakdown' => $shippingBreakdown,
            'useBiteship' => $useBiteship,
        ];
    }
}
