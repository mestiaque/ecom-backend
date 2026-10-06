@extends('me::master')
@section('title', $product->exists ? 'Edit Product' : 'Add Product')

@push('buttons')
    @component('me::components.btn.add-button', ['route' => route('ecom.products.index'), 'text' => 'All Products', 'class' => 'btn-encodex-list'])
    @endcomponent
@endpush

@php
    // After a failed save, rows come back from old() (an unticked "Active" box is not sent, so default it to 0)
    $variants = old('variants') !== null
        ? array_map(fn ($row) => $row + ['is_active' => 0], old('variants'))
        : ($product->exists
            ? $product->variants->map(fn ($v) => $v->only(['id', 'sku', 'price', 'discount_price', 'stock', 'media_id']) + ['is_active' => $v->is_active ? 1 : 0, 'values' => $v->values->pluck('id')->all()])->all()
            : []);
    $hasVariants = (bool) old('has_variants', $product->has_variants);
    $attributeData = $attributes->map(fn ($a) => [
        'id' => $a->id,
        'name' => $a->name,
        'color' => $a->isColor(),
        'values' => $a->values->map(fn ($v) => ['id' => $v->id, 'value' => $v->value, 'color' => $v->color_code])->all(),
    ])->values();
    $imageData = $product->exists ? $product->images->map(fn ($i, $n) => ['id' => $i->id, 'label' => 'Image ' . ($n + 1), 'url' => $i->url])->values() : collect();
@endphp

