<?php

namespace App\DTOs\Schedule;

use App\Http\Requests\Schedule\StoreScheduleRequest;

class ScheduleBatchData
{
    /**
     * @param ScheduleEntryData[] $entries
     */
    public function __construct(
        public ?string $roleId,
        public string $startDate,
        public string $endDate,
        public array $entries,
    ) {}

    public static function fromRequest(StoreScheduleRequest $request): self
    {
        $p = $request->validated();
        $entries = array_map(
            fn(array $e) => ScheduleEntryData::fromArray($e),
            (array) ($p['entries'] ?? []),
        );
        $role = isset($p['role_id']) ? (string) $p['role_id'] : null;
        if ($role === '') {
            $role = null;
        }
        return new self(
            roleId: $role,
            startDate: (string) $p['start_date'],
            endDate: (string) $p['end_date'],
            entries: $entries,
        );
    }
}
