<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\DTOs\Setting\SettingData;
use App\Http\Resources\SettingResource;
use App\Models\CompanyProfile;
use App\Models\OrderSetting;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SettingController extends Controller
{
    public function index(Request $request): Response
    {
        $companyProfile = CompanyProfile::query()->first();
        $orderSettings = OrderSetting::all()->pluck('value', 'key')->toArray();

        return Inertia::render('Domains/Admin/Settings/System/Form', [
            'settings' => SettingResource::make([
                'company_profile' => $companyProfile,
                'order_settings' => $orderSettings,
            ])->toArray($request),
        ]);
    }

    public function update(Request $request, SettingService $service): RedirectResponse
    {
        try {
            $validated = $request->validate(SettingData::rules());
            $data = SettingData::fromValidated($validated);
            $service->update($data);
            Inertia::flash('toast', [
                'message' => 'Pengaturan berhasil disimpan',
                'type' => 'success',
            ]);
            return redirect()->route('admin.settings.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menyimpan pengaturan: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }
}
