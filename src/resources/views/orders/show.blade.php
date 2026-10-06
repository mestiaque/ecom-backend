@extends('me::master')
@section('title', 'Order ' . $order->order_number)

@push('buttons')
    @component('me::components.btn.add-button', ['route' => route('ecom.orders.index'), 'text' => 'All Orders', 'class' => 'btn-encodex-list'])
    @endcomponent
    @if(can('ecom_order.invoice'))
        <a href="{{ route('ecom.orders.invoice', $order) }}" target="_blank" class="btn btn-sm btn-secondary shadow-sm"><i class="fas fa-print me-1"></i>Print Invoice</a>
        <a download href="{{ route('ecom.orders.invoice-pdf', $order) }}" class="no-loader btn btn-sm btn-danger shadow-sm"><i class="fas fa-file-pdf me-1"></i>PDF</a>
    @endif
@endpush

@php($due = max(0, (float) $order->total - $paid))

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        {{-- Summary --}}
        <div class="card glass-card mb-3">
            <div class="card-body d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <div>
                    <div class="small text-muted">Placed {{ $order->created_at->format('d M Y, h:i A') }}</div>
                    <h5 class="fw-bold mb-0">{{ $order->order_number }}</h5>
                </div>
                <div class="text-center"><div class="small text-muted">Status</div>@include('ecom::partials.status-badge', ['status' => $order->status])</div>
                <div class="text-center"><div class="small text-muted">Payment</div>{{ $order->payment_method->label() }} @include('ecom::partials.status-badge', ['status' => $order->payment_status])</div>
                <div class="text-center"><div class="small text-muted">Total</div><b class="fs-5">{{ ecom_money($order->total) }}</b></div>
                <div class="text-center"><div class="small text-muted">Due</div><b class="fs-5 {{ $due > 0 ? 'text-danger' : 'text-success' }}">{{ ecom_money($due) }}</b></div>
            </div>
        </div>

        {{-- Items --}}
        <div class="card glass-card mb-3">
            <div class="card-header fw-semibold"><i class="fas fa-box me-1"></i> Items</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light"><tr><th class="ps-3">Product</th><th class="text-end">Price</th><th class="text-center">Qty</th><th class="text-end pe-3">Total</th></tr></thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            @if($item->product?->thumbnail)<img src="{{ $item->product->thumbnail }}" class="rounded" style="width:40px;height:40px;object-fit:cover" alt="">@endif
                                            <div>
                                                @if($item->product)<a href="{{ route('ecom.products.show', $item->product) }}">{{ $item->product_name }}</a>@else{{ $item->product_name }}@endif
                                                <div class="small text-muted">{{ $item->variant_label }} @if($item->sku) · SKU {{ $item->sku }} @endif</div>
                                                @if($item->warranty_label)
                                                    @php($endsAt = $item->warrantyEndsAt())
                                                    <div class="small {{ $endsAt && $endsAt->isPast() ? 'text-muted' : 'text-success' }}">
                                                        <i class="fas fa-shield-alt"></i> {{ $item->warranty_label }}
                                                        @if($endsAt) · {{ $endsAt->isPast() ? 'expired' : 'valid until' }} {{ $endsAt->format('d M Y') }} @else · starts on delivery @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">{{ ecom_money($item->unit_price) }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end pe-3">{{ ecom_money($item->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="small">
                            <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end pe-3">{{ ecom_money($order->subtotal) }}</td></tr>
                            @if((float) $order->discount > 0)
                                <tr><td colspan="3" class="text-end">Discount @if($order->coupon_code)<span class="badge bg-info-subtle text-info">{{ $order->coupon_code }}</span>@endif</td><td class="text-end pe-3 text-danger">− {{ ecom_money($order->discount) }}</td></tr>
                            @endif
                            <tr><td colspan="3" class="text-end">Delivery Charge @if($order->shippingZone)<span class="text-muted">({{ $order->shippingZone->name }})</span>@endif @if((float) $order->shipping_discount > 0)<span class="badge bg-success-subtle text-success">{{ ecom_money($order->shipping_discount) }} off</span>@endif</td><td class="text-end pe-3">{{ ecom_money($order->shipping_charge) }}</td></tr>
                            <tr class="fw-bold fs-6"><td colspan="3" class="text-end">Total</td><td class="text-end pe-3">{{ ecom_money($order->total) }}</td></tr>
                            <tr><td colspan="3" class="text-end">Paid</td><td class="text-end pe-3 text-success">{{ ecom_money($paid) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Payments --}}
        <div class="card glass-card mb-3">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="fas fa-money-bill-wave me-1"></i> Payments</span>
                <div class="d-flex gap-1">
                    @if(can('ecom_payment.record') && $due > 0)
                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal"><i class="fas fa-plus"></i> Record Payment</button>
                    @endif
                    @if(can('ecom_payment.refund') && $paid > 0)
                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#refundModal"><i class="fas fa-undo"></i> Refund</button>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0 small">
                    <thead class="table-light"><tr><th class="ps-3">Date</th><th>Type</th><th>Method</th><th>Trx ID</th><th class="text-end">Amount</th><th>Note</th></tr></thead>
                    <tbody>
                        @forelse($order->transactions as $trx)
                            <tr>
                                <td class="ps-3 text-nowrap">{{ $trx->created_at->format('d M Y h:i A') }}</td>
                                <td><span class="badge {{ $trx->type === 'refund' ? 'bg-danger' : 'bg-success' }}">{{ ucfirst($trx->type) }}</span> @if($trx->status !== 'success')<span class="badge bg-warning text-dark">{{ $trx->status }}</span>@endif</td>
                                <td>{{ \ME\Ecom\Enums\PaymentMethod::tryFrom($trx->method)?->label() ?? $trx->method }}</td>
                                <td>{{ $trx->trx_id ?: '—' }}</td>
                                <td class="text-end">{{ ecom_money($trx->amount) }}</td>
                                <td>{{ $trx->note }} @if($trx->user)<span class="text-muted">— {{ $trx->user->name }}</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">No payments yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Timeline --}}
        <div class="card glass-card mb-3">
            <div class="card-header fw-semibold"><i class="fas fa-history me-1"></i> Status History & Comments</div>
            <div class="card-body">
                @if(can('ecom_order.note'))
                    <form action="{{ route('ecom.orders.notes', $order) }}" method="POST" class="mb-3">
                        @csrf
                        <div class="input-group input-group-sm">
                            <input type="text" name="note" class="form-control" placeholder="Add an admin comment (only staff can see it)" required maxlength="2000">
                            <button class="btn btn-primary"><i class="fas fa-comment"></i> Add</button>
                        </div>
                    </form>
                @endif
                <ul class="list-unstyled mb-0">
                    @forelse($order->notes as $note)
                        <li class="d-flex gap-2 mb-3">
                            <div>
                                @if($note->status)
                                    <span class="badge rounded-circle bg-{{ $note->status->color() }} p-2"><i class="{{ $note->status->icon() }}"></i></span>
                                @else
                                    <span class="badge rounded-circle bg-light text-secondary p-2 border"><i class="fas fa-comment"></i></span>
                                @endif
                            </div>
                            <div>
                                <div class="small">
                                    @if($note->status)<b>{{ $note->status->label() }}</b>@else<b>Comment</b>@endif
                                    <span class="text-muted">· {{ $note->created_at->format('d M Y, h:i A') }} · {{ $note->user?->name ?? 'System' }}</span>
                                </div>
                                @if($note->note)<div>{{ $note->note }}</div>@endif
                            </div>
                        </li>
                    @empty
                        <li class="text-muted small">No history yet</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Status change --}}
        @if(can('ecom_order.status'))
            <div class="card glass-card mb-3">
                <div class="card-header fw-semibold"><i class="fas fa-exchange-alt me-1"></i> Update Status</div>
                <div class="card-body">
                    @php($next = $order->status->allowedNext())
                    @if($next)
                        <form action="{{ route('ecom.orders.status', $order) }}" method="POST">
                            @csrf @method('PATCH')
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                @foreach($next as $status)
                                    <input type="radio" class="btn-check" name="status" id="st_{{ $status->value }}" value="{{ $status->value }}" @checked($loop->first) required>
                                    <label class="btn btn-sm btn-outline-{{ $status->color() }}" for="st_{{ $status->value }}"><i class="{{ $status->icon() }} me-1"></i>{{ $status->label() }}</label>
                                @endforeach
                            </div>
                            <textarea name="note" rows="2" class="form-control form-control-sm mb-2" placeholder="Note (optional)"></textarea>
                            <button class="btn btn-sm btn-encodex-save w-100" onclick="return this.form.status.value === 'cancelled' || this.form.status.value === 'returned' ? confirm('Stock will be added back. Continue?') : true">
                                <i class="fas fa-check me-1"></i> Update
                            </button>
                        </form>
                        <div class="small text-muted mt-2">Flow: Pending → Confirmed → Processing → Shipped → Delivered. Cancel before shipping, return after.</div>
                    @else
                        <div class="text-muted small">This order is {{ strtolower($order->status->label()) }}; its status can no longer change.</div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Customer --}}
        <div class="card glass-card mb-3">
            <div class="card-header fw-semibold"><i class="fas fa-user me-1"></i> Customer & Delivery</div>
            <div class="card-body small">
                <div class="fw-semibold fs-6">
                    @if($order->customer && can('ecom_customer.view'))
                        <a href="{{ route('ecom.customers.show', $order->customer) }}">{{ $order->customer_name }}</a>
                    @else
                        {{ $order->customer_name }}
                    @endif
                    @if($order->customer?->is_blocked)<span class="badge bg-danger">Blocked</span>@endif
                </div>
                <div><i class="fas fa-phone fa-fw text-muted"></i> <a href="tel:{{ $order->customer_phone }}">{{ $order->customer_phone }}</a></div>
                @if($order->customer_email)<div><i class="fas fa-envelope fa-fw text-muted"></i> {{ $order->customer_email }}</div>@endif
                <div class="mt-2"><i class="fas fa-map-marker-alt fa-fw text-muted"></i> {{ $order->shipping_address }}@if($order->city), {{ $order->city }}@endif</div>
                @if($order->billing_address)<div class="mt-1"><i class="fas fa-file-invoice fa-fw text-muted"></i> <span class="text-muted">Billing:</span> {{ $order->billing_address }}</div>@endif
                @if($order->shippingZone)<div><i class="fas fa-truck fa-fw text-muted"></i> {{ $order->shippingZone->name }}</div>@endif
                @if($order->customer_note)
                    <div class="alert alert-warning py-1 px-2 mt-2 mb-0"><b>Customer note:</b> {{ $order->customer_note }}</div>
                @endif
            </div>
        </div>

        {{-- Customer tracking link --}}
        <div class="card glass-card mb-3">
            <div class="card-header fw-semibold"><i class="fas fa-link me-1"></i> Customer Tracking Link</div>
            <div class="card-body small">
                @php($trackingLink = $order->trackingPageUrl())
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" value="{{ $trackingLink }}" id="trackingLink" readonly>
                    <button class="btn btn-outline-secondary" type="button" title="Copy"
                        onclick="navigator.clipboard.writeText(document.getElementById('trackingLink').value).then(() => toastr.success('Tracking link copied'))"><i class="fas fa-copy"></i></button>
                    <a href="{{ $trackingLink }}" target="_blank" class="btn btn-outline-primary" title="Open"><i class="fas fa-external-link-alt"></i></a>
                </div>
                <div class="text-muted mt-1">Send it by SMS / WhatsApp — valid for 30 days. Customers can also look up the order at
                    <a href="{{ route('ecom.track.form') }}" target="_blank">{{ route('ecom.track.form') }}</a> with the order number and phone.</div>
            </div>
        </div>

        {{-- Courier --}}
        <div class="card glass-card mb-3">
            <div class="card-header fw-semibold"><i class="fas fa-shipping-fast me-1"></i> Courier</div>
            <div class="card-body small">
                @if($order->courier)
                    <div><b>{{ config("ecom.couriers.{$order->courier}.label", $order->courier) }}</b></div>
                    <div>Tracking: <b>{{ $order->tracking_id }}</b>
                        @if($url = $order->trackingUrl())<a href="{{ $url }}" target="_blank" class="ms-1"><i class="fas fa-external-link-alt"></i> Track</a>@endif
                    </div>
                    @if($order->consignment_id && $order->consignment_id !== $order->tracking_id)<div>Consignment: {{ $order->consignment_id }}</div>@endif
                    <div class="text-muted">Sent {{ $order->sent_to_courier_at?->format('d M Y, h:i A') }}</div>
                @else
                    <div class="text-muted mb-2">Not sent to a courier yet.</div>
                @endif
                @if(can('ecom_order.courier') && ! $order->status->releasesStock())
                    <button class="btn btn-sm btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#courierModal">
                        <i class="fas fa-paper-plane"></i> {{ $order->courier ? 'Change courier' : 'Send to courier' }}
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Courier modal --}}
@if(can('ecom_order.courier'))
<div class="modal fade" id="courierModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('ecom.orders.courier', $order) }}">
            @csrf
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-shipping-fast me-1"></i> Send to Courier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Courier</label>
                    <select name="courier" class="form-select form-select-sm" id="courierSelect">
                        @foreach($couriers as $key => $courier)
                            <option value="{{ $key }}" data-configured="{{ $courier->isConfigured() ? 1 : 0 }}">{{ $courier->label() }}{{ $courier->isConfigured() ? '' : ' (API not configured)' }}</option>
                        @endforeach
                        <option value="manual" data-configured="0">Other courier</option>
                    </select>
                </div>
                <div class="mb-2">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="mode" id="modeApi" value="api" checked>
                        <label class="form-check-label small" for="modeApi">Book through API</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="mode" id="modeManual" value="manual">
                        <label class="form-check-label small" for="modeManual">Already booked — enter tracking ID</label>
                    </div>
                </div>
                <div class="js-manual-name mb-2" style="display:none">
                    <input type="text" name="manual_courier" class="form-control form-control-sm" placeholder="Courier name (e.g. Sundarban)">
                </div>
                <div class="js-manual mb-2" style="display:none">
                    <input type="text" name="tracking_id" class="form-control form-control-sm" placeholder="Tracking ID">
                </div>
                <div class="js-api">
                    @foreach($couriers as $key => $courier)
                        <div class="js-options" data-courier="{{ $key }}" style="display:none">
                            @foreach($courier->orderFields() as $field => $meta)
                                <div class="mb-2">
                                    <label class="form-label small mb-0">{{ $meta['label'] }} @if($meta['required'])<span class="text-danger">*</span>@endif</label>
                                    <input type="text" name="options[{{ $field }}]" class="form-control form-control-sm" data-required="{{ $meta['required'] ? 1 : 0 }}" disabled>
                                    @isset($meta['help'])<small class="text-muted">{{ $meta['help'] }}</small>@endisset
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                    <input type="text" name="options[note]" class="form-control form-control-sm" placeholder="Note for the rider (optional)" value="{{ $order->customer_note }}">
                    <div class="small text-muted mt-2">Cash to collect: <b>{{ ecom_money($due) }}</b></div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-sm btn-encodex-save"><i class="fas fa-paper-plane me-1"></i> Submit</button></div>
        </form>
    </div>
