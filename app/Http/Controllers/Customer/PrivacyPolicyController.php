<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyPolicyController extends Controller
{
    /**
     * Display the privacy policy page.
     *
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render('Domains/Customer/PrivacyPolicy/Index');
    }
}
