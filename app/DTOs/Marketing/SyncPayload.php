<?php

declare(strict_types=1);

namespace App\DTOs\Marketing;

use App\Http\Requests\Marketing\SyncRequest;

final class SyncPayload
{
    public function __construct(
        public array $customersCreate = [],
        public array $customersUpdate = [],
        public array $salesCreate = [],
        public array $salesUpdate = [],
        public array $notificationsMarkRead = [],
        public ?array $profileUpdate = null,
    ) {}

    public static function fromRequest(SyncRequest $request): self
    {
        $p = $request->validated();
        return new self(
            customersCreate: is_array($p['customers_create'] ?? null) ? (array) $p['customers_create'] : [],
            customersUpdate: is_array($p['customers_update'] ?? null) ? (array) $p['customers_update'] : [],
            salesCreate: is_array($p['sales_create'] ?? null) ? (array) $p['sales_create'] : [],
            salesUpdate: is_array($p['sales_update'] ?? null) ? (array) $p['sales_update'] : [],
            notificationsMarkRead: is_array($p['notifications_mark_read'] ?? null) ? (array) $p['notifications_mark_read'] : [],
            profileUpdate: is_array($p['profile_update'] ?? null) ? (array) $p['profile_update'] : null,
        );
    }
}
