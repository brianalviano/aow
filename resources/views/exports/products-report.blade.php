<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
        <title>Laporan Produk</title>
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

            .amount {
                font-weight: 600;
                color: #1e3a5f;
            }

            .rank {
                font-weight: 700;
                color: #a0aec0;
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
            <h1>Laporan Produk Terlaris</h1>
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
                <strong>Total Produk</strong>
                {{ number_format($products->count()) }} produk
            </div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-value">{{ number_format($summary['total_sold']) }}</div>
                <div class="stat-label">Total Terjual (qty)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</div>
                <div class="stat-label">Total Pendapatan</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Terjual (qty)</th>
                    <th>Pendapatan (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $i => $product)
                    <tr>
                        <td class="rank">{{ $i + 1 }}</td>
                        <td>{{ $product->product_name }}</td>
                        <td>{{ $product->category_name ?? '-' }}</td>
                        <td>{{ number_format($product->total_sold) }}</td>
                        <td class="amount">{{ number_format($product->total_revenue, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding: 16px; color:#a0aec0;">Tidak ada data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            Dokumen ini dibuat secara otomatis oleh sistem &bull; {{ config('app.name') }}
        </div>
    </body>

</html>
