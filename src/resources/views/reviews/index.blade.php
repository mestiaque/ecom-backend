@extends('me::master')
@section('title', 'Reviews')

@section('content')
<div class="card glass-card w-100">
    <form method="GET" action="{{ url()->current() }}" class="mb-3">
        <div class="row g-2">
            <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Product, name or text" value="{{ request('search') }}"></div>
            <div class="col-md">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="pending" @selected(request('status') === 'pending')>Waiting for approval ({{ $pendingCount }})</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                </select>
            </div>
            <div class="col-md">
                <select name="rating" class="form-select form-select-sm">
                    <option value="">Any rating</option>
                    @for($i = 5; $i >= 1; $i--)<option value="{{ $i }}" @selected((string) request('rating') === (string) $i)>{{ $i }} star</option>@endfor
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
            <thead class="text-center"><tr><th>Product</th><th>Customer</th><th>Rating</th><th style="width:40%">Review</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($reviews as $review)
                    <tr>
                        <td>@if($review->product)<a href="{{ route('ecom.products.show', $review->product) }}">{{ \Illuminate\Support\Str::limit($review->product->title, 40) }}</a>@else — @endif</td>
                        <td>
                            @if($review->customer_id)<a href="{{ route('ecom.customers.show', $review->customer_id) }}">{{ $review->name }}</a>@else{{ $review->name }}@endif
                        </td>
                        <td class="text-center text-nowrap text-warning">
                            @for($i = 1; $i <= 5; $i++)<i class="{{ $i <= $review->rating ? 'fas' : 'far' }} fa-star"></i>@endfor
                        </td>
                        <td class="small">{{ $review->comment }}</td>
                        <td class="small text-nowrap">{{ $review->created_at->format('d M Y') }}</td>
                        <td class="text-center">@include('ecom::partials.active-badge', ['active' => $review->is_approved, 'on' => 'Approved', 'off' => 'Pending'])</td>
                        <td class="text-center text-nowrap">
                            @if(can('ecom_review.approve'))
                                <form action="{{ route('ecom.reviews.approve', $review) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm {{ $review->is_approved ? 'btn-encodex-deactive' : 'btn-encodex-active' }}" title="{{ $review->is_approved ? 'Hide' : 'Approve' }}">
                                        <i class="fas {{ $review->is_approved ? 'fa-eye-slash' : 'fa-check' }}"></i>
                                    </button>
                                </form>
                            @endif
                            @include('ecom::partials.actions', ['delete' => can('ecom_review.delete') ? route('ecom.reviews.destroy', $review) : null])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No reviews found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reviews->hasPages())<div class="mt-3">{{ $reviews->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
