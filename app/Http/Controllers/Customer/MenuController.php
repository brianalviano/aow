<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    /**
     * Display the customer menu page.
     *
     * @return Response
     */
    public function index(): Response
    {
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

        return Inertia::render('Domains/Customer/Menu/Index', [
            'activeOrdersCount'        => $activeOrdersCount,
            'unreadNotificationsCount' => $unreadNotificationsCount,
        ]);
    }
}
