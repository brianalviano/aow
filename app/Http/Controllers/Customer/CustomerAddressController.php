<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\DTOs\CustomerAddress\CustomerAddressData;
use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\{Inertia, Response};

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
    public function store(CustomerAddressData $data): RedirectResponse
    {
        $validated = $data->toArray();

        try {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $validated) {
                $customerId = Auth::guard('customer')->id();

                // Handle guest registration
                if (!$customerId) {
                    $dto = new \App\DTOs\Customer\RegisterCustomerDTO(
                        name: $validated['register_name'],
                        username: null,
                        phone: $validated['register_phone'],
                        address: $validated['address'],
                        email: $validated['email'],
                        password: $validated['password'],
                    );

                    $customer = $this->authService->register($dto);
                    Auth::guard('customer')->login($customer);
                    request()->session()->regenerate();
                    $customerId = $customer->id;
                }

                $validated['customer_id'] = $customerId;
                $address = CustomerAddress::create($validated);

                $this->setCheckoutAddressInSession($address);

                return redirect()->route('customer.order-type.index');
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to store address and register customer', [
                'error' => $e->getMessage(),
                'payload' => $validated,
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
    public function update(CustomerAddressData $data, CustomerAddress $address): RedirectResponse
    {
        // Security check
        if ($address->customer_id !== Auth::guard('customer')->id()) {
            abort(403);
        }

        $address->update($data->toArray());

        $this->setCheckoutAddressInSession($address);

        return redirect()->route('customer.order-type.index');
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
