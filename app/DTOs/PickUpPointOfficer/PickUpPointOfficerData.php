<?php

declare(strict_types=1);

namespace App\DTOs\PickUpPointOfficer;

use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Data Transfer Object untuk Pick Up Point Officer create/update operations.
 *
 * Dynamic unique email validation via rules() override for update operations.
 *
 * @property string $name Nama officer
 * @property string $phone Nomor telepon
 * @property string $email Email (unique)
 * @property string|null $password Password (required on create, nullable on update)
 * @property string|null $pickUpPointId ID pick-up point yang di-assign
 * @property bool $isActive Status aktif
 */
class PickUpPointOfficerData extends Data
{
    public function __construct(
        #[Rule('required', 'string', 'max:255')]
        public readonly string $name,

        #[Rule('required', 'string', 'max:20')]
        public readonly string $phone,

        public readonly string $email,

        #[Rule('nullable', 'string', 'min:8', 'confirmed')]
        public readonly ?string $password = null,

        #[Rule('nullable', 'exists:pick_up_points,id')]
        public readonly ?string $pickUpPointId = null,

        #[Rule('sometimes', 'boolean')]
        public readonly bool $isActive = true,
    ) {}

    /**
     * Dynamic rules for email uniqueness (ignore current officer on update).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        $officer = request()->route('pick_up_point_officer');
        $officerId = is_object($officer) ? $officer->id : $officer;

        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                $officerId
                    ? 'unique:pick_up_point_officers,email,' . $officerId
                    : 'unique:pick_up_point_officers,email',
            ],
        ];
    }
}
