<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DropPoint;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderTypeController extends Controller
{
    /**
     * Display the order type selection page.
     */
    public function index(Request $request): Response
    {
        $dropPointId = $request->query('drop_point_id');
        $dropPoint = $dropPointId ? DropPoint::find($dropPointId) : null;

        $settings = \App\DTOs\Setting\OrderSettingsDTO::load();
        $instantStartTime = $settings->instantOrderStartTime;
        $instantEndTime = $settings->instantOrderEndTime;

        $currentTime = now()->format('H:i');
        $isInstantAvailable = $currentTime >= $instantStartTime && $currentTime <= $instantEndTime;

        return Inertia::render('Domains/Customer/OrderType/Index', [
            'dropPointId' => $dropPointId,
            'dropPointName' => $dropPoint?->name,
            'isInstantAvailable' => $isInstantAvailable,
            'instantStartTime' => $instantStartTime,
            'instantEndTime' => $instantEndTime,
        ]);
    }

    /**
     * Store the selected order type in session and redirect.
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_type' => 'required|in:instant,preorder',
            'drop_point_id' => 'nullable|exists:drop_points,id',
        ]);

        if ($request->order_type === 'instant') {
            $settings = \App\DTOs\Setting\OrderSettingsDTO::load();
            $instantStartTime = $settings->instantOrderStartTime;
            $instantEndTime = $settings->instantOrderEndTime;

            $currentTime = now()->format('H:i');
            if ($currentTime < $instantStartTime || $currentTime > $instantEndTime) {
                return back()->with('toast', [
                    'message' => "Instant delivery hanya tersedia pukul {$instantStartTime} - {$instantEndTime} WIB.",
                    'type' => 'error'
                ]);
            }
        }

        session(['checkout_order_type' => $request->order_type]);

        if ($request->drop_point_id) {
            $dropPoint = DropPoint::findOrFail($request->drop_point_id);
            session([
                'checkout_drop_point' => [
                    'id' => $dropPoint->id,
                    'name' => $dropPoint->name,
                    'address' => $dropPoint->address,
                    'latitude' => $dropPoint->latitude,
                    'longitude' => $dropPoint->longitude,
                ],
                'checkout_address' => null,
            ]);

            return redirect()->route('customer.products', $request->drop_point_id);
        }

        return redirect()->route('customer.products.general');
    }
}
