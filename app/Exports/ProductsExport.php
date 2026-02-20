<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\{ProductType, ProductVariantType};
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize, WithDrawings};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Color, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use Milon\Barcode\DNS1D;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize, WithDrawings
{
    private string $q;

    public function __construct(string $q = '')
    {
        $this->q = $q;
    }

    public function query()
    {
        return Product::query()
            ->with(['productCategory', 'productSubCategory', 'productUnit', 'productFactory', 'productSubFactory', 'productCondition', 'parentProduct'])
            ->when($this->q !== '', function (Builder $builder) {
                $q = $this->q;
                $builder->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%")
                        ->orWhere('sku', 'ilike', "%{$q}%")
                        ->orWhere('description', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'Name',
            'SKU',
            'Description',
            'Active',
            'Category',
            'Sub Category',
            'Unit',
            'Factory',
            'Sub Factory',
            'Condition',
            'Type',
            'Variant Type',
            'Parent SKU',
            'Weight',
            'Min Stock',
            'Max Stock',
            'Barcode',
        ];
    }

    public function map($p): array
    {
        $typeVal = is_string($p->product_type) ? $p->product_type : ($p->product_type?->value ?? '');
        $variantVal = is_string($p->product_variant_type) ? $p->product_variant_type : ($p->product_variant_type?->value ?? '');
        $typeLabel = $typeVal !== '' && ProductType::tryFrom($typeVal) ? ProductType::from($typeVal)->label() : '';
        $variantLabel = $variantVal !== '' && ProductVariantType::tryFrom($variantVal) ? ProductVariantType::from($variantVal)->label() : '';
        return [
            (string) $p->name,
            (string) $p->sku,
            (string) $p->description,
            $p->is_active ? 'Yes' : 'No',
            $p->productCategory ? (string) $p->productCategory->name : '',
            $p->productSubCategory ? (string) $p->productSubCategory->name : '',
            $p->productUnit ? (string) $p->productUnit->code : '',
            $p->productFactory ? (string) $p->productFactory->name : '',
            $p->productSubFactory ? (string) $p->productSubFactory->name : '',
            $p->productCondition ? (string) $p->productCondition->name : '',
            $typeLabel,
            $variantLabel,
            $p->parentProduct ? (string) $p->parentProduct->sku : '',
            $p->weight !== null ? (string) $p->weight : '',
            (string) $p->min_stock,
            (string) $p->max_stock,
            '',
        ];
    }

    public function drawings(): array
    {
        $rows = $this->query()->get(['id', 'sku', 'name']);
        $drawings = [];
        $dns = new DNS1D();
        $rowNumber = 2;
        foreach ($rows as $row) {
            $sku = (string) ($row->sku ?? '');
            if ($sku === '') {
                $rowNumber++;
                continue;
            }
            $pngBase64 = $dns->getBarcodePNG($sku, 'C128', 2, 50);
            $imageData = base64_decode($pngBase64);
            if ($imageData === false) {
                $rowNumber++;
                continue;
            }
            $im = imagecreatefromstring($imageData);
            if ($im === false) {
                $rowNumber++;
                continue;
            }
            $drawing = new MemoryDrawing();
            $drawing->setImageResource($im);
            $drawing->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
            $drawing->setMimeType(MemoryDrawing::MIMETYPE_PNG);
            $drawing->setHeight(50);
            $barcodeColumn = Coordinate::stringFromColumnIndex(count($this->headings()));
            $drawing->setCoordinates($barcodeColumn . $rowNumber);
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(5);
            $drawings[] = $drawing;
            $rowNumber++;
        }
        return $drawings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
                $sheet->getStyle("A1:{$lastColumn}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A1:{$lastColumn}1")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFDDEBF7');

                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->setColor(new Color('FFBFBFBF'));

                if ($lastRow >= 2) {
                    $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    for ($r = 2; $r <= $lastRow; $r++) {
                        $sheet->getRowDimension($r)->setRowHeight(40);
                    }
                }
            },
        ];
    }
}
