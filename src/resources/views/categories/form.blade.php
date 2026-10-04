@extends('me::master')
@section('title', $category->exists ? 'Edit Category' : 'Add Category')

@push('buttons')
    @component('me::components.btn.add-button', ['route' => route('ecom.categories.index'), 'text' => 'All Categories', 'class' => 'btn-encodex-list'])
    @endcomponent
@endpush

@section('content')
<div class="card glass-card">
    <div class="card-body">
        <form action="{{ $category->exists ? route('ecom.categories.update', $category) : route('ecom.categories.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if($category->exists) @method('PUT') @endif
            <div class="row">
                <div class="col-md-6">
                    @include('ecom::partials.field', ['name' => 'name', 'label' => 'Name', 'value' => $category->name, 'required' => true, 'icon' => 'fas fa-folder'])
                    @include('ecom::partials.field', ['name' => 'slug', 'label' => 'Slug', 'value' => $category->slug, 'help' => 'Leave empty to make it from the name'])
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-primary"><i class="fas fa-sitemap me-1"></i> Parent Category</label>
                        <select name="parent_id" class="form-select form-select-sm @error('parent_id') is-invalid @enderror">
                            <option value="">— None (top level) —</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $parent->id)>{{ str_repeat('— ', $parent->depth) }}{{ $parent->name }}</option>
                            @endforeach
                        </select>
                        @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @include('ecom::partials.field', ['name' => 'sort_order', 'label' => 'Sort Order', 'value' => $category->sort_order ?? 0, 'type' => 'number'])
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-primary">Description</label>
                        <textarea name="description" rows="3" class="form-control form-control-sm">{{ old('description', $category->description) }}</textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    @include('ecom::partials.image-input', ['name' => 'image', 'label' => 'Category Image', 'current' => $category->image, 'help' => 'Square icon/thumbnail, max 2MB'])
                    @include('ecom::partials.image-input', ['name' => 'banner', 'label' => 'Category Banner', 'current' => $category->banner, 'help' => 'Wide banner for the category page, max 4MB'])
                    @include('ecom::partials.switch', ['name' => 'is_active', 'label' => 'Active', 'checked' => $category->is_active])
                </div>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-encodex-save"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
