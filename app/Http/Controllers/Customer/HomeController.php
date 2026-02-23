<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Customer\HomeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Display the customer home page.
     *
     * @param HomeService $homeService
     * @return Response
     */
    public function index(HomeService $homeService): Response
    {
        $data = $homeService->getHomeData();

        return Inertia::render('Domains/Customer/Home/Index', [
            'totalDropPoints' => $data['totalDropPoints'],
            'dropPoints'      => $data['dropPoints'],
        ]);
    }
}
