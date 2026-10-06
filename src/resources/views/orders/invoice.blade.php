@php
    $money = fn ($amount) => ecom_money($amount, $pdf);
    $storeName = ecom_setting('store_name', get_setting('app_name', config('app.name')));
    $logo = \ME\Models\Setting::image('ecom_store_logo');
    $logoSrc = null;
    if ($logo) {
        // dompdf reads local files faster and without HTTP; the browser needs a URL
        $logoSrc = $pdf ? $logo->absolutePath() : $logo->url();
        if ($pdf && ! is_file($logoSrc)) { $logoSrc = null; }
    }
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #222; margin: 0; padding: 24px; background: #fff; }
        .wrap { max-width: 800px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; }
        .head td { vertical-align: top; }
        .title { font-size: 26px; font-weight: bold; color: #0d6efd; letter-spacing: 2px; text-align: right; }
        .muted { color: #666; }
        .box { border: 1px solid #ddd; padding: 10px; }
        .items th { background: #f1f4f9; text-align: left; padding: 8px; border-bottom: 2px solid #ccd; font-size: 11px; text-transform: uppercase; }
        .items td { padding: 8px; border-bottom: 1px solid #eee; }
        .right { text-align: right; }
        .center { text-align: center; }
        .totals td { padding: 4px 8px; }
        .grand td { font-size: 15px; font-weight: bold; border-top: 2px solid #222; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; background: #eee; text-transform: uppercase; }
        .paid { background: #d1f2e0; color: #0f6b3a; }
        .unpaid { background: #fdecc8; color: #8a5a00; }
        .footer { margin-top: 30px; text-align: center; font-size: 11px; color: #777; border-top: 1px dashed #ccc; padding-top: 10px; }
        .print-bar { text-align: center; margin-bottom: 16px; }
        .print-bar button { padding: 8px 18px; font-size: 14px; cursor: pointer; }
        @media print { .print-bar { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
<div class="wrap">
    @unless($pdf)
        <div class="print-bar">
            <button onclick="window.print()">🖨 Print</button>
            <a href="{{ route('ecom.orders.invoice-pdf', $order) }}"><button type="button">⬇ Download PDF</button></a>
        </div>
    @endunless

    <table class="head">
        <tr>
            <td style="width:60%">
                @if($logoSrc)<img src="{{ $logoSrc }}" alt="" style="max-height:60px;max-width:200px"><br>@endif
                <b style="font-size:16px">{{ $storeName }}</b><br>
                <span class="muted">
                    @if(ecom_setting('store_address')){{ ecom_setting('store_address') }}<br>@endif
                    @if(ecom_setting('store_phone'))Phone: {{ ecom_setting('store_phone') }}@endif
                    @if(ecom_setting('store_email')) · {{ ecom_setting('store_email') }}@endif
                </span>
            </td>
            <td>
                <div class="title">INVOICE</div>
                <table style="margin-top:8px">
                    <tr><td class="right muted">Invoice No:</td><td class="right"><b>{{ $order->order_number }}</b></td></tr>
                    <tr><td class="right muted">Date:</td><td class="right">{{ $order->created_at->format('d M Y') }}</td></tr>
                    <tr><td class="right muted">Payment:</td><td class="right">{{ $order->payment_method->label() }}</td></tr>
                    <tr><td class="right muted">Status:</td><td class="right"><span class="badge {{ $order->payment_status->value === 'paid' ? 'paid' : 'unpaid' }}">{{ $order->payment_status->label() }}</span></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table style="margin:18px 0">
        <tr>
            <td class="box" style="width:50%">
                <div class="muted" style="font-size:10px;text-transform:uppercase">{{ $order->billing_address ? 'Ship To' : 'Bill / Ship To' }}</div>
                <b>{{ $order->customer_name }}</b><br>
                {{ $order->customer_phone }}@if($order->customer_email) · {{ $order->customer_email }}@endif<br>
                {{ $order->shipping_address }}@if($order->city), {{ $order->city }}@endif
                @if($order->billing_address)
                    <div class="muted" style="font-size:10px;text-transform:uppercase;margin-top:8px">Bill To</div>
                    {{ $order->billing_address }}
                @endif
            </td>
            <td style="width:2%"></td>
            <td class="box">
                <div class="muted" style="font-size:10px;text-transform:uppercase">Delivery</div>
                @if($order->shippingZone){{ $order->shippingZone->name }}<br>@endif
                @if($order->courier)Courier: {{ config("ecom.couriers.{$order->courier}.label", $order->courier) }}<br>Tracking: <b>{{ $order->tracking_id }}</b>@else<span class="muted">—</span>@endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead><tr><th style="width:5%">#</th><th>Item</th><th class="right">Price</th><th class="center">Qty</th><th class="right">Total</th></tr></thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->product_name }}@if($item->variant_label)<br><span class="muted" style="font-size:11px">{{ $item->variant_label }}</span>@endif
                        @if($item->warranty_label)<br><span class="muted" style="font-size:11px">Warranty: {{ $item->warranty_label }}@if($endsAt = $item->warrantyEndsAt()) (until {{ $endsAt->format('d M Y') }})@endif</span>@endif</td>
                    <td class="right">{{ $money($item->unit_price) }}</td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="right">{{ $money($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" style="width:45%;margin-left:55%;margin-top:10px">
        <tr><td>Subtotal</td><td class="right">{{ $money($order->subtotal) }}</td></tr>
        @if((float) $order->discount > 0)<tr><td>Discount @if($order->coupon_code)({{ $order->coupon_code }})@endif</td><td class="right">- {{ $money($order->discount) }}</td></tr>@endif
        <tr><td>Delivery Charge @if((float) $order->shipping_discount > 0)<span class="muted">({{ $money($order->shipping_discount) }} off)</span>@endif</td><td class="right">{{ $money($order->shipping_charge) }}</td></tr>
        <tr class="grand"><td>Total</td><td class="right">{{ $money($order->total) }}</td></tr>
        <tr><td class="muted">Paid</td><td class="right muted">{{ $money($order->paidAmount()) }}</td></tr>
        <tr><td><b>Due</b></td><td class="right"><b>{{ $money(max(0, (float) $order->total - $order->paidAmount())) }}</b></td></tr>
    </table>

    <div class="footer">
        {{ ecom_setting('invoice_footer', 'Thank you for shopping with us!') }}
    </div>
</div>
</body>
</html>
