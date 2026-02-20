<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Color, Fill};

class EmployeesExport implements FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize
{
    private string $q;

    public function __construct(string $q = '')
    {
        $this->q = $q;
    }

    public function query()
    {
        return User::query()
            ->with(['role'])
            ->when($this->q !== '', function (Builder $builder) {
                $q = $this->q;
                $builder->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%")
                        ->orWhere('email', 'ilike', "%{$q}%")
                        ->orWhere('phone_number', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Phone Number',
            'Join Date',
            'Role',
            'Address',
            'Birth Date',
            'Gender',
        ];
    }

    public function map($user): array
    {
        return [
            (string) $user->name,
            (string) $user->email,
            $user->phone_number ? (string) $user->phone_number : '',
            $user->join_date ? $user->join_date->format('d/m/Y') : '',
            $user->role ? (string) $user->role->name : '',
            $user->address ? (string) $user->address : '',
            $user->birth_date ? $user->birth_date->format('d/m/Y') : '',
            $user->gender?->value ?? '',
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
                    $sheet->getStyle("H2:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            },
        ];
    }
}
