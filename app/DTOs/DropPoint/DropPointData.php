<?php

declare(strict_types=1);

namespace App\DTOs\DropPoint;

use App\Enums\DropPointCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Data Transfer Object for DropPoint create/update operations.
 *
 * @property string $name Nama drop point
 * @property DropPointCategory $category Kategori drop point
 * @property UploadedFile|null $photo Foto drop point
 * @property string $address Alamat lengkap
 * @property string|null $phone Nomor telepon
 * @property float $latitude Koordinat latitude
 * @property float $longitude Koordinat longitude
 * @property string|null $picName Nama PIC
 * @property string|null $picPhone No. telepon PIC
 * @property bool $isActive Status aktif
 * @property int $deliveryFee Biaya delivery (IDR)
 * @property int|null $minPoQty Minimum kuantitas PO
 * @property int|null $minPoAmount Minimum nominal PO (IDR)
 */
class DropPointData extends Data
{
    public function __construct(
        #[Rule('required', 'string', 'max:255')]
        public readonly string $name,

        public readonly DropPointCategory $category,

        #[Rule('nullable', 'image', 'max:2048')]
        public readonly ?UploadedFile $photo = null,

        #[Rule('required', 'string')]
        public readonly string $address = '',

        #[Rule('nullable', 'string', 'max:20')]
        public readonly ?string $phone = null,

        #[Rule('required', 'numeric', 'between:-90,90')]
        public readonly float $latitude = 0,

        #[Rule('required', 'numeric', 'between:-180,180')]
        public readonly float $longitude = 0,

        #[Rule('nullable', 'string', 'max:255')]
        public readonly ?string $picName = null,

        #[Rule('nullable', 'string', 'max:20')]
        public readonly ?string $picPhone = null,

        #[Rule('sometimes', 'boolean')]
        public readonly bool $isActive = true,

        #[Rule('required', 'integer', 'min:0')]
        public readonly int $deliveryFee = 0,

        #[Rule('nullable', 'integer', 'min:0')]
        public readonly ?int $minPoQty = null,

        #[Rule('nullable', 'integer', 'min:0')]
        public readonly ?int $minPoAmount = null,
    ) {}

    /**
     * Dynamic rules for category enum validation.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'category' => ['required', 'string', new Enum(DropPointCategory::class)],
        ];
    }
}
