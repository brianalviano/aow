<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateAccountRequest;
use App\Http\Resources\EmployeeResource;
use App\Services\AccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AccountSettingController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $user->load(['role']);
        return Inertia::render('Domains/Admin/Settings/Account/Form', [
            'account' => EmployeeResource::make($user)->toArray($request),
        ]);
    }

    public function update(UpdateAccountRequest $request, AccountService $service): RedirectResponse
    {
        try {
            $service->update($request->user(), \App\DTOs\Account\AccountData::fromUpdateRequest($request));
            Inertia::flash('toast', [
                'message' => 'Pengaturan akun berhasil disimpan',
                'type' => 'success',
            ]);
            return redirect()->route('account.settings.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menyimpan pengaturan akun: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }
}
