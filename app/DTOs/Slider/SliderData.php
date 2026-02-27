<?php

declare(strict_types=1);

namespace App\DTOs\Slider;

use App\Http\Requests\Admin\Slider\{StoreSliderRequest, UpdateSliderRequest};
use Illuminate\Http\UploadedFile;

/**
 * Data Transfer Object for Slider.
 */
class SliderData
{
    public function __construct(
        public readonly string $name,
        public readonly string|UploadedFile|null $photo = null,
    ) {}

    /**
     * Create DTO from Store Form Request.
     *
     * @param StoreSliderRequest $request
     * @return self
     */
    public static function fromStoreRequest(StoreSliderRequest $request): self
    {
        return new self(
            name: (string) $request->validated('name'),
            photo: $request->file('photo'),
        );
    }

    /**
     * Create DTO from Update Form Request.
     *
     * @param UpdateSliderRequest $request
     * @return self
     */
    public static function fromUpdateRequest(UpdateSliderRequest $request): self
    {
        return new self(
            name: (string) $request->validated('name'),
            photo: $request->file('photo') ?? $request->validated('photo'),
        );
    }
}
