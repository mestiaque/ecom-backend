@extends('me::master')
@section('title', 'Orders')

@push('buttons')
    @if(can('ecom_order.invoice'))
        {{-- Ticked rows → one print page / one PDF (opens in a new tab) --}}
        <button type="submit" form="ecBulkInvoices" class="btn btn-sm btn-secondary shadow-sm" data-bulk-invoice disabled title="Tick orders in the list first">
            <i class="fas fa-print me-1"></i>Print Invoices <span class="badge bg-light text-dark" data-bulk-count>0</span>
        </button>
        <button type="submit" form="ecBulkInvoices" name="pdf" value="1" class="no-loader btn btn-sm btn-danger shadow-sm" data-bulk-invoice disabled>
            <i class="fas fa-file-pdf me-1"></i>PDF
        </button>
    @endif
    @if(can('ecom_order.export'))
        <a download href="{{ route('ecom.orders.export', request()->query()) }}" class="no-loader btn btn-sm btn-success shadow-sm"><i class="fas fa-file-csv me-1"></i>Export CSV</a>
    @endif
@endpush

@section('content')
<div class="card glass-card w-100">
    {{-- Status tabs --}}
    <ul class="nav nav-pills nav-sm mb-3 flex-nowrap overflow-auto small">
        <li class="nav-item">
            <a class="nav-link py-1 {{ request('status') ? '' : 'active' }}" href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}">
                All <span class="badge bg-secondary">{{ $statusCounts->sum() }}</span>
            </a>
        </li>
        @foreach(\ME\Ecom\Enums\OrderStatus::cases() as $status)
            <li class="nav-item">
                <a class="nav-link py-1 text-nowrap {{ request('status') === $status->value ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => $status->value, 'page' => null]) }}">
                    {{ $status->label() }} <span class="badge bg-{{ $status->color() }}">{{ $statusCounts[$status->value] ?? 0 }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <form method="GET" action="{{ url()->current() }}" class="mb-3">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <div class="row g-2">
            <div class="col-md-3"><input type="text" name="search" class="form-control form-control-sm" placeholder="Order no, name, phone, tracking" value="{{ request('search') }}"></div>
            <div class="col-md">
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">Any Payment Method</option>
                    @foreach(\ME\Ecom\Enums\PaymentMethod::cases() as $method)
                        <option value="{{ $method->value }}" @selected(request('payment_method') === $method->value)>{{ $method->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md">
                <select name="payment_status" class="form-select form-select-sm">
                    <option value="">Any Payment Status</option>
                    @foreach(\ME\Ecom\Enums\PaymentStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('payment_status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto"><input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}" title="From date"></div>
            <div class="col-md-auto"><input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}" title="To date"></div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-sm btn-encodex-search rounded"><i class="fas fa-search"></i> Search</button>
                <a href="{{ url()->current() }}" class="btn btn-sm btn-encodex-clear rounded"><i class="fas fa-eraser"></i> Reset</a>
            </div>
        </div>
    </form>

    @if(can('ecom_order.invoice'))
        <form id="ecBulkInvoices" action="{{ route('ecom.orders.invoices') }}" method="GET" target="_blank" class="d-none"></form>
    @endif
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover table-striped table-encodex align-middle">
            <thead class="text-center">
                <tr>@if(can('ecom_order.invoice'))<th style="width:32px"><input type="checkbox" class="form-check-input" data-bulk-all title="Select all on this page"></th>@endif<th>Order</th><th>Date</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Courier</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        @if(can('ecom_order.invoice'))<td class="text-center"><input type="checkbox" class="form-check-input" name="ids[]" value="{{ $order->id }}" form="ecBulkInvoices" data-bulk-row></td>@endif
                        <td><a href="{{ route('ecom.orders.show', $order) }}" class="fw-semibold">{{ $order->order_number }}</a></td>
                        <td class="small text-nowrap">{{ $order->created_at->format('d M Y') }}<div class="text-muted">{{ $order->created_at->format('h:i A') }}</div></td>
                        <td>{{ $order->customer_name }}<div class="small text-muted">{{ $order->customer_phone }}</div></td>
                        <td class="text-center">{{ $order->items_count }}</td>
                        <td class="text-end fw-semibold text-nowrap">{{ ecom_money($order->total) }}</td>
                        <td class="text-center small">
                            {{ $order->payment_method->label() }}<br>
                            @include('ecom::partials.status-badge', ['status' => $order->payment_status])
                        </td>
                        <td class="text-center">@include('ecom::partials.status-badge', ['status' => $order->status])</td>
                        <td class="small">
                            @if($order->courier)
                                {{ config("ecom.couriers.{$order->courier}.label", $order->courier) }}
                                <div class="text-muted">{{ $order->tracking_id }}</div>
                            @else — @endif
                        </td>
                        <td class="text-center text-nowrap">
                            <a href="{{ route('ecom.orders.show', $order) }}" class="btn btn-sm btn-encodex-show" title="View"><i class="fas fa-eye"></i></a>
                            @if(can('ecom_order.invoice'))
                                <a href="{{ route('ecom.orders.invoice', $order) }}" target="_blank" class="btn btn-sm btn-encodex-list text-white" title="Invoice"><i class="fas fa-print"></i></a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No orders found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())<div class="mt-3">{{ $orders->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rows = () => document.querySelectorAll('[data-bulk-row]');
        const refresh = function () {
            const ticked = [...rows()].filter(box => box.checked).length;
            document.querySelectorAll('[data-bulk-count]').forEach(el => el.textContent = ticked);
            document.querySelectorAll('[data-bulk-invoice]').forEach(btn => btn.disabled = ticked === 0);
        };
        document.querySelector('[data-bulk-all]')?.addEventListener('change', function () {
            rows().forEach(box => box.checked = this.checked);
            refresh();
        });
        rows().forEach(box => box.addEventListener('change', refresh));
    });
</script>
@endpush
