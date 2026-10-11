{{--
    Invoice document — one or many orders, each on its own page. Classic Bangladeshi retail layout
    (StarTech / Ryans / Daraz style): company header, Bill To + Invoice Details boxes, a fully bordered item
    grid with warranty, totals, amount in words, terms and two signatures.
    Used for the browser (print) and dompdf (PDF), so layout is tables + CSS 2.1 only. Data: InvoiceService::data().
--}}
@php
    $accent = $options['accent'];
    $money = fn ($amount) => ecom_money($amount, $pdf);
    $plain = fn ($amount) => number_format((float) $amount, 2);
    $stampFor = fn ($order) => match (true) {
        in_array($order->status->value, ['cancelled', 'returned'], true) => ['text' => strtoupper($order->status->label()), 'color' => '#c0392b'],
        $order->payment_status->value === 'paid' => ['text' => 'PAID', 'color' => '#1e8449'],
        $order->payment_status->value === 'refunded' => ['text' => 'REFUNDED', 'color' => '#7f8c8d'],
        default => ['text' => 'UNPAID', 'color' => '#c0392b'],
    };
    $terms = collect(preg_split('~\R~', (string) $options['terms']))->map(fn ($line) => trim($line))->filter()->values();
    $currency = $pdf ? config('ecom.currency_code', 'BDT') : config('ecom.currency_symbol', '৳');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $orders->count() === 1 ? 'Invoice '.$invoices->number($orders->first()) : 'Invoices ('.$orders->count().')' }}</title>
    <style>
        @page { margin: 22px 28px 50px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, "Segoe UI", Arial, sans-serif; font-size: 10.5px; line-height: 1.4; color: #222; margin: 0; background: {{ $pdf ? '#ffffff' : '#e9ecef' }}; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .sheet { position: relative; background: #fff; }
        .page-break { page-break-after: always; }
        .muted { color: #666; }
        .small { font-size: 9px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .bold { font-weight: bold; }

        /* Header */
        .header td { vertical-align: middle; }
        .company-name { font-size: 17px; font-weight: bold; color: #111; margin-top: 4px; }
        .company-info { font-size: 9.5px; color: #444; line-height: 1.5; }
        .doc-title { font-size: 26px; font-weight: bold; letter-spacing: 3px; color: {{ $accent }}; text-align: right; }
        .doc-sub { text-align: right; font-size: 10px; color: #444; margin-top: 2px; }
        .rule { border-top: 2px solid {{ $accent }}; margin: 10px 0 12px; }

        /* Bordered boxes and grids */
        .box { border: 1px solid #c9ced4; }
        .box-title { background: #f1f3f5; border-bottom: 1px solid #c9ced4; padding: 5px 8px; font-size: 9.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; color: #333; }
        .box-body { padding: 7px 8px; font-size: 10px; line-height: 1.55; }
        .kv td { padding: 3px 8px; border-bottom: 1px solid #e3e6ea; font-size: 10px; }
        .kv tr:last-child td { border-bottom: 0; }
        .kv td.k { color: #555; width: 42%; }
        .kv td.v { font-weight: bold; }

        .grid { margin-top: 12px; }
        .grid th { background: #f1f3f5; border: 1px solid #c9ced4; padding: 6px 6px; font-size: 9px; text-transform: uppercase; letter-spacing: .2px; color: #333; white-space: nowrap; }
        .grid td { border: 1px solid #c9ced4; padding: 6px 6px; font-size: 10px; }
        .grid .item-name { font-weight: bold; color: #111; }

        .totals td { border: 1px solid #c9ced4; padding: 5px 8px; font-size: 10px; }
        .totals td.k { background: #f8f9fa; color: #444; }
        .totals tr.grand td { background: {{ $accent }}; color: #fff; font-weight: bold; font-size: 11.5px; border-color: {{ $accent }}; }
        .totals tr.due td { font-weight: bold; font-size: 11px; }

        .words { border: 1px solid #c9ced4; padding: 6px 8px; font-size: 10px; margin-top: 8px; }
        .terms { margin-top: 10px; font-size: 9px; color: #444; }
        .terms ol { margin: 3px 0 0 16px; padding: 0; }
        .terms li { margin-bottom: 1px; }

        .pill { display: inline-block; padding: 1px 7px; border-radius: 8px; font-size: 8.5px; font-weight: bold; color: #fff; }

        /* Signatures */
        .signs { margin-top: 34px; }
        .signs td { width: 33%; vertical-align: bottom; }
        .sign-line { border-top: 1px solid #555; padding-top: 3px; font-size: 9.5px; text-align: center; width: 170px; }
        .stamp { display: inline-block; border: 2px solid; border-radius: 6px; padding: 2px 12px; font-size: 18px; font-weight: bold; letter-spacing: 3px; opacity: .55; transform: rotate(-8deg); }

        .footer { margin-top: 14px; text-align: center; color: #666; font-size: 9px; border-top: 1px solid #dee2e6; padding-top: 6px; }
        .footer.fixed { position: fixed; left: 0; right: 0; bottom: -34px; margin: 0; }

        /* Browser only */
        .screen { max-width: 820px; margin: 22px auto; padding: 32px 36px; box-shadow: 0 6px 30px rgba(0, 0, 0, .12); }
        .toolbar { max-width: 820px; margin: 18px auto 0; text-align: right; font-family: system-ui, sans-serif; }
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
    @endphp
    <div class="sheet {{ $pdf ? '' : 'screen' }} {{ $loop->last ? '' : 'page-break' }}">

        {{-- Company + title --}}
        <table class="header">
            <tr>
                <td style="width:60%">
                    @if($store['logo'])<img src="{{ $store['logo'] }}" alt="" style="max-height:46px;max-width:180px"><br>@endif
                    <div class="company-name">{{ $store['name'] }}</div>
                    <div class="company-info">
                        @if($store['address']){{ $store['address'] }}<br>@endif
                        @if($store['phone'])Hotline: {{ $store['phone'] }}@endif
                        @if($store['phone'] && $store['email']) &nbsp;|&nbsp; @endif
                        @if($store['email'])Email: {{ $store['email'] }}@endif
                        @if($options['tax_number'])<br>{{ $options['tax_label'] ?: 'BIN' }}: {{ $options['tax_number'] }}@endif
                    </div>
                </td>
                <td>
                    <div class="doc-title">INVOICE</div>
                    <div class="doc-sub"><b>{{ $invoices->number($order) }}</b></div>
                    <div class="doc-sub">Date: {{ $order->created_at->format('d M Y') }}</div>
                </td>
            </tr>
        </table>
        <div class="rule"></div>

        {{-- Customer + invoice details --}}
        <table>
            <tr>
                <td style="width:49%" class="box">
                    <div class="box-title">Bill To / Ship To</div>
                    <div class="box-body">
                        <b style="font-size:11px">{{ $order->customer_name }}</b><br>
                        Phone: {{ $order->customer_phone }}<br>
                        @if($order->customer_email)Email: {{ $order->customer_email }}<br>@endif
                        Address: {{ $order->shipping_address }}@if($order->city), {{ $order->city }}@endif
                        @if($order->billing_address)
                            <br><span class="muted">Billing:</span> {{ $order->billing_address }}
                        @endif
                    </div>
                </td>
                <td style="width:2%"></td>
                <td class="box">
                    <div class="box-title">Invoice Details</div>
                    <table class="kv">
                        <tr><td class="k">Invoice No</td><td class="v">{{ $invoices->number($order) }}</td></tr>
                        <tr><td class="k">Order No</td><td class="v">{{ $order->order_number }}</td></tr>
                        <tr><td class="k">Order Date</td><td class="v">{{ $order->created_at->format('d M Y, h:i A') }}</td></tr>
                        <tr><td class="k">Payment</td><td class="v">{{ $order->payment_method->label() }} <span class="pill" style="background: {{ $stamp['color'] }}">{{ $order->payment_status->label() }}</span></td></tr>
                        <tr><td class="k">Delivery</td><td class="v">{{ $order->shippingZone?->name ?? '—' }}</td></tr>
                        @if($order->courier)
                            <tr><td class="k">Courier / Tracking</td><td class="v">{{ config("ecom.couriers.{$order->courier}.label", $order->courier) }} — {{ $order->tracking_id }}</td></tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        {{-- Items --}}
        <table class="grid">
            <thead>
                <tr>
                    <th class="center" style="width:5%">SL</th>
                    <th style="text-align:left">Product Description</th>
                    <th class="center" style="width:17%">Warranty</th>
                    <th class="center" style="width:7%">Qty</th>
                    <th class="right" style="width:15%">Unit Price ({{ $currency }})</th>
                    <th class="right" style="width:15%">Total ({{ $currency }})</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>
                            <span class="item-name">{{ $item->product_name }}</span>
                            @if($item->variant_label)<br><span class="muted small">{{ $item->variant_label }}</span>@endif
                            @if($options['show_sku'] && $item->sku)<br><span class="muted small">SKU: {{ $item->sku }}</span>@endif
                        </td>
                        <td class="center small">
                            @if($item->warranty_label)
                                {{ $item->warranty_label }}
                                @if($endsAt = $item->warrantyEndsAt())<br><span class="muted">till {{ $endsAt->format('d M Y') }}</span>@endif
                            @else
                                <span class="muted">No warranty</span>
                            @endif
                        </td>
                        <td class="center">{{ $item->quantity }}</td>
                        <td class="right">{{ $plain($item->unit_price) }}</td>
                        <td class="right bold">{{ $plain($item->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Words + payment (left) and totals (right) --}}
        <table style="margin-top:10px">
            <tr>
                <td style="width:56%;padding-right:12px">
                    <div class="words" style="margin-top:0"><b>In Words:</b> {{ $invoices->inWords($order->total) }}</div>
                    <div class="box" style="margin-top:8px">
                        <div class="box-title">Payment Information</div>
                        <table class="kv">
                            <tr><td class="k">Method</td><td class="v">{{ $order->payment_method->label() }}</td></tr>
                            @forelse($payments as $payment)
                                <tr>
                                    <td class="k">{{ $payment->type === 'refund' ? 'Refunded' : 'Paid' }} {{ $payment->created_at->format('d M Y') }}</td>
                                    <td class="v">{{ $payment->type === 'refund' ? '- ' : '' }}{{ $money($payment->amount) }}@if($payment->trx_id) <span class="muted small" style="font-weight:normal">TrxID: {{ $payment->trx_id }}</span>@endif</td>
                                </tr>
                            @empty
                                <tr><td class="k">Status</td><td class="v">{{ $order->payment_method->value === 'cod' ? 'Cash to be collected on delivery' : 'Not received yet' }}</td></tr>
                            @endforelse
                        </table>
                    </div>
                    @if($order->customer_note)
                        <div class="words"><b>Customer Note:</b> {{ $order->customer_note }}</div>
                    @endif
                    @if($options['notes'])
                        <div class="words"><b>Note:</b> {!! nl2br(e($options['notes'])) !!}</div>
                    @endif
                </td>
                <td>
                    <table class="totals">
                        <tr><td class="k">Sub Total</td><td class="right">{{ $money($order->subtotal) }}</td></tr>
                        <tr><td class="k">Discount @if($order->coupon_code)<span class="small">({{ $order->coupon_code }})</span>@endif</td><td class="right">{{ (float) $order->discount > 0 ? '- '.$money($order->discount) : $money(0) }}</td></tr>
                        <tr><td class="k">Delivery Charge</td><td class="right">{{ (float) $order->shipping_charge > 0 ? $money($order->shipping_charge) : 'Free' }}</td></tr>
                        <tr class="grand"><td>Grand Total</td><td class="right">{{ $money($order->total) }}</td></tr>
                        <tr><td class="k">Paid</td><td class="right">{{ $money($paid) }}</td></tr>
                        <tr class="due"><td class="k" style="font-weight:bold;color:#111">Due</td><td class="right">{{ $money($due) }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        @if($terms->isNotEmpty())
            <div class="terms">
                <b>Terms &amp; Conditions</b>
                <ol>
                    @foreach($terms as $term)<li>{{ $term }}</li>@endforeach
                </ol>
            </div>
        @endif

        {{-- Signatures (with the payment stamp in the middle) --}}
        <table class="signs">
            <tr>
                <td><div class="sign-line">Customer Signature<br><span class="muted small">{{ $order->customer_name }}</span></div></td>
                <td class="center"><span class="stamp" style="color: {{ $stamp['color'] }}; border-color: {{ $stamp['color'] }}">{{ $stamp['text'] }}</span></td>
                <td>
                    @if($options['signature'])
                        <div class="sign-line" style="margin-left:auto">{{ $options['signature'] }}<br><span class="muted small">{{ $store['name'] }}</span></div>
                    @endif
                </td>
            </tr>
        </table>

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
