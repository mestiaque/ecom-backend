@extends('me::master')
@section('title', 'Couriers')

@section('content')
<form action="{{ route('ecom.settings.courier.update') }}" method="POST" autocomplete="off">
    @csrf @method('PUT')
    <div class="row g-3">
        @foreach($couriers as $key => $courier)
            @php($prefix = "courier_{$key}_")
            <div class="col-lg-4">
                <div class="card glass-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">{{ $courier->label() }}
                            @if($courier->isConfigured())<span class="badge bg-success ms-1">Ready</span>@endif
                        </span>
                        <div class="form-check form-switch mb-0">
                            <input type="hidden" name="{{ $prefix }}enabled" value="0">
                            <input class="form-check-input" type="checkbox" name="{{ $prefix }}enabled" value="1" id="{{ $prefix }}enabled" @checked($settings->bool($prefix . 'enabled'))>
                            <label class="form-check-label small" for="{{ $prefix }}enabled">Enabled</label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <label class="form-label small mb-0">Mode</label>
                            <select name="{{ $prefix }}mode" class="form-select form-select-sm">
                                <option value="live" @selected($settings->get($prefix . 'mode', 'live') === 'live')>Live</option>
                                <option value="sandbox" @selected($settings->get($prefix . 'mode') === 'sandbox')>Sandbox (test)</option>
                            </select>
                        </div>
                        @foreach($courier->credentialFields() as $field => $meta)
                            <div class="mb-2">
                                <label class="form-label small mb-0">{{ $meta['label'] }}</label>
                                @if($meta['secret'])
                                    <input type="password" name="{{ $prefix . $field }}" class="form-control form-control-sm" autocomplete="new-password"
                                           placeholder="{{ $settings->get($prefix . $field) ? '•••••••• saved — leave empty to keep' : '' }}">
                                @else
                                    <input type="text" name="{{ $prefix . $field }}" class="form-control form-control-sm" value="{{ $settings->get($prefix . $field) }}">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="alert alert-info small mt-3">
        When a courier is enabled and its credentials are filled, the order page can book the parcel directly and saves the tracking ID.
        Any other courier can still be used by entering its tracking ID by hand.
    </div>
    <div class="text-end"><button type="submit" class="btn btn-encodex-save"><i class="fas fa-save me-1"></i> Save</button></div>
</form>
@endsection
