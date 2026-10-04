@extends('me::master')
@section('title', 'Categories')

@push('buttons')
    @if(can('ecom_category.create'))
        @component('me::components.btn.add-button', ['route' => route('ecom.categories.create'), 'text' => 'Add Category', 'class' => 'btn-encodex-create'])
        @endcomponent
    @endif
@endpush

@section('content')
<div class="card glass-card w-100">
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover table-striped table-encodex align-middle">
            <thead class="text-center">
                <tr><th>Image</th><th>Name</th><th>Slug</th><th>Products</th><th>Order</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td class="text-center" style="width:60px">
                            @if($category->image)
                                <img src="{{ ecom_image($category->image) }}" alt="" class="rounded" style="width:40px;height:40px;object-fit:cover">
                            @else
                                <i class="fas fa-folder text-warning fa-lg"></i>
                            @endif
                        </td>
                        <td>
                            <span style="padding-left: {{ $category->depth * 22 }}px">
                                @if($category->depth)<i class="fas fa-level-up-alt fa-rotate-90 text-muted me-1"></i>@endif
                                <b>{{ $category->name }}</b>
                            </span>
                            @if($category->banner)<span class="badge bg-info-subtle text-info ms-1" title="Has banner"><i class="fas fa-image"></i></span>@endif
                        </td>
                        <td class="small text-muted">{{ $category->slug }}</td>
                        <td class="text-center"><a href="{{ route('ecom.products.index', ['category' => $category->id]) }}">{{ $category->products_count }}</a></td>
                        <td class="text-center">{{ $category->sort_order }}</td>
                        <td class="text-center">@include('ecom::partials.active-badge', ['active' => $category->is_active])</td>
                        <td class="text-center">
                            @include('ecom::partials.actions', [
                                'edit' => can('ecom_category.edit') ? route('ecom.categories.edit', $category) : null,
                                'delete' => can('ecom_category.delete') ? route('ecom.categories.destroy', $category) : null,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No categories yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
