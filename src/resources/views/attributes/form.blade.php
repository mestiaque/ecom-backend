@extends('me::master')
@section('title', $attribute->exists ? 'Edit Attribute' : 'Add Attribute')

@push('buttons')
    @component('me::components.btn.add-button', ['route' => route('ecom.attributes.index'), 'text' => 'All Attributes', 'class' => 'btn-encodex-list'])
    @endcomponent
@endpush

@php
    $values = old('values', $attribute->exists ? $attribute->values->map(fn ($v) => $v->only(['id', 'value', 'color_code']) + ['used' => $v->variants_count ?? 0])->all() : [['value' => ''], ['value' => '']]);
@endphp

@section('content')
<form action="{{ $attribute->exists ? route('ecom.attributes.update', $attribute) : route('ecom.attributes.store') }}" method="POST">
    @csrf
    @if($attribute->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card glass-card">
                <div class="card-body">
                    @include('ecom::partials.field', ['name' => 'name', 'label' => 'Attribute Name', 'value' => $attribute->name, 'required' => true, 'icon' => 'fas fa-palette', 'help' => 'e.g. Color, Size, Storage, Material'])
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-primary">Display Type</label>
                        <select name="type" class="form-select form-select-sm" id="attrType">
                            @foreach(\ME\Ecom\Models\Attribute::TYPES as $type => $label)
                                <option value="{{ $type }}" @selected(old('type', $attribute->type) === $type)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">"Color swatch" lets you pick a colour for each value.</small>
                    </div>
                    @include('ecom::partials.field', ['name' => 'sort_order', 'label' => 'Sort Order', 'value' => $attribute->sort_order ?? 0, 'type' => 'number', 'help' => 'Lower comes first in variant names, e.g. Color before Size'])
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card glass-card">
                <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list me-1"></i> Values</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addValue"><i class="fas fa-plus"></i> Add value</button>
                </div>
                <div class="card-body">
                    @error('values')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                    @foreach($errors->get('values.*') as $messages)<div class="text-danger small">{{ $messages[0] }}</div>@endforeach
                    <div id="valueRows">
                        @foreach($values as $i => $row)
                            <div class="input-group input-group-sm mb-2 js-value-row">
                                <span class="input-group-text js-handle" style="cursor:grab"><i class="fas fa-grip-vertical text-muted"></i></span>
                                <input type="hidden" name="values[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                                <input type="text" name="values[{{ $i }}][value]" value="{{ $row['value'] ?? '' }}" class="form-control" placeholder="e.g. Red, XL, 128GB">
                                <input type="color" name="values[{{ $i }}][color_code]" value="{{ $row['color_code'] ?? '#000000' }}" class="form-control form-control-color js-color" title="Swatch colour" style="max-width:52px">
                                @if(!empty($row['used']))<span class="input-group-text small text-muted">{{ $row['used'] }} variants</span>@endif
                                <button type="button" class="btn btn-outline-danger js-remove" {{ !empty($row['used']) ? 'disabled title=In use' : '' }}><i class="fas fa-times"></i></button>
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted">Drag <i class="fas fa-grip-vertical"></i> to change the order customers see. Values used by variants cannot be removed.</small>
                </div>
            </div>
            <div class="text-end mt-3"><button type="submit" class="btn btn-encodex-save"><i class="fas fa-save me-1"></i> Save</button></div>
        </div>
    </div>
</form>

<template id="valueRow">
    <div class="input-group input-group-sm mb-2 js-value-row">
        <span class="input-group-text js-handle" style="cursor:grab"><i class="fas fa-grip-vertical text-muted"></i></span>
        <input type="hidden" name="values[__i__][id]" value="">
        <input type="text" name="values[__i__][value]" class="form-control" placeholder="e.g. Red, XL, 128GB">
        <input type="color" name="values[__i__][color_code]" value="#000000" class="form-control form-control-color js-color" title="Swatch colour" style="max-width:52px">
        <button type="button" class="btn btn-outline-danger js-remove"><i class="fas fa-times"></i></button>
    </div>
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = document.getElementById('valueRows');
    const type = document.getElementById('attrType');
    let index = {{ count($values) + 1 }};

    function toggleColors() {
        rows.querySelectorAll('.js-color').forEach(input => {
            input.style.display = type.value === 'color' ? '' : 'none';
            input.disabled = type.value !== 'color';
        });
    }

    document.getElementById('addValue').addEventListener('click', function () {
        rows.insertAdjacentHTML('beforeend', document.getElementById('valueRow').innerHTML.replaceAll('__i__', index++));
        toggleColors();
        rows.lastElementChild.querySelector('input[type=text]').focus();
    });
    rows.addEventListener('click', e => { const b = e.target.closest('.js-remove'); if (b && !b.disabled) b.closest('.js-value-row').remove(); });
    type.addEventListener('change', toggleColors);
    toggleColors();
    new Sortable(rows, { handle: '.js-handle', animation: 150 });
});
</script>
@endpush
