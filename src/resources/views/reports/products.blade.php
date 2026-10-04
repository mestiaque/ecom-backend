@extends('me::master')
@section('title', 'Product Report')

@section('content')
<div class="card glass-card w-100">
    @include('ecom::partials.date-filter')
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover table-striped table-encodex">
            <thead class="text-center"><tr><th>#</th><th>Product</th><th>Quantity Sold</th><th>Orders</th><th>Revenue</th><th>Share</th></tr></thead>
            <tbody>
                @php($total = max(1, (float) $rows->sum('revenue')))
                @forelse($rows as $row)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>@if($row->product_id)<a href="{{ route('ecom.products.show', $row->product_id) }}">{{ $row->product_name }}</a>@else{{ $row->product_name }} <span class="text-muted small">(deleted)</span>@endif</td>
                        <td class="text-center fw-semibold">{{ $row->quantity }}</td>
                        <td class="text-center">{{ $row->orders }}</td>
                        <td class="text-end">{{ ecom_money($row->revenue) }}</td>
                        <td style="width:18%">
                            <div class="progress" style="height:8px" title="{{ round($row->revenue / $total * 100, 1) }}%"><div class="progress-bar" style="width: {{ $row->revenue / $total * 100 }}%"></div></div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No sales in this period</td></tr>
                @endforelse
            </tbody>
            @if($rows->isNotEmpty())
                <tfoot class="fw-bold"><tr><td colspan="2" class="text-end">Total</td><td class="text-center">{{ $rows->sum('quantity') }}</td><td></td><td class="text-end">{{ ecom_money($rows->sum('revenue')) }}</td><td></td></tr></tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
