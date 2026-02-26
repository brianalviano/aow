<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
        <title>Laporan Pesanan</title>
        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                font-family: 'DejaVu Sans', Arial, sans-serif;
                font-size: 10px;
                color: #1a1a2e;
                background: #ffffff;
            }

            .header {
                background: #1e3a5f;
                color: #ffffff;
                padding: 18px 24px;
                margin-bottom: 20px;
                border-radius: 4px;
            }

            .header h1 {
                font-size: 18px;
                font-weight: 700;
                letter-spacing: 0.5px;
            }

            .header .subtitle {
                font-size: 10px;
                margin-top: 4px;
                opacity: 0.85;
            }

            .meta-bar {
                display: flex;
                gap: 24px;
                margin-bottom: 16px;
                padding: 10px 14px;
                background: #f0f4f8;
                border-radius: 4px;
                border-left: 4px solid #1e3a5f;
            }

            .meta-item {
                font-size: 9px;
                color: #555;
            }

            .meta-item strong {
                display: block;
                font-size: 10px;
                color: #1a1a2e;
                margin-bottom: 2px;
            }

            .stats {
                display: flex;
                gap: 10px;
                margin-bottom: 20px;
            }

            .stat-card {
                flex: 1;
                padding: 12px;
                border-radius: 4px;
                border: 1px solid #e2e8f0;
            }

            .stat-card .stat-value {
                font-size: 14px;
                font-weight: 700;
                color: #1e3a5f;
            }

            .stat-card .stat-label {
                font-size: 8px;
                color: #718096;
                margin-top: 2px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 9px;
            }

            thead tr {
                background: #1e3a5f;
                color: #ffffff;
            }

            thead th {
                padding: 8px 10px;
                text-align: left;
                font-weight: 600;
                letter-spacing: 0.3px;
                white-space: nowrap;
            }

            tbody tr:nth-child(even) {
                background: #f7fafc;
            }

            tbody tr {
                border-bottom: 1px solid #e8edf2;
            }

            tbody td {
                padding: 7px 10px;
                vertical-align: top;
            }

            .badge {
                display: inline-block;
                padding: 2px 7px;
                border-radius: 20px;
                font-size: 8px;
                font-weight: 600;
            }

            .badge-success {
                background: #c6f6d5;
                color: #22543d;
            }

            .badge-warning {
                background: #fefcbf;
                color: #744210;
            }

            .badge-info {
                background: #bee3f8;
                color: #2a4365;
            }

            .badge-primary {
                background: #bee3f8;
                color: #2c5282;
            }

            .badge-danger {
                background: #fed7d7;
                color: #742a2a;
            }

            .badge-secondary {
                background: #e2e8f0;
                color: #4a5568;
            }

            .amount {
                font-weight: 600;
                color: #1e3a5f;
            }

            .footer {
                margin-top: 20px;
                padding-top: 10px;
                border-top: 1px solid #e2e8f0;
                font-size: 8px;
                color: #a0aec0;
                text-align: right;
            }
        </style>
    </head>

    <body>
        <div class="header">
            <h1>Laporan Pesanan</h1>
            <div class="subtitle">{{ $settings->name ?? config('app.name') }} &bull; Dicetak pada: {{ now()->format('d F Y, H:i') }} WIB</div>
        </div>

        <div class="meta-bar">
            <div class="meta-item">
                <strong>Periode</strong>
                {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : 'Semua' }}
                {{ $dateTo ? ' – ' . \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '' }}
            </div>
            @if ($dropPointName)
                <div class="meta-item">
                    <strong>Drop Point</strong>
                    {{ $dropPointName }}
                </div>
            @endif
            <div class="meta-item">
                <strong>Total Baris</strong>
                {{ number_format($orders->count()) }} pesanan
            </div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-value">{{ number_format($summary['total_orders']) }}</div>
                <div class="stat-label">Total Pesanan</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</div>
                <div class="stat-label">Total Pendapatan</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ number_format($summary['total_pending']) }}</div>
                <div class="stat-label">Dalam Proses</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ number_format($summary['total_cancelled']) }}</div>
                <div class="stat-label">Dibatalkan</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>No. Pesanan</th>
                    <th>Tanggal</th>
                    <th>Customer</th>
                    <th>Drop Point</th>
                    <th>Total (Rp)</th>
                    <th>Status Pesanan</th>
                    <th>Status Bayar</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $orderStatusMap = [
                        'pending' => ['label' => 'Menunggu', 'class' => 'badge-warning'],
                        'confirmed' => ['label' => 'Dikonfirmasi', 'class' => 'badge-info'],
                        'shipped' => ['label' => 'Dikirim', 'class' => 'badge-primary'],
                        'delivered' => ['label' => 'Selesai', 'class' => 'badge-success'],
                        'cancelled' => ['label' => 'Dibatalkan', 'class' => 'badge-danger'],
                    ];
                    $paymentStatusMap = [
                        'pending' => ['label' => 'Belum Bayar', 'class' => 'badge-warning'],
                        'paid' => ['label' => 'Lunas', 'class' => 'badge-success'],
                        'failed' => ['label' => 'Gagal', 'class' => 'badge-danger'],
                        'refunded' => ['label' => 'Dikembalikan', 'class' => 'badge-info'],
                        'cash' => ['label' => 'Bayar di Tempat', 'class' => 'badge-secondary'],
                    ];
                @endphp
                @forelse($orders as $i => $order)
                    @php
                        $os = $orderStatusMap[$order->order_status] ?? ['label' => $order->order_status, 'class' => 'badge-secondary'];
                        $ps = $paymentStatusMap[$order->payment_status] ?? ['label' => $order->payment_status, 'class' => 'badge-secondary'];
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $order->number }}</td>
                        <td>{{ $order->created_at?->format('d/m/Y') }}</td>
                        <td>{{ $order->customer?->name ?? '-' }}</td>
                        <td>{{ $order->dropPoint?->name ?? '-' }}</td>
                        <td class="amount">{{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        <td><span class="badge {{ $os['class'] }}">{{ $os['label'] }}</span></td>
                        <td><span class="badge {{ $ps['class'] }}">{{ $ps['label'] }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding: 16px; color:#a0aec0;">Tidak ada data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            Dokumen ini dibuat secara otomatis oleh sistem &bull; {{ config('app.name') }}
        </div>
    </body>

</html>
