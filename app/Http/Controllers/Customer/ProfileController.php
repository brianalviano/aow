<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ProfileUpdateRequest;
use App\Models\DropPoint;
use App\Services\CustomerProfileService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private readonly CustomerProfileService $profileService
    ) {}

    /**
     * Show the profile edit form.
     */
    public function edit(): Response
    {
        $customer = auth('customer')->user();

        // Fetch drop points for the dropdown
        $dropPoints = DropPoint::orderBy('name')->get(['id', 'name', 'address']);

        return Inertia::render('Domains/Customer/Profile/Edit', [
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'schoolClass' => $customer->school_class,
                'dropPointId' => $customer->drop_point_id,
                'username' => $customer->username,
            ],
            'dropPoints' => $dropPoints,
        ]);
    }

    /**
     * Update the customer's profile.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $customer = auth('customer')->user();
        $dto = $request->toDTO();

        $this->profileService->updateProfile($customer, $dto);

        Inertia::flash('toast', [
            'message' => 'Profil berhasil diperbarui',
            'type' => 'success',
        ]);

        return redirect()->route('customer.profile.edit');
    }
}
