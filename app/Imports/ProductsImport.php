<?php

declare(strict_types=1);

namespace App\Imports;

use App\Services\ProductService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow
{
    private ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    public function collection(Collection $rows)
    {
        $normalized = [];
        foreach ($rows as $row) {
            $normalized[] = [
                'name' => isset($row['name']) ? (string) $row['name'] : '',
                'sku' => isset($row['sku']) ? (string) $row['sku'] : '',
                'description' => isset($row['description']) ? (string) $row['description'] : '',
                'category_name' => isset($row['category_name']) ? (string) $row['category_name'] : null,
                'sub_category_name' => isset($row['sub_category_name']) ? (string) $row['sub_category_name'] : null,
                'unit_code' => isset($row['unit_code']) ? (string) $row['unit_code'] : null,
                'factory_name' => isset($row['factory_name']) ? (string) $row['factory_name'] : null,
                'sub_factory_name' => isset($row['sub_factory_name']) ? (string) $row['sub_factory_name'] : null,
                'condition_name' => isset($row['condition_name']) ? (string) $row['condition_name'] : null,
                'product_type' => isset($row['product_type']) ? (string) $row['product_type'] : null,
                'product_variant_type' => isset($row['product_variant_type']) ? (string) $row['product_variant_type'] : null,
                'parent_sku' => isset($row['parent_sku']) ? (string) $row['parent_sku'] : null,
                'warehouse_code' => isset($row['warehouse_code']) ? (string) $row['warehouse_code'] : null,
                'weight' => isset($row['weight']) ? (string) $row['weight'] : null,
                'is_active' => isset($row['is_active']) ? (string) $row['is_active'] : null,
                'min_stock' => isset($row['min_stock']) ? (string) $row['min_stock'] : null,
                'max_stock' => isset($row['max_stock']) ? (string) $row['max_stock'] : null,
                'image_url' => isset($row['image_url']) ? (string) $row['image_url'] : null,
            ];
        }

        $this->service->importBatch($normalized);
    }
}
