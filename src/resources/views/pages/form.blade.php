@extends('me::master')
@section('title', $page->exists ? 'Edit Page' : 'Add Page')

@push('buttons')
    @component('me::components.btn.add-button', ['route' => route('ecom.pages.index'), 'text' => 'All Pages', 'class' => 'btn-encodex-list'])
    @endcomponent
@endpush

@section('content')
<form action="{{ $page->exists ? route('ecom.pages.update', $page) : route('ecom.pages.store') }}" method="POST">
    @csrf
    @if($page->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card glass-card">
                <div class="card-body">
                    @include('ecom::partials.field', ['name' => 'title', 'label' => 'Title', 'value' => $page->title, 'required' => true])
                    <label class="font-weight-bold text-primary">Content</label>
                    <textarea name="content" class="summernote" data-height="380">{{ old('content', $page->content) }}</textarea>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card glass-card">
                <div class="card-body">
                    @include('ecom::partials.field', ['name' => 'slug', 'label' => 'Slug (URL)', 'value' => $page->slug, 'help' => 'Leave empty to make it from the title'])
                    @include('ecom::partials.field', ['name' => 'meta_title', 'label' => 'SEO Title', 'value' => $page->meta_title])
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-primary">SEO Description</label>
                        <textarea name="meta_description" rows="3" class="form-control form-control-sm">{{ old('meta_description', $page->meta_description) }}</textarea>
                    </div>
                    @include('ecom::partials.switch', ['name' => 'is_active', 'label' => 'Published', 'checked' => $page->is_active])
                    <button type="submit" class="btn btn-encodex-save w-100"><i class="fas fa-save me-1"></i> Save</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
