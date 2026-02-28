<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerAddressRequest;
use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CustomerAddressController extends Controller
{
    public function __construct(
        private readonly \App\Services\CustomerAuthService $authService,
    ) {}

    /**
     * Show the form for creating a new custom address.
     *
     * @return Response
     */
    public function index(): Response
    {
        $savedAddresses = [];
        if (Auth::guard('customer')->check()) {
            $savedAddresses = CustomerAddress::where('customer_id', Auth::guard('customer')->id())
                ->latest()
                ->get();
        }

        return Inertia::render('Domains/Customer/Address/Index', [
            'tomtomApiKey' => config('tomtom.api_key'),
            'defaultCenter' => [
                'lat' => config('tomtom.geofence.center_lat'),
                'lng' => config('tomtom.geofence.center_long'),
            ],
            'isAuthenticated' => Auth::guard('customer')->check(),
            'savedAddresses' => $savedAddresses,
        ]);
    }

    /**
     * Store a newly created custom address in storage and session.
     *
     * @param StoreCustomerAddressRequest $request
     * @return RedirectResponse
     */
    public function store(StoreCustomerAddressRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $data) {
                $customerId = Auth::guard('customer')->id();

                // Handle guest registration
                if (!$customerId) {
                    $dto = new \App\DTOs\Customer\RegisterCustomerDTO(
                        name: $data['register_name'],
                        username: null,
                        phone: $data['register_phone'],
                        address: $data['address'],
                        email: $data['email'],
                        password: $data['password'],
                    );

                    $customer = $this->authService->register($dto);
                    Auth::guard('customer')->login($customer);
                    $request->session()->regenerate();
                    $customerId = $customer->id;
                }

                $data['customer_id'] = $customerId;
                $address = CustomerAddress::create($data);

                $this->setCheckoutAddressInSession($address);

                return redirect()->route('customer.products.general');
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to store address and register customer', [
                'error' => $e->getMessage(),
                'payload' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Update the specified address.
     *
     * @param StoreCustomerAddressRequest $request
     * @param CustomerAddress $address
     * @return RedirectResponse
     */
    public function update(StoreCustomerAddressRequest $request, CustomerAddress $address): RedirectResponse
    {
        // Security check
        if ($address->customer_id !== Auth::guard('customer')->id()) {
            abort(403);
        }

        $data = $request->validated();
        $address->update($data);

        $this->setCheckoutAddressInSession($address);

        return redirect()->route('customer.products.general');
    }

    /**
     * Remove the specified address.
     *
     * @param CustomerAddress $address
     * @return RedirectResponse
     */
    public function destroy(CustomerAddress $address): RedirectResponse
    {
        // Security check
        if ($address->customer_id !== Auth::guard('customer')->id()) {
            abort(403);
        }

        $address->delete();

        // If the deleted address was in session, clear it
        if (session('checkout_address.id') === $address->id) {
            session(['checkout_address' => null]);
        }

        \Inertia\Inertia::flash('toast', [
            'message' => 'Alamat berhasil dihapus',
            'type' => 'success',
        ]);

        return back();
    }

    /**
     * Helper to set checkout address in session.
     *
     * @param CustomerAddress $address
     * @return void
     */
    private function setCheckoutAddressInSession(CustomerAddress $address): void
    {
        session([
            'checkout_address' => [
                'id' => $address->id,
                'name' => $address->name,
                'address' => $address->address,
                'phone' => $address->phone,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
                'note' => $address->note,
            ],
            // Clear any previously selected drop point
            'checkout_drop_point' => null,
        ]);
    }
}
