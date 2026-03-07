<?php

declare(strict_types=1);

namespace App\DTOs\Checkout;

use App\Enums\DropPointCategory;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Data Transfer Object for processing an order.
 *
 * This class encapsulates all necessary information required to create an order
 * and manage customer data during the checkout process.
 *
 * Uses a custom factory method because cart, dropPoint, and address data
 * come from the session (not the HTTP request), so Data::from() cannot be used directly.
 *
 * @property string $name Customer's full name.
 * @property string $phone Customer's phone number.
 * @property string $email Customer's email address.
 * @property string|null $schoolClass Customer's school/class description.
 * @property string $paymentMethodId Selected payment method ID.
 * @property array $cart Items currently in the customer's cart.
 * @property array|null $dropPoint Target drop point information.
 * @property array|null $address Target custom address information.
 * @property string|null $deliveryDate Scheduled delivery date (YYYY-MM-DD).
 * @property string|null $deliveryTime Scheduled delivery time (HH:MM).
 */
class ProcessOrderData extends Data
{
    public function __construct(
        #[Rule('required', 'string', 'max:255')]
        public readonly string $name,

        #[Rule('required', 'string', 'max:20')]
        public readonly string $phone,

        #[Rule('required', 'email', 'max:255')]
        public readonly string $email,

        public readonly ?string $schoolClass = null,

        #[Rule('required', 'exists:payment_methods,id')]
        public readonly string $paymentMethodId = '',

        public readonly array $cart = [],

        public readonly ?array $dropPoint = null,

        public readonly ?array $address = null,

        #[Rule('nullable', 'date')]
        public readonly ?string $deliveryDate = null,

        #[Rule('nullable')]
        public readonly ?string $deliveryTime = null,
    ) {}

    /**
     * Dynamic rules: school_class is required when drop point category is SCHOOL.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        $dropPoint = session('checkout_drop_point');
        $isSchool = $dropPoint && ($dropPoint['category'] ?? '') === DropPointCategory::SCHOOL->value;

        return [
            'school_class' => [($isSchool ? 'required' : 'nullable'), 'string', 'max:255'],
        ];
    }

    /**
     * Create DTO instance from validated request data and session data.
     *
     * This factory is necessary because cart, dropPoint, and address come
     * from the session, not the HTTP request body.
     *
     * @param array<string, mixed> $validated Validated request data
     * @param array $cart Cart data from session
     * @param array|null $dropPoint Drop point data from session
     * @param array|null $address Custom address data from session
     * @return self
     */
    public static function fromCheckout(
        array $validated,
        array $cart,
        ?array $dropPoint = null,
        ?array $address = null,
    ): self {
        return new self(
            name: (string) ($validated['name'] ?? ''),
            phone: (string) ($validated['phone'] ?? ''),
            email: (string) ($validated['email'] ?? ''),
            schoolClass: $validated['school_class'] ?? null,
            paymentMethodId: (string) ($validated['payment_method_id'] ?? ''),
            cart: $cart,
            dropPoint: $dropPoint,
            address: $address,
            deliveryDate: $validated['delivery_date'] ?? null,
            deliveryTime: $validated['delivery_time'] ?? null,
        );
    }
}
