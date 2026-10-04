@extends('me::master')
@section('title', 'Customer Report')

@section('content')
<div class="card glass-card w-100">
    @include('ecom::partials.date-filter')
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover table-striped table-encodex">
            <thead class="text-center"><tr><th>#</th><th>Customer</th><th>Phone</th><th>Orders</th><th>Spent</th><th>Avg. Order</th><th>Last Order</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td><a href="{{ route('ecom.customers.show', $row->id) }}">{{ $row->name }}</a></td>
                        <td>{{ $row->phone }}</td>
                        <td class="text-center">{{ $row->orders }}</td>
                        <td class="text-end fw-semibold">{{ ecom_money($row->spent) }}</td>
                        <td class="text-end">{{ ecom_money($row->spent / max(1, $row->orders)) }}</td>
                        <td class="small">{{ \Carbon\Carbon::parse($row->last_order_at)->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No customer orders in this period</td></tr>
                @endforelse
            </tbody>
            @if($rows->isNotEmpty())
                <tfoot class="fw-bold"><tr><td colspan="3" class="text-end">{{ $rows->count() }} customers</td><td class="text-center">{{ $rows->sum('orders') }}</td><td class="text-end">{{ ecom_money($rows->sum('spent')) }}</td><td colspan="2"></td></tr></tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
