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

            .button {
                background-color: #facc15;
                color: #000;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 5px;
                font-weight: bold;
                display: inline-block;
                margin-top: 20px;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <div class="header">
                <h2>Pesanan Baru Ditugaskan</h2>
                <p>Nomor Pesanan: <strong>#{{ $order->number }}</strong></p>
            </div>
            <div class="content">
                <p>Halo Chef {{ $chef->name }},</p>
                <p>Ada pesanan baru yang memerlukan konfirmasi Anda. Silakan login ke dashboard untuk menerima atau menolak pesanan tersebut.</p>

                <p><strong>Detail Pesanan:</strong></p>
                <ul>
                    <li>Tanggal Pengiriman: {{ $order->delivery_date }}</li>
                    <li>Waktu Pengiriman: {{ $order->delivery_time }}</li>
                </ul>

                <p>Silakan klik tombol di bawah ini untuk melihat dashboard Anda:</p>
                <div style="text-align: center;">
                    <a class="button" href="{{ url('/chef/login') }}">Buka Dashboard Chef</a>
                </div>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
            </div>
        </div>
    </body>

</html>
