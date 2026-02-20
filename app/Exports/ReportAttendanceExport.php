<?php

declare(strict_types=1);

namespace App\Exports;

use App\Services\ReportAttendanceService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\{FromArray, WithHeadings, WithEvents, ShouldAutoSize};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Color, Fill};

class ReportAttendanceExport implements FromArray, WithHeadings, WithEvents, ShouldAutoSize
{
    private ReportAttendanceService $service;
    private string $startDate;
    private string $endDate;
    private array $headings = [];
    private array $rows = [];
    private array $dates = [];
    private array $statuses = [];

    public function __construct(ReportAttendanceService $service, string $startDate = '', string $endDate = '')
    {
        $this->service = $service;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->prepare();
    }

    private function prepare(): void
    {
        $data = $this->service->generate($this->startDate, $this->endDate);
        $dates = $data['dates'] ?? [];
        $report = $data['report'] ?? [];

        $this->dates = $dates;

        $formattedDates = [];
        foreach ($dates as $dt) {
            $formattedDates[] = Carbon::parse($dt)->format('d/m/Y');
        }
        $this->headings = array_merge(
            ['Nama', 'Role'],
            $formattedDates,
            ['Hadir', 'Terlambat', 'Izin', 'Alpha', 'Libur', 'Hari Kerja']
        );

        $rows = [];
        $statuses = [];
        foreach ($report as $item) {
            $row = [
                (string) ($item['employee']['name'] ?? ''),
                (string) ($item['employee']['role']['name'] ?? '-'),
            ];
            $statusRow = [];
            foreach ($dates as $date) {
                $cell = $item['days'][$date] ?? null;
                $row[] = $cell ? (string) ($cell['label'] ?? '-') : '-';
                $statusRow[] = $cell ? (string) ($cell['status'] ?? '') : '';
            }
            $counts = (array) ($item['summary']['counts'] ?? []);
            $workdays = (int) ($item['summary']['workdays'] ?? 0);
            $row[] = (string) ($counts['hadir'] ?? 0);
            $row[] = (string) ($counts['telat'] ?? 0);
            $row[] = (string) ($counts['izin'] ?? 0);
            $row[] = (string) ($counts['alpha'] ?? 0);
            $row[] = (string) ($counts['libur'] ?? 0);
            $row[] = (string) $workdays;
            $rows[] = $row;
            $statuses[] = $statusRow;
        }

        $this->rows = $rows;
        $this->statuses = $statuses;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastColumnIndex = 2 + count($this->dates);
                $lastColumn = Coordinate::stringFromColumnIndex($lastColumnIndex);
                $lastRow = 1 + count($this->rows);

                $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
                $sheet->getStyle("A1:{$lastColumn}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A1:{$lastColumn}1")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFDDEBF7');

                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->setColor(new Color('FFBFBFBF'));

                if ($lastRow >= 2 && $lastColumnIndex >= 3) {
                    $sheet->getStyle("A2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("C2:{$lastColumn}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $colorMap = [
                    'hadir' => 'FFC6EFCE',
                    'telat' => 'FFFFF2CC',
                    'izin' => 'FFBDD7EE',
                    'alpha' => 'FFF8CBAD',
                    'libur' => 'FFEDEDED',
                    'pending' => 'FFFFFFFF',
                    'off' => 'FFFFFFFF',
                ];

                for ($i = 0; $i < count($this->statuses); $i++) {
                    $rowNum = 2 + $i;
                    $statusRow = $this->statuses[$i] ?? [];
                    // Only color date columns, skip summary columns
                    for ($j = 0; $j < count($statusRow); $j++) {
                        $colIndex = 3 + $j;
                        $col = Coordinate::stringFromColumnIndex($colIndex);
                        $cellRange = "{$col}{$rowNum}";
                        $st = strtolower((string) ($statusRow[$j] ?? ''));
                        $fillColor = $colorMap[$st] ?? 'FFFFFFFF';
                        $sheet->getStyle($cellRange)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB($fillColor);
                    }
                }
            },
        ];
    }
}
