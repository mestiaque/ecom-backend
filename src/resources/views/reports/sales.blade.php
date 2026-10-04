@extends('me::master')
@section('title', 'Sales Report')

@section('content')
<div class="card glass-card w-100 mb-3">
    @php($periodSelect = '<div class="col-md-auto"><label class="small text-muted mb-0">Group by</label><select name="period" class="form-select form-select-sm">'
        . collect(['daily' => 'Day', 'weekly' => 'Week', 'monthly' => 'Month'])->map(fn ($l, $v) => '<option value="' . $v . '"' . ($period === $v ? ' selected' : '') . '>' . $l . '</option>')->implode('')
        . '</select></div>')
    @include('ecom::partials.date-filter', ['extra' => $periodSelect])

    <div class="row g-2 mb-3 text-center">
        <div class="col-6 col-md"><div class="border rounded p-2"><div class="small text-muted">Revenue</div><div class="fw-bold fs-5">{{ ecom_money($totals['revenue']) }}</div></div></div>
        <div class="col-6 col-md"><div class="border rounded p-2"><div class="small text-muted">Orders</div><div class="fw-bold fs-5">{{ $totals['orders'] }}</div></div></div>
        <div class="col-6 col-md"><div class="border rounded p-2"><div class="small text-muted">Items Sold</div><div class="fw-bold fs-5">{{ $totals['items'] }}</div></div></div>
        <div class="col-6 col-md"><div class="border rounded p-2"><div class="small text-muted">Avg. Order</div><div class="fw-bold fs-5">{{ ecom_money($totals['orders'] ? $totals['revenue'] / $totals['orders'] : 0) }}</div></div></div>
        <div class="col-6 col-md"><div class="border rounded p-2"><div class="small text-muted">Discounts</div><div class="fw-bold fs-5">{{ ecom_money($totals['discount']) }}</div></div></div>
        <div class="col-6 col-md"><div class="border rounded p-2"><div class="small text-muted">Shipping</div><div class="fw-bold fs-5">{{ ecom_money($totals['shipping']) }}</div></div></div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3 small">
        @foreach(\ME\Ecom\Enums\OrderStatus::cases() as $status)
            <span class="badge bg-{{ $status->color() }}-subtle text-{{ $status->color() }}-emphasis">{{ $status->label() }}: {{ $statusCounts[$status->value] }}</span>
        @endforeach
        <span class="text-muted">(cancelled and returned orders are not counted in sales)</span>
    </div>

    <div id="salesChart" style="min-height:300px"></div>
</div>

<div class="card glass-card w-100">
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover table-striped table-encodex">
            <thead class="text-center"><tr><th>Period</th><th>Orders</th><th>Revenue</th><th>Discount</th><th>Shipping</th></tr></thead>
            <tbody>
                @foreach($rows->reverse() as $row)
                    <tr class="{{ $row['orders'] ? '' : 'text-muted' }}">
                        <td>{{ $row['label'] }}</td>
                        <td class="text-center">{{ $row['orders'] }}</td>
                        <td class="text-end">{{ ecom_money($row['revenue']) }}</td>
                        <td class="text-end">{{ ecom_money($row['discount']) }}</td>
                        <td class="text-end">{{ ecom_money($row['shipping']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const currency = @json(config('ecom.currency_symbol'));
    new ApexCharts(document.querySelector('#salesChart'), {
        chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [{ name: 'Revenue', data: @json($rows->pluck('revenue')) }],
        xaxis: { categories: @json($rows->pluck('label')) },
        yaxis: { labels: { formatter: v => currency + Math.round(v).toLocaleString() } },
        colors: ['#4e73df'],
        dataLabels: { enabled: false },
        plotOptions: { bar: { borderRadius: 3, columnWidth: '55%' } },
    }).render();
});
</script>
@endpush
