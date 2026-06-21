<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Struk #{{ $order->number }}</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: "Courier New", Courier, monospace;
            color: #000 !important;
            background-color: transparent !important;
        }
        body {
            margin: 0;
            padding: 10px;
            width: 80mm;
            background: #fff !important;
            font-size: 11px;
            line-height: 1.3;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-sm { font-size: 10px; }
        .text-xs { font-size: 8px; }
        .text-lg { font-size: 14px; }
        .uppercase { text-transform: uppercase; }
        .italic { font-style: italic; }
        
        .border-b-dashed { border-bottom: 1px dashed #000; }
        .border-b-dotted { border-bottom: 1px dotted #000; }
        .border-b-solid { border-bottom: 1px solid #000; }
        .border-t-dashed { border-top: 1px dashed #000; }
        .border-t-dotted { border-top: 1px dotted #000; }
        .border-t-solid { border-top: 1px solid #000; }
        .border-box { border: 1px solid #000; }
        
        .pb-1 { padding-bottom: 4px; }
        .pb-2 { padding-bottom: 8px; }
        .pt-1 { padding-top: 4px; }
        .pt-2 { padding-top: 8px; }
        .mt-1 { margin-top: 4px; }
        .mt-2 { margin-top: 8px; }
        .my-2 { margin-top: 8px; margin-bottom: 8px; }
        .my-4 { margin-top: 16px; margin-bottom: 16px; }
        .space-y-1 > * + * { margin-top: 4px; }
        .space-y-2 > * + * { margin-top: 8px; }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 4px 0;
            vertical-align: top;
        }
        
        .flex { display: flex; }
        .justify-between { justify-content: space-between; }
        .align-top { vertical-align: top; }
        .pl-2 { padding-left: 8px; }
        
        .page-break {
            page-break-after: always;
            break-after: page;
        }
        
        @page {
            size: 80mm auto;
            margin: 0;
        }
        
        @media print {
            body {
                width: 80mm;
                padding: 5px;
            }
        }
    </style>
</head>
<body>

    <!-- SECTION 1: CHECKER DAPUR -->
    <div class="space-y-2">
        <div class="text-center font-bold text-lg uppercase border-b-dashed pb-2">
            {{ $settings->name ?? 'AOWENAK' }}
            <div class="text-sm font-normal mt-1" style="font-size: 11px;">CHECKER DAPUR</div>
        </div>
        
        <div class="space-y-1 mt-2 text-sm">
            <div><strong>No. Pesanan:</strong> #{{ $order->number }}</div>
            <div><strong>Tgl Kirim:</strong> {{ $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->translatedFormat('d M Y') : '-' }} {{ $order->delivery_time ? '@ ' . $order->delivery_time : '' }}</div>
            <div><strong>Tipe:</strong> {{ $order->shipping_method === 'instant' ? 'Instant Delivery' : 'Pre-order' }}</div>
            <div><strong>Pelanggan:</strong> {{ $order->customer->name ?? '-' }}</div>
            <div><strong>Drop Point:</strong> {{ $order->dropPoint->name ?? ($order->customerAddress->address ?? 'Ambil Sendiri') }}</div>
            @if($order->school_class)
                <div><strong>Kelas:</strong> {{ $order->school_class }}</div>
            @endif
            @if($order->note)
                <div class="border-box p-1 mt-1">
                    <strong>Catatan Order:</strong> {{ $order->note }}
                </div>
            @endif
        </div>
        
        <div class="border-t-dashed my-2"></div>
        
        <table>
            <thead>
                <tr class="border-b-solid">
                    <th class="text-left py-1" style="width: 35px;">Qty</th>
                    <th class="text-left py-1">Item Menu & Pilihan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr class="border-b-dotted">
                        <td class="py-1 font-bold text-lg" style="font-size: 15px;">{{ $item->quantity }}x</td>
                        <td class="py-1">
                            <div class="font-bold text-sm" style="font-size: 12px;">{{ $item->product->name ?? '-' }}</div>
                            @if($item->options && count($item->options) > 0)
                                <div class="text-xs pl-2">
                                    - {{ collect($item->options)->map(fn($o) => ($o->productOption->name ?? '') . ': ' . ($o->productOptionItem->name ?? ''))->implode(', ') }}
                                </div>
                            @endif
                            @if($item->note)
                                <div class="text-xs italic pl-2 mt-1">
                                    * Catatan: {{ $item->note }}
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="border-t-dashed pt-2 text-center text-xs">
            Dicetak: {{ \Carbon\Carbon::now()->timezone('Asia/Jakarta')->format('d/m/Y H.i.s') }}
        </div>
    </div>
    
    <div class="page-break my-4 border-t-dashed"></div>
    
    <!-- SECTION 2: CUSTOMER INVOICE -->
    <div class="space-y-2 pt-2">
        <div class="text-center font-bold text-sm uppercase">
            {{ $settings->name ?? 'AOWENAK' }}
            <div class="text-xs font-normal normal-case mt-1" style="font-size: 9px; line-height: 1.2;">
                {{ $settings->address ?? '' }}<br/>
                Telp/WA: {{ $settings->whatsapp ?? ($settings->phone ?? '') }}
            </div>
            <div class="text-sm font-bold border-t-dashed border-b-dashed py-1 mt-2" style="font-size: 11px;">
                STRUK PEMBAYARAN CUSTOMER
            </div>
        </div>
        
        <div class="space-y-1 mt-2 text-xs" style="font-size: 9px;">
            <div><strong>No. Pesanan:</strong> #{{ $order->number }}</div>
            <div><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($order->created_at)->timezone('Asia/Jakarta')->translatedFormat('d/m/Y H.i') }}</div>
            <div><strong>Kirim Pada:</strong> {{ $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->translatedFormat('d M Y') : '-' }} {{ $order->delivery_time ? '@ ' . $order->delivery_time : '' }}</div>
            <div><strong>Pelanggan:</strong> {{ $order->customer->name ?? '-' }} ({{ $order->customer->phone ?? '-' }})</div>
            <div><strong>Drop Point:</strong> {{ $order->dropPoint->name ?? ($order->customerAddress->address ?? 'Ambil Sendiri') }}</div>
            @if($order->school_class)
                <div><strong>Kelas:</strong> {{ $order->school_class }}</div>
            @endif
        </div>
        
        <div class="border-t-dashed my-2"></div>
        
        <table class="text-xs" style="font-size: 9px;">
            <thead>
                <tr class="border-b-solid">
                    <th class="text-left py-1">Menu & Pilihan</th>
                    <th class="text-right py-1">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr class="border-b-dotted">
                        <td class="py-1">
                            <div class="font-bold">{{ $item->product->name ?? '-' }}</div>
                            @if($item->options && count($item->options) > 0)
                                <div class="text-xs pl-2" style="font-size: 8px;">
                                    {{ collect($item->options)->map(fn($o) => ($o->productOption->name ?? '') . ': ' . ($o->productOptionItem->name ?? ''))->implode(', ') }}
                                </div>
                            @endif
                            <div class="text-xs" style="font-size: 8px; color: #555 !important;">
                                {{ $item->quantity }} x Rp {{ number_format($item->quantity > 0 ? ($item->subtotal / $item->quantity) : 0, 0, ',', '.') }}
                            </div>
                        </td>
                        <td class="text-right py-1" style="vertical-align: bottom;">
                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="border-t-dashed my-2"></div>
        
        <div class="space-y-1 text-xs" style="font-size: 9px;">
            <div class="flex justify-between">
                <span>Total Item:</span>
                <span>Rp {{ number_format(collect($order->items)->sum('subtotal'), 0, ',', '.') }}</span>
            </div>
            @if($order->discount_amount > 0)
                <div class="flex justify-between">
                    <span>Diskon:</span>
                    <span>-Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                </div>
            @endif
            @if($order->delivery_fee > 0)
                <div class="flex justify-between">
                    <span>Ongkos Kirim:</span>
                    <span>Rp {{ number_format($order->delivery_fee, 0, ',', '.') }}</span>
                </div>
            @endif
            @if($order->admin_fee > 0)
                <div class="flex justify-between">
                    <span>Biaya Admin:</span>
                    <span>Rp {{ number_format($order->admin_fee, 0, ',', '.') }}</span>
                </div>
            @endif
            @if($order->service_fee > 0)
                <div class="flex justify-between">
                    <span>Biaya Layanan:</span>
                    <span>Rp {{ number_format($order->service_fee, 0, ',', '.') }}</span>
                </div>
            @endif
            @if($order->tax_amount > 0)
                <div class="flex justify-between">
                    <span>Pajak:</span>
                    <span>Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="flex justify-between font-bold border-t-dotted pt-1 mt-1" style="font-size: 12px;">
                <span>TOTAL BAYAR:</span>
                <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>
        
        <div class="border-t-dashed my-2"></div>
        
        <div class="text-center text-xs" style="font-size: 9px;">
            <div>Metode: {{ $order->paymentMethod->name ?? '-' }}</div>
            <div class="font-bold">STATUS: {{ $order->payment_status === 'paid' ? 'LUNAS' : 'BELUM BAYAR' }}</div>
            <div class="pt-2 italic">Terima kasih atas pesanan Anda!</div>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
            window.onafterprint = function() {
                window.close();
            };
        };
    </script>
</body>
</html>
