@extends('me::master')
@section('title', 'Store Info')

@section('content')
<form action="{{ route('ecom.settings.store.update') }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card glass-card mb-3">
                <div class="card-header fw-semibold"><i class="fas fa-store me-1"></i> Store & Contact</div>
                <div class="card-body">
                    <div class="row">
                        @foreach(['store_name', 'store_tagline', 'store_phone', 'store_email', 'store_hotline_hours', 'order_prefix'] as $key)
                            <div class="col-md-6">@include('ecom::partials.field', ['name' => $key, 'label' => $fields[$key], 'value' => $settings->get($key), 'help' => $key === 'order_prefix' ? 'e.g. ORD- → ORD-000123 (new orders only)' : null])</div>
                        @endforeach
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-primary">{{ $fields['store_address'] }}</label>
                        <textarea name="store_address" rows="2" class="form-control form-control-sm">{{ old('store_address', $settings->get('store_address')) }}</textarea>
                    </div>
                    @include('ecom::partials.field', ['name' => 'invoice_footer', 'label' => $fields['invoice_footer'], 'value' => $settings->get('invoice_footer')])
                </div>
            </div>
            <div class="card glass-card mb-3">
                <div class="card-header fw-semibold"><i class="fas fa-share-alt me-1"></i> Social Links</div>
                <div class="card-body">
                    <div class="row">
                        @foreach(['social_facebook' => 'fab fa-facebook', 'social_instagram' => 'fab fa-instagram', 'social_youtube' => 'fab fa-youtube', 'social_tiktok' => 'fab fa-tiktok', 'social_whatsapp' => 'fab fa-whatsapp'] as $key => $icon)
                            <div class="col-md-6">@include('ecom::partials.field', ['name' => $key, 'label' => $fields[$key], 'value' => $settings->get($key), 'icon' => $icon])</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card glass-card mb-3">
                <div class="card-header fw-semibold"><i class="fas fa-image me-1"></i> Branding</div>
                <div class="card-body">
                    @include('me::components.media-input', ['name' => 'store_logo', 'collection' => 'image', 'model' => \ME\Models\Setting::firstWhere('key', 'ecom_store_logo'), 'label' => 'Site Logo', 'help' => 'Shown on the website and invoices'])
                    @include('me::components.media-input', ['name' => 'store_favicon', 'collection' => 'image', 'model' => \ME\Models\Setting::firstWhere('key', 'ecom_store_favicon'), 'label' => 'Favicon', 'accept' => 'image/*,.ico'])
                </div>
            </div>
            <button type="submit" class="btn btn-encodex-save w-100"><i class="fas fa-save me-1"></i> Save</button>
        </div>
    </div>
</form>
@endsection
