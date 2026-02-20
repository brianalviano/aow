<?php
declare(strict_types=1);

namespace App\DTOs\Customer;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;

class CustomerData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public ?string $address,
        public ?float $latitude,
        public ?float $longitude,
        public bool $isActive,
        public string $source,
        public string $createdById,
        public bool $isVisibleInPos,
        public bool $isVisibleInMarketing,
        public array $marketerIds = [],
    ) {}

    public static function fromStoreRequest(StoreCustomerRequest $request, string $createdById, string $source): self
    {
        $p = $request->validated();
        $marketerIds = [];
        if (array_key_exists('marketer_ids', $p) && is_array($p['marketer_ids'])) {
            foreach ($p['marketer_ids'] as $id) {
                if (is_string($id) && $id !== '') {
                    $marketerIds[] = (string) $id;
                }
            }
            $marketerIds = array_values(array_unique($marketerIds));
        }
        return new self(
            name: (string) $p['name'],
            email: (string) $p['email'],
            phone: $p['phone'] ?? null,
            address: $p['address'] ?? null,
            latitude: isset($p['latitude']) && $p['latitude'] !== null ? (float) $p['latitude'] : null,
            longitude: isset($p['longitude']) && $p['longitude'] !== null ? (float) $p['longitude'] : null,
            isActive: isset($p['is_active']) ? (bool) $p['is_active'] : true,
            source: $source,
            createdById: $createdById,
            isVisibleInPos: isset($p['is_visible_in_pos']) ? (bool) $p['is_visible_in_pos'] : true,
            isVisibleInMarketing: isset($p['is_visible_in_marketing']) ? (bool) $p['is_visible_in_marketing'] : true,
            marketerIds: $marketerIds,
        );
    }

    public static function fromArray(array $p, string $createdById, string $source): self
    {
        $marketerIds = [];
        if (array_key_exists('marketer_ids', $p) && is_array($p['marketer_ids'])) {
            foreach ((array) $p['marketer_ids'] as $id) {
                if (is_string($id) && $id !== '') {
                    $marketerIds[] = (string) $id;
                }
            }
            $marketerIds = array_values(array_unique($marketerIds));
        }
        return new self(
            name: (string) ($p['name'] ?? ''),
            email: (string) ($p['email'] ?? ''),
            phone: array_key_exists('phone', $p) && $p['phone'] !== null && $p['phone'] !== '' ? (string) $p['phone'] : null,
            address: array_key_exists('address', $p) && $p['address'] !== null && $p['address'] !== '' ? (string) $p['address'] : null,
            latitude: array_key_exists('latitude', $p) && $p['latitude'] !== null && $p['latitude'] !== '' ? (float) $p['latitude'] : null,
            longitude: array_key_exists('longitude', $p) && $p['longitude'] !== null && $p['longitude'] !== '' ? (float) $p['longitude'] : null,
            isActive: isset($p['is_active']) ? (bool) $p['is_active'] : true,
            source: $source,
            createdById: $createdById,
            isVisibleInPos: isset($p['is_visible_in_pos']) ? (bool) $p['is_visible_in_pos'] : true,
            isVisibleInMarketing: isset($p['is_visible_in_marketing']) ? (bool) $p['is_visible_in_marketing'] : true,
            marketerIds: $marketerIds,
        );
    }

    public static function fromUpdateRequest(UpdateCustomerRequest $request, string $createdById, string $source): self
    {
        $p = $request->validated();
        $marketerIds = [];
        if (array_key_exists('marketer_ids', $p) && is_array($p['marketer_ids'])) {
            foreach ($p['marketer_ids'] as $id) {
                if (is_string($id) && $id !== '') {
                    $marketerIds[] = (string) $id;
                }
            }
            $marketerIds = array_values(array_unique($marketerIds));
        }
        return new self(
            name: (string) $p['name'],
            email: (string) $p['email'],
            phone: $p['phone'] ?? null,
            address: $p['address'] ?? null,
            latitude: isset($p['latitude']) && $p['latitude'] !== null ? (float) $p['latitude'] : null,
            longitude: isset($p['longitude']) && $p['longitude'] !== null ? (float) $p['longitude'] : null,
            isActive: isset($p['is_active']) ? (bool) $p['is_active'] : true,
            source: $source,
            createdById: $createdById,
            isVisibleInPos: isset($p['is_visible_in_pos']) ? (bool) $p['is_visible_in_pos'] : true,
            isVisibleInMarketing: isset($p['is_visible_in_marketing']) ? (bool) $p['is_visible_in_marketing'] : true,
            marketerIds: $marketerIds,
        );
    }
}
