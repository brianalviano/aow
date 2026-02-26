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

            .status-badge {
                padding: 6px 14px;
                border-radius: 20px;
                display: inline-block;
                font-size: 0.9em;
                font-weight: 600;
            }

            .status-confirmed {
                background: #e0f2fe;
                color: #0369a1;
            }

            .status-shipped {
                background: #ede9fe;
                color: #6d28d9;
            }

            .status-delivered {
                background: #dcfce7;
                color: #15803d;
            }

            .status-cancelled {
                background: #fee2e2;
                color: #b91c1c;
            }

            .status-default {
                background: #f3f4f6;
                color: #374151;
            }

            .cancellation-note {
                background: #fff7ed;
                border-left: 4px solid #f97316;
                padding: 12px 16px;
                border-radius: 4px;
                margin-top: 16px;
                font-style: italic;
                color: #9a3412;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <div class="header">
                <h2>Update Status Pesanan</h2>
                <p>Nomor Pesanan: <strong>#{{ $order->number }}</strong></p>
            </div>
            <div class="content">
                <p>Halo {{ $order->customer->name }},</p>

                @php
                    $badgeClass = match ($newStatus) {
                        'confirmed' => 'status-confirmed',
                        'shipped'   => 'status-shipped',
                        'delivered' => 'status-delivered',
                        'cancelled' => 'status-cancelled',
                        default     => 'status-default',
                    };
                    $statusLabel = match ($newStatus) {
                        'confirmed' => 'Dikonfirmasi',
                        'shipped'   => 'Sedang Dikirim',
                        'delivered' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default     => ucfirst($newStatus),
                    };
                    $bodyMessage = match ($newStatus) {
                        'confirmed' => 'Pesanan Anda telah dikonfirmasi oleh admin dan sedang kami proses untuk pengiriman.',
                        'shipped'   => 'Kabar gembira! Pesanan Anda kini sedang dalam proses pengiriman.',
                        'delivered' => 'Pesanan Anda telah selesai. Terima kasih telah berbelanja bersama kami!',
                        'cancelled' => 'Mohon maaf, pesanan Anda telah dibatalkan.',
                        default     => 'Status pesanan Anda telah diperbarui.',
                    };
                @endphp

                <p>{{ $bodyMessage }}</p>

                <div style="margin: 20px 0;">
                    Status Pesanan:
                    <span class="status-badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                </div>

                @if ($newStatus === 'cancelled' && $order->cancellation_note)
                    <div class="cancellation-note">
                        <strong>Alasan Pembatalan:</strong><br>
                        "{{ $order->cancellation_note }}"
                    </div>
                @endif

                <p style="margin-top: 20px;">
                    Jika Anda memiliki pertanyaan, silakan hubungi kami.
                </p>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
            </div>
        </div>
    </body>

</html>
