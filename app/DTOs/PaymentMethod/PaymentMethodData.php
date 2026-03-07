<?php

declare(strict_types=1);

namespace App\DTOs\PaymentMethod;

use App\Enums\{PaymentMethodCategory, PaymentMethodType};
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule as ValidationRule;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Data Transfer Object for PaymentMethod create/update operations.
 *
 * @property string $name Nama metode pembayaran
 * @property PaymentMethodCategory|null $category Kategori (bank_transfer, e_wallet, dll)
 * @property PaymentMethodType $type Tipe (manual, midtrans)
 * @property UploadedFile|null $photo Logo/icon pembayaran
 * @property bool $isActive Status aktif
 * @property string|null $code Kode internal
 * @property string|null $accountNumber Nomor rekening
 * @property string|null $accountName Nama pemegang rekening
 * @property string|null $paymentGuideId ID panduan pembayaran
 * @property float $serviceFeeRate Persentase biaya layanan
 * @property int $serviceFeeFixed Biaya layanan tetap (IDR)
 */
class PaymentMethodData extends Data
{
    public function __construct(
        #[Rule('required', 'string', 'max:255')]
        public readonly string $name,

        public readonly ?PaymentMethodCategory $category,

        public readonly PaymentMethodType $type,

        #[Rule('nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048')]
        public readonly ?UploadedFile $photo = null,

        #[Rule('required', 'boolean')]
        public readonly bool $isActive = true,

        #[Rule('nullable', 'string', 'max:255')]
        public readonly ?string $code = null,

        #[Rule('nullable', 'string', 'max:255')]
        public readonly ?string $accountNumber = null,

        #[Rule('nullable', 'string', 'max:255')]
        public readonly ?string $accountName = null,

        #[Rule('nullable', 'exists:payment_guides,id')]
        public readonly ?string $paymentGuideId = null,

        #[Rule('nullable', 'numeric', 'min:0', 'max:100')]
        public readonly float $serviceFeeRate = 0,

        #[Rule('nullable', 'integer', 'min:0')]
        public readonly int $serviceFeeFixed = 0,
    ) {}

    /**
     * Dynamic rules for enum validation on category and type fields.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'category' => ['nullable', ValidationRule::enum(PaymentMethodCategory::class)],
            'type' => ['required', ValidationRule::enum(PaymentMethodType::class)],
        ];
    }
}
