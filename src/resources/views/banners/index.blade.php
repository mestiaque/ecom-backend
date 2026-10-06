@extends('me::master')
@section('title', 'Banners / Slider')

@push('buttons')
    @component('me::components.btn.add-button', ['route' => route('ecom.banners.create'), 'text' => 'Add Banner', 'class' => 'btn-encodex-create'])
    @endcomponent
@endpush

@section('content')
@foreach(\ME\Ecom\Models\Banner::POSITIONS as $position => $positionLabel)
    <div class="card glass-card w-100 mb-3">
        <div class="card-header fw-semibold">{{ $positionLabel }}</div>
        <div class="card-body">
            <div class="row g-3">
                @forelse($banners->where('position', $position) as $banner)
                    <div class="col-md-6 col-xl-4">
                        <div class="card h-100 {{ $banner->is_active ? '' : 'opacity-50' }}">
                            <img src="{{ $banner->image_url }}" class="card-img-top" style="aspect-ratio:16/7;object-fit:cover" alt="">
                            <div class="card-body py-2">
                                <div class="fw-semibold">{{ $banner->title ?: 'Untitled' }}</div>
                                <div class="small text-muted">{{ $banner->subtitle }}</div>
                                @if($banner->link)<div class="small text-truncate"><i class="fas fa-link"></i> {{ $banner->link }}</div>@endif
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center py-1">
                                <span class="small">Order {{ $banner->sort_order }} · @include('ecom::partials.active-badge', ['active' => $banner->is_active])</span>
                                @include('ecom::partials.actions', ['edit' => route('ecom.banners.edit', $banner), 'delete' => route('ecom.banners.destroy', $banner)])
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-muted small">No banners here yet.</div>
                @endforelse
            </div>
        </div>
    </div>
@endforeach
@endsection
