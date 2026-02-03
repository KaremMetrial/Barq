<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->order_number }}</title>
    <style>
        @page {
            margin: 0;
            size: 80mm 297mm;
        }
        body {
            font-family: 'Arial', sans-serif, 'DejaVu Sans', 'Noto Sans Arabic';
            width: 80mm;
            margin: 0;
            padding: 2mm;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
            background: #fff;
            direction: {{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }};
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        
        .header {
            margin-bottom: 5mm;
            border-bottom: 1px dashed #000;
            padding-bottom: 3mm;
            text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};
        }
        .logo {
            max-width: 40mm;
            margin-bottom: 2mm;
        }
        .store-name {
            font-size: 16pt;
            margin: 0;
            color: #00835B;
            font-family: 'Arial', sans-serif, 'DejaVu Sans', 'Noto Sans Arabic';
        }
        
        .order-info {
            margin-bottom: 5mm;
            font-size: 10pt;
            text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};
            font-family: 'Arial', sans-serif, 'DejaVu Sans', 'Noto Sans Arabic';
        }
        .order-number {
            font-size: 14pt;
            margin-top: 2mm;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5mm;
        }
        .table th {
            border-bottom: 1px solid #000;
            text-align: inherit;
            padding: 1mm 0;
            font-size: 10pt;
        }
        .table td {
            padding: 2mm 0;
            vertical-align: top;
        }
        .item-details {
            font-size: 9pt;
            color: #444;
            padding-left: 2mm;
            font-family: 'Arial', sans-serif, 'DejaVu Sans', 'Noto Sans Arabic';
        }
        
        .totals {
            width: 100%;
            border-top: 1px dashed #000;
            padding-top: 3mm;
            margin-bottom: 5mm;
            text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};
            font-family: 'Arial', sans-serif, 'DejaVu Sans', 'Noto Sans Arabic';
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
        }
        .final-total {
            font-size: 14pt;
            margin-top: 2mm;
            border-top: 2px solid #000;
            padding-top: 2mm;
        }
        
        .footer {
            margin-top: 10mm;
            font-size: 9pt;
            border-top: 1px dashed #000;
            padding-top: 5mm;
            font-family: 'Arial', sans-serif, 'DejaVu Sans', 'Noto Sans Arabic';
        }
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
        
        .print-btn {
            background: #00835B;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-bottom: 20px;
            font-size: 12pt;
        }
    </style>
