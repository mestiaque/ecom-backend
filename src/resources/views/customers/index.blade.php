@extends('me::master')
@section('title', 'Customers')

@section('content')
<div class="card glass-card w-100">
    <form method="GET" action="{{ url()->current() }}" class="mb-3">
        <div class="row g-2">
            <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Name, phone or email" value="{{ request('search') }}"></div>
            <div class="col-md">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="blocked" @selected(request('status') === 'blocked')>Blocked</option>
                </select>
            </div>
            <div class="col-md">
                <select name="sort" class="form-select form-select-sm">
                    <option value="">Newest first</option>
                    <option value="lifetime_value" @selected(request('sort') === 'lifetime_value')>Top spenders</option>
                    <option value="orders_count" @selected(request('sort') === 'orders_count')>Most orders</option>
                    <option value="last_order_at" @selected(request('sort') === 'last_order_at')>Recent buyers</option>
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
            <thead class="text-center"><tr><th>Customer</th><th>Phone</th><th>Orders</th><th>Lifetime Value</th><th>Last Order</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td><a href="{{ route('ecom.customers.show', $customer) }}" class="fw-semibold">{{ $customer->name }}</a><div class="small text-muted">{{ $customer->email }}</div></td>
                        <td>{{ $customer->phone }}</td>
                        <td class="text-center">{{ $customer->orders_count }}</td>
                        <td class="text-end fw-semibold">{{ ecom_money($customer->lifetime_value) }}</td>
                        <td class="small">{{ $customer->last_order_at ? \Carbon\Carbon::parse($customer->last_order_at)->format('d M Y') : '—' }}</td>
                        <td class="small">{{ $customer->created_at->format('d M Y') }}</td>
                        <td class="text-center">@include('ecom::partials.active-badge', ['active' => ! $customer->is_blocked, 'off' => 'Blocked'])</td>
                        <td class="text-center text-nowrap">
                            <a href="{{ route('ecom.customers.show', $customer) }}" class="btn btn-sm btn-encodex-show" title="View"><i class="fas fa-eye"></i></a>
                            @if(can('ecom_customer.block'))
                                <form action="{{ route('ecom.customers.block', $customer) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm {{ $customer->is_blocked ? 'btn-encodex-active' : 'btn-encodex-deactive' }}" title="{{ $customer->is_blocked ? 'Unblock' : 'Block' }}"
                                        onclick="return confirm('{{ $customer->is_blocked ? 'Unblock' : 'Block' }} this customer?')">
                                        <i class="fas {{ $customer->is_blocked ? 'fa-unlock' : 'fa-ban' }}"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No customers found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())<div class="mt-3">{{ $customers->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