@section('content')
<form action="{{ $product->exists ? route('ecom.products.update', $product) : route('ecom.products.store') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
    @csrf
    @if($product->exists) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card glass-card mb-3">
                <div class="card-header fw-semibold"><i class="fas fa-info-circle me-1"></i> Basic Information</div>
                <div class="card-body">
                    @include('ecom::partials.field', ['name' => 'title', 'label' => 'Title', 'value' => $product->title, 'required' => true, 'icon' => 'fas fa-heading'])
                    <div class="row">
                        <div class="col-md-6">@include('ecom::partials.field', ['name' => 'slug', 'label' => 'Slug', 'value' => $product->slug, 'help' => 'Leave empty to make it from the title'])</div>
                        <div class="col-md-6">@include('ecom::partials.field', ['name' => 'sku', 'label' => 'SKU', 'value' => $product->sku, 'icon' => 'fas fa-barcode'])</div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-primary">Short Description</label>
                        <textarea name="short_description" rows="2" class="form-control form-control-sm">{{ old('short_description', $product->short_description) }}</textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-primary">Description</label>
                        <textarea name="description" class="summernote" data-height="220">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card glass-card mb-3">
                <div class="card-header fw-semibold"><i class="fas fa-tag me-1"></i> Price & Stock</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">@include('ecom::partials.field', ['name' => 'price', 'label' => 'Regular Price', 'value' => $product->price, 'type' => 'number', 'step' => '0.01', 'required' => true])</div>
                        <div class="col-md-4">@include('ecom::partials.field', ['name' => 'discount_price', 'label' => 'Discount Price', 'value' => $product->discount_price, 'type' => 'number', 'step' => '0.01', 'help' => 'Selling price; empty = no discount'])</div>
                        <div class="col-md-4">@include('ecom::partials.field', ['name' => 'cost_price', 'label' => 'Cost Price', 'value' => $product->cost_price, 'type' => 'number', 'step' => '0.01', 'help' => 'For your reference only'])</div>
                        <div class="col-md-4" id="simpleStock" @if($hasVariants) style="display:none" @endif>
                            @include('ecom::partials.field', ['name' => 'stock', 'label' => 'Stock Quantity', 'value' => $product->stock ?? 0, 'type' => 'number'])
                        </div>
                        <div class="col-md-4">@include('ecom::partials.field', ['name' => 'low_stock_threshold', 'label' => 'Low Stock Alert At', 'value' => $product->low_stock_threshold, 'type' => 'number', 'help' => 'Empty = shop default (' . (int) ecom_setting('low_stock_threshold', config('ecom.low_stock_threshold')) . ')'])</div>
                        <div class="col-md-4">@include('ecom::partials.field', ['name' => 'weight', 'label' => 'Weight (kg)', 'value' => $product->weight, 'type' => 'number', 'step' => '0.01'])</div>
                    </div>
                </div>
            </div>

            <div class="card glass-card mb-3">
                <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-layer-group me-1"></i> Variants (color, size, storage…)</span>
                    <div class="form-check form-switch mb-0">
                        <input type="hidden" name="has_variants" value="0">
                        <input class="form-check-input" type="checkbox" name="has_variants" value="1" id="has_variants" @checked($hasVariants)>
                        <label class="form-check-label small" for="has_variants">This product has variants</label>
                    </div>
                </div>
                <div class="card-body" id="variantBox" @unless($hasVariants) style="display:none" @endunless>
                    @if($attributes->isEmpty())
                        <div class="alert alert-warning small mb-0">
                            No attributes yet. <a href="{{ route('ecom.attributes.create') }}" target="_blank">Create Color, Size, etc.</a> first, then reload this page.
                        </div>
                    @else
                        <div class="small text-muted mb-2">
                            <b>1.</b> Pick the values this product comes in. <b>2.</b> Click <b>Generate variants</b> — one row is made for every combination.
                            <b>3.</b> Set stock (and price if it differs). Empty price = product price. Product stock becomes the total of all variants.
                            <a href="{{ route('ecom.attributes.index') }}" target="_blank" class="ms-1">Manage attributes <i class="fas fa-external-link-alt"></i></a>
                        </div>
                        <div class="row g-2 mb-2" id="attributePickers">
                            @foreach($attributes as $attribute)
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold mb-0">{{ $attribute->name }}</label>
                                    <select multiple class="form-select form-select-sm js-attribute" data-attribute="{{ $attribute->id }}" style="width:100%" data-placeholder="Not used">
                                        @foreach($attribute->values as $value)
                                            <option value="{{ $value->id }}" data-color="{{ $attribute->isColor() ? $value->color_code : '' }}">{{ $value->value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            <button type="button" class="btn btn-sm btn-primary" id="generateVariants"><i class="fas fa-magic me-1"></i> Generate variants</button>
                            <span class="text-muted small ms-md-auto">Set for all rows:</span>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm" style="width:110px" placeholder="Price" id="bulkPrice">
                            <input type="number" min="0" class="form-control form-control-sm" style="width:90px" placeholder="Stock" id="bulkStock">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="applyBulk">Apply</button>
                        </div>

                        @error('variants')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        @foreach(collect($errors->get('variants.*'))->flatten()->unique() as $message)
                            <div class="text-danger small">{{ $message }}</div>
                        @endforeach

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0" id="variantTable">
                                <thead class="table-light text-center small">
                                    <tr><th>Variant</th><th>SKU</th><th>Price</th><th>Discount</th><th>Stock *</th>@if($imageData->isNotEmpty())<th>Image</th>@endif<th>Active</th><th></th></tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="text-muted small mt-1" id="variantSummary"></div>
                        @if($imageData->isEmpty())
                            <div class="text-muted small mt-1"><i class="fas fa-info-circle"></i> Upload images and save the product to give each variant its own photo (e.g. per colour).</div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card glass-card mb-3">
                <div class="card-header fw-semibold"><i class="fas fa-eye me-1"></i> Visibility</div>
                <div class="card-body">
                    @include('ecom::partials.switch', ['name' => 'is_active', 'label' => 'Active (visible in shop)', 'checked' => $product->is_active])
                    @include('ecom::partials.switch', ['name' => 'is_featured', 'label' => 'Featured product', 'checked' => $product->is_featured])
                </div>
            </div>

            <div class="card glass-card mb-3">
                <div class="card-header fw-semibold"><i class="fas fa-truck me-1"></i> Delivery</div>
                <div class="card-body">
                    @include('ecom::partials.switch', ['name' => 'free_delivery', 'label' => 'Free delivery', 'checked' => $product->free_delivery, 'help' => 'An order with only free-delivery products ships free'])
                    @include('ecom::partials.field', ['name' => 'delivery_charge_adjustment', 'label' => 'Extra / less delivery charge (per unit)', 'value' => $product->delivery_charge_adjustment, 'type' => 'number', 'step' => '0.01', 'help' => 'Added to the zone charge for each unit, e.g. 150 for a heavy item, -20 for a small one. Empty = zone charge only'])
                </div>
            </div>

            <div class="card glass-card mb-3">
                <div class="card-header fw-semibold"><i class="fas fa-sitemap me-1"></i> Organisation</div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-primary">Category</label>
                        <select name="category_id" class="form-select form-select-sm">
                            <option value="">— None —</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ str_repeat('— ', $category->depth) }}{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-primary">Brand</label>
                        <select name="brand_id" class="form-select form-select-sm">
                            <option value="">— None —</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product->brand_id) === (string) $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mt-3 mb-0">
                        <label class="font-weight-bold text-primary"><i class="fas fa-shield-alt me-1"></i> Warranty</label>
                        <select name="warranty_id" class="form-select form-select-sm @error('warranty_id') is-invalid @enderror">
                            <option value="">— No warranty —</option>
                            @foreach($warranties as $warranty)
                                <option value="{{ $warranty->id }}" @selected((string) old('warranty_id', $product->warranty_id) === (string) $warranty->id)>{{ $warranty->name }} ({{ $warranty->days }} days)</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Manage in <a href="{{ route('ecom.warranties.index') }}" target="_blank">Catalog → Warranties</a>.</small>
                    </div>
                </div>
            </div>

            <div class="card glass-card mb-3">
                <div class="card-header fw-semibold"><i class="fas fa-images me-1"></i> Images</div>
                <div class="card-body">
                    {{-- Photos are kept in me_media (metheme Media Library) --}}
                    @include('me::components.media-input', [
                        'name' => 'images',
                        'collection' => 'gallery',
                        'model' => $product,
                        'multiple' => true,
                        'help' => 'Select several images at once (max 10, 4MB each). Drag to reorder — the main image is shown first.',
                    ])
                </div>
            </div>

            <button type="submit" class="btn btn-encodex-save w-100"><i class="fas fa-save me-1"></i> {{ $product->exists ? 'Update Product' : 'Save Product' }}</button>
        </div>
    </div>
