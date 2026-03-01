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
        </style>
    </head>

    <body>
        <div class="container">
            <div class="header">
                <h2>Verifikasi Pembayaran Manual</h2>
                <p>Nomor Pesanan: <strong>#{{ $order->number }}</strong></p>
            </div>
            <div class="content">
                <p>Halo {{ $adminUser->name ?? 'Admin' }},</p>
                <p>Customer <strong>{{ $order->customer->name }}</strong> telah mengunggah bukti pembayaran untuk pesanan #{{ $order->number }}.</p>
                <p>Harap segera login ke dashboard admin dan melakukan verifikasi pembayaran ini agar pesanan dapat segera diproses.</p>
                <p style="margin-top: 20px;"><a href="{{ url('/admin/orders/' . $order->id) }}">Lihat Pesanan di Dashboard Admin</a></p>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
            </div>
        </div>
    </body>

</html>
