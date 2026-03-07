<?php

declare(strict_types=1);

namespace App\DTOs\PickUpPoint;

use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Data Transfer Object for PickUpPoint create/update operations.
 *
 * @property string $name Nama pick-up point
 * @property string $address Alamat lengkap
 * @property float $latitude Koordinat latitude
 * @property float $longitude Koordinat longitude
 * @property string|null $description Deskripsi
 * @property bool $isActive Status aktif
 * @property array<string> $officerIds ID officer yang di-assign
 */
class PickUpPointData extends Data
{
    public function __construct(
        #[Rule('required', 'string', 'max:255')]
        public readonly string $name,

        #[Rule('required', 'string')]
        public readonly string $address,

        #[Rule('required', 'numeric', 'between:-90,90')]
        public readonly float $latitude,

        #[Rule('required', 'numeric', 'between:-180,180')]
        public readonly float $longitude,

        #[Rule('nullable', 'string', 'max:1000')]
        public readonly ?string $description = null,

        #[Rule('sometimes', 'boolean')]
        public readonly bool $isActive = true,

        #[Rule('nullable', 'array')]
        public readonly array $officerIds = [],
    ) {}

    /**
     * Dynamic rules for nested array item validation.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'officer_ids.*' => ['exists:pick_up_points_officers,id'],
        ];
    }
}
