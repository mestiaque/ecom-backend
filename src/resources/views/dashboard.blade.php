@extends('me::master')
@section('title', 'Dashboard')

@push('css')
<style>
    .ec-stat { border: 0; border-radius: 16px; transition: transform .15s ease, box-shadow .15s ease; }
    .ec-stat:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(15, 45, 74, .12); }
    .ec-stat .ec-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #fff; }
    .ec-stat .ec-value { font-size: 1.45rem; font-weight: 700; line-height: 1.15; }
    .ec-stat .ec-label { font-size: .78rem; color: #6c757d; text-transform: uppercase; letter-spacing: .03em; }
    .ec-stat .ec-sub { font-size: .78rem; color: #6c757d; }
    .ec-g1 { background: linear-gradient(135deg, #4e73df, #224abe); }
    .ec-g2 { background: linear-gradient(135deg, #1cc88a, #13855c); }
    .ec-g3 { background: linear-gradient(135deg, #f6c23e, #dda20a); }
    .ec-g4 { background: linear-gradient(135deg, #e74a3b, #be2617); }
    .ec-g5 { background: linear-gradient(135deg, #36b9cc, #258391); }
    .ec-g6 { background: linear-gradient(135deg, #858796, #60616f); }
    .ec-panel { border: 0; border-radius: 16px; }
    .ec-panel .card-header { background: transparent; border-bottom: 1px solid rgba(0,0,0,.06); font-weight: 600; }
    .ec-status { border-radius: 12px; padding: .6rem .75rem; text-decoration: none; display: block; }
    .ec-status:hover { filter: brightness(.96); }
    .ec-thumb { width: 36px; height: 36px; object-fit: cover; border-radius: 8px; background: #f1f3f5; }
</style>
@endpush

@section('content')
@php
    $cards = [
        ['label' => "Today's Sales",   'value' => ecom_money($today['revenue']), 'sub' => $today['orders'] . ' orders · ' . $today['items'] . ' items', 'icon' => 'fas fa-coins',         'grad' => 'ec-g1'],
        ["label" => "Today's Orders",  'value' => $today['orders'],              'sub' => 'Discount ' . ecom_money($today['discount']),                  'icon' => 'fas fa-shopping-bag',  'grad' => 'ec-g5'],
        ['label' => 'This Month Sales','value' => ecom_money($month['revenue']), 'sub' => $month['orders'] . ' orders · ' . $month['items'] . ' items', 'icon' => 'fas fa-chart-line',    'grad' => 'ec-g2'],
        ['label' => 'Avg. Order (Month)', 'value' => ecom_money($month['orders'] ? $month['revenue'] / $month['orders'] : 0), 'sub' => 'Shipping ' . ecom_money($month['shipping']), 'icon' => 'fas fa-receipt', 'grad' => 'ec-g6'],
        ['label' => 'New Customers',   'value' => $newCustomersToday,            'sub' => $newCustomersMonth . ' this month',                           'icon' => 'fas fa-user-plus',     'grad' => 'ec-g3'],
        ['label' => 'Low Stock',       'value' => $lowStockCount,                'sub' => $pendingReviews . ' reviews waiting',                         'icon' => 'fas fa-exclamation-triangle', 'grad' => 'ec-g4'],
    ];
@endphp

<div class="row g-3 mb-3 mt-1">
    @foreach($cards as $card)
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card ec-stat glass-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="ec-label">{{ $card['label'] }}</div>
                        <div class="ec-icon {{ $card['grad'] }}"><i class="{{ $card['icon'] }}"></i></div>
                    </div>
                    <div class="ec-value">{{ $card['value'] }}</div>
                    <div class="ec-sub mt-1">{{ $card['sub'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Orders by status --}}
<div class="card ec-panel glass-card mb-3">
    <div class="card-header"><i class="fas fa-tasks me-1 text-primary"></i> Orders by Status</div>
    <div class="card-body">
        <div class="row g-2">
            @foreach(\ME\Ecom\Enums\OrderStatus::cases() as $status)
                <div class="col-6 col-md-3 col-xl">
                    <a href="{{ route('ecom.orders.index', ['status' => $status->value]) }}" class="ec-status bg-{{ $status->color() }}-subtle text-{{ $status->color() }}-emphasis">
                        <div class="small"><i class="{{ $status->icon() }} me-1"></i>{{ $status->label() }}</div>
                        <div class="fs-4 fw-bold">{{ $statusCounts[$status->value] ?? 0 }}</div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card ec-panel glass-card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="fas fa-chart-area me-1 text-primary"></i> Sales</span>
                <div class="btn-group btn-group-sm" id="ecChartPeriod">
                    <button type="button" class="btn btn-outline-primary active" data-period="daily">Daily</button>
                    <button type="button" class="btn btn-outline-primary" data-period="weekly">Weekly</button>
                    <button type="button" class="btn btn-outline-primary" data-period="monthly">Monthly</button>
                </div>
            </div>
            <div class="card-body">
                <div id="ecSalesChart" style="min-height: 320px;"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card ec-panel glass-card h-100">
            <div class="card-header"><i class="fas fa-trophy me-1 text-warning"></i> Top Selling (30 days)</div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <tbody>
                        @forelse($topProducts as $row)
                            <tr>
                                <td class="ps-3">
                                    @if($row->product_id)
                                        <a href="{{ route('ecom.products.show', $row->product_id) }}">{{ \Illuminate\Support\Str::limit($row->product_name, 34) }}</a>
                                    @else
                                        {{ \Illuminate\Support\Str::limit($row->product_name, 34) }}
                                    @endif
                                    <div class="small text-muted">{{ ecom_money($row->revenue) }}</div>
                                </td>
                                <td class="text-end pe-3 align-middle"><span class="badge bg-primary-subtle text-primary">{{ $row->quantity }} sold</span></td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-muted py-4">No sales yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card ec-panel glass-card h-100">
            <div class="card-header d-flex justify-content-between">
                <span><i class="fas fa-receipt me-1 text-info"></i> Recent Orders</span>
                <a href="{{ route('ecom.orders.index') }}" class="small">View all</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <tbody>
                            @forelse($recentOrders as $order)
                                <tr>
                                    <td class="ps-3"><a href="{{ route('ecom.orders.show', $order) }}" class="fw-semibold">{{ $order->order_number }}</a></td>
                                    <td>{{ $order->customer_name }}<div class="small text-muted">{{ $order->customer_phone }}</div></td>
                                    <td class="text-end">{{ ecom_money($order->total) }}</td>
                                    <td class="text-center">@include('ecom::partials.status-badge', ['status' => $order->status])</td>
                                    <td class="text-muted small pe-3 text-end">{{ $order->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr><td class="text-center text-muted py-4">No orders yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card ec-panel glass-card h-100">
            <div class="card-header d-flex justify-content-between">
                <span><i class="fas fa-exclamation-triangle me-1 text-danger"></i> Low Stock Alert</span>
                <a href="{{ route('ecom.products.index', ['stock' => 'low']) }}" class="small">View all</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <tbody>
                        @forelse($lowStock as $product)
                            <tr>
                                <td class="ps-3" style="width:48px">
                                    @if($product->thumbnail)<img src="{{ $product->thumbnail }}" class="ec-thumb" alt="">@else<div class="ec-thumb"></div>@endif
                                </td>
                                <td><a href="{{ route('ecom.products.edit', $product) }}">{{ \Illuminate\Support\Str::limit($product->title, 30) }}</a></td>
                                <td class="text-end pe-3">
                                    <span class="badge {{ $product->stock <= 0 ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $product->stock <= 0 ? 'Out' : $product->stock . ' left' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-muted py-4">All products are well stocked</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const currency = @json(config('ecom.currency_symbol'));
    const chart = new ApexCharts(document.querySelector('#ecSalesChart'), {
        chart: { type: 'area', height: 320, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [{ name: 'Revenue', type: 'area', data: [] }, { name: 'Orders', type: 'column', data: [] }],
        xaxis: { categories: [] },
        yaxis: [
            { title: { text: 'Revenue' }, labels: { formatter: v => currency + Math.round(v).toLocaleString() } },
            { opposite: true, title: { text: 'Orders' }, labels: { formatter: v => Math.round(v) } },
        ],
        stroke: { curve: 'smooth', width: [2, 0] },
        colors: ['#4e73df', '#1cc88a'],
        dataLabels: { enabled: false },
        fill: { type: ['gradient', 'solid'], gradient: { opacityFrom: .35, opacityTo: .05 } },
        plotOptions: { bar: { columnWidth: '40%', borderRadius: 3 } },
        noData: { text: 'Loading…' },
    });
    chart.render();

    function load(period) {
        fetch(@json(route('ecom.dashboard.chart')) + '?period=' + period, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                chart.updateOptions({ xaxis: { categories: data.labels } });
                chart.updateSeries([{ name: 'Revenue', type: 'area', data: data.revenue }, { name: 'Orders', type: 'column', data: data.orders }]);
            });
    }

    document.querySelectorAll('#ecChartPeriod button').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('#ecChartPeriod button').forEach(b => b.classList.remove('active'));
            button.classList.add('active');
            load(button.dataset.period);
        });
    });

    load('daily');
});
</script>
@endpush
