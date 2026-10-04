@extends('me::master')
@section('title', 'Shipping')

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card glass-card">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="fas fa-map-marked-alt me-1"></i> Delivery Zones & Charges</span>
                <button class="btn btn-sm btn-encodex-create text-white" data-bs-toggle="modal" data-bs-target="#zoneModal" data-zone="">
                    <i class="fas fa-plus"></i> Add Zone
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light"><tr><th class="ps-3">Zone</th><th class="text-end">Charge</th><th>Delivery Time</th><th class="text-center">Orders</th><th class="text-center">Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($zones as $zone)
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $zone->name }}</td>
                                <td class="text-end">{{ ecom_money($zone->charge) }}</td>
                                <td>{{ $zone->delivery_time ?: '—' }}</td>
                                <td class="text-center">{{ $zone->orders_count }}</td>
                                <td class="text-center">@include('ecom::partials.active-badge', ['active' => $zone->is_active])</td>
                                <td class="text-end pe-3 text-nowrap">
                                    <button class="btn btn-sm btn-encodex-edit" data-bs-toggle="modal" data-bs-target="#zoneModal"
                                        data-zone="{{ json_encode($zone->only(['id', 'name', 'charge', 'delivery_time', 'sort_order', 'is_active'])) }}"><i class="fas fa-edit"></i></button>
                                    @include('ecom::partials.actions', ['delete' => route('ecom.shipping.zones.destroy', $zone)])
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No zones yet. Add e.g. "Inside Dhaka" and "Outside Dhaka".</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card glass-card mt-3">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="fas fa-percent me-1"></i> Delivery Discounts by Order Amount</span>
                <button class="btn btn-sm btn-encodex-create text-white" data-bs-toggle="modal" data-bs-target="#discountModal" data-discount="">
                    <i class="fas fa-plus"></i> Add Rule
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light"><tr><th class="ps-3">Order amount</th><th>Discount</th><th>Zone</th><th class="text-center">Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($discounts as $discount)
                            <tr>
                                <td class="ps-3 fw-semibold">{{ ecom_money($discount->min_order_amount) }} or more</td>
                                <td>{{ $discount->label }}</td>
                                <td>{{ $discount->zone?->name ?? 'All zones' }}</td>
                                <td class="text-center">@include('ecom::partials.active-badge', ['active' => $discount->is_active])</td>
                                <td class="text-end pe-3 text-nowrap">
                                    <button class="btn btn-sm btn-encodex-edit" data-bs-toggle="modal" data-bs-target="#discountModal"
                                        data-discount="{{ json_encode($discount->only(['id', 'shipping_zone_id', 'min_order_amount', 'type', 'value', 'is_active'])) }}"><i class="fas fa-edit"></i></button>
                                    @include('ecom::partials.actions', ['delete' => route('ecom.shipping.discounts.destroy', $discount)])
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No rules yet. Add e.g. "{{ config('ecom.currency_symbol') }}2000 or more → Free delivery".</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer small text-muted">When several rules match, the one with the highest order amount is used (a zone's own rule wins a tie).</div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card glass-card mb-3">
            <div class="card-header fw-semibold"><i class="fas fa-calculator me-1"></i> How delivery is calculated</div>
            <div class="card-body small">
                <ol class="ps-3 mb-0">
                    <li class="mb-1"><b>Zone charge</b> of the area the customer picks.</li>
                    <li class="mb-1"><b>+ product extra / less charge</b> × quantity (Product → Delivery).</li>
                    <li class="mb-1"><b>Free</b> when every product in the order has <i>Free delivery</i>.</li>
                    <li><b>− order-amount discount</b> from the rules on the left.</li>
                </ol>
            </div>
        </div>
        <div class="card glass-card">
            <div class="card-header fw-semibold"><i class="fas fa-boxes me-1"></i> Stock</div>
            <div class="card-body">
                <form action="{{ route('ecom.shipping.settings') }}" method="POST">
                    @csrf @method('PUT')
                    @include('ecom::partials.field', ['name' => 'low_stock_threshold', 'label' => 'Default low-stock alert level', 'value' => $settings->get('low_stock_threshold', config('ecom.low_stock_threshold')), 'type' => 'number', 'help' => 'Used for products without their own level'])
                    <button class="btn btn-encodex-save w-100"><i class="fas fa-save me-1"></i> Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="discountModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('ecom.shipping.discounts.store') }}" id="discountForm">
            @csrf
            <input type="hidden" name="_method" value="POST" id="discountMethod">
            <div class="modal-header"><h5 class="modal-title" id="discountTitle">Add Delivery Discount</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label small mb-0">When order subtotal is at least *</label>
                <input type="number" step="0.01" min="0" name="min_order_amount" class="form-control form-control-sm mb-2" placeholder="2000" required>
                <label class="form-label small mb-0">Discount *</label>
                <select name="type" class="form-select form-select-sm mb-2" id="discountType">
                    @foreach(\ME\Ecom\Models\ShippingDiscount::TYPES as $type => $label)
                        <option value="{{ $type }}">{{ $label }}</option>
                    @endforeach
                </select>
                <div id="discountValueBox">
                    <label class="form-label small mb-0" id="discountValueLabel">Amount</label>
                    <input type="number" step="0.01" min="0" name="value" class="form-control form-control-sm mb-2">
                </div>
                <label class="form-label small mb-0">Zone</label>
                <select name="shipping_zone_id" class="form-select form-select-sm mb-2">
                    <option value="">All zones</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                    @endforeach
                </select>
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="discountActive" checked>
                    <label class="form-check-label" for="discountActive">Active</label>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-sm btn-encodex-save"><i class="fas fa-save me-1"></i> Save</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="zoneModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('ecom.shipping.zones.store') }}" id="zoneForm">
            @csrf
            <input type="hidden" name="_method" value="POST" id="zoneMethod">
            <div class="modal-header"><h5 class="modal-title" id="zoneTitle">Add Zone</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label small mb-0">Zone name *</label>
                <input type="text" name="name" class="form-control form-control-sm mb-2" placeholder="Inside Dhaka" required>
                <label class="form-label small mb-0">Delivery charge *</label>
                <input type="number" step="0.01" min="0" name="charge" class="form-control form-control-sm mb-2" required>
                <label class="form-label small mb-0">Delivery time</label>
                <input type="text" name="delivery_time" class="form-control form-control-sm mb-2" placeholder="1-2 days">
                <label class="form-label small mb-0">Sort order</label>
                <input type="number" min="0" name="sort_order" class="form-control form-control-sm mb-2" value="0">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="zoneActive" checked>
                    <label class="form-check-label" for="zoneActive">Active</label>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-sm btn-encodex-save"><i class="fas fa-save me-1"></i> Save</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('zoneModal').addEventListener('show.bs.modal', function (event) {
    const raw = event.relatedTarget.dataset.zone;
    const zone = raw ? JSON.parse(raw) : null;
    const form = document.getElementById('zoneForm');
    form.action = zone ? @json(route('ecom.shipping.zones.update', '__id__')).replace('__id__', zone.id) : @json(route('ecom.shipping.zones.store'));
    document.getElementById('zoneMethod').value = zone ? 'PUT' : 'POST';
    document.getElementById('zoneTitle').textContent = zone ? 'Edit Zone' : 'Add Zone';
    form.name.value = zone ? zone.name : '';
    form.charge.value = zone ? zone.charge : '';
    form.delivery_time.value = zone ? (zone.delivery_time || '') : '';
    form.sort_order.value = zone ? zone.sort_order : 0;
    document.getElementById('zoneActive').checked = zone ? !!zone.is_active : true;
});

