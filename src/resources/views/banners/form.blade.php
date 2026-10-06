@extends('me::master')
@section('title', $banner->exists ? 'Edit Banner' : 'Add Banner')

@push('buttons')
    @component('me::components.btn.add-button', ['route' => route('ecom.banners.index'), 'text' => 'All Banners', 'class' => 'btn-encodex-list'])
    @endcomponent
@endpush

@section('content')
<div class="card glass-card">
    <div class="card-body">
        <form action="{{ $banner->exists ? route('ecom.banners.update', $banner) : route('ecom.banners.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if($banner->exists) @method('PUT') @endif
            <div class="row">
                <div class="col-md-6">
                    @include('me::components.media-input', ['name' => 'image', 'collection' => 'image', 'model' => $banner, 'label' => 'Image', 'required' => true, 'help' => 'Slider: about 1600×700px. Max 4MB.'])
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-primary">Position</label>
                        <select name="position" class="form-select form-select-sm">
                            @foreach(\ME\Ecom\Models\Banner::POSITIONS as $value => $label)
                                <option value="{{ $value }}" @selected(old('position', $banner->position) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @include('ecom::partials.field', ['name' => 'sort_order', 'label' => 'Sort Order', 'value' => $banner->sort_order ?? 0, 'type' => 'number'])
                    @include('ecom::partials.switch', ['name' => 'is_active', 'label' => 'Active', 'checked' => $banner->is_active])
                </div>
                <div class="col-md-6">
                    @include('ecom::partials.field', ['name' => 'title', 'label' => 'Title', 'value' => $banner->title])
                    @include('ecom::partials.field', ['name' => 'subtitle', 'label' => 'Subtitle', 'value' => $banner->subtitle])
                    @include('ecom::partials.field', ['name' => 'link', 'label' => 'Link', 'value' => $banner->link, 'help' => 'e.g. /category/men or a full URL'])
                    @include('ecom::partials.field', ['name' => 'button_text', 'label' => 'Button Text', 'value' => $banner->button_text, 'help' => 'e.g. Shop Now'])
                </div>
            </div>
            <div class="text-end"><button type="submit" class="btn btn-encodex-save"><i class="fas fa-save me-1"></i> Save</button></div>
        </form>
    </div>
</div>
@endsection
