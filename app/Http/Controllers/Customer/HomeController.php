<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\SliderResource;
use App\Models\DropPoint;
use App\Models\Order;
use App\Models\Slider;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Display the customer home page.
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


        $sliders = Slider::orderBy('created_at', 'desc')->get();

        return Inertia::render('Domains/Customer/Home/Index', [
            'sliders'                  => SliderResource::collection($sliders),
            'activeOrdersCount'        => $activeOrdersCount,
            'unreadNotificationsCount' => $unreadNotificationsCount,
        ]);
    }
}
