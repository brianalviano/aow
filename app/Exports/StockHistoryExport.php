<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\StockBucket;
use App\Models\StockCard;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Color, Fill};

class StockHistoryExport implements FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize
{
    private string $q;
    private string $dateFrom;
    private string $dateTo;
    private string $type;
    private string $productId;
    private string $bucket;
    private string $warehouseId;

    public function __construct(string $q = '', string $dateFrom = '', string $dateTo = '', string $type = '', string $productId = '', string $bucket = '', string $warehouseId = '')
    {
        $this->q = $q;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->type = strtolower($type);
        $this->productId = $productId;
        $this->bucket = strtolower($bucket);
        $this->warehouseId = $warehouseId;
    }

    public function query()
    {
        return StockCard::query()
            ->with(['stock.product', 'stock.warehouse', 'user'])
            ->when($this->q !== '', function (Builder $builder) {
                $q = $this->q;
                $builder->where(function ($w) use ($q) {
                    $w->where('type', 'ilike', "%{$q}%")
                        ->orWhere('notes', 'ilike', "%{$q}%")
                        ->orWhereHas('stock.product', function ($p) use ($q) {
                            $p->where('name', 'ilike', "%{$q}%")
                                ->orWhere('sku', 'ilike', "%{$q}%");
                        })
                        ->orWhereHas('stock.warehouse', function ($wh) use ($q) {
                            $wh->where('name', 'ilike', "%{$q}%")
                                ->orWhere('code', 'ilike', "%{$q}%");
                        });
                });
            })
            ->when($this->type !== '' && in_array($this->type, ['in', 'out'], true), function (Builder $builder) {
                $builder->where('type', $this->type);
            })
            ->when($this->productId !== '', function (Builder $builder) {
                $productId = $this->productId;
                $builder->whereHas('stock', function ($s) use ($productId) {
                    $s->where('product_id', $productId);
                });
            })
            ->when($this->warehouseId !== '', function (Builder $builder) {
                $warehouseId = $this->warehouseId;
                $builder->whereHas('stock', function ($s) use ($warehouseId) {
                    $s->where('warehouse_id', $warehouseId);
                });
            })
            ->when($this->bucket !== '' && in_array($this->bucket, ['vat', 'non_vat'], true), function (Builder $builder) {
                $bucket = $this->bucket;
                $builder->whereHas('stock', function ($s) use ($bucket) {
                    if ($bucket === 'vat') {
                        $s->where('bucket', 'vat');
                    } else {
                        $s->where(function ($q) {
                            $q->whereNull('bucket')->orWhere('bucket', 'non_vat');
                        });
                    }
                });
            })
            ->when($this->dateFrom !== '', function (Builder $builder) {
                $builder->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo !== '', function (Builder $builder) {
                $builder->whereDate('created_at', '<=', $this->dateTo);
            })
            ->orderBy('created_at');
    }

    public function headings(): array
    {
        return [
            'Date',
            'Warehouse',
            'Product',
            'Type',
            'VAT',
            'Quantity',
            'Balance Before',
            'Balance After',
            'Unit Price',
            'Subtotal',
            'Last HPP',
            'Reference',
            'Notes',
            'User',
        ];
    }

    public function map($card): array
    {
        return [
            $card->created_at ? $card->created_at->format('d/m/Y H:i') : '',
            $card->stock?->warehouse ? (string) $card->stock->warehouse->name : '',
            $card->stock?->product ? (string) $card->stock->product->name : '',
            $card->type ? (string) $card->type->label() : '',
            ($card->stock && $card->stock->bucket === StockBucket::Vat) ? 'PPN' : 'Non PPN',
            (int) $card->quantity,
            (int) ($card->balance_before ?? 0),
            (int) $card->balance_after,
            (int) ($card->unit_price ?? 0),
            (int) ($card->subtotal ?? 0),
            (int) ($card->last_hpp ?? 0),
            $card->referencable ? (string) $card->referencable : '',
            $card->notes ? (string) $card->notes : '',
            $card->user ? (string) $card->user->name : '',
        ];
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
                    $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("G2:L{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            },
        ];
    }
}
