<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            margin-bottom: 10px;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            background: #f4f4f4;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #eee;
        }

        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .totals {
            float: right;
            width: 250px;
        }

        .totals table {
            width: 100%;
        }

        .text-right {
            text-align: right;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            background: #22c55e;
            color: #fff;
            font-weight: bold;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        {{-- Menggunakan path lokal untuk dompdf agar lebih cepat dan aman --}}
        <img src="{{ public_path('logo.svg') }}" class="logo">
        <div class="invoice-title">Official Receipt</div>
        <div>{{ $order->branch->name }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td>
                <strong>Order Number:</strong> {{ $order->order_number }}<br>
                <strong>Date:</strong> {{ $order->created_at->format('d M Y H:i') }}
            </td>
            <td class="text-right">
                <strong>Table:</strong> #{{ $order->table->number }}<br>
                <div class="status-badge">PAID / LUNAS</div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Menu Item</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->menu->name }}</strong>
                        @if ($item->notes)
                            <br><small style="color: #666 italic">Note: {{ $item->notes }}</small>
                        @endif
                    </td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->price_at_order, 0, '.', ',') }}</td>
                    <td class="text-right">{{ number_format($item->subtotal, 0, '.', ',') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Subtotal</td>
                <td class="text-right">IDR {{ number_format($order->items->sum('subtotal'), 0, '.', ',') }}</td>
            </tr>
            <tr>
                <td>Tax ({{ $order->tax_percentage }}%)</td>
                <td class="text-right">IDR {{ number_format($order->tax_amount, 0, '.', ',') }}</td>
            </tr>
            <tr style="font-weight: bold; font-size: 14px;">
                <td>Total</td>
                <td class="text-right">IDR {{ number_format($order->total_amount, 0, '.', ',') }}</td>
            </tr>
        </table>
    </div>

    <div style="clear: both;"></div>

    <div class="footer">
        <p>Solutions built for the way you work.</p>
        <p>Thank you for dining with us at QRESTA!</p>
    </div>
</body>

</html>
