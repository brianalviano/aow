<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Marketing;

use App\DTOs\Employee\EmployeeData;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\HandlesApiExceptions;
use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Requests\Employee\UpdateMyProfileRequest;
use App\Http\Resources\EmployeeResource;
use App\Services\EmployeeService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use HandlesApiExceptions;
    use RespondsWithJson;

    public function show(Request $request, NotificationService $notificationService): JsonResponse
    {
        return $this->apiTry(function () use ($request, $notificationService) {
            $user = $request->user();
            $stats = $notificationService->getStatsForUser($user);
            return $this->apiResponse('Profil saya', [
                'user' => EmployeeResource::make($user)->toArray($request),
                'notifications_count' => (int) $stats['unread'],
            ]);
        }, $request, [
            'user_id' => (string) $request->user()->getAuthIdentifier(),
        ]);
    }

    public function update(UpdateMyProfileRequest $request, EmployeeService $service): JsonResponse
    {
        return $this->apiTry(function () use ($request, $service) {
            $user = $request->user();
            $updated = $service->update($user, EmployeeData::fromMyProfileRequest($request));
            return $this->apiResponse('Profil berhasil diperbarui', [
                'user' => EmployeeResource::make($updated)->toArray($request),
            ]);
        }, $request, [
            'user_id' => (string) $request->user()->getAuthIdentifier(),
        ]);
    }
}
