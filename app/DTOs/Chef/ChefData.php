<?php

declare(strict_types=1);

namespace App\DTOs\Chef;

use App\Enums\ChefOrderType;
use Illuminate\Validation\Rule as ValidationRule;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Data Transfer Object for Chef create/update operations.
 *
 * Transports validated chef data from HTTP request to Service layer.
 * Product IDs are passed separately for many-to-many sync.
 * Dynamic unique email validation via rules() override for update operations.
 *
 * @property string $name Nama chef
 * @property string|null $businessName Nama usaha chef
 * @property string $email Email chef (unique, digunakan untuk login)
 * @property string|null $password Password chef (null saat update jika tidak diubah)
 * @property string|null $phone Nomor telepon
 * @property string|null $bankName Nama bank untuk transfer
 * @property string|null $accountNumber Nomor rekening
 * @property string|null $accountName Nama pemegang rekening
 * @property string|null $note Catatan internal
 * @property float $feePercentage Persentase fee perusahaan (0–100)
 * @property string|null $address Alamat chef
 * @property float|null $longitude Koordinat longitude
 * @property float|null $latitude Koordinat latitude
 * @property bool $isActive Status aktif
 * @property array<ChefOrderType> $orderTypes Tipe pesanan (instant/preorder)
 * @property array<string> $productIds UUID produk yang di-assign ke chef
 */
class ChefData extends Data
{
    public function __construct(
        #[Rule('required', 'string', 'max:255')]
        public readonly string $name,

        #[Rule('nullable', 'string', 'max:255')]
        public readonly ?string $businessName,

        public readonly string $email,

        #[Rule('nullable', 'string', 'min:8')]
        public readonly ?string $password,

        #[Rule('nullable', 'string', 'max:20')]
        public readonly ?string $phone,

        #[Rule('nullable', 'string', 'max:255')]
        public readonly ?string $bankName,

        #[Rule('nullable', 'string', 'max:50')]
        public readonly ?string $accountNumber,

        #[Rule('nullable', 'string', 'max:255')]
        public readonly ?string $accountName,

        #[Rule('nullable', 'string')]
        public readonly ?string $note,

        #[Rule('required', 'numeric', 'between:0,100')]
        public readonly float $feePercentage,

        #[Rule('nullable', 'string')]
        public readonly ?string $address,

        #[Rule('nullable', 'numeric', 'between:-180,180')]
        public readonly ?float $longitude,

        #[Rule('nullable', 'numeric', 'between:-90,90')]
        public readonly ?float $latitude,

        #[Rule('sometimes', 'boolean')]
        public readonly bool $isActive = true,

        #[Rule('required', 'array', 'min:1')]
        public readonly array $orderTypes = [],

        #[Rule('nullable', 'array')]
        public readonly array $productIds = [],
    ) {}

    /**
     * Dynamic rules for email uniqueness (ignore current chef on update)
     * and nested array item validation.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        $chefId = request()->route('chef')?->id ?? request()->route('chef');

        return [
            'email' => [
                'required',
                'email',
                'max:255',
                $chefId
                    ? ValidationRule::unique('chefs', 'email')->ignore($chefId)
                    : 'unique:chefs,email',
            ],
            'order_types.*' => ['string', ValidationRule::in(ChefOrderType::values())],
            'product_ids.*' => ['uuid', 'exists:products,id'],
        ];
    }
}
