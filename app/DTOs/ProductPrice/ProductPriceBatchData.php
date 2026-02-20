<?php

declare(strict_types=1);

namespace App\DTOs\ProductPrice;

use App\Http\Requests\ProductPrice\StoreProductPricesRequest;

/**
 * DTO untuk batch harga produk.
 */
class ProductPriceBatchData
{
    /**
     * @param ProductPriceEntryData[] $entries
     */
    public function __construct(
        public ?string $q,
        public array $entries,
    ) {}

    /**
     * Buat DTO dari FormRequest.
     *
     * @param StoreProductPricesRequest $request
     * @return self
     */
    public static function fromRequest(StoreProductPricesRequest $request): self
    {
        $p = $request->validated();
        $entries = array_map(
            fn(array $e) => ProductPriceEntryData::fromArray($e),
            (array) ($p['entries'] ?? []),
        );
        $q = isset($p['q']) ? (string) $p['q'] : null;
        if ($q === '') {
            $q = null;
        }
        return new self(
            q: $q,
            entries: $entries,
        );
    }
}
