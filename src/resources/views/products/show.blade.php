@extends('me::master')
@section('title', $product->title)

@push('buttons')
    @component('me::components.btn.add-button', ['route' => route('ecom.products.index'), 'text' => 'All Products', 'class' => 'btn-encodex-list'])
    @endcomponent
    @if(can('ecom_product.edit'))
        @component('me::components.btn.add-button', ['route' => route('ecom.products.edit', $product), 'text' => 'Edit', 'class' => 'btn-encodex-edit', 'icon' => 'edit'])
        @endcomponent
    @endif
@endpush

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card glass-card">
            <div class="card-body">
                @if($product->images->isNotEmpty())
                    <img src="{{ $product->images->first()->url }}" class="img-fluid rounded border mb-2 w-100" style="aspect-ratio:1;object-fit:cover" alt="" id="mainImage">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($product->images as $image)
                            <img src="{{ $image->thumb_url }}" data-full="{{ $image->url }}" loading="lazy" class="rounded border" style="width:56px;height:56px;object-fit:cover;cursor:pointer" alt="" onclick="document.getElementById('mainImage').src=this.dataset.full">
                        @endforeach
                    </div>
                @else
                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="aspect-ratio:1"><i class="fas fa-box fa-3x text-secondary"></i></div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card glass-card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @include('ecom::partials.active-badge', ['active' => $product->is_active, 'off' => 'Hidden'])
                    @if($product->is_featured)<span class="badge bg-warning text-dark"><i class="fas fa-star"></i> Featured</span>@endif
                    @if($campaign = $product->activeCampaign())<span class="badge bg-danger"><i class="fas fa-bolt"></i> {{ $campaign->title }}</span>@endif
                </div>
                <h4 class="fw-bold mb-1">{{ $product->title }}</h4>
                <div class="text-muted small mb-3">
                    SKU: {{ $product->sku ?: '—' }} · Category: {{ $product->category?->name ?? '—' }} · Brand: {{ $product->brand?->name ?? '—' }} · Slug: {{ $product->slug }}
                </div>
                <div class="small mb-3">
                    <i class="fas fa-shield-alt {{ $product->warranty ? 'text-success' : 'text-muted' }}"></i>
                    @if($product->warranty)<b>{{ $product->warranty->name }}</b> <span class="text-muted">({{ $product->warranty->period }} = {{ $product->warranty->days }} days)</span>@else<span class="text-muted">No warranty</span>@endif
                </div>
                <div class="row g-3 text-center">
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small text-muted">Regular Price</div><div class="fw-bold">{{ ecom_money($product->price) }}</div></div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small text-muted">Selling Price</div><div class="fw-bold text-success">{{ ecom_money($product->finalPrice()) }}</div></div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small text-muted">Stock</div><div class="fw-bold {{ $product->isLowStock() ? 'text-danger' : '' }}">{{ $product->stock }}</div></div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small text-muted">Sold</div><div class="fw-bold">{{ $sold }}</div></div></div>
                </div>
                @if($product->reviews_count)
                    <div class="mt-3 small">
                        <i class="fas fa-star text-warning"></i> {{ number_format((float) $product->reviews_avg_rating, 1) }} average from approved reviews ·
                        <a href="{{ route('ecom.reviews.index', ['search' => $product->title]) }}">{{ $product->reviews_count }} reviews</a>
                    </div>
                @endif
            </div>
        </div>

        @if($product->has_variants)
            <div class="card glass-card mb-3">
                <div class="card-header fw-semibold"><i class="fas fa-layer-group me-1"></i> Variants ({{ $product->variants->count() }})</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0 align-middle">
                        <thead class="table-light"><tr><th class="ps-3">Variant</th><th>SKU</th><th class="text-end">Price</th><th class="text-center">Stock</th><th class="text-center">Status</th></tr></thead>
                        <tbody>
                            @foreach($product->variants as $variant)
                                <tr>
                                    <td class="ps-3 text-nowrap">
                                        @if($variant->image)
                                            <img src="{{ $variant->image->thumb_url }}" data-full="{{ $variant->image->url }}" loading="lazy" alt="" class="rounded border me-1" style="width:32px;height:32px;object-fit:cover;cursor:pointer" onclick="document.getElementById('mainImage').src=this.dataset.full">
                                        @endif
                                        @foreach($variant->orderedValues() as $value)
                                            @include('ecom::attributes.value-chip', ['value' => $value, 'attribute' => $value->attribute])
                                        @endforeach
                                    </td>
                                    <td>{{ $variant->sku ?: '—' }}</td>
                                    <td class="text-end">{{ ecom_money($variant->selling_price) }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $variant->stock <= 0 ? 'bg-danger' : ($variant->stock <= $product->low_stock_level ? 'bg-warning text-dark' : 'bg-success-subtle text-success') }}">{{ $variant->stock }}</span>
                                    </td>
                                    <td class="text-center">@include('ecom::partials.active-badge', ['active' => $variant->is_active])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($product->short_description || $product->description)
            <div class="card glass-card">
                <div class="card-header fw-semibold"><i class="fas fa-align-left me-1"></i> Description</div>
                <div class="card-body">
                    @if($product->short_description)<p class="text-muted">{{ $product->short_description }}</p>@endif
                    <div class="summernote-content">{!! $product->description !!}</div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
