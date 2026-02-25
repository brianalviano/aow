@php
    use App\Enums\PaymentMethodCategory;
    $isCash = $order->paymentMethod?->category === PaymentMethodCategory::CASH;
@endphp
<!DOCTYPE html>
<html>

    <head>
        <meta charset="utf-8">
        <style>
            body {
                font-family: sans-serif;
                line-height: 1.6;
                color: #333;
            }

            .container {
                width: 90%;
                max-width: 600px;
                margin: 20px auto;
                border: 1px solid #ddd;
                padding: 20px;
                border-radius: 8px;
            }

            .header {
                text-align: center;
                border-bottom: 2px solid #f8fafc;
                padding-bottom: 10px;
            }

            .content {
                margin-top: 20px;
            }

            .footer {
                margin-top: 30px;
                font-size: 0.8em;
                color: #777;
                text-align: center;
            }

            .table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
            }

            .table th,
            .table td {
                border-bottom: 1px solid #eee;
                padding: 10px;
                text-align: left;
            }

            .table th {
                background: #f8fafc;
            }

            .total-row td {
                font-weight: bold;
            }

            .status {
                padding: 5px 10px;
                border-radius: 4px;
                display: inline-block;
                background: #fff3cd;
                color: #856404;
                font-size: 0.9em;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <div class="header">
                <h2>Konfirmasi Pesanan</h2>
                <p>Nomor Pesanan: <strong>#{{ $order->number }}</strong></p>
            </div>
            <div class="content">
                <p>Halo {{ $order->customer->name }},</p>
                @if ($isCash)
                    <p>Terima kasih atas pesanan Anda. Pesanan Anda telah kami terima dan akan kami siapkan untuk pengiriman.</p>
                @else
                    <p>Terima kasih atas pesanan Anda. Pesanan Anda telah kami terima dan sedang menunggu pembayaran.</p>
                @endif

                <div style="margin-bottom: 20px;">
                    @if ($isCash)
                        <span class="status" style="background: #e1f5fe; color: #01579b;">Status Pembayaran: Bayar di Tempat (Tunai)</span>
                    @else
                        <span class="status">Status Pembayaran: Belum Dibayar (Pending)</span>
                    @endif
                </div>

                <h3>Rincian Pesanan:</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Qty</th>
                            <th>Harga</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items()->with(['product', 'options.productOptionItem'])->get() as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->product->name }}</strong>
                                    @if ($item->options->count() > 0)
                                        <br>
                                        <small style="color: #666;">
                                            @foreach ($item->options as $option)
                                                {{ $option->productOption->name }}: {{ $option->productOptionItem->name }}@if (!$loop->last)
                                                    ,
                                                @endif
                                            @endforeach
                                        </small>
                                    @endif
                                </td>
                                <td>{{ $item->quantity }}</td>
                                <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right;">Subtotal:</td>
                            <td>Rp {{ number_format($order->total_amount - $order->delivery_fee - $order->tax_amount - $order->admin_fee, 0, ',', '.') }}</td>
                        </tr>
                        @if ($order->delivery_fee > 0)
                            <tr>
                                <td colspan="3" style="text-align: right;">Ongkos Kirim:</td>
                                <td>Rp {{ number_format($order->delivery_fee, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if ($order->admin_fee > 0)
                            <tr>
                                <td colspan="3" style="text-align: right;">Biaya Admin:</td>
                                <td>Rp {{ number_format($order->admin_fee, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if ($order->tax_amount > 0)
                            <tr>
                                <td colspan="3" style="text-align: right;">Pajak:</td>
                                <td>Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;">Total Akhir:</td>
                            <td>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>

                <p style="margin-top: 20px;">
                    <strong>Metode Pembayaran:</strong> {{ $order->paymentMethod->name }}
                </p>

                @if ($isCash)
                    <p>Silakan siapkan uang tunai sesuai total tagihan saat pesanan Anda diterima.</p>
                @else
                    <p>Silakan lakukan pembayaran sesuai dengan metode yang Anda pilih.</p>
                @endif
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
            </div>
        </div>
    </body>

</html>
