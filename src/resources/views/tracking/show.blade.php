@extends('ecom::tracking.layout')
@section('title', 'Order ' . $order->order_number)

@php
    $status = $order->status;
    $stopped = in_array($status, [\ME\Ecom\Enums\OrderStatus::Cancelled, \ME\Ecom\Enums\OrderStatus::Returned], true);
    // For a cancelled/returned order, show progress up to the last normal step it reached
    $reachedIndex = collect($steps)->keys()->filter(fn ($i) => $reachedAt->has($steps[$i]->value))->max() ?? 0;
    $currentIndex = $stopped ? $reachedIndex : array_search($status, $steps, true);
    $progress = count($steps) > 1 ? $currentIndex / (count($steps) - 1) * 80 : 0;
    $labels = ['pending' => 'Order placed', 'confirmed' => 'Confirmed', 'processing' => 'Packed', 'shipped' => 'On the way', 'delivered' => 'Delivered'];
    $phone = $order->customer_phone;
    $maskedPhone = strlen($phone) > 6 ? substr($phone, 0, 3) . str_repeat('•', strlen($phone) - 6) . substr($phone, -3) : $phone;
@endphp

@section('content')
<div class="card ec-card mb-3">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <div class="text-muted small">Order</div>
                <h4 class="fw-bold mb-0">{{ $order->order_number }}</h4>
                <div class="text-muted small">Placed {{ $order->created_at->format('d M Y, h:i A') }}</div>
            </div>
            <span class="badge fs-6 bg-{{ $status->color() }}">{{ $labels[$status->value] ?? $status->label() }}</span>
        </div>

        @if($status === \ME\Ecom\Enums\OrderStatus::Cancelled)
            <div class="alert alert-danger mb-3"><i class="fas fa-times-circle me-1"></i> This order was cancelled{{ $order->cancelled_at ? ' on ' . $order->cancelled_at->format('d M Y') : '' }}. Any payment made will be refunded.</div>
        @elseif($status === \ME\Ecom\Enums\OrderStatus::Returned)
            <div class="alert alert-secondary mb-3"><i class="fas fa-undo me-1"></i> This order was returned. Any payment made will be refunded.</div>
        @elseif($status === \ME\Ecom\Enums\OrderStatus::Delivered)
            <div class="alert alert-success mb-3"><i class="fas fa-check-circle me-1"></i> Delivered on {{ $order->delivered_at?->format('d M Y, h:i A') }}. Thank you for shopping with us!</div>
        @endif

        <div class="ec-steps">
            <div class="ec-progress" style="width: {{ $progress }}%; {{ $stopped ? 'background:#9ca3af' : '' }}"></div>
            @foreach($steps as $i => $step)
                <div class="ec-step {{ $i <= $currentIndex ? 'done' : '' }} {{ $i === $currentIndex && ! $stopped ? 'current' : '' }}">
                    <div class="ec-dot"><i class="{{ $step->icon() }}"></i></div>
                    <div class="ec-label">{{ $labels[$step->value] }}</div>
                    <div class="ec-time">{{ $reachedAt->has($step->value) ? $reachedAt[$step->value]->format('d M, h:i A') : '' }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card ec-card h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="fas fa-box me-1 text-success"></i> Items</h6>
                @foreach($order->items as $item)
                    <div class="d-flex gap-3 mb-3">
                        @if($item->product?->thumbnail)
                            <img src="{{ $item->product->thumbnail }}" alt="" class="rounded border" style="width:56px;height:56px;object-fit:cover">
                        @else
                            <div class="rounded border bg-light d-flex align-items-center justify-content-center" style="width:56px;height:56px"><i class="fas fa-box text-muted"></i></div>
                        @endif
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $item->product_name }}</div>
                            @if($item->variant_label)<div class="small text-muted">{{ $item->variant_label }}</div>@endif
                            <div class="small text-muted">Qty {{ $item->quantity }} × {{ ecom_money($item->unit_price) }}</div>
                            @if($item->warranty_label)
                                @php($endsAt = $item->warrantyEndsAt())
                                <div class="small {{ $endsAt && $endsAt->isPast() ? 'text-muted' : 'text-success' }}">
                                    <i class="fas fa-shield-alt"></i> {{ $item->warranty_label }}
                                    @if($endsAt) — {{ $endsAt->isPast() ? 'expired on' : 'valid until' }} {{ $endsAt->format('d M Y') }} @else — starts when delivered @endif
                                </div>
                            @endif
                        </div>
                        <div class="fw-semibold text-nowrap">{{ ecom_money($item->line_total) }}</div>
                    </div>
                @endforeach
                <hr>
                <div class="d-flex justify-content-between small"><span>Subtotal</span><span>{{ ecom_money($order->subtotal) }}</span></div>
                @if((float) $order->discount > 0)<div class="d-flex justify-content-between small text-danger"><span>Discount</span><span>− {{ ecom_money($order->discount) }}</span></div>@endif
                <div class="d-flex justify-content-between small"><span>Delivery charge</span><span>{{ ecom_money($order->shipping_charge) }}</span></div>
                <div class="d-flex justify-content-between fw-bold fs-5 mt-1"><span>Total</span><span>{{ ecom_money($order->total) }}</span></div>
                <div class="d-flex justify-content-between small mt-1">
                    <span>{{ $order->payment_method->label() }}</span>
                    <span class="badge bg-{{ $order->payment_status->color() }}">{{ $order->payment_status->label() }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card ec-card mb-3">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="fas fa-truck me-1 text-success"></i> Delivery</h6>
                @if($order->courier)
                    <div class="small text-muted">Courier</div>
                    <div class="fw-semibold">{{ config("ecom.couriers.{$order->courier}.label", $order->courier) }}</div>
                    <div class="small text-muted mt-2">Tracking ID</div>
                    <div class="fw-semibold font-monospace">{{ $order->tracking_id }}</div>
                    @if($url = $order->trackingUrl())
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success mt-2"><i class="fas fa-external-link-alt me-1"></i>Track on courier website</a>
                    @endif
                @else
                    <div class="small text-muted">Your parcel has not been handed to the courier yet. You will get the tracking ID by SMS.</div>
                @endif
                <hr>
                <div class="small">
                    <div class="fw-semibold">{{ $order->customer_name }}</div>
                    <div class="text-muted">{{ $maskedPhone }}</div>
                    <div class="text-muted">{{ collect([$order->city, $order->shippingZone?->name])->filter()->implode(' · ') }}</div>
                    @if($order->shippingZone?->delivery_time && ! $stopped && $status !== \ME\Ecom\Enums\OrderStatus::Delivered)
                        <div class="mt-1"><i class="far fa-clock"></i> Usually delivered in {{ $order->shippingZone->delivery_time }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card ec-card">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="fas fa-history me-1 text-success"></i> History</h6>
                <ul class="list-unstyled small mb-0">
                    @foreach($history->reverse() as $entry)
                        <li class="d-flex justify-content-between mb-2">
                            <span><i class="{{ $entry->status->icon() }} text-{{ $entry->status->color() }} me-1"></i>{{ $labels[$entry->status->value] ?? $entry->status->label() }}</span>
                            <span class="text-muted">{{ $entry->created_at->format('d M Y, h:i A') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
