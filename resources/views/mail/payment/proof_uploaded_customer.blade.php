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
                <h2>Bukti Pembayaran Diterima</h2>
                <p>Nomor Pesanan: <strong>#{{ $order->number }}</strong></p>
            </div>
            <div class="content">
                <p>Halo {{ $order->customer->name }},</p>
                <p>Terima kasih! Bukti pembayaran untuk pesanan Anda telah berhasil diunggah.</p>
                <p><strong>Harap ditunggu,</strong> bukti pembayaran Anda untuk pesanan #{{ $order->number }} saat ini sedang dalam antrean verifikasi oleh tim admin kami.</p>
                <p>Kami akan memberitahu Anda kembali segera setelah pembayaran Anda dikonfirmasi.</p>
                <p style="margin-top: 20px;">Jika Anda memiliki pertanyaan, silakan hubungi kami.</p>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
            </div>
        </div>
    </body>

</html>
