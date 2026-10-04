@extends('me::master')
@section('title', 'Payment Methods')

@section('content')
<form action="{{ route('ecom.settings.payment.update') }}" method="POST" autocomplete="off">
    @csrf @method('PUT')
    <div class="row g-3">
        @foreach($methods as $method)
            @php($prefix = "payment_{$method->value}_")
            <div class="col-lg-6">
                <div class="card glass-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">{{ $method->label() }}</span>
                        <div class="form-check form-switch mb-0">
                            <input type="hidden" name="{{ $prefix }}enabled" value="0">
                            <input class="form-check-input" type="checkbox" name="{{ $prefix }}enabled" value="1" id="{{ $prefix }}enabled" @checked(old($prefix . 'enabled', $settings->get($prefix . 'enabled', $method->value === 'cod' ? '1' : '0')))>
                            <label class="form-check-label small" for="{{ $prefix }}enabled">Enabled</label>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($method->hasSandbox())
                            <div class="mb-2">
                                <label class="form-label small mb-0">Mode</label>
                                <select name="{{ $prefix }}mode" class="form-select form-select-sm">
                                    <option value="sandbox" @selected($settings->get($prefix . 'mode', 'sandbox') === 'sandbox')>Sandbox (test)</option>
                                    <option value="live" @selected($settings->get($prefix . 'mode') === 'live')>Live</option>
                                </select>
                            </div>
                        @endif
                        @foreach($method->credentialFields() as $field => $meta)
                            <div class="mb-2">
                                <label class="form-label small mb-0">{{ $meta['label'] }}</label>
                                @if($meta['secret'])
                                    <input type="password" name="{{ $prefix . $field }}" class="form-control form-control-sm" autocomplete="new-password"
                                           placeholder="{{ $settings->get($prefix . $field) ? '•••••••• saved — leave empty to keep' : '' }}">
                                @elseif(str_contains($field, 'key'))
                                    <textarea name="{{ $prefix . $field }}" rows="2" class="form-control form-control-sm">{{ old($prefix . $field, $settings->get($prefix . $field)) }}</textarea>
                                @else
                                    <input type="text" name="{{ $prefix . $field }}" class="form-control form-control-sm" value="{{ old($prefix . $field, $settings->get($prefix . $field)) }}">
                                @endif
                            </div>
                        @endforeach
                        <label class="form-label small mb-0">Instructions shown to customers</label>
                        <textarea name="{{ $prefix }}instructions" rows="2" class="form-control form-control-sm" placeholder="{{ $method->value === 'cod' ? 'Pay the rider when you receive the parcel.' : 'You will be redirected to complete payment.' }}">{{ old($prefix . 'instructions', $settings->get($prefix . 'instructions')) }}</textarea>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="alert alert-info small mt-3">
        Gateway credentials are stored encrypted. Online checkout (redirect + callback) for bKash, Nagad and SSLCommerz runs on the storefront; payments you receive by hand can be added on each order with <b>Record Payment</b>.
    </div>
    <div class="text-end"><button type="submit" class="btn btn-encodex-save"><i class="fas fa-save me-1"></i> Save</button></div>
</form>
@endsection
