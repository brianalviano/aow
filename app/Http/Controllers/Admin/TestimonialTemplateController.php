<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\TestimonialTemplate\TestimonialTemplateData;
use App\Http\Controllers\Controller;
use App\Models\TestimonialTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\{Inertia, Response};
use Throwable;

/**
 * Handles admin CRUD operations for testimonial templates.
 */
class TestimonialTemplateController extends Controller
{
    /**
     * Display a listing of testimonial templates.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $limit = (int) $request->query('limit', 15);

        $templates = TestimonialTemplate::query()
            ->when($search, function ($query, $search) {
                $query->where('customer_name', 'ilike', "%{$search}%")
                    ->orWhere('content', 'ilike', "%{$search}%");
            })
            ->latest()
            ->paginate($limit)
            ->withQueryString();

        return Inertia::render('Domains/Admin/TestimonialTemplate/Index', [
            'templates' => $templates,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new template.
     */
    public function create(): Response
    {
        return Inertia::render('Domains/Admin/TestimonialTemplate/Form');
    }

    /**
     * Store a newly created template.
     */
    public function store(TestimonialTemplateData $data): RedirectResponse
    {
        try {
            TestimonialTemplate::create($data->toArray());

            Inertia::flash('toast', [
                'message' => 'Template Testimoni berhasil dibuat',
                'type' => 'success',
            ]);

            return redirect()->route('admin.testimonial-templates.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat Template: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back()->withInput();
        }
    }

    /**
     * Show the form for editing the specified template.
     */
    public function edit(TestimonialTemplate $testimonialTemplate): Response
    {
        return Inertia::render('Domains/Admin/TestimonialTemplate/Form', [
            'template' => $testimonialTemplate,
        ]);
    }

    /**
     * Update the specified template.
     */
    public function update(TestimonialTemplateData $data, TestimonialTemplate $testimonialTemplate): RedirectResponse
    {
        try {
            $testimonialTemplate->update($data->toArray());

            Inertia::flash('toast', [
                'message' => 'Template Testimoni berhasil diperbarui',
                'type' => 'success',
            ]);

            return redirect()->route('admin.testimonial-templates.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui Template: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back()->withInput();
        }
    }

    /**
     * Remove the specified template.
     */
    public function destroy(TestimonialTemplate $testimonialTemplate): RedirectResponse
    {
        try {
            $testimonialTemplate->delete();

            Inertia::flash('toast', [
                'message' => 'Template Testimoni berhasil dihapus',
                'type' => 'success',
            ]);

            return redirect()->route('admin.testimonial-templates.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus Template: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back();
        }
    }
}
