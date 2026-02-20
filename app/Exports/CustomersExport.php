<?php
declare(strict_types=1);

namespace App\Exports;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Color, Fill};

class CustomersExport implements FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize
{
    private string $q;

    public function __construct(string $q = '')
    {
        $this->q = $q;
    }

    public function query()
    {
        return Customer::query()
            ->when($this->q !== '', function (Builder $builder) {
                $q = $this->q;
                $builder->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%")
                        ->orWhere('email', 'ilike', "%{$q}%")
                        ->orWhere('phone', 'ilike', "%{$q}%")
                        ->orWhere('address', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Phone',
            'Active',
            'Last Transaction',
            'Address',
        ];
    }

    public function map($c): array
    {
        return [
            (string) $c->name,
            (string) $c->email,
            $c->phone ? (string) $c->phone : '',
            $c->is_active ? 'Yes' : 'No',
            $c->last_transaction_at ? $c->last_transaction_at->format('d/m/Y') : '',
            $c->address ? (string) $c->address : '',
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
                    $sheet->getStyle("D2:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            },
        ];
    }
}

