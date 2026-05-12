<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\DTOs\Customer\LoginCustomerDTO;
use App\DTOs\Customer\RegisterCustomerDTO;
use App\DTOs\CustomerAddress\CustomerAddressData;
use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Models\DropPoint;
use App\Services\CustomerAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CustomerAddressController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $authService,
    ) {}

    /**
     * Show the form for creating a new custom address.
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
     */
    public function store(CustomerAddressData $data): RedirectResponse
    {
        $validated = $data->toArray();

        try {
            return DB::transaction(function () use ($data, $validated) {
                $customerId = Auth::guard('customer')->id();

                // Handle guest registration
                if (! $customerId) {
                    $dto = new RegisterCustomerDTO(
                        name: $data->registerName,
                        username: null,
                        phone: $data->registerPhone,
                        address: $data->address,
                        email: $data->email,
                        password: $data->password,
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
            Log::error('Failed to store address and register customer', [
                'error' => $e->getMessage(),
                'payload' => $validated,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle authentication for existing users.
     */
    public function login(LoginCustomerDTO $dto): RedirectResponse
    {
        if ($this->authService->login($dto)) {
            request()->session()->regenerate();

            Inertia::flash('toast', [
                'message' => 'Berhasil login',
                'type' => 'success',
            ]);

            return redirect()->route('customer.addresses.index');
        }

        return back()->withErrors([
            'login' => 'Identitas atau kata sandi salah',
        ]);
    }

    /**
     * Update the specified address.
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

        Inertia::flash('toast', [
            'message' => 'Alamat berhasil dihapus',
            'type' => 'success',
        ]);

        return back();
    }

    /**
     * Helper to set checkout address in session and automatically find nearest drop point.
     */
    private function setCheckoutAddressInSession(CustomerAddress $address): void
    {
        $nearestDropPoint = DropPoint::findNearest($address->latitude, $address->longitude);
        $dropPointData = null;

        if ($nearestDropPoint) {
            $dropPointData = [
                'id' => $nearestDropPoint->id,
                'name' => $nearestDropPoint->name,
                'address' => $nearestDropPoint->address,
                'latitude' => $nearestDropPoint->latitude,
                'longitude' => $nearestDropPoint->longitude,
                'category' => $nearestDropPoint->category->value,
            ];
        }

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
            'checkout_drop_point' => $dropPointData,
        ]);
    }
}
