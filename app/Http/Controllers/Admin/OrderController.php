<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles admin CRUD operations for customer orders.
 */
class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(): Response
    {
        return Inertia::render('Domains/Admin/Order/Index');
    }

    /**
     * Display the specified order detail.
     */
    public function show(int $id): Response
    {
        return Inertia::render('Domains/Admin/Order/Show');
    }
}
