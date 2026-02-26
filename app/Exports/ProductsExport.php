<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    ShouldAutoSize
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill};

/**
 * Exports product sales aggregation data to an Excel spreadsheet.
 *
 * Columns: Produk, Kategori, Total Terjual (qty), Total Pendapatan (Rp).
 *
 * @param Collection $products Collection of stdClass rows from OrderItem aggregation query.
 */
class ProductsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(private readonly Collection $products) {}

    public function collection(): Collection
    {
        return $this->products;
    }

    public function title(): string
    {
        return 'Laporan Produk';
    }

    /**
     * @return array<string>
     */
    public function headings(): array
    {
        return [
            'Produk',
            'Kategori',
            'Total Terjual (qty)',
            'Total Pendapatan (Rp)',
        ];
    }

    /**
     * @param  object $product stdClass with product_name, category_name, total_sold, total_revenue
     * @return array<mixed>
     */
    public function map($product): array
    {
        return [
            $product->product_name,
            $product->category_name ?? '-',
            (int) $product->total_sold,
            (int) $product->total_revenue,
        ];
    }

    /**
     * Style the header row with a dark background.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
