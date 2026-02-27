<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\FileHelperTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model representing a slider for the frontend.
 */
class Slider extends Model
{
    use HasFactory, HasUuids, FileHelperTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'photo',
    ];

    /**
     * Get the photo URL.
     *
     * @param string|null $value
     * @return string|null
     */
    public function getPhotoAttribute(?string $value): ?string
    {
        return $this->getFileUrl($value);
    }
}
