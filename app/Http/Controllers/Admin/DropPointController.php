<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles admin CRUD operations for drop points.
 */
class DropPointController extends Controller
{
    /**
     * Display a listing of drop points.
     */
    public function index(): Response
    {
        return Inertia::render('Domains/Admin/DropPoint/Index');
    }

    /**
     * Show the form for creating a new drop point.
     */
    public function create(): Response
    {
        return Inertia::render('Domains/Admin/DropPoint/Create');
    }

    /**
     * Store a newly created drop point.
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('admin.drop-points.index');
    }

    /**
     * Show the form for editing the specified drop point.
     */
    public function edit(int $id): Response
    {
        return Inertia::render('Domains/Admin/DropPoint/Edit');
    }

    /**
     * Update the specified drop point.
     */
    public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('admin.drop-points.index');
    }

    /**
     * Remove the specified drop point.
     */
    public function destroy(int $id): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('admin.drop-points.index');
    }
}
