{{--
    Invoice document — one or many orders, each on its own page. Used for the browser (print) and dompdf (PDF),
    so layout is tables + CSS 2.1 only (no flex / grid). Data: InvoiceService::data().
--}}
@php
    $accent = $options['accent'];
    $money = fn ($amount) => ecom_money($amount, $pdf);
    $stampFor = fn ($order) => match (true) {
        in_array($order->status->value, ['cancelled', 'returned'], true) => ['text' => strtoupper($order->status->label()), 'color' => '#c0392b'],
        $order->payment_status->value === 'paid' => ['text' => 'PAID', 'color' => '#1e8449'],
        $order->payment_status->value === 'refunded' => ['text' => 'REFUNDED', 'color' => '#7f8c8d'],
        default => ['text' => 'UNPAID', 'color' => '#d68910'],
    };
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $orders->count() === 1 ? 'Invoice '.$invoices->number($orders->first()) : 'Invoices ('.$orders->count().')' }}</title>
    <style>
        @page { margin: 24px 30px 58px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, "Segoe UI", Arial, sans-serif; font-size: 11px; line-height: 1.45; color: #2b2f36; margin: 0; background: {{ $pdf ? '#ffffff' : '#eef1f5' }}; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .sheet { position: relative; background: #fff; }
        .page-break { page-break-after: always; }
        .accent { color: {{ $accent }}; }
        .muted { color: #6b7280; }
        .small { font-size: 9.5px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .upper { text-transform: uppercase; letter-spacing: .8px; }
        .bar { height: 6px; background: {{ $accent }}; }
        .head td { padding-top: 14px; }
        .store-name { font-size: 18px; font-weight: bold; color: #111827; }
        .doc-title { font-size: 30px; font-weight: bold; letter-spacing: 4px; color: {{ $accent }}; text-align: right; line-height: 1; }
        .meta td { padding: 2px 0; font-size: 10.5px; }
        .meta td.label { color: #6b7280; text-align: right; padding-right: 10px; }
        .meta td.value { text-align: right; font-weight: bold; white-space: nowrap; }
        .pill { display: inline-block; padding: 2px 9px; border-radius: 9px; font-size: 9px; font-weight: bold; color: #fff; }
        .parties { margin-top: 14px; }
        .party { border: 1px solid #e5e7eb; border-top: 3px solid {{ $accent }}; padding: 9px 11px; }
        .party .caption { font-size: 9px; font-weight: bold; color: {{ $accent }}; margin-bottom: 4px; }
        .party b { font-size: 12px; color: #111827; }
        .items { margin-top: 14px; }
        .items th { background: {{ $accent }}; color: #fff; font-size: 9.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .6px; padding: 7px 8px; text-align: left; }
        .items td { padding: 7px 8px; border-bottom: 1px solid #edf0f3; }
        .items tr.alt td { background: #f8fafc; }
        .items .item-name { font-weight: bold; color: #111827; }
        .summary { margin-top: 14px; }
        .totals td { padding: 5px 10px; }
        .totals tr.line td { border-bottom: 1px solid #edf0f3; }
        .totals tr.grand td { background: {{ $accent }}; color: #fff; font-size: 13px; font-weight: bold; padding: 9px 10px; }
        .totals tr.due td { font-weight: bold; font-size: 12px; color: #111827; border-top: 2px solid #111827; }
        .panel { border: 1px solid #e5e7eb; padding: 8px 11px; margin-bottom: 7px; }
        .panel .caption { font-size: 9px; font-weight: bold; color: {{ $accent }}; margin-bottom: 4px; }
        .pay-table td { padding: 2px 0; font-size: 10px; }
        .sign { position: relative; margin-top: 26px; height: 46px; }
        .sign-line { position: absolute; right: 0; bottom: 0; border-top: 1px solid #9ca3af; padding-top: 4px; width: 200px; text-align: center; font-size: 10px; color: #4b5563; }
        .footer { margin-top: 16px; border-top: 1px solid #e5e7eb; padding-top: 8px; text-align: center; color: #6b7280; font-size: 10px; }
        /* PDF: the footer sits at the bottom of every page */
        .footer.fixed { position: fixed; left: 0; right: 0; bottom: -40px; margin: 0; }
        .stamp { position: absolute; left: 10px; bottom: 2px; border: 3px solid; border-radius: 8px; padding: 3px 14px; font-size: 24px; font-weight: bold; letter-spacing: 3px;
                 transform: rotate(-10deg); opacity: .3; }
        /* Browser only */
        .screen { max-width: 820px; margin: 24px auto; padding: 34px 40px; box-shadow: 0 6px 30px rgba(17, 24, 39, .12); border-radius: 6px; }
        .toolbar { max-width: 820px; margin: 20px auto 0; text-align: right; font-family: system-ui, sans-serif; }
        .toolbar a, .toolbar button { display: inline-block; margin-left: 6px; padding: 8px 16px; border-radius: 6px; border: 1px solid {{ $accent }}; background: #fff; color: {{ $accent }};
                                      font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .toolbar .primary { background: {{ $accent }}; color: #fff; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .screen { box-shadow: none; margin: 0; padding: 0; max-width: none; }
            .page-break { break-after: page; }
        }
    </style>
</head>
<body>
@if(! $pdf && $actions)
    <div class="toolbar">
        <span class="muted" style="float:left;padding-top:8px">{{ $orders->count() === 1 ? 'Invoice '.$invoices->number($orders->first()) : $orders->count().' invoices' }}</span>
        <button type="button" class="primary" onclick="window.print()">Print</button>
        @foreach($actions as $label => $url)
            <a href="{{ $url }}" download class="no-loader">{{ $label }}</a>
        @endforeach
    </div>
@endif

@foreach($orders as $order)
    @php
        $stamp = $stampFor($order);
        $paid = $order->paidAmount();
        $due = max(0, round((float) $order->total - $paid, 2));
        $payments = $order->transactions->where('status', 'success')->sortBy('id');
        $billTo = $order->billing_address ?: null;
    @endphp
    <div class="sheet {{ $pdf ? '' : 'screen' }} {{ $loop->last ? '' : 'page-break' }}">
        <div class="bar"></div>

        {{-- Seller + invoice details --}}
        <table class="head">
            <tr>
                <td style="width:58%">
                    @if($store['logo'])<img src="{{ $store['logo'] }}" alt="" style="max-height:52px;max-width:190px;margin-bottom:6px"><br>@endif
                    <div class="store-name">{{ $store['name'] }}</div>
                    @if($store['tagline'])<div class="muted small">{{ $store['tagline'] }}</div>@endif
                    <div class="muted" style="margin-top:4px">
                        @if($store['address']){{ $store['address'] }}<br>@endif
                        @if($store['phone'])Phone: {{ $store['phone'] }}@endif
                        @if($store['phone'] && $store['email']) &middot; @endif
                        @if($store['email']){{ $store['email'] }}@endif
                        @if($options['tax_number'])<br>{{ $options['tax_label'] ?: 'VAT Reg. No' }}: {{ $options['tax_number'] }}@endif
                    </div>
                </td>
                <td>
                    <div class="doc-title">INVOICE</div>
                    <table class="meta" style="margin-top:10px">
                        <tr><td class="label">Invoice No</td><td class="value">{{ $invoices->number($order) }}</td></tr>
                        <tr><td class="label">Order No</td><td class="value">{{ $order->order_number }}</td></tr>
                        <tr><td class="label">Order Date</td><td class="value">{{ $order->created_at->format('d M Y') }}</td></tr>
                        <tr><td class="label">Payment</td><td class="value">{{ $order->payment_method->label() }}</td></tr>
                        <tr><td class="label">Status</td><td class="value"><span class="pill" style="background: {{ $stamp['color'] }}">{{ $order->payment_status->label() }}</span></td></tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- Customer / delivery --}}
        <table class="parties">
            <tr>
                <td class="party" style="width:{{ $billTo ? '32%' : '49%' }}">
                    <div class="caption upper">{{ $billTo ? 'Ship To' : 'Bill To / Ship To' }}</div>
                    <b>{{ $order->customer_name }}</b><br>
                    {{ $order->customer_phone }}@if($order->customer_email)<br>{{ $order->customer_email }}@endif<br>
                    {{ $order->shipping_address }}@if($order->city)<br>{{ $order->city }}@endif
                </td>
                <td style="width:2%"></td>
                @if($billTo)
                    <td class="party" style="width:32%">
                        <div class="caption upper">Bill To</div>
                        {{ $billTo }}
                    </td>
                    <td style="width:2%"></td>
                @endif
                <td class="party">
                    <div class="caption upper">Delivery</div>
                    @if($order->shippingZone){{ $order->shippingZone->name }}@if($order->shippingZone->delivery_time) <span class="muted">({{ $order->shippingZone->delivery_time }})</span>@endif<br>@endif
                    @if($order->courier)
                        Courier: {{ config("ecom.couriers.{$order->courier}.label", $order->courier) }}<br>
                        Tracking: <b>{{ $order->tracking_id }}</b>
                    @else
                        <span class="muted">Courier not assigned yet</span>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Items --}}
        <table class="items">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th>Description</th>
                    @if($options['show_sku'])<th style="width:16%">SKU</th>@endif
                    <th class="right" style="width:15%">Unit Price</th>
                    <th class="center" style="width:8%">Qty</th>
                    <th class="right" style="width:16%">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr class="{{ $loop->even ? 'alt' : '' }}">
                        <td class="muted">{{ $loop->iteration }}</td>
                        <td>
                            <div class="item-name">{{ $item->product_name }}</div>
                            @if($item->variant_label)<div class="muted small">{{ $item->variant_label }}</div>@endif
                            @if($item->warranty_label)
                                <div class="muted small">Warranty: {{ $item->warranty_label }}@if($endsAt = $item->warrantyEndsAt()) (until {{ $endsAt->format('d M Y') }})@endif</div>
                            @endif
                        </td>
                        @if($options['show_sku'])<td class="muted small">{{ $item->sku ?: '—' }}</td>@endif
                        <td class="right">{{ $money($item->unit_price) }}</td>
                        <td class="center">{{ $item->quantity }}</td>
                        <td class="right"><b>{{ $money($item->line_total) }}</b></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Payment info + totals --}}
        <table class="summary">
            <tr>
                <td style="width:53%;padding-right:16px">
                    <div class="panel">
                        <div class="caption upper">Payment Information</div>
                        <table class="pay-table">
                            <tr><td class="muted" style="width:38%">Method</td><td>{{ $order->payment_method->label() }}</td></tr>
                            @forelse($payments as $payment)
                                <tr>
                                    <td class="muted">{{ $payment->type === 'refund' ? 'Refund' : 'Paid' }} {{ $payment->created_at->format('d M Y') }}</td>
                                    <td>{{ $payment->type === 'refund' ? '- ' : '' }}{{ $money($payment->amount) }}@if($payment->trx_id) <span class="muted small">· TrxID {{ $payment->trx_id }}</span>@endif</td>
                                </tr>
                            @empty
                                <tr><td class="muted">Received</td><td>{{ $order->payment_method->value === 'cod' ? 'To be collected on delivery' : 'Not received yet' }}</td></tr>
                            @endforelse
                        </table>
                    </div>
                    @if($order->customer_note)
                        <div class="panel"><div class="caption upper">Customer Note</div>{{ $order->customer_note }}</div>
                    @endif
                    @if($options['notes'])
                        <div class="panel"><div class="caption upper">Notes</div>{!! nl2br(e($options['notes'])) !!}</div>
                    @endif
                </td>
                <td>
                    <table class="totals">
                        <tr class="line"><td class="muted">Subtotal</td><td class="right">{{ $money($order->subtotal) }}</td></tr>
                        @if((float) $order->discount > 0)
                            <tr class="line"><td class="muted">Discount @if($order->coupon_code)<span class="small">({{ $order->coupon_code }})</span>@endif</td><td class="right">- {{ $money($order->discount) }}</td></tr>
                        @endif
                        <tr class="line">
                            <td class="muted">Delivery @if((float) $order->shipping_discount > 0)<span class="small">({{ $money($order->shipping_discount) }} off)</span>@endif</td>
                            <td class="right">{{ (float) $order->shipping_charge > 0 ? $money($order->shipping_charge) : 'Free' }}</td>
                        </tr>
                        <tr class="grand"><td>Total</td><td class="right">{{ $money($order->total) }}</td></tr>
                        <tr><td class="muted">Paid</td><td class="right">{{ $money($paid) }}</td></tr>
                        <tr class="due"><td>Balance Due</td><td class="right">{{ $money($due) }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        @if($options['terms'])
            <div class="panel" style="margin-top:12px"><div class="caption upper">Terms &amp; Conditions</div><span class="small">{!! nl2br(e($options['terms'])) !!}</span></div>
        @endif

        {{-- Status stamp (left) and signature line (right) --}}
        <div class="sign">
            <div class="stamp" style="color: {{ $stamp['color'] }}; border-color: {{ $stamp['color'] }}">{{ $stamp['text'] }}</div>
            @if($options['signature'])<div class="sign-line">{{ $options['signature'] }}</div>@endif
        </div>

        @unless($pdf)
            @include('ecom::invoices.footer')
        @endunless
    </div>
@endforeach

@if($pdf)
    @include('ecom::invoices.footer', ['fixed' => true])
@endif
</body>
</html>
