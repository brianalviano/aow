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
                width: 80%;
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

            .credentials {
                background: #f9f9f9;
                padding: 15px;
                border-radius: 4px;
                margin: 10px 0;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <div class="header">
                <h2>Selamat Datang di {{ $companyName }}!</h2>
            </div>
            <div class="content">
                <p>Halo <strong>{{ $customer->name }}</strong>,</p>
                <p>Terima kasih telah bergabung dengan {{ $companyName }}. Akun Anda telah berhasil dibuat. Berikut adalah informasi login Anda:</p>

                <div class="credentials">
                    <p><strong>No. HP:</strong> {{ $customer->phone }}</p>
                    <p><strong>Password:</strong> {{ $password }}</p>
                </div>

                <p>Silakan gunakan informasi di atas untuk masuk ke akun Anda dan melacak pesanan Anda.</p>
                <p>Kami menyarankan Anda untuk segera merubah kata sandi Anda setelah berhasil masuk.</p>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
            </div>
        </div>
    </body>

</html>
