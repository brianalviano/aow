<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exports order data to an Excel spreadsheet.
 *
 * Columns: No. Pesanan, Tanggal, Customer, Drop Point, Total (Rp), Status Pesanan, Status Bayar.
 *
 * @param  Collection  $orders  Collection of Order models with customer and dropPoint relations loaded.
 */
class OrdersExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
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
     * @param  Order  $order
     * @return array<mixed>
     */
    public function map($order): array
    {
        $orderStatusKey = $order->order_status instanceof OrderStatus
            ? $order->order_status->value
            : (string) $order->order_status;

        $paymentStatusKey = $order->payment_status instanceof PaymentStatus
            ? $order->payment_status->value
            : (string) $order->payment_status;

        $orderStatusMap = [
            'pending' => 'Menunggu Konfirmasi',
            'confirmed' => 'Dikonfirmasi',
            'cooking' => 'Sedang Dimasak',
            'on_delivery' => 'Sedang Dikirim',
            'arrived' => 'Tiba di Tujuan',
            'delivered' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ];

        $paymentStatusMap = [
            'pending' => 'Belum Bayar',
            'paid' => 'Lunas',
            'failed' => 'Gagal',
            'refunded' => 'Dikembalikan',
            'cash' => 'Bayar di Tempat',
        ];

        return [
            $order->number,
            $order->created_at?->format('d/m/Y'),
            $order->customer?->name ?? '-',
            $order->customer?->email ?? '-',
            $order->dropPoint?->name ?? '-',
            $order->total_amount,
            $orderStatusMap[$orderStatusKey] ?? $orderStatusKey,
            $paymentStatusMap[$paymentStatusKey] ?? $paymentStatusKey,
        ];
    }

    /**
     * Style the header row with a dark background.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
