@extends('me::master')
@section('title', $coupon->exists ? 'Edit Coupon' : 'Add Coupon')

@push('buttons')
    @component('me::components.btn.add-button', ['route' => route('ecom.coupons.index'), 'text' => 'All Coupons', 'class' => 'btn-encodex-list'])
    @endcomponent
@endpush

@section('content')
<div class="card glass-card">
    <div class="card-body">
        <form action="{{ $coupon->exists ? route('ecom.coupons.update', $coupon) : route('ecom.coupons.store') }}" method="POST" autocomplete="off">
            @csrf
            @if($coupon->exists) @method('PUT') @endif
            <div class="row">
                <div class="col-md-6">
                    @include('ecom::partials.field', ['name' => 'code', 'label' => 'Coupon Code', 'value' => $coupon->code, 'required' => true, 'icon' => 'fas fa-ticket-alt', 'help' => 'Letters, numbers, - and _ only. Saved in capitals.', 'attrs' => 'style="text-transform:uppercase"'])
                    @include('ecom::partials.field', ['name' => 'description', 'label' => 'Description', 'value' => $coupon->description])
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-primary">Discount Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select form-select-sm">
                                    <option value="percent" @selected(old('type', $coupon->type) === 'percent')>Percentage (%)</option>
                                    <option value="fixed" @selected(old('type', $coupon->type) === 'fixed')>Fixed amount ({{ config('ecom.currency_symbol') }})</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">@include('ecom::partials.field', ['name' => 'value', 'label' => 'Value', 'value' => $coupon->value, 'type' => 'number', 'step' => '0.01', 'required' => true])</div>
                    </div>
                    <div class="row">
                        <div class="col-6">@include('ecom::partials.field', ['name' => 'min_order_amount', 'label' => 'Minimum Order Amount', 'value' => $coupon->min_order_amount, 'type' => 'number', 'step' => '0.01'])</div>
                        <div class="col-6">@include('ecom::partials.field', ['name' => 'max_discount', 'label' => 'Max Discount (for %)', 'value' => $coupon->max_discount, 'type' => 'number', 'step' => '0.01'])</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-6">@include('ecom::partials.field', ['name' => 'starts_at', 'label' => 'Starts At', 'value' => $coupon->starts_at?->format('Y-m-d\TH:i'), 'type' => 'datetime-local'])</div>
                        <div class="col-6">@include('ecom::partials.field', ['name' => 'expires_at', 'label' => 'Expiry Date', 'value' => $coupon->expires_at?->format('Y-m-d\TH:i'), 'type' => 'datetime-local'])</div>
                    </div>
                    <div class="row">
                        <div class="col-6">@include('ecom::partials.field', ['name' => 'usage_limit', 'label' => 'Total Usage Limit', 'value' => $coupon->usage_limit, 'type' => 'number', 'help' => 'Empty = unlimited'])</div>
                        <div class="col-6">@include('ecom::partials.field', ['name' => 'usage_limit_per_customer', 'label' => 'Limit per Customer', 'value' => $coupon->usage_limit_per_customer, 'type' => 'number', 'help' => 'Empty = unlimited'])</div>
                    </div>
                    @if($coupon->exists)<p class="small text-muted">Used {{ $coupon->used_count }} times so far.</p>@endif
                    @include('ecom::partials.switch', ['name' => 'is_active', 'label' => 'Active', 'checked' => $coupon->is_active])
                </div>
            </div>
            <div class="text-end"><button type="submit" class="btn btn-encodex-save"><i class="fas fa-save me-1"></i> Save</button></div>
        </form>
    </div>
</div>
@endsection
