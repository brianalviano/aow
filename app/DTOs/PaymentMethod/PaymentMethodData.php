<?php

declare(strict_types=1);

namespace App\DTOs\PaymentMethod;

use App\Http\Requests\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Requests\PaymentMethod\UpdatePaymentMethodRequest;
use Illuminate\Http\UploadedFile;

final class PaymentMethodData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $iconClass,
        public string|UploadedFile|null $image,
        public string $mdrPercentage,
        public bool $isActive,
    ) {}

    public static function fromStoreRequest(StorePaymentMethodRequest $request): self
    {
        $v = $request->validated();
        return new self(
            $v['name'],
            $v['description'] ?? null,
            $v['icon_class'] ?? null,
            $request->file('image_url') ?? ($v['image_url'] ?? null),
            (string) $v['mdr_percentage'],
            (bool) $v['is_active'],
        );
    }

    public static function fromUpdateRequest(UpdatePaymentMethodRequest $request): self
    {
        $v = $request->validated();
        return new self(
            $v['name'],
            $v['description'] ?? null,
            $v['icon_class'] ?? null,
            $request->file('image_url') ?? ($v['image_url'] ?? null),
            (string) $v['mdr_percentage'],
            (bool) $v['is_active'],
        );
    }
}
