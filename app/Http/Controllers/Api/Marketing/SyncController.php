<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\HandlesApiExceptions;
use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Requests\Marketing\SyncRequest;
use App\DTOs\Marketing\SyncPayload;
use App\Http\Resources\CustomerResource;
use App\Services\{MarketingSyncService, SalesViewService, NotificationService};
use Illuminate\Http\JsonResponse;

final class SyncController extends Controller
{
    use HandlesApiExceptions;
    use RespondsWithJson;

    public function __construct(
        private readonly MarketingSyncService $service,
        private readonly SalesViewService $salesView,
        private readonly NotificationService $notificationService,
    ) {}

    public function sync(SyncRequest $request): JsonResponse
    {
        return $this->apiTry(function () use ($request) {
            $payload = SyncPayload::fromRequest($request);
            $result = $this->service->sync($payload, $request->user());

            $customersCreated = array_map(
                fn($c) => CustomerResource::make($c)->toArray($request),
                (array) ($result['customers_created'] ?? []),
            );
            $customersUpdated = array_map(
                fn($c) => CustomerResource::make($c)->toArray($request),
                (array) ($result['customers_updated'] ?? []),
            );
            $salesCreated = array_map(
                fn($s) => $this->salesView->buildSalePayload($s, true),
                (array) ($result['sales_created'] ?? []),
            );
            $salesUpdated = array_map(
                fn($s) => $this->salesView->buildSalePayload($s, true),
                (array) ($result['sales_updated'] ?? []),
            );

            $stats = $this->notificationService->getStatsForUser($request->user());

            return $this->apiResponse('Sinkronisasi berhasil', [
                'summary' => [
                    'customers_created' => (int) count($customersCreated),
                    'customers_updated' => (int) count($customersUpdated),
                    'sales_created' => (int) count($salesCreated),
                    'sales_updated' => (int) count($salesUpdated),
                    'notifications_marked' => (int) ($result['notifications_marked'] ?? 0),
                    'profile_updated' => (bool) ($result['profile_updated'] ?? false),
                ],
                'mappings' => (array) ($result['mappings'] ?? []),
                'customers' => [
                    'created' => $customersCreated,
                    'updated' => $customersUpdated,
                ],
                'sales' => [
                    'created' => $salesCreated,
                    'updated' => $salesUpdated,
                ],
                'notifications_count' => (int) $stats['unread'],
            ]);
        }, $request, [
            'user_id' => (string) ($request->user()?->getAuthIdentifier() ?? ''),
        ]);
    }
}
