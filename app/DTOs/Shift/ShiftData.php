<?php

declare(strict_types=1);

namespace App\DTOs\Shift;

use App\Http\Requests\Shift\StoreShiftRequest;
use App\Http\Requests\Shift\UpdateShiftRequest;

class ShiftData
{
    public function __construct(
        public ?string $startTime,
        public ?string $endTime,
        public bool $isOvernight,
        public bool $isOff,
    ) {}

    public static function fromStoreRequest(StoreShiftRequest $request): self
    {
        $p = $request->validated();
        return new self(
            startTime: isset($p['start_time']) ? (string) $p['start_time'] : null,
            endTime: isset($p['end_time']) ? (string) $p['end_time'] : null,
            isOvernight: is_bool($p['is_overnight']) ? $p['is_overnight'] : filter_var($p['is_overnight'], FILTER_VALIDATE_BOOLEAN),
            isOff: is_bool($p['is_off']) ? $p['is_off'] : filter_var($p['is_off'], FILTER_VALIDATE_BOOLEAN),
        );
    }

    public static function fromUpdateRequest(UpdateShiftRequest $request): self
    {
        $p = $request->validated();
        return new self(
            startTime: isset($p['start_time']) ? (string) $p['start_time'] : null,
            endTime: isset($p['end_time']) ? (string) $p['end_time'] : null,
            isOvernight: is_bool($p['is_overnight']) ? $p['is_overnight'] : filter_var($p['is_overnight'], FILTER_VALIDATE_BOOLEAN),
            isOff: is_bool($p['is_off']) ? $p['is_off'] : filter_var($p['is_off'], FILTER_VALIDATE_BOOLEAN),
        );
    }
}
