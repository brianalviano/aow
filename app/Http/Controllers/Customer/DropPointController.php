<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\DropPointResource;
use App\Models\{DropPoint, Order};
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DropPointController extends Controller
{
    /**
     * Display a listing of the active drop points.
     */
    public function index(): Response
    {
        $activeDropPoints = DropPoint::query()
            ->where('is_active', true)
            ->get();

        $activeOrdersCount = 0;
        $unreadNotificationsCount = 0;

        if (Auth::guard('customer')->check()) {
            /** @var \App\Models\Customer $user */
            $user = Auth::guard('customer')->user();
            $unreadNotificationsCount = $user->unreadNotifications()->count();

            $activeOrdersCount = Order::where('customer_id', $user->id)
                ->whereNotIn('order_status', ['delivered', 'cancelled'])
                ->count();
        }

        return Inertia::render('Domains/Customer/DropPoint/List', [
            'totalDropPoints'          => $activeDropPoints->count(),
            'dropPoints'               => DropPointResource::collection($activeDropPoints)->resolve(),
            'activeOrdersCount'        => $activeOrdersCount,
            'unreadNotificationsCount' => $unreadNotificationsCount,
        ]);
    }

    /**
     * Display the specified drop point detail page.
     */
    public function show(string $id): Response
    {
        $dropPoint = DropPoint::query()
            ->where('is_active', true)
            ->findOrFail($id);

        return Inertia::render('Domains/Customer/DropPoint/Index', [
            'dropPoint' => DropPointResource::make($dropPoint)->resolve(),
        ]);
    }
}
