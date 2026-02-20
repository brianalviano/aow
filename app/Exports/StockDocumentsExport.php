<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\{StockBucket, StockDocumentType, StockDocumentReason, StockDocumentStatus};
use App\Models\StockDocument;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Color, Fill};

class StockDocumentsExport implements FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize
{
    private string $tab;
    private string $q;
    private string $warehouseId;
    private string $dateFrom;
    private string $dateTo;
    private string $bucket;
    private string $reason;

    public function __construct(string $tab = 'in', string $q = '', string $warehouseId = '', string $dateFrom = '', string $dateTo = '', string $bucket = '', string $reason = '')
    {
        $this->tab = in_array(strtolower($tab), ['in', 'out'], true) ? strtolower($tab) : 'in';
        $this->q = $q;
        $this->warehouseId = $warehouseId;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->bucket = strtolower($bucket);
        $this->reason = $reason;
    }

    public function query()
    {
        $type = $this->tab === 'in' ? StockDocumentType::In->value : StockDocumentType::Out->value;
        return StockDocument::query()
            ->with(['warehouse', 'user'])
            ->where('type', $type)
            ->when($this->q !== '', function (Builder $builder) {
                $q = $this->q;
                $builder->where(function ($w) use ($q) {
                    $w->where('number', 'ilike', "%{$q}%")
                        ->orWhere('notes', 'ilike', "%{$q}%");
                });
            })
            ->when($this->warehouseId !== '', fn(Builder $b) => $b->where('warehouse_id', $this->warehouseId))
            ->when($this->bucket !== '' && in_array($this->bucket, StockBucket::values(), true), fn(Builder $b) => $b->where('bucket', $this->bucket))
            ->when($this->reason !== '' && in_array($this->reason, StockDocumentReason::values(), true), fn(Builder $b) => $b->where('reason', $this->reason))
            ->when($this->dateFrom !== '', fn(Builder $b) => $b->where('document_date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn(Builder $b) => $b->where('document_date', '<=', $this->dateTo))
            ->orderByDesc('document_date')
            ->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'Number',
            'Date',
            'Type',
            'Reason',
            'Status',
            'Bucket',
            'Warehouse',
            'User',
            'Notes',
        ];
    }

    public function map($doc): array
    {
        $typeVal = is_string($doc->type) ? $doc->type : ($doc->type?->value ?? '');
        $reasonVal = is_string($doc->reason) ? $doc->reason : ($doc->reason?->value ?? '');
        $statusVal = is_string($doc->status) ? $doc->status : ($doc->status?->value ?? '');
        $bucketVal = $doc->bucket instanceof StockBucket ? $doc->bucket->value : ($doc->bucket ?? '');
        $typeLabel = $typeVal !== '' && StockDocumentType::tryFrom($typeVal) ? StockDocumentType::from($typeVal)->label() : '';
        $reasonLabel = $reasonVal !== '' && StockDocumentReason::tryFrom($reasonVal) ? StockDocumentReason::from($reasonVal)->label() : '';
        $statusLabel = $statusVal !== '' && StockDocumentStatus::tryFrom($statusVal) ? StockDocumentStatus::from($statusVal)->label() : '';
        $bucketLabel = $bucketVal !== '' && StockBucket::tryFrom($bucketVal) ? (StockBucket::from($bucketVal) === StockBucket::Vat ? 'PPN' : 'Non PPN') : '';
        return [
            (string) ($doc->number ?? ''),
            $doc->document_date ? $doc->document_date->format('Y-m-d') : '',
            $typeLabel,
            $reasonLabel,
            $statusLabel,
            $bucketLabel,
            $doc->warehouse ? (string) $doc->warehouse->name : '',
            $doc->user ? (string) $doc->user->name : '',
            $doc->notes ? (string) $doc->notes : '',
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
                    $sheet->getStyle("B2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            },
        ];
    }
}

