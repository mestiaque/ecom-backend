@extends('me::master')
@section('title', 'Products')

@push('buttons')
    @if(can('ecom_product.import'))
        <a href="#" class="btn btn-sm btn-encodex-list text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#importModal"><i class="fas fa-file-import me-1"></i>Import CSV</a>
    @endif
    @if(can('ecom_product.export'))
        <a href="{{ route('ecom.products.export', request()->query()) }}" class="btn btn-sm btn-success shadow-sm"><i class="fas fa-file-export me-1"></i>Export CSV</a>
    @endif
    @if(can('ecom_product.create'))
        @component('me::components.btn.add-button', ['route' => route('ecom.products.create'), 'text' => 'Add Product', 'class' => 'btn-encodex-create'])
        @endcomponent
    @endif
@endpush

@section('content')
@include('ecom::partials.import-errors')
<div class="card glass-card w-100">
    <form method="GET" action="{{ url()->current() }}" class="mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Title or SKU" value="{{ request('search') }}">
            </div>
            <div class="col-md">
                <select name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ str_repeat('— ', $category->depth) }}{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md">
                <select name="brand" class="form-select form-select-sm">
                    <option value="">All Brands</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" @selected((string) request('brand') === (string) $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Any Status</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Hidden</option>
                </select>
            </div>
            <div class="col-md">
                <select name="stock" class="form-select form-select-sm">
                    <option value="">Any Stock</option>
                    <option value="low" @selected(request('stock') === 'low')>Low stock</option>
                    <option value="out" @selected(request('stock') === 'out')>Out of stock</option>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-sm btn-encodex-search rounded"><i class="fas fa-search"></i> Search</button>
                <a href="{{ url()->current() }}" class="btn btn-sm btn-encodex-clear rounded"><i class="fas fa-eraser"></i> Reset</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover table-striped table-encodex align-middle">
            <thead class="text-center">
                <tr>
                    <th>#</th>
                    <th>Image</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td class="text-center">{{ $products->firstItem() + $loop->index }}</td>
                        <td class="text-center">
                            @if($product->thumbnail)
                                <img src="{{ $product->thumbnail }}" loading="lazy" alt="" class="rounded" style="width:44px;height:44px;object-fit:cover">
                            @else
                                <div class="bg-light rounded d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px"><i class="fas fa-box text-secondary"></i></div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('ecom.products.show', $product) }}" class="fw-semibold">{{ $product->title }}</a>
                            @if($product->is_featured)<i class="fas fa-star text-warning small" title="Featured"></i>@endif
                            <div class="small text-muted">
                                {{ $product->sku ?: 'No SKU' }}
                                @if($product->brand) · {{ $product->brand->name }} @endif
                                @if($product->has_variants) · <span class="badge bg-info-subtle text-info">Variants</span> @endif
                            </div>
                        </td>
                        <td>{{ $product->category?->name ?? '—' }}</td>
                        <td class="text-end text-nowrap">
                            @if($product->discount_price !== null)
                                <del class="small text-muted">{{ ecom_money($product->price) }}</del><br>
                                <b>{{ ecom_money($product->discount_price) }}</b>
                            @else
                                <b>{{ ecom_money($product->price) }}</b>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($product->stock <= 0)
                                <span class="badge bg-danger">Out of stock</span>
                            @elseif($product->isLowStock())
                                <span class="badge bg-warning text-dark">{{ $product->stock }} · Low</span>
                            @else
                                <span class="badge bg-success-subtle text-success">{{ $product->stock }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if(can('ecom_product.edit'))
                                <form action="{{ route('ecom.products.toggle', $product) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm p-0 border-0" title="{{ $product->is_active ? 'Hide' : 'Activate' }}">
                                        @include('ecom::partials.active-badge', ['active' => $product->is_active, 'off' => 'Hidden'])
                                    </button>
                                </form>
                            @else
                                @include('ecom::partials.active-badge', ['active' => $product->is_active, 'off' => 'Hidden'])
                            @endif
                        </td>
                        <td class="text-center">
                            @include('ecom::partials.actions', [
                                'show' => route('ecom.products.show', $product),
                                'edit' => can('ecom_product.edit') ? route('ecom.products.edit', $product) : null,
                                'delete' => can('ecom_product.delete') ? route('ecom.products.destroy', $product) : null,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No products found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($products->hasPages())
        <div class="mt-3">{{ $products->links('pagination::bootstrap-5') }}</div>
    @endif
</div>

@if(can('ecom_product.import'))
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('ecom.products.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-import me-1"></i> Import Products (CSV)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">
                    Rows are matched by <b>SKU</b>: an existing SKU is updated, a new one is created.
                    Category can be nested with <code>&gt;</code> (e.g. <code>Men &gt; T-Shirts</code>); missing categories and brands are created.
                    Variants are not imported — add them on the product page.
                </p>
                <a href="{{ route('ecom.products.import-template') }}" class="small"><i class="fas fa-download"></i> Download template</a>
                <input type="file" name="file" accept=".csv,text/csv" class="form-control form-control-sm mt-2" required>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-sm btn-encodex-save"><i class="fas fa-upload me-1"></i> Import</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
