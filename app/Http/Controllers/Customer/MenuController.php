<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
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
        return Inertia::render('Domains/Customer/Menu/Index');
    }
}
