<?php

declare(strict_types=1);

namespace App\DTOs\PaymentMethod;

use App\Enums\{PaymentMethodCategory, PaymentMethodType};
use App\Http\Requests\Admin\PaymentMethod\{StorePaymentMethodRequest, UpdatePaymentMethodRequest};
use Illuminate\Http\UploadedFile;

/**
 * Data Transfer Object for PaymentMethod.
 */
class PaymentMethodData
{
    public function __construct(
        public readonly string $name,
        public readonly ?PaymentMethodCategory $category,
        public readonly PaymentMethodType $type,
        public readonly ?UploadedFile $photo,
        public readonly bool $isActive,
        public readonly ?string $code = null,
        public readonly ?string $accountNumber = null,
        public readonly ?string $accountName = null,
        public readonly ?string $paymentGuideId = null,
        public readonly float $serviceFeeRate = 0,
        public readonly int $serviceFeeFixed = 0,
    ) {}

    /**
     * Create DTO from Store Form Request.
     */
    public static function fromStoreRequest(StorePaymentMethodRequest $request): self
    {
        return new self(
            name: (string) $request->validated('name'),
            category: $request->validated('category') ? PaymentMethodCategory::from($request->validated('category')) : null,
            type: PaymentMethodType::from((string) $request->validated('type')),
            photo: $request->file('photo'),
            isActive: (bool) $request->validated('is_active'),
            code: (string) $request->validated('code'),
            accountNumber: (string) $request->validated('account_number'),
            accountName: (string) $request->validated('account_name'),
            paymentGuideId: (string) $request->validated('payment_guide_id'),
            serviceFeeRate: (float) $request->validated('service_fee_rate', 0),
            serviceFeeFixed: (int) $request->validated('service_fee_fixed', 0),
        );
    }

    /**
     * Create DTO from Update Form Request.
     */
    public static function fromUpdateRequest(UpdatePaymentMethodRequest $request): self
    {
        return new self(
            name: (string) $request->validated('name'),
            category: $request->validated('category') ? PaymentMethodCategory::from($request->validated('category')) : null,
            type: PaymentMethodType::from((string) $request->validated('type')),
            photo: $request->file('photo'),
            isActive: (bool) $request->validated('is_active'),
            code: (string) $request->validated('code'),
            accountNumber: (string) $request->validated('account_number'),
            accountName: (string) $request->validated('account_name'),
            paymentGuideId: (string) $request->validated('payment_guide_id'),
            serviceFeeRate: (float) $request->validated('service_fee_rate', 0),
            serviceFeeFixed: (int) $request->validated('service_fee_fixed', 0),
        );
    }
}