</head>
<body onload="/* window.print() */">
    <div class="no-print">
        <button onclick="window.print()" class="print-btn">Print Invoice</button>
    </div>

    <div class="header text-center">
        @if($order->store->logo)
            <img src="{{ public_path('storage/' . $order->store->logo) }}" alt="Logo" class="logo">
        @endif
        <h1 class="store-name">{{ $order->store->name }}</h1>
        <p>{{ $order->store->address_place }}</p>
        <p>{{ $order->store->phone }}</p>
    </div>

    <div class="order-info">
        <div class="order-number font-bold text-center">#{{ $order->order_number }}</div>
        <div class="text-center">{{ $order->created_at->format('Y-m-d H:i:s') }}</div>
        <div style="margin-top: 3mm;">
            <strong>Customer:</strong> {{ $order->user->first_name . ' ' . $order->user->last_name ?? 'Guest' }}<br>
            <strong>Phone:</strong> {{ $order->user->phone ?? 'N/A' }}<br>
            @if($order->isDeliver())
                <strong>Address:</strong> {{ $order->deliveryAddress->getFullAddressAttribute() ?? 'N/A' }}
            @endif
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>
                        <div class="font-bold">{{ $item->product->name }}</div>
                        @if($item->productOptionValue)
                            <div class="item-details">- {{ $item->productOptionValue->name }}</div>
                        @endif
                        @foreach($item->addOns as $addon)
                            <div class="item-details">+ {{ $addon->name }} (x{{ $addon->pivot->quantity }})</div>
                        @endforeach
                        @if($item->note)
                            <div class="item-details">Note: {{ $item->note }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">
                        @php
                            $currencyFactor = $order->store->currency_factor ?? 100;
                            $formattedPrice = \App\Helpers\CurrencyHelper::formatPrice(
                                $item->total_price, 
                                $order->store->currency_code ?? 'EGP',
                                $order->store->currency_symbol ?? 'EGP',
                                $currencyFactor
                            );
                        @endphp
                        {{ $formattedPrice }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="total-row">
            <span>Subtotal</span>
            <span>
                @php
                    $currencyFactor = $order->store->currency_factor ?? 100;
                    $formattedSubtotal = \App\Helpers\CurrencyHelper::formatPrice(
                        $order->total_amount, 
                        $order->store->currency_code ?? 'EGP',
                        $order->store->currency_symbol ?? 'EGP',
                        $currencyFactor
                    );
                @endphp
                {{ $formattedSubtotal }}
            </span>
        </div>
        
        @if($order->discount_amount > 0)
            <div class="total-row">
                <span>Discount</span>
                <span>
                    @php
                        $currencyFactor = $order->store->currency_factor ?? 100;
                        $formattedDiscount = \App\Helpers\CurrencyHelper::formatPrice(
                            $order->discount_amount, 
                            $order->store->currency_code ?? 'EGP',
                            $order->store->currency_symbol ?? 'EGP',
                            $currencyFactor
                        );
                    @endphp
                    -{{ $formattedDiscount }}
                </span>
            </div>
        @endif
        
        @if($order->delivery_fee > 0)
            <div class="total-row">
                <span>Delivery Fee</span>
                <span>
                    @php
                        $currencyFactor = $order->store->currency_factor ?? 100;
                        $formattedDelivery = \App\Helpers\CurrencyHelper::formatPrice(
                            $order->delivery_fee, 
                            $order->store->currency_code ?? 'EGP',
                            $order->store->currency_symbol ?? 'EGP',
                            $currencyFactor
                        );
                    @endphp
                    {{ $formattedDelivery }}
                </span>
            </div>
        @endif
        
        @if($order->tax_amount > 0)
            <div class="total-row">
                <span>Tax</span>
                <span>
                    @php
                        $currencyFactor = $order->store->currency_factor ?? 100;
                        $formattedTax = \App\Helpers\CurrencyHelper::formatPrice(
                            $order->tax_amount, 
                            $order->store->currency_code ?? 'EGP',
                            $order->store->currency_symbol ?? 'EGP',
                            $currencyFactor
                        );
                    @endphp
                    {{ $formattedTax }}
                </span>
            </div>
        @endif
        
        @if($order->service_fee > 0)
            <div class="total-row">
                <span>Service Fee</span>
                <span>
                    @php
                        $currencyFactor = $order->store->currency_factor ?? 100;
                        $formattedService = \App\Helpers\CurrencyHelper::formatPrice(
                            $order->service_fee, 
                            $order->store->currency_code ?? 'EGP',
                            $order->store->currency_symbol ?? 'EGP',
                            $currencyFactor
                        );
                    @endphp
                    {{ $formattedService }}
                </span>
            </div>
        @endif

        <div class="total-row final-total font-bold">
            <span>Total</span>
            <span>
                @php
                    $currencyFactor = $order->store->currency_factor ?? 100;
                    $finalAmount = $order->total_amount - $order->discount_amount + $order->delivery_fee + $order->service_fee + $order->tax_amount;
                    $formattedTotal = \App\Helpers\CurrencyHelper::formatPrice(
                        $finalAmount, 
                        $order->store->currency_code ?? 'EGP',
                        $order->store->currency_symbol ?? 'EGP',
                        $currencyFactor
                    );
                @endphp
                {{ $formattedTotal }} {{ $order->store->currency_symbol ?? 'EGP' }}
            </span>
        </div>
    </div>

    <div class="order-info">
        <strong>Payment:</strong> {{ $order->paymentMethod->name ?? 'Cash' }}<br>
        <strong>Status:</strong> {{ ucfirst($order->payment_status->value ?? 'unpaid') }}
    </div>

    <div class="footer text-center">
        <p>Thank you for choosing {{ $order->store->name }}!</p>
        <p>Powered by Barq</p>
    </div>

    <script>
        // Auto print or other logic can go here
    </script>
</body>
</html>
