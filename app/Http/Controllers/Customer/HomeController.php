<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DropPoint;
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
        $activeDropPoints = DropPoint::query()
            ->where('is_active', true)
            ->get();

        return Inertia::render('Domains/Customer/Home/Index', [
            'totalDropPoints' => $activeDropPoints->count(),
            'dropPoints'      => $activeDropPoints,
        ]);
    }
}
