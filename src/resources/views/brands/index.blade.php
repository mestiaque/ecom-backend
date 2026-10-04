@extends('me::master')
@section('title', 'Brands')

@push('buttons')
    @if(can('ecom_brand.create'))
        @component('me::components.btn.add-button', ['route' => route('ecom.brands.create'), 'text' => 'Add Brand', 'class' => 'btn-encodex-create'])
        @endcomponent
    @endif
@endpush

@section('content')
<div class="card glass-card w-100">
    <form method="GET" action="{{ url()->current() }}" class="mb-3">
        <div class="row g-2">
            <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Brand name" value="{{ request('search') }}"></div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-sm btn-encodex-search rounded"><i class="fas fa-search"></i> Search</button>
                <a href="{{ url()->current() }}" class="btn btn-sm btn-encodex-clear rounded"><i class="fas fa-eraser"></i> Reset</a>
            </div>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover table-striped table-encodex align-middle">
            <thead class="text-center"><tr><th>#</th><th>Logo</th><th>Name</th><th>Products</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($brands as $brand)
                    <tr>
                        <td class="text-center">{{ $brands->firstItem() + $loop->index }}</td>
                        <td class="text-center" style="width:80px">
                            @if($brand->logo)<img src="{{ ecom_image($brand->logo) }}" alt="" style="height:36px;max-width:70px;object-fit:contain">@else<i class="fas fa-tag text-secondary"></i>@endif
                        </td>
                        <td><b>{{ $brand->name }}</b><div class="small text-muted">{{ $brand->slug }}</div></td>
                        <td class="text-center"><a href="{{ route('ecom.products.index', ['brand' => $brand->id]) }}">{{ $brand->products_count }}</a></td>
                        <td class="text-center">@include('ecom::partials.active-badge', ['active' => $brand->is_active])</td>
                        <td class="text-center">
                            @include('ecom::partials.actions', [
                                'edit' => can('ecom_brand.edit') ? route('ecom.brands.edit', $brand) : null,
                                'delete' => can('ecom_brand.delete') ? route('ecom.brands.destroy', $brand) : null,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No brands yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($brands->hasPages())<div class="mt-3">{{ $brands->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
