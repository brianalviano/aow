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

            .status-accepted {
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

            .status-rejected {
                background: #fee2e2;
                color: #b91c1c;
            }

            .status-default {
                background: #f3f4f6;
                color: #374151;
            }

            .item-list {
                background: #f8fafc;
                border-radius: 6px;
                padding: 15px;
                margin-top: 15px;
            }

            .item-list ul {
                margin: 0;
                padding-left: 20px;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <div class="header">
                <h2>Update Status Item Pesanan</h2>
                <p>Nomor Pesanan: <strong>#{{ $order->number }}</strong></p>
            </div>
            <div class="content">
                <p>Halo {{ $order->customer->name }},</p>

                @php
                    $badgeClass = match ($newStatus->value) {
                        'accepted' => 'status-accepted',
                        'shipped' => 'status-shipped',
                        'delivered' => 'status-delivered',
                        'rejected' => 'status-rejected',
                        default => 'status-default',
                    };

                    $statusLabel = $newStatus->label();
                @endphp

                <p>Kabar mengenai pesanan Anda! Beberapa item masakan pada pesanan Anda telah diperbarui statusnya oleh Chef kami menjadi:</p>

                <div style="margin: 20px 0;">
                    Status Terbaru:
                    <span class="status-badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                </div>

                <div class="item-list">
                    <strong>Item yang Diperbarui:</strong>
                    <ul>
                        @foreach ($items as $item)
                            <li>{{ $item->product ? $item->product->name : 'Item' }} (x{{ $item->quantity }})</li>
                        @endforeach
                    </ul>
                </div>

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
