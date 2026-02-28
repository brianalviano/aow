<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chef;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for chef dashboard.
 */
class DashboardController extends Controller
{
    /**
     * Show the chef dashboard.
     *
     * @return \Inertia\Response
     */
    public function index(): Response
    {
        return Inertia::render('Domains/Chef/Dashboard/Index');
    }
}
