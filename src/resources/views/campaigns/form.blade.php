@extends('me::master')
@section('title', $campaign->exists ? 'Edit Campaign' : 'Add Campaign')

@push('buttons')
    @component('me::components.btn.add-button', ['route' => route('ecom.campaigns.index'), 'text' => 'All Campaigns', 'class' => 'btn-encodex-list'])
    @endcomponent
@endpush

@section('content')
<div class="card glass-card">
    <div class="card-body">
        <form action="{{ $campaign->exists ? route('ecom.campaigns.update', $campaign) : route('ecom.campaigns.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if($campaign->exists) @method('PUT') @endif
            <div class="row">
                <div class="col-md-6">
                    @include('ecom::partials.field', ['name' => 'title', 'label' => 'Title', 'value' => $campaign->title, 'required' => true, 'icon' => 'fas fa-bolt'])
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-primary">Discount Type</label>
                                <select name="discount_type" class="form-select form-select-sm">
                                    <option value="percent" @selected(old('discount_type', $campaign->discount_type) === 'percent')>Percentage (%)</option>
                                    <option value="fixed" @selected(old('discount_type', $campaign->discount_type) === 'fixed')>Fixed amount ({{ config('ecom.currency_symbol') }})</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">@include('ecom::partials.field', ['name' => 'discount_value', 'label' => 'Discount', 'value' => $campaign->discount_value, 'type' => 'number', 'step' => '0.01', 'required' => true])</div>
                    </div>
                    <div class="row">
                        <div class="col-6">@include('ecom::partials.field', ['name' => 'starts_at', 'label' => 'Starts At', 'value' => $campaign->starts_at?->format('Y-m-d\TH:i'), 'type' => 'datetime-local', 'required' => true])</div>
                        <div class="col-6">@include('ecom::partials.field', ['name' => 'ends_at', 'label' => 'Ends At', 'value' => $campaign->ends_at?->format('Y-m-d\TH:i'), 'type' => 'datetime-local', 'required' => true])</div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-primary">Description</label>
                        <textarea name="description" rows="3" class="form-control form-control-sm">{{ old('description', $campaign->description) }}</textarea>
                    </div>
                    @include('me::components.media-input', ['name' => 'banner', 'collection' => 'banner', 'model' => $campaign, 'label' => 'Banner'])
                    @include('ecom::partials.switch', ['name' => 'is_active', 'label' => 'Active', 'checked' => $campaign->is_active])
                </div>
                <div class="col-md-6">
                    <label class="font-weight-bold text-primary"><i class="fas fa-box me-1"></i> Products in this campaign</label>
                    <p class="small text-muted mb-1">The discount is applied on top of each product's selling price while the campaign runs.</p>
                    <select name="products[]" multiple class="form-select form-select-sm" id="campaignProducts" style="width:100%">
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected(in_array($product->id, old('products', $selected)))>{{ $product->title }}{{ $product->sku ? " ({$product->sku})" : '' }} — {{ ecom_money($product->price) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="text-end mt-3"><button type="submit" class="btn btn-encodex-save"><i class="fas fa-save me-1"></i> Save</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () { $('#campaignProducts').select2({ placeholder: 'Search products…', closeOnSelect: false }); });
</script>
@endpush
