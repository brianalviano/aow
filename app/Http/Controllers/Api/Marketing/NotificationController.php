<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\{HandlesApiExceptions, RespondsWithJson};
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\{JsonResponse, Request};

/**
 * NotificationController (API Marketing).
 *
 * Menyediakan endpoint daftar notifikasi (dengan paginasi) dan
 * menandai notifikasi sebagai dibaca untuk aplikasi Marketing.
 *
 * @author
 * @package Http\Controllers\Api\Marketing
 */
class NotificationController extends Controller
{
    use HandlesApiExceptions;
    use RespondsWithJson;

    /**
     * Daftar notifikasi user yang sedang login dengan paginasi.
     *
     * Query: status=unread|read (opsional), per_page (opsional, default 10).
     *
     * @param Request $request
     * @param NotificationService $service
     * @return JsonResponse
     */
    public function index(Request $request, NotificationService $service): JsonResponse
    {
        return $this->apiTry(function () use ($request, $service) {
            $perPage = (int) max(1, (int) $request->integer('per_page', 10));
            $status = (string) $request->string('status')->toString();

            $paginator = $service->paginateForUser($request->user(), $perPage, $status !== '' ? $status : null);
            $items = array_map(
                fn($n) => NotificationResource::make($n)->toArray($request),
                $paginator->items(),
            );

            return $this->apiResponse('Berhasil mengambil daftar notifikasi', [
                'items' => $items,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
                'filters' => [
                    'status' => $status,
                ],
            ]);
        }, $request, [
            'user_id' => (string) ($request->user()?->getAuthIdentifier() ?? ''),
            'status' => (string) $request->input('status', ''),
            'per_page' => (string) $request->input('per_page', ''),
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai telah dibaca.
     *
     * @param Request $request
     * @param NotificationService $service
     * @param string $notification ID notifikasi (DatabaseNotification)
     * @return JsonResponse
     *
     * @throws \Throwable
     */
    public function mark(Request $request, NotificationService $service, string $notification): JsonResponse
    {
        return $this->apiTry(function () use ($request, $service, $notification) {
            $service->markAsRead($request->user(), $notification);
            return $this->apiResponse('Notifikasi ditandai sebagai dibaca', [
                'notification_id' => (string) $notification,
            ]);
        }, $request, [
            'user_id' => (string) ($request->user()?->getAuthIdentifier() ?? ''),
            'notification_id' => (string) $notification,
        ]);
    }
}
