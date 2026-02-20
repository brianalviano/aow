<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\PaymentMethod\PaymentMethodData;
use App\Models\PaymentMethod;
use App\Traits\FileHelperTrait;

final class PaymentMethodService
{
    use FileHelperTrait;

    public function create(PaymentMethodData $data): PaymentMethod
    {
        $storedImage = null;
        if ($data->image !== null) {
            $stored = $this->handleFileInput($data->image, null, 'payment_method_images');
            $storedImage = str_starts_with((string) $stored, '/storage/') ? ltrim(substr((string) $stored, 9), '/') : $stored;
        }

        return PaymentMethod::query()->create([
            'name' => $data->name,
            'description' => $data->description,
            'icon_class' => $data->iconClass,
            'image_url' => $storedImage,
            'mdr_percentage' => $data->mdrPercentage,
            'is_active' => $data->isActive,
        ]);
    }

    public function update(PaymentMethod $paymentMethod, PaymentMethodData $data): PaymentMethod
    {
        $updates = [
            'name' => $data->name,
            'description' => $data->description,
            'icon_class' => $data->iconClass,
            'mdr_percentage' => $data->mdrPercentage,
            'is_active' => $data->isActive,
        ];

        if ($data->image !== null) {
            $existingPublicPath = $paymentMethod->image_url ? '/storage/' . ltrim((string) $paymentMethod->image_url, '/') : null;
            $stored = $this->handleFileInput($data->image, $existingPublicPath, 'payment_method_images');
            $updates['image_url'] = str_starts_with((string) $stored, '/storage/') ? ltrim(substr((string) $stored, 9), '/') : $stored;
        }

        $paymentMethod->fill($updates);

        $paymentMethod->save();

        return $paymentMethod;
    }

    public function delete(PaymentMethod $paymentMethod): void
    {
        $old = $paymentMethod->image_url;
        $paymentMethod->delete();
        if ($old) {
            $this->deleteFile('/storage/' . ltrim((string) $old, '/'));
        }
    }
}
