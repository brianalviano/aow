<?php

namespace App\DTOs\Attendance;

use App\Http\Requests\Attendance\StoreCheckOutRequest;
use Illuminate\Http\UploadedFile;

class CheckOutData
{
    public function __construct(
        public float $lat,
        public float $long,
        public UploadedFile $photo,
        public ?string $notes,
    ) {}

    public static function fromRequest(StoreCheckOutRequest $request): self
    {
        $p = $request->validated();
        return new self(
            lat: (float) $p['lat'],
            long: (float) $p['long'],
            photo: $request->file('photo'),
            notes: isset($p['notes']) ? (string) $p['notes'] : null,
        );
    }
}
