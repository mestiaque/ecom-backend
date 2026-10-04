{{-- Report date range filter. $from, $to (Carbon), $extra (slot html) --}}
<form method="GET" action="{{ url()->current() }}" class="mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-auto">
            <label class="small text-muted mb-0">From</label>
            <input type="date" name="from" class="form-control form-control-sm" value="{{ $from->toDateString() }}">
        </div>
        <div class="col-md-auto">
            <label class="small text-muted mb-0">To</label>
            <input type="date" name="to" class="form-control form-control-sm" value="{{ $to->toDateString() }}">
        </div>
        {!! $extra ?? '' !!}
        <div class="col-md-auto">
            <button type="submit" class="btn btn-sm btn-encodex-search rounded"><i class="fas fa-search"></i> Show</button>
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-success rounded"><i class="fas fa-file-csv"></i> Export CSV</a>
        </div>
        <div class="col-md-auto ms-md-auto">
            @php($presets = ['Today' => [now(), now()], '7 days' => [now()->subDays(6), now()], '30 days' => [now()->subDays(29), now()], 'This month' => [now()->startOfMonth(), now()], 'This year' => [now()->startOfYear(), now()]])
            @foreach($presets as $name => [$a, $b])
                <a href="{{ request()->fullUrlWithQuery(['from' => $a->toDateString(), 'to' => $b->toDateString(), 'export' => null]) }}" class="btn btn-sm btn-outline-secondary rounded">{{ $name }}</a>
            @endforeach
        </div>
    </div>
</form>
