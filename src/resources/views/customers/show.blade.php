@extends('me::master')
@section('title', $customer->name)

@push('buttons')
    @component('me::components.btn.add-button', ['route' => route('ecom.customers.index'), 'text' => 'All Customers', 'class' => 'btn-encodex-list'])
    @endcomponent
@endpush

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card glass-card mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    @if($customer->avatar_url)
                        <img src="{{ $customer->avatar_url }}" alt="" class="rounded-circle" style="width:56px;height:56px;object-fit:cover">
                    @else
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width:56px;height:56px;font-size:1.4rem">{{ $customer->initial }}</div>
                    @endif
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $customer->name }}</h5>
                        @include('ecom::partials.active-badge', ['active' => ! $customer->is_blocked, 'off' => 'Blocked'])
                    </div>
                </div>
                <div class="small">
                    <div><i class="fas fa-phone fa-fw text-muted"></i> {{ $customer->phone }} @if($customer->phone_verified_at)<i class="fas fa-check-circle text-success" title="Verified"></i>@endif</div>
                    @if($customer->email)<div><i class="fas fa-envelope fa-fw text-muted"></i> {{ $customer->email }} @if($customer->email_verified_at)<i class="fas fa-check-circle text-success" title="Verified"></i>@endif</div>@endif
                    @if($customer->address)<div><i class="fas fa-map-marker-alt fa-fw text-muted"></i> {{ $customer->address }}@if($customer->city), {{ $customer->city }}@endif</div>@endif
                    <div><i class="fas fa-calendar fa-fw text-muted"></i> Joined {{ $customer->created_at->format('d M Y') }}</div>
                </div>
                @if($customer->is_blocked && $customer->block_reason)
                    <div class="alert alert-danger small py-1 px-2 mt-2 mb-0">Blocked: {{ $customer->block_reason }}</div>
                @endif

                @if(can('ecom_customer.block'))
                    <form action="{{ route('ecom.customers.block', $customer) }}" method="POST" class="mt-3">
                        @csrf @method('PATCH')
                        @unless($customer->is_blocked)
                            <input type="text" name="block_reason" class="form-control form-control-sm mb-2" placeholder="Reason (optional)">
                        @endunless
                        <button class="btn btn-sm w-100 {{ $customer->is_blocked ? 'btn-success' : 'btn-danger' }}" onclick="return confirm('Are you sure?')">
                            <i class="fas {{ $customer->is_blocked ? 'fa-unlock' : 'fa-ban' }} me-1"></i>{{ $customer->is_blocked ? 'Unblock customer' : 'Block customer' }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="row g-2 mb-3 text-center">
            <div class="col-4"><div class="card glass-card"><div class="card-body p-2"><div class="small text-muted">Orders</div><div class="fw-bold fs-5">{{ $customer->orders_count }}</div></div></div></div>
            <div class="col-8"><div class="card glass-card"><div class="card-body p-2"><div class="small text-muted">Lifetime Value</div><div class="fw-bold fs-5 text-success">{{ ecom_money($customer->lifetime_value) }}</div></div></div></div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card glass-card mb-3">
            <div class="card-header fw-semibold"><i class="fas fa-receipt me-1"></i> Order History</div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light"><tr><th class="ps-3">Order</th><th>Date</th><th class="text-center">Items</th><th class="text-end">Total</th><th class="text-center">Payment</th><th class="text-center">Status</th></tr></thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td class="ps-3"><a href="{{ route('ecom.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                                <td class="small">{{ $order->created_at->format('d M Y') }}</td>
                                <td class="text-center">{{ $order->items_count }}</td>
                                <td class="text-end">{{ ecom_money($order->total) }}</td>
                                <td class="text-center">@include('ecom::partials.status-badge', ['status' => $order->payment_status])</td>
                                <td class="text-center">@include('ecom::partials.status-badge', ['status' => $order->status])</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No orders yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($orders->hasPages())<div class="card-footer">{{ $orders->links('pagination::bootstrap-5') }}</div>@endif
        </div>

        @if($reviews->isNotEmpty())
            <div class="card glass-card">
                <div class="card-header fw-semibold"><i class="fas fa-star me-1"></i> Reviews</div>
                <ul class="list-group list-group-flush small">
                    @foreach($reviews as $review)
                        <li class="list-group-item">
                            <span class="text-warning">@for($i = 1; $i <= 5; $i++)<i class="{{ $i <= $review->rating ? 'fas' : 'far' }} fa-star"></i>@endfor</span>
                            <b>{{ $review->product?->title }}</b> — {{ $review->comment }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
@endsection
