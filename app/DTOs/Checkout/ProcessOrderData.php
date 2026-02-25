<?php

declare(strict_types=1);

namespace App\DTOs\Checkout;

use App\Http\Requests\Customer\Checkout\ProcessPaymentRequest;

/**
 * Data Transfer Object for processing an order.
 * 
 * This class encapsulates all necessary information required to create an order
 * and manage customer data during the checkout process.
 */
class ProcessOrderData
{
    /**
     * Create a new ProcessOrderData instance.
     *
     * @param string $name Customer's full name.
     * @param string $phone Customer's phone number.
     * @param string $email Customer's email address.
     * @param string $schoolClass Customer's school/class description.
     * @param int $paymentMethodId Selected payment method ID.
     * @param array $cart Items currently in the customer's cart.
     * @param array $dropPoint Target drop point information.
     * @param string|null $deliveryDate Scheduled delivery date (YYYY-MM-DD).
     * @param string|null $deliveryTime Scheduled delivery time (HH:MM).
     */
    public function __construct(
        public readonly string $name,
        public readonly string $phone,
        public readonly string $email,
        public readonly ?string $schoolClass,
        public readonly string $paymentMethodId,
        public readonly array $cart,
        public readonly array $dropPoint,
        public readonly ?string $deliveryDate = null,
        public readonly ?string $deliveryTime = null,
    ) {}

    /**
     * Create a DTO instance from a validated request and session data.
     *
     * @param ProcessPaymentRequest $request The validated request containing user input.
     * @param array $cart The cart data retrieved from session.
     * @param array $dropPoint The drop point data retrieved from session.
     * @return self
     */
    public static function fromRequest(ProcessPaymentRequest $request, array $cart, array $dropPoint): self
    {
        return new self(
            name: $request->validated('name'),
            phone: $request->validated('phone'),
            email: $request->validated('email'),
            schoolClass: $request->validated('school_class'),
            paymentMethodId: $request->validated('payment_method_id'),
            cart: $cart,
            dropPoint: $dropPoint,
            deliveryDate: $request->validated('delivery_date'),
            deliveryTime: $request->validated('delivery_time'),
        );
    }
}
