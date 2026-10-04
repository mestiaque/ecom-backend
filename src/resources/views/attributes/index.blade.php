@extends('me::master')
@section('title', 'Variant Attributes')

@push('buttons')
    @if(can('ecom_attribute.create'))
        @component('me::components.btn.add-button', ['route' => route('ecom.attributes.create'), 'text' => 'Add Attribute', 'class' => 'btn-encodex-create'])
        @endcomponent
    @endif
@endpush

@section('content')
<div class="card glass-card w-100">
    <p class="small text-muted mb-3">
        Attributes are the options a product can come in — Color, Size, Storage, Material… Pick them on a product to create its variants;
        each variant has its own SKU, price, stock and image.
    </p>
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover table-striped table-encodex align-middle">
            <thead class="text-center"><tr><th>Attribute</th><th>Type</th><th style="width:55%">Values</th><th>Used by</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($attributes as $attribute)
                    <tr>
                        <td><b>{{ $attribute->name }}</b></td>
                        <td class="text-center small">{{ \ME\Ecom\Models\Attribute::TYPES[$attribute->type] ?? $attribute->type }}</td>
                        <td>
                            @foreach($attribute->values as $value)
                                @include('ecom::attributes.value-chip', ['value' => $value, 'attribute' => $attribute])
                            @endforeach
                        </td>
                        <td class="text-center">{{ $attribute->values->sum('variants_count') }} variants</td>
                        <td class="text-center">
                            @include('ecom::partials.actions', [
                                'edit' => can('ecom_attribute.edit') ? route('ecom.attributes.edit', $attribute) : null,
                                'delete' => can('ecom_attribute.delete') ? route('ecom.attributes.destroy', $attribute) : null,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No attributes yet. Add Color and Size to start.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
