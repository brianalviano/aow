<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Marketing\SyncPayload;
use App\DTOs\Customer\CustomerData;
use App\DTOs\Sales\SalesData;
use App\DTOs\Employee\EmployeeData;
use App\Enums\CustomerSource;
use App\Models\{Customer, Sales};
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

final class MarketingSyncService
{
    public function __construct(
        private readonly CustomerService $customerService,
        private readonly SalesService $salesService,
        private readonly NotificationService $notificationService,
        private readonly EmployeeService $employeeService,
    ) {}

    public function sync(SyncPayload $payload, Authenticatable $user): array
    {
        try {
            $userId = (string) $user->getAuthIdentifier();

            $createdCustomers = [];
            $customerIdMap = [];
            foreach ((array) $payload->customersCreate as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $dto = CustomerData::fromArray($row, $userId, CustomerSource::Marketing->value);
                $created = $this->customerService->create($dto);
                $createdCustomers[] = $created;
                $clientId = isset($row['client_id']) ? (string) $row['client_id'] : null;
                if ($clientId !== null && $clientId !== '') {
                    $customerIdMap[] = ['client_id' => $clientId, 'id' => (string) $created->id];
                }
            }

            $updatedCustomers = [];
            foreach ((array) $payload->customersUpdate as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $id = isset($row['id']) ? (string) $row['id'] : '';
                $customer = Customer::query()->findOrFail($id);
                $dto = CustomerData::fromArray($row, $userId, CustomerSource::Marketing->value);
                $updatedCustomers[] = $this->customerService->update($customer, $dto);
            }

            $createdSales = [];
            $salesIdMap = [];
            foreach ((array) $payload->salesCreate as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $dto = SalesData::fromArray($row, $userId);
                $created = $this->salesService->create($dto);
                $createdSales[] = $created;
                $clientId = isset($row['client_id']) ? (string) $row['client_id'] : null;
                if ($clientId !== null && $clientId !== '') {
                    $salesIdMap[] = ['client_id' => $clientId, 'id' => (string) $created->id];
                }
            }

            $updatedSales = [];
            foreach ((array) $payload->salesUpdate as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $id = isset($row['id']) ? (string) $row['id'] : (string) ($row['sales_id'] ?? '');
                $sales = Sales::query()->findOrFail($id);
                $updatedSales[] = $this->salesService->update($sales, (array) $row, $userId);
            }

            $notificationsMarked = 0;
            foreach ((array) $payload->notificationsMarkRead as $nid) {
                $nidStr = (string) $nid;
                if ($nidStr === '') {
                    continue;
                }
                $this->notificationService->markAsRead($user, $nidStr);
                $notificationsMarked++;
            }

            $profileUpdated = false;
            if (is_array($payload->profileUpdate)) {
                $data = EmployeeData::fromArrayMyProfile((array) $payload->profileUpdate);
                $this->employeeService->update($user, $data);
                $profileUpdated = true;
            }

            return [
                'customers_created' => $createdCustomers,
                'customers_updated' => $updatedCustomers,
                'sales_created' => $createdSales,
                'sales_updated' => $updatedSales,
                'notifications_marked' => $notificationsMarked,
                'profile_updated' => $profileUpdated,
                'mappings' => [
                    'customers' => $customerIdMap,
                    'sales' => $salesIdMap,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('marketing_sync_failed', [
                'user_id' => (string) ($user?->getAuthIdentifier() ?? ''),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
