<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\HandlesApiExceptions;
use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Models\Customer;
use App\Services\CustomerService;
use App\Services\EmployeeService;
use App\DTOs\Customer\CustomerData;
use App\Enums\CustomerSource;
use App\Http\Requests\Customer\{StoreCustomerRequest, UpdateCustomerRequest};
use App\Http\Resources\CustomerResource;
use Illuminate\Http\{JsonResponse, Request};

/**
 * CustomerController (API Marketing).
 *
 * Endpoints untuk create, read, update Customer pada aplikasi Marketing.
 *
 * @author
 * @package Http\Controllers\Api\Marketing
 */
class CustomerController extends Controller
{
    use HandlesApiExceptions;
    use RespondsWithJson;

    public function __construct(
        private readonly CustomerService $service,
        private readonly EmployeeService $employeeService,
    ) {}

    /**
     * Daftar customers yang terlihat di Marketing.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        return $this->apiTry(function () use ($request) {
            $q = (string) $request->string('q')->toString();
            $isActive = (string) $request->string('is_active')->toString();
            $perPage = (int) $request->integer('per_page', 10);
            $latParam = $request->input('lat', $request->input('latitude'));
            $longParam = $request->input('long', $request->input('longitude'));
            if ($latParam !== null && $longParam !== null && $request->user()) {
                $this->employeeService->updateLocation($request->user(), (float) $latParam, (float) $longParam);
            }

            $query = Customer::query()
                ->where('is_visible_in_marketing', true)
                ->when($q !== '', function ($builder) use ($q) {
                    $builder->where(function ($w) use ($q) {
                        $w->where('name', 'ilike', "%{$q}%")
                            ->orWhere('email', 'ilike', "%{$q}%")
                            ->orWhere('phone', 'ilike', "%{$q}%")
                            ->orWhere('address', 'ilike', "%{$q}%");
                    });
                })
                ->when($isActive !== '', function ($builder) use ($isActive) {
                    $builder->where('is_active', $isActive === '1' || $isActive === 'true');
                })
                ->orderBy('name');

            $customers = $query->paginate($perPage)->appends([
                'q' => $q,
                'is_active' => $isActive,
            ]);

            $items = array_map(
                fn($c) => CustomerResource::make($c)->toArray($request),
                $customers->items(),
            );

            return $this->apiResponse('Berhasil mengambil daftar customer', [
                'items' => $items,
                'meta' => [
                    'current_page' => $customers->currentPage(),
                    'per_page' => $customers->perPage(),
                    'total' => $customers->total(),
                    'last_page' => $customers->lastPage(),
                ],
                'filters' => [
                    'q' => $q,
                    'is_active' => $isActive,
                ],
            ]);
        }, $request, [
            'user_id' => (string) ($request->user()?->getAuthIdentifier() ?? ''),
            'lat' => (string) ($request->input('lat', $request->input('latitude')) ?? ''),
            'long' => (string) ($request->input('long', $request->input('longitude')) ?? ''),
        ]);
    }

    /**
     * Daftar semua customers tanpa paginasi dan tanpa filter.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function all(Request $request): JsonResponse
    {
        return $this->apiTry(function () use ($request) {
            $latParam = $request->input('lat', $request->input('latitude'));
            $longParam = $request->input('long', $request->input('longitude'));
            if ($latParam !== null && $longParam !== null && $request->user()) {
                $this->employeeService->updateLocation($request->user(), (float) $latParam, (float) $longParam);
            }

            $customers = Customer::query()
                ->where('is_visible_in_marketing', true)
                ->orderBy('name')
                ->get();

            $items = array_map(
                fn($c) => CustomerResource::make($c)->toArray($request),
                $customers->all(),
            );

            return $this->apiResponse('Berhasil mengambil daftar customer', [
                'items' => $items,
            ]);
        }, $request, [
            'user_id' => (string) ($request->user()?->getAuthIdentifier() ?? ''),
            'lat' => (string) ($request->input('lat', $request->input('latitude')) ?? ''),
            'long' => (string) ($request->input('long', $request->input('longitude')) ?? ''),
        ]);
    }

    /**
     * Detail satu customer.
     *
     * @param Request $request
     * @param Customer $customer
     * @return JsonResponse
     */
    public function show(Request $request, Customer $customer): JsonResponse
    {
        $customer->load(['marketers']);
        return $this->apiResponse('Berhasil mengambil detail customer', [
            'customer' => CustomerResource::make($customer)->toArray($request),
        ]);
    }

    /**
     * Buat customer baru.
     *
     * @param StoreCustomerRequest $request
     * @return JsonResponse
     *
     * @throws \Throwable
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        return $this->apiTry(function () use ($request) {
            $userId = (string) $request->user()?->id;
            $dto = CustomerData::fromStoreRequest(
                $request,
                $userId,
                CustomerSource::Marketing->value
            );
            $customer = $this->service->create($dto);
            return $this->apiResponse('Berhasil membuat customer', [
                'customer' => CustomerResource::make($customer)->toArray($request),
            ]);
        }, $request, [
            'user_id' => (string) $request->user()?->id,
        ]);
    }

    /**
     * Update customer.
     *
     * @param UpdateCustomerRequest $request
     * @param Customer $customer
     * @return JsonResponse
     *
     * @throws \Throwable
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        return $this->apiTry(function () use ($request, $customer) {
            $userId = (string) $request->user()?->id;
            $dto = CustomerData::fromUpdateRequest(
                $request,
                $userId,
                CustomerSource::Marketing->value
            );
            $updated = $this->service->update($customer, $dto);
            return $this->apiResponse('Berhasil memperbarui customer', [
                'customer' => CustomerResource::make($updated)->toArray($request),
            ]);
        }, $request, [
            'user_id' => (string) $request->user()?->id,
            'customer_id' => (string) $customer->getKey(),
        ]);
    }
}
