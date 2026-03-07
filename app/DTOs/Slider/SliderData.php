<?php

declare(strict_types=1);

namespace App\DTOs\Slider;

use App\Traits\FileHelperTrait;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Data Transfer Object for Slider create/update operations.
 *
 * Uses FileHelperTrait for dynamic photo validation rules
 * that support both file uploads and URL strings.
 *
 * @property string $name Nama slider
 * @property string|UploadedFile|null $photo Gambar slider (file upload atau URL)
 */
class SliderData extends Data
{
    use FileHelperTrait;

    public function __construct(
        #[Rule('required', 'string', 'max:255')]
        public readonly string $name,

        public readonly string|UploadedFile|null $photo = null,
    ) {}

    /**
     * Dynamic rules for photo field using FileHelperTrait.
     *
     * Photo is required on store (no existing slider), nullable on update.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        $isUpdate = request()->route('slider') !== null;
        $self = new self(name: '');

        return [
            'photo' => $self->getFileValidationRules(!$isUpdate, [
                'allowed_types' => ['image/jpeg', 'image/png', 'image/webp'],
                'max_size' => 2 * 1024 * 1024, // 2MB
            ]),
        ];
    }
}
