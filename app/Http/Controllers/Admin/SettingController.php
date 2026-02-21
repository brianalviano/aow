<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\DTOs\Setting\SettingData;
use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\CompanyProfile;
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
        $setting = CompanyProfile::query()->first();
        return Inertia::render('Domains/Admin/Settings/System/Form', [
            'settings' => $setting ? SettingResource::make($setting)->toArray($request) : null,
        ]);
    }

    public function update(UpdateSettingRequest $request, SettingService $service): RedirectResponse
    {
        try {
            $service->update(SettingData::fromUpdateRequest($request));
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
