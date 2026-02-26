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
 * Exports order data to an Excel spreadsheet.
 *
 * Columns: No. Pesanan, Tanggal, Customer, Drop Point, Total (Rp), Status Pesanan, Status Bayar.
 *
 * @param Collection $orders Collection of Order models with customer and dropPoint relations loaded.
 */
class OrdersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(private readonly Collection $orders) {}

    public function collection(): Collection
    {
        return $this->orders;
    }

    public function title(): string
    {
        return 'Laporan Pesanan';
    }

    /**
     * @return array<string>
     */
    public function headings(): array
    {
        return [
            'No. Pesanan',
            'Tanggal',
            'Customer',
            'Email',
            'Drop Point',
            'Total (Rp)',
            'Status Pesanan',
            'Status Bayar',
        ];
    }

    /**
     * @param  \App\Models\Order $order
     * @return array<mixed>
     */
    public function map($order): array
    {
        $orderStatusMap = [
            'pending'   => 'Menunggu',
            'confirmed' => 'Dikonfirmasi',
            'shipped'   => 'Dikirim',
            'delivered' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ];

        $paymentStatusMap = [
            'pending'  => 'Belum Bayar',
            'paid'     => 'Lunas',
            'failed'   => 'Gagal',
            'refunded' => 'Dikembalikan',
            'cash'     => 'Bayar di Tempat',
        ];

        return [
            $order->number,
            $order->created_at?->format('d/m/Y'),
            $order->customer?->name ?? '-',
            $order->customer?->email ?? '-',
            $order->dropPoint?->name ?? '-',
            $order->total_amount,
            $orderStatusMap[$order->order_status] ?? $order->order_status,
            $paymentStatusMap[$order->payment_status] ?? $order->payment_status,
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
