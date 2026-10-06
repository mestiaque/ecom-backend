@extends('me::master')
@section('title', 'Transactions')

@push('buttons')
    <a download href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="no-loader btn btn-sm btn-success shadow-sm"><i class="fas fa-file-csv me-1"></i>Export CSV</a>
@endpush

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card glass-card h-100"><div class="card-body"><div class="small text-muted">Received</div><div class="fs-4 fw-bold text-success">{{ ecom_money($totals->received) }}</div></div></div></div>
    <div class="col-md-4"><div class="card glass-card h-100"><div class="card-body"><div class="small text-muted">Refunded</div><div class="fs-4 fw-bold text-danger">{{ ecom_money($totals->refunded) }}</div></div></div></div>
    <div class="col-md-4"><div class="card glass-card h-100"><div class="card-body"><div class="small text-muted">Net</div><div class="fs-4 fw-bold">{{ ecom_money($totals->received - $totals->refunded) }}</div></div></div></div>
</div>

<div class="card glass-card w-100">
    <form method="GET" action="{{ url()->current() }}" class="mb-3">
        <div class="row g-2">
            <div class="col-md-3"><input type="text" name="search" class="form-control form-control-sm" placeholder="Trx ID or order no" value="{{ request('search') }}"></div>
            <div class="col-md">
                <select name="method" class="form-select form-select-sm">
                    <option value="">Any Method</option>
                    @foreach(\ME\Ecom\Enums\PaymentMethod::cases() as $method)
                        <option value="{{ $method->value }}" @selected(request('method') === $method->value)>{{ $method->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md">
                <select name="type" class="form-select form-select-sm">
                    <option value="">Payments & Refunds</option>
                    <option value="payment" @selected(request('type') === 'payment')>Payments</option>
                    <option value="refund" @selected(request('type') === 'refund')>Refunds</option>
                </select>
            </div>
            <div class="col-md-auto"><input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}"></div>
            <div class="col-md-auto"><input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}"></div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-sm btn-encodex-search rounded"><i class="fas fa-search"></i> Search</button>
                <a href="{{ url()->current() }}" class="btn btn-sm btn-encodex-clear rounded"><i class="fas fa-eraser"></i> Reset</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover table-striped table-encodex align-middle">
            <thead class="text-center"><tr><th>Date</th><th>Order</th><th>Type</th><th>Method</th><th>Trx ID</th><th>Amount</th><th>Status</th><th>Note</th></tr></thead>
            <tbody>
                @forelse($transactions as $trx)
                    <tr>
                        <td class="small text-nowrap">{{ $trx->created_at->format('d M Y h:i A') }}</td>
                        <td>@if($trx->order)<a href="{{ route('ecom.orders.show', $trx->order) }}">{{ $trx->order->order_number }}</a>@endif</td>
                        <td class="text-center"><span class="badge {{ $trx->type === 'refund' ? 'bg-danger' : 'bg-success' }}">{{ ucfirst($trx->type) }}</span></td>
                        <td>{{ \ME\Ecom\Enums\PaymentMethod::tryFrom($trx->method)?->label() ?? $trx->method }}</td>
                        <td>{{ $trx->trx_id ?: '—' }}</td>
                        <td class="text-end fw-semibold {{ $trx->type === 'refund' ? 'text-danger' : '' }}">{{ $trx->type === 'refund' ? '−' : '' }}{{ ecom_money($trx->amount) }}</td>
                        <td class="text-center"><span class="badge bg-{{ $trx->status === 'success' ? 'success' : ($trx->status === 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($trx->status) }}</span></td>
                        <td class="small">{{ $trx->note }} @if($trx->user)<span class="text-muted">— {{ $trx->user->name }}</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No transactions found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())<div class="mt-3">{{ $transactions->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