</div>
@endif

{{-- Payment modal --}}
@if(can('ecom_payment.record'))
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('ecom.orders.payments', $order) }}">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Record Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label small mb-0">Method</label>
                <select name="method" class="form-select form-select-sm mb-2">
                    @foreach(\ME\Ecom\Enums\PaymentMethod::cases() as $method)
                        <option value="{{ $method->value }}" @selected($order->payment_method === $method)>{{ $method->label() }}</option>
                    @endforeach
                </select>
                <label class="form-label small mb-0">Amount</label>
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ $due }}" class="form-control form-control-sm mb-2" required>
                <label class="form-label small mb-0">Transaction ID</label>
                <input type="text" name="trx_id" class="form-control form-control-sm mb-2" placeholder="bKash / Nagad / card trx id">
                <label class="form-label small mb-0">Note</label>
                <input type="text" name="note" class="form-control form-control-sm">
            </div>
            <div class="modal-footer"><button class="btn btn-sm btn-success"><i class="fas fa-save me-1"></i> Save</button></div>
        </form>
    </div>
</div>
@endif

@if(can('ecom_payment.refund'))
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('ecom.orders.refunds', $order) }}">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Refund</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label small mb-0">Amount (max {{ ecom_money($paid) }})</label>
                <input type="number" step="0.01" min="0.01" max="{{ $paid }}" name="amount" value="{{ $paid }}" class="form-control form-control-sm mb-2" required>
                <label class="form-label small mb-0">Refunded via</label>
                <select name="method" class="form-select form-select-sm mb-2">
                    @foreach(\ME\Ecom\Enums\PaymentMethod::cases() as $method)
                        <option value="{{ $method->value }}" @selected($order->payment_method === $method)>{{ $method->label() }}</option>
                    @endforeach
                </select>
                <label class="form-label small mb-0">Transaction ID</label>
                <input type="text" name="trx_id" class="form-control form-control-sm mb-2">
                <label class="form-label small mb-0">Reason</label>
                <input type="text" name="note" class="form-control form-control-sm">
            </div>
            <div class="modal-footer"><button class="btn btn-sm btn-danger" onclick="return confirm('Record this refund?')"><i class="fas fa-undo me-1"></i> Refund</button></div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('courierModal');
    if (!modal) return;
    const select = modal.querySelector('#courierSelect');

    function refresh() {
        const option = select.selectedOptions[0];
        const isOther = select.value === 'manual';
        if (isOther || option.dataset.configured !== '1') modal.querySelector('#modeManual').checked = true;
        modal.querySelector('#modeApi').disabled = isOther || option.dataset.configured !== '1';
        const manual = modal.querySelector('#modeManual').checked;

        modal.querySelector('.js-manual').style.display = manual ? '' : 'none';
        modal.querySelector('.js-manual-name').style.display = isOther ? '' : 'none';
        modal.querySelector('.js-api').style.display = manual ? 'none' : '';
        modal.querySelector('[name=tracking_id]').required = manual;
        modal.querySelector('[name=manual_courier]').required = isOther;
        modal.querySelectorAll('.js-options').forEach(function (box) {
            const active = !manual && box.dataset.courier === select.value;
            box.style.display = active ? '' : 'none';
            box.querySelectorAll('input').forEach(function (input) {
                input.disabled = !active;
                input.required = active && input.dataset.required === '1';
            });
        });
    }

    select.addEventListener('change', refresh);
    modal.querySelectorAll('[name=mode]').forEach(radio => radio.addEventListener('change', refresh));
    refresh();
});
</script>
@endpush
