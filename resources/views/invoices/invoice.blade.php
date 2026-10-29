<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Invoice') }} {{ $order->invoice_number }}</title>
    <style>
        @page { margin: 36px 40px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2a1a; line-height: 1.45; }
        h1 { font-size: 22px; margin: 0; color: #395e2c; letter-spacing: 1px; }
        h2 { font-size: 12px; margin: 0 0 6px; color: #395e2c; text-transform: uppercase; letter-spacing: 0.5px; }
        table { width: 100%; border-collapse: collapse; }
        .muted { color: #6b7566; }
        .right { text-align: right; }
        .center { text-align: center; }
        .header td { vertical-align: top; }
        .rule { border-bottom: 2px solid #467536; margin: 12px 0 16px; }
        .box { border: 1px solid #d9e4d0; padding: 10px 12px; }
        .items th { background: #467536; color: #ffffff; text-align: left; padding: 7px 8px; font-size: 10px; text-transform: uppercase; }
        .items td { padding: 7px 8px; border-bottom: 1px solid #e3ebdc; }
        .totals td { padding: 4px 8px; }
        .totals .grand td { border-top: 2px solid #467536; font-size: 13px; font-weight: bold; padding-top: 8px; color: #395e2c; }
        .badge { display: inline-block; padding: 3px 10px; border: 1px solid #467536; color: #467536; font-weight: bold; font-size: 10px; }
        .footer { margin-top: 28px; text-align: center; font-size: 10px; color: #6b7566; }
    </style>
</head>
<body>
    @php
        $customer = $order->user;
        $money = fn ($amount) => '₹'.number_format((float) $amount, 2);
        $taxRate = rtrim(rtrim(number_format((float) $order->tax_rate, 2), '0'), '.');
    @endphp

    <table class="header">
        <tr>
            <td style="width: 60%;">
                <h1>{{ $company['name'] }}</h1>
                @if ($company['address'])<div class="muted">{!! nl2br(e($company['address'])) !!}</div>@endif
                @if ($company['phone'])<div class="muted">{{ __('Phone') }}: {{ $company['phone'] }}</div>@endif
                @if ($company['email'])<div class="muted">{{ $company['email'] }}</div>@endif
                @if ($company['gstin'])<div><strong>GSTIN:</strong> {{ $company['gstin'] }}</div>@endif
            </td>
            <td class="right" style="width: 40%;">
                <div style="font-size: 20px; font-weight: bold; color: #395e2c;">{{ __('TAX INVOICE') }}</div>
                <div style="margin-top: 6px;"><strong>{{ __('Invoice no.') }}:</strong> {{ $order->invoice_number }}</div>
                <div><strong>{{ __('Date') }}:</strong> {{ ($order->paid_at ?? $order->created_at)->format('d M Y') }}</div>
                <div><strong>{{ __('Order no.') }}:</strong> #{{ $order->id }}</div>
                <div style="margin-top: 6px;"><span class="badge">{{ __('PAID') }}</span></div>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <table>
        <tr>
            <td style="width: 50%; padding-right: 10px; vertical-align: top;">
                <div class="box">
                    <h2>{{ __('Billed to') }}</h2>
                    <strong>{{ $customer->business_name ?: $customer->name }}</strong><br>
                    @if ($customer->business_name){{ $customer->name }}<br>@endif
                    @if ($customer->address){{ $customer->address }}<br>@endif
                    {{ collect([$customer->city, $customer->state, $customer->pincode])->filter()->join(', ') }}<br>
                    @if ($customer->phone){{ __('Phone') }}: {{ $customer->phone }}<br>@endif
                    {{ $customer->email }}
                </div>
            </td>
            <td style="width: 50%; padding-left: 10px; vertical-align: top;">
                <div class="box">
                    <h2>{{ __('Payment') }}</h2>
                    {{ __('Method') }}: {{ $order->payment_method === 'razorpay' ? __('Online (Razorpay)') : __('Offline / manual') }}<br>
                    @if ($order->razorpay_payment_id){{ __('Payment ID') }}: {{ $order->razorpay_payment_id }}<br>@endif
                    {{ __('Paid on') }}: {{ ($order->paid_at ?? $order->created_at)->format('d M Y, h:i A') }}
                    @if ($order->package)<br>{{ __('Package') }}: {{ $order->package->name }}@endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items" style="margin-top: 18px;">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th>{{ __('Product') }}</th>
                <th class="center" style="width: 10%;">{{ __('Qty') }}</th>
                <th class="right" style="width: 18%;">{{ __('Unit price') }}</th>
                <th class="right" style="width: 18%;">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="right">{{ $money($item->unit_price) }}</td>
                    <td class="right">{{ $money($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top: 14px;">
        <tr>
            <td style="width: 55%;"></td>
            <td style="width: 45%;">
                <table class="totals">
                    <tr>
                        <td>{{ __('Subtotal') }}</td>
                        <td class="right">{{ $money($order->subtotal) }}</td>
                    </tr>
                    @if ((float) $order->discount_amount > 0)
                        <tr>
                            <td>{{ __('Discount') }}</td>
                            <td class="right">-{{ $money($order->discount_amount) }}</td>
                        </tr>
                    @endif
                    @if ((float) $order->tax_amount > 0)
                        <tr>
                            <td>{{ $tax['label'] }} ({{ $taxRate }}%{{ $order->tax_inclusive ? ', '.__('included in price') : '' }})</td>
                            <td class="right">{{ $money($order->tax_amount) }}</td>
                        </tr>
                    @endif
                    <tr class="grand">
                        <td>{{ __('Total paid') }}</td>
                        <td class="right">{{ $money($order->total_amount) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($order->notes)
        <div class="box" style="margin-top: 16px;">
            <h2>{{ __('Notes') }}</h2>
            {{ $order->notes }}
        </div>
    @endif

    <div class="footer">
        {{ $footerNote }}<br>
        {{ __('This is a computer generated invoice and does not require a signature.') }}
    </div>
</body>
</html>
