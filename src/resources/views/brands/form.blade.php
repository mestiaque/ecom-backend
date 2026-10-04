@extends('me::master')
@section('title', $brand->exists ? 'Edit Brand' : 'Add Brand')

@push('buttons')
    @component('me::components.btn.add-button', ['route' => route('ecom.brands.index'), 'text' => 'All Brands', 'class' => 'btn-encodex-list'])
    @endcomponent
@endpush

@section('content')
<div class="card glass-card">
    <div class="card-body">
        <form action="{{ $brand->exists ? route('ecom.brands.update', $brand) : route('ecom.brands.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if($brand->exists) @method('PUT') @endif
            <div class="row">
                <div class="col-md-6">
                    @include('ecom::partials.field', ['name' => 'name', 'label' => 'Name', 'value' => $brand->name, 'required' => true, 'icon' => 'fas fa-tag'])
                    @include('ecom::partials.field', ['name' => 'slug', 'label' => 'Slug', 'value' => $brand->slug, 'help' => 'Leave empty to make it from the name'])
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-primary">Description</label>
                        <textarea name="description" rows="3" class="form-control form-control-sm">{{ old('description', $brand->description) }}</textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    @include('ecom::partials.image-input', ['name' => 'logo', 'label' => 'Logo', 'current' => $brand->logo])
                    @include('ecom::partials.switch', ['name' => 'is_active', 'label' => 'Active', 'checked' => $brand->is_active])
                </div>
            </div>
            <div class="text-end"><button type="submit" class="btn btn-encodex-save"><i class="fas fa-save me-1"></i> Save</button></div>
        </form>
    </div>
</div>
@endsection
