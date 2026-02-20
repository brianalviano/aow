<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Tampilkan halaman daftar notifikasi dengan filter status dan pagination.
     *
     * @param Request $request
     * @param NotificationService $service
     * @return Response
     */
    public function index(Request $request, NotificationService $service): Response
    {
        $user = Auth::user();
        $limit = (int) max(1, (int) $request->integer('limit', 10));
        $status = (string) ($request->query('status') ?? '');

        $paginator = $service->paginateForUser($user, $limit, $status);

        return Inertia::render('Domains/Admin/Notifications/Index', [
            'notifications' => NotificationResource::collection($paginator->items()),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
            'filters' => [
                'status' => $status,
            ],
            'stats' => $service->getStatsForUser($user),
        ]);
    }

    /**
     * Ambil statistik notifikasi untuk user saat ini.
     *
     * @param Request $request
     * @param NotificationService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(Request $request, NotificationService $service)
    {
        $user = Auth::user();

        return response()->json([
            'stats' => $service->getStatsForUser($user),
        ], HttpResponse::HTTP_OK);
    }

    /**
     * Ambil daftar notifikasi terbaru (ringkas) untuk dropdown/preview.
     *
     * @param Request $request
     * @param NotificationService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request, NotificationService $service)
    {
        $user = Auth::user();
        $limit = (int) max(1, (int) $request->integer('limit', 5));
        $items = $service->listLatestForUser($user, $limit);

        return response()->json([
            'notifications' => NotificationResource::collection($items),
            'stats' => $service->getStatsForUser($user),
        ], HttpResponse::HTTP_OK);
    }

    /**
     * Tandai satu notifikasi sebagai telah dibaca.
     *
     * @param string $notificationId
     * @param NotificationService $service
     * @return \Illuminate\Http\Response
     */
    public function mark(string $notificationId, NotificationService $service)
    {
        $user = Auth::user();
        $service->markAsRead($user, $notificationId);
        return response()->noContent();
    }

    /**
     * Tandai seluruh notifikasi sebagai telah dibaca.
     *
     * @param NotificationService $service
     * @return \Illuminate\Http\Response
     */
    public function markAll(NotificationService $service)
    {
        $user = Auth::user();
        $service->markAllAsRead($user);
        return response()->noContent();
    }
}
