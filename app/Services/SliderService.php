<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Slider\SliderData;
use App\Models\Slider;
use App\Traits\RetryableTransactionsTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for Slider business logic.
 */
class SliderService
{
    use RetryableTransactionsTrait;

    /**
     * Get paginated sliders.
     */
    public function getPaginated(int $perPage = 10, ?string $search = null)
    {
        return Slider::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'ilike', "%{$search}%");
            })
            ->orderBy('name', 'asc')
            ->paginate($perPage);
    }

    /**
     * Store a newly created slider.
     *
     * @param SliderData $data
     * @return Slider
     * @throws \Throwable
     */
    public function createSlider(SliderData $data): Slider
    {
        return $this->runWithRetry(function () use ($data) {
            try {
                return DB::transaction(function () use ($data) {
                    $slider = new Slider();
                    $photoPath = $slider->handleFileInput($data->photo, null, 'sliders');

                    return Slider::create([
                        'name' => $data->name,
                        'photo' => $photoPath,
                    ]);
                });
            } catch (\Throwable $e) {
                Log::error('Failed to create slider', [
                    'error' => $e->getMessage(),
                    'data' => [
                        'name' => $data->name,
                    ],
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Update the specified slider.
     *
     * @param Slider $slider
     * @param SliderData $data
     * @return Slider
     * @throws \Throwable
     */
    public function updateSlider(Slider $slider, SliderData $data): Slider
    {
        return $this->runWithRetry(function () use ($slider, $data) {
            try {
                return DB::transaction(function () use ($slider, $data) {
                    $photoPath = $slider->handleFileInput($data->photo, $slider->getRawOriginal('photo'), 'sliders');

                    $slider->update([
                        'name' => $data->name,
                        'photo' => $photoPath,
                    ]);

                    return $slider->refresh();
                });
            } catch (\Throwable $e) {
                Log::error('Failed to update slider', [
                    'error' => $e->getMessage(),
                    'slider_id' => $slider->id,
                    'data' => [
                        'name' => $data->name,
                    ],
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Delete the specified slider.
     *
     * @param Slider $slider
     * @return bool|null
     * @throws \Throwable
     */
    public function deleteSlider(Slider $slider): ?bool
    {
        return $this->runWithRetry(function () use ($slider) {
            try {
                return DB::transaction(function () use ($slider) {
                    $slider->deleteFile($slider->getRawOriginal('photo'));
                    return $slider->delete();
                });
            } catch (\Throwable $e) {
                Log::error('Failed to delete slider', [
                    'error' => $e->getMessage(),
                    'slider_id' => $slider->id,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }
}
