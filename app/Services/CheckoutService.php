<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Setting\OrderSettingsDTO;
use App\Models\CompanyProfile;
use App\Models\CustomerAddress;
use App\Models\DropPoint;
use App\Models\PaymentMethod;
use App\Traits\RetryableTransactionsTrait;

/**
 * Service for handling checkout business logic.
 *
 * Provides methods for fee calculation including centralized dynamic shipping
 * via Biteship API (Grab/Gojek instant couriers) for custom address delivery.
 */
class CheckoutService
{
    use RetryableTransactionsTrait;

    /**
     * Create a new CheckoutService instance.
     *
     * @param  BiteshipService  $biteshipService  Service for fetching shipping rates.
     */
    public function __construct(
        private readonly BiteshipService $biteshipService,
    ) {}

    /**
     * Calculate checkout fees based on cart and drop point or custom address.
     *
     * @param  array  $cart  The current items in the cart.
     * @param  string|null  $dropPointId  The selected drop point ID.
     * @param  string|null  $addressId  The selected custom address ID.
     * @param  string|null  $paymentMethodId  The selected payment method ID.
     * @return array Calculated fees including delivery, admin, tax, and shipping breakdown.
     */
    public function calculateFees(
        array $cart,
        ?string $dropPointId = null,
        ?string $addressId = null,
        ?string $paymentMethodId = null,
    ): array {
        $settings = OrderSettingsDTO::load();
        $subtotal = collect($cart)->sum('totalPrice');
        $dropPoint = $dropPointId ? DropPoint::find($dropPointId) : null;
        $address = $addressId ? CustomerAddress::find($addressId) : null;

        // Logic for Delivery Fee
        $deliveryFeeMode = $settings->deliveryFeeMode;
        $minOrderFreeDelivery = $settings->freeCourierMinOrder;

        $shippingBreakdown = [];
        $useBiteship = false;

        if ($address && $address->latitude && $address->longitude) {
            // Custom address
            $useBiteship = false;

            if ($subtotal >= $minOrderFreeDelivery && $minOrderFreeDelivery > 0) {
                $deliveryFee = 0;
            } else {
                $company = CompanyProfile::first();
                $originLat = -7.2575; // Default Surabaya center coordinates if company coordinates are not set
                $originLng = 112.7521;
                $originAddress = $company?->address ?? 'Surabaya';

                $rateResult = $this->biteshipService->getCheapestRate(
                    originLat: $originLat,
                    originLng: $originLng,
                    destLat: (float) $address->latitude,
                    destLng: (float) $address->longitude,
                );

                $shippingBreakdown[] = [
                    'courier_company' => $rateResult['courier_company'],
                    'courier_type' => $rateResult['courier_type'],
                    'courier_name' => $rateResult['courier_name'],
                    'fee' => $rateResult['fee'],
                    'success' => $rateResult['success'],
                    'error' => $rateResult['error'],
                    'origin_address' => $originAddress,
                    'origin_latitude' => $originLat,
                    'origin_longitude' => $originLng,
                    'destination_latitude' => (float) $address->latitude,
                    'destination_longitude' => (float) $address->longitude,
                ];

                $deliveryFee = $rateResult['fee'];
            }
        } else {
            // Drop point → flat fee logic
            if ($subtotal >= $minOrderFreeDelivery && $minOrderFreeDelivery > 0) {
                $deliveryFee = 0;
            } else {
                $baseDeliveryFee = match ($deliveryFeeMode) {
                    'free' => 0,
                    'flat' => $settings->deliveryFeeFlat,
                    default => (int) ($dropPoint?->delivery_fee ?? $settings->deliveryFeeFlat),
                };
                $deliveryFee = $baseDeliveryFee;
            }
        }

        // Logic for Admin Fee
        $adminFeeEnabled = $settings->adminFeeEnabled;
        $adminFee = 0;
        if ($adminFeeEnabled) {
            $adminFeeType = $settings->adminFeeType;
            $adminFeeValue = $settings->adminFeeValue;
            $adminFee = $adminFeeType === 'fixed' ? $adminFeeValue : (int) round($subtotal * $adminFeeValue / 100);
        }

        // Logic for Tax
        $taxEnabled = $settings->taxEnabled;
        $taxPercentage = $settings->taxPercentage;
        $taxAmount = 0;
        if ($taxEnabled) {
            $taxAmount = (int) round($subtotal * $taxPercentage / 100);
        }

        // Logic for Payment Method Service Fee
        $serviceFee = 0;
        if ($paymentMethodId) {
            $paymentMethod = PaymentMethod::find($paymentMethodId);
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
                'flat' => $settings->deliveryFeeFlat,
                default => (int) ($dropPoint?->delivery_fee ?? $settings->deliveryFeeFlat),
            },
            'adminFeeType' => $settings->adminFeeType,
            'adminFeeValue' => $settings->adminFeeValue,
            'shippingBreakdown' => $shippingBreakdown,
            'useBiteship' => $useBiteship,
        ];
    }
}
