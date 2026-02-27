<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $limit = (int) $request->query('limit', 15);

        $customers = $this->customerService->getPaginated($limit, $search);

        return Inertia::render('Domains/Admin/Customer/Index', [
            'customers' => CustomerResource::collection($customers),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer): Response
    {
        return Inertia::render('Domains/Admin/Customer/Show', [
            'customer' => new CustomerResource($customer),
        ]);
    }
}
