<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class TermsAndConditionController extends Controller
{
    /**
     * Display the terms of service page.
     *
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render('Domains/Customer/TermsAndCondition/Index', [
            'appUrl' => config('app.url'),
        ]);
    }
}
