<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\Slider\SliderData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Slider\{StoreSliderRequest, UpdateSliderRequest};
use App\Http\Resources\SliderResource;
use App\Models\Slider;
use App\Services\SliderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Handles admin CRUD operations for sliders.
 */
class SliderController extends Controller
{
    public function __construct(
        private readonly SliderService $sliderService
    ) {}

    /**
     * Display a listing of sliders.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $limit = (int) $request->query('limit', 15);

        $sliders = $this->sliderService->getPaginated($limit, $search);

        return Inertia::render('Domains/Admin/Slider/Index', [
            'sliders' => SliderResource::collection($sliders),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new slider.
     */
    public function create(): Response
    {
        return Inertia::render('Domains/Admin/Slider/Form');
    }

    /**
     * Store a newly created slider.
     */
    public function store(StoreSliderRequest $request): RedirectResponse
    {
        try {
            $data = SliderData::fromStoreRequest($request);

            $this->sliderService->createSlider($data);

            Inertia::flash('toast', [
                'message' => 'Slider berhasil dibuat',
                'type' => 'success',
            ]);

            return redirect()->route('admin.sliders.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat Slider: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back();
        }
    }

    /**
     * Show the form for editing the specified slider.
     */
    public function edit(Slider $slider): Response
    {
        return Inertia::render('Domains/Admin/Slider/Form', [
            'slider' => new SliderResource($slider),
        ]);
    }

    /**
     * Update the specified slider.
     */
    public function update(UpdateSliderRequest $request, Slider $slider): RedirectResponse
    {
        try {
            $data = SliderData::fromUpdateRequest($request);

            $this->sliderService->updateSlider($slider, $data);

            Inertia::flash('toast', [
                'message' => 'Slider berhasil diperbarui',
                'type' => 'success',
            ]);

            return redirect()->route('admin.sliders.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui Slider: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back();
        }
    }

    /**
     * Remove the specified slider.
     */
    public function destroy(Slider $slider): RedirectResponse
    {
        try {
            $this->sliderService->deleteSlider($slider);

            Inertia::flash('toast', [
                'message' => 'Slider berhasil dihapus',
                'type' => 'success',
            ]);

            return redirect()->route('admin.sliders.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus Slider: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back();
        }
    }
}
