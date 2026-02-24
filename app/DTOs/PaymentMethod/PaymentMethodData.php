<?php

declare(strict_types=1);

namespace App\DTOs\PaymentMethod;

use App\Enums\{PaymentMethodCategory, PaymentMethodType};
use App\Http\Requests\Admin\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Requests\Admin\PaymentMethod\UpdatePaymentMethodRequest;
use Illuminate\Http\UploadedFile;

/**
 * Data Transfer Object for PaymentMethod.
 */
class PaymentMethodData
{
    public function __construct(
        public readonly string $name,
        public readonly ?PaymentMethodCategory $category,
        public readonly ?UploadedFile $photo,
        public readonly bool $isActive,
        public readonly PaymentMethodType $type,
        public readonly ?string $accountNumber = null,
        public readonly ?string $accountName = null,
    ) {}

    /**
     * Create DTO from Store Form Request.
     */
    public static function fromStoreRequest(StorePaymentMethodRequest $request): self
    {
        return new self(
            name: (string) $request->validated('name'),
            category: $request->validated('category') ? PaymentMethodCategory::from($request->validated('category')) : null,
            photo: $request->file('photo'),
            isActive: (bool) $request->validated('is_active'),
            type: PaymentMethodType::from($request->validated('type')),
            accountNumber: $request->validated('account_number'),
            accountName: $request->validated('account_name'),
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
            photo: $request->file('photo'),
            isActive: (bool) $request->validated('is_active'),
            type: PaymentMethodType::from($request->validated('type')),
            accountNumber: $request->validated('account_number'),
            accountName: $request->validated('account_name'),
        );
    }
}