const discountModal = document.getElementById('discountModal');
const toggleDiscountValue = function () {
    const type = document.getElementById('discountType').value;
    document.getElementById('discountValueBox').style.display = type === 'free' ? 'none' : '';
    document.getElementById('discountValueLabel').textContent = type === 'percent' ? 'Percent off (0–100)' : 'Amount off';
};
document.getElementById('discountType').addEventListener('change', toggleDiscountValue);
discountModal.addEventListener('show.bs.modal', function (event) {
    const raw = event.relatedTarget.dataset.discount;
    const rule = raw ? JSON.parse(raw) : null;
    const form = document.getElementById('discountForm');
    form.action = rule ? @json(route('ecom.shipping.discounts.update', '__id__')).replace('__id__', rule.id) : @json(route('ecom.shipping.discounts.store'));
    document.getElementById('discountMethod').value = rule ? 'PUT' : 'POST';
    document.getElementById('discountTitle').textContent = rule ? 'Edit Delivery Discount' : 'Add Delivery Discount';
    form.min_order_amount.value = rule ? rule.min_order_amount : '';
    form.type.value = rule ? rule.type : 'free';
    form.value.value = rule && rule.type !== 'free' ? rule.value : '';
    form.shipping_zone_id.value = rule && rule.shipping_zone_id ? rule.shipping_zone_id : '';
    document.getElementById('discountActive').checked = rule ? !!rule.is_active : true;
    toggleDiscountValue();
});
</script>
@endpush
