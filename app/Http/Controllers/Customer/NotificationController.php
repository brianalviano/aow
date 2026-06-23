<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Display the notification list.
     */
    public function index(): Response
    {
        /** @var Customer $user */
        $user = Auth::guard('customer')->user();

        $notifications = $user->notifications()->paginate(15);

        return Inertia::render('Domains/Customer/Notification/Index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a specific notification or all notifications as read.
     */
    public function markAsRead(Request $request): RedirectResponse
    {
        /** @var Customer $user */
        $user = Auth::guard('customer')->user();

        if ($request->has('id')) {
            $notificationId = $request->input('id');
            $notification = $user->notifications()->where('id', $notificationId)->first();

            if ($notification) {
                $notification->markAsRead();
            }
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return redirect()->back();
    }
}
