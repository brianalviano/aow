<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles admin report views and exports.
 */
class ReportController extends Controller
{
    /**
     * Display the reports overview page.
     */
    public function index(): Response
    {
        return Inertia::render('Domains/Admin/Report/Index');
    }
}
