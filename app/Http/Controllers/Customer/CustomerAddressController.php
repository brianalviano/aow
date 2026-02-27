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
    /**
     * Show the form for creating a new custom address.
     *
     * @return Response
     */
    public function create(): Response
    {
        return Inertia::render('Domains/Customer/Address/Create', [
            'tomtomApiKey' => config('tomtom.api_key'),
            'defaultCenter' => [
                'lat' => config('tomtom.geofence.center_lat'),
                'lng' => config('tomtom.geofence.center_long'),
            ],
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

        $address = null;

        // If user is logged in, optionally save it to their profile if we want to store history
        // Or we can just store the data in DB. Since customer_id is nullable, we can associate if logged in.
        if (Auth::guard('customer')->check()) {
            $data['customer_id'] = Auth::guard('customer')->id();
        }

        $address = CustomerAddress::create($data);

        // Store the selected address in the session, similar to checkout_drop_point
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

        return redirect()->route('customer.products.general');
    }
}