</form>

@endsection

@push('css')
<style>
    .ec-swatch { display:inline-block; width:12px; height:12px; border-radius:50%; border:1px solid rgba(0,0,0,.25); vertical-align:middle; margin-right:3px; }
    #variantTable td { padding: .2rem .3rem; }
    #variantTable .ec-variant-label .badge { font-weight: 500; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('has_variants');
    const box = document.getElementById('variantBox');
    const simpleStock = document.getElementById('simpleStock');
    const tbody = document.querySelector('#variantTable tbody');
    const attributes = @json($attributeData);
    const images = @json($imageData);
    const initialRows = @json(array_values($variants));
    const valueInfo = {};
    attributes.forEach(a => a.values.forEach(v => valueInfo[v.id] = { ...v, attribute: a }));
    let index = 0;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const sortValues = ids => ids.map(Number).filter(id => valueInfo[id])
        .sort((a, b) => attributes.indexOf(valueInfo[a].attribute) - attributes.indexOf(valueInfo[b].attribute));
    const keyOf = ids => ids.map(Number).sort((a, b) => a - b).join('-');

    function chip(id) {
        const v = valueInfo[id];
        const swatch = v.color ? '<span class="ec-swatch" style="background:' + esc(v.color) + '"></span>' : '';
        return '<span class="badge bg-light text-dark border me-1" title="' + esc(v.attribute.name) + '">' + swatch + esc(v.value) + '</span>';
    }

    function addRow(row) {
        if (!tbody) return;
        const i = index++;
        const ids = sortValues(row.values || []);
        const name = f => 'variants[' + i + '][' + f + ']';
        const imageCell = images.length
            ? '<td><select name="' + name('media_id') + '" class="form-select form-select-sm"><option value="">Main</option>' +
              images.map(img => '<option value="' + img.id + '"' + (String(row.media_id) === String(img.id) ? ' selected' : '') + '>' + esc(img.label) + '</option>').join('') + '</select></td>'
            : '';
        const tr = document.createElement('tr');
        tr.dataset.key = keyOf(ids);
        tr.innerHTML =
            '<td class="ec-variant-label text-nowrap"><input type="hidden" name="' + name('id') + '" value="' + esc(row.id) + '">' +
                ids.map(id => '<input type="hidden" name="' + name('values') + '[]" value="' + id + '">').join('') + ids.map(chip).join('') + '</td>' +
            '<td><input name="' + name('sku') + '" value="' + esc(row.sku) + '" class="form-control form-control-sm" style="min-width:110px"></td>' +
            '<td><input type="number" step="0.01" min="0" name="' + name('price') + '" value="' + esc(row.price) + '" class="form-control form-control-sm js-price" placeholder="Product price"></td>' +
            '<td><input type="number" step="0.01" min="0" name="' + name('discount_price') + '" value="' + esc(row.discount_price) + '" class="form-control form-control-sm"></td>' +
            '<td><input type="number" min="0" name="' + name('stock') + '" value="' + esc(row.stock ?? 0) + '" class="form-control form-control-sm js-stock" style="width:80px" required></td>' +
            imageCell +
            '<td class="text-center"><input type="checkbox" class="form-check-input" name="' + name('is_active') + '" value="1"' + (row.is_active === undefined || Number(row.is_active) ? ' checked' : '') + '></td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger js-remove-variant" title="Remove"><i class="fas fa-times"></i></button></td>';
        tbody.appendChild(tr);
    }

    function summary() {
        const el = document.getElementById('variantSummary');
        if (!el) return;
        const rows = tbody.querySelectorAll('tr');
        const stock = Array.from(tbody.querySelectorAll('.js-stock')).reduce((s, input) => s + (parseInt(input.value) || 0), 0);
        el.textContent = rows.length ? rows.length + ' variants · total stock ' + stock : 'No variants yet — pick values above and click Generate variants.';
    }

    function syncPickers() {
        // Pre-select the values already used by the rows
        const used = new Set();
        tbody.querySelectorAll('input[name$="[values][]"]').forEach(input => used.add(input.value));
        document.querySelectorAll('.js-attribute').forEach(select => {
            Array.from(select.options).forEach(option => option.selected = used.has(option.value));
            if (window.jQuery && $(select).data('select2')) $(select).trigger('change.select2');
        });
    }

    function generate() {
        const groups = Array.from(document.querySelectorAll('.js-attribute'))
            .map(select => Array.from(select.selectedOptions).map(o => Number(o.value)))
            .filter(group => group.length);
        if (!groups.length) { alert('Pick at least one value (e.g. a colour or a size) first.'); return; }

        const combos = groups.reduce((acc, group) => acc.flatMap(combo => group.map(id => [...combo, id])), [[]]);
        const wanted = new Set(combos.map(keyOf));
        const existing = Array.from(tbody.querySelectorAll('tr'));
        const stale = existing.filter(tr => !wanted.has(tr.dataset.key));

        if (stale.length && !confirm(stale.length + ' existing variant(s) do not match the picked values and will be removed. Continue?')) return;
        stale.forEach(tr => tr.remove());

        const have = new Set(Array.from(tbody.querySelectorAll('tr')).map(tr => tr.dataset.key));
        const baseSku = (document.getElementById('sku')?.value || '').trim();
        combos.filter(combo => !have.has(keyOf(combo))).forEach(combo => addRow({
            values: combo,
            sku: baseSku ? baseSku + '-' + sortValues(combo).map(id => valueInfo[id].value.replace(/\s+/g, '').toUpperCase()).join('-') : '',
            stock: 0,
        }));
        summary();
    }

    function setEnabled() {
        // Hidden variant rows must not be submitted (stock is required)
        box.querySelectorAll('#variantTable input, #variantTable select').forEach(input => input.disabled = !toggle.checked);
    }

    initialRows.forEach(addRow);
    syncPickers();
    summary();
    setEnabled();

    if (window.jQuery && $.fn.select2) {
        const swatch = option => {
            if (!option.id || !option.element || !option.element.dataset.color) return option.text;
            return $('<span><span class="ec-swatch" style="background:' + option.element.dataset.color + '"></span>' + esc(option.text) + '</span>');
        };
        $('.js-attribute').each(function () {
            $(this).select2({ placeholder: $(this).data('placeholder'), closeOnSelect: false, templateResult: swatch, templateSelection: swatch, width: '100%' });
        });
    }

    toggle.addEventListener('change', function () {
        box.style.display = toggle.checked ? '' : 'none';
        simpleStock.style.display = toggle.checked ? 'none' : '';
        setEnabled();
    });
    document.getElementById('generateVariants')?.addEventListener('click', generate);
    document.getElementById('applyBulk')?.addEventListener('click', function () {
        const price = document.getElementById('bulkPrice').value;
        const stock = document.getElementById('bulkStock').value;
        if (price !== '') tbody.querySelectorAll('.js-price').forEach(input => input.value = price);
        if (stock !== '') tbody.querySelectorAll('.js-stock').forEach(input => input.value = stock);
        summary();
    });
    tbody?.addEventListener('click', function (e) {
        const button = e.target.closest('.js-remove-variant');
        if (button) { button.closest('tr').remove(); syncPickers(); summary(); }
    });
    tbody?.addEventListener('input', summary);

    });
});
</script>
@endpush
