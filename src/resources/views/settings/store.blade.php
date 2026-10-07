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
            <div class="card glass-card mb-3">
                <div class="card-header d-flex align-items-center">
                    <span class="fw-semibold"><i class="fas fa-file-invoice me-1"></i> Invoice</span>
                    @if($latestOrder = \ME\Ecom\Models\Order::latest('id')->first())
                        <a href="{{ route('ecom.orders.invoice', $latestOrder) }}" target="_blank" class="btn btn-sm btn-outline-primary">Preview</a>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">@include('ecom::partials.field', ['name' => 'invoice_prefix', 'label' => $fields['invoice_prefix'], 'value' => $settings->get('invoice_prefix', 'INV-'), 'help' => 'INV- → INV-000123'])</div>
                        <div class="col-6">
                            <div class="form-group mb-3">
                                <label for="invoice_accent" class="font-weight-bold text-primary">{{ $fields['invoice_accent'] }}</label>
                                <input type="color" id="invoice_accent" name="invoice_accent" class="form-control form-control-sm form-control-color w-100 @error('invoice_accent') is-invalid @enderror" value="{{ old('invoice_accent', $settings->get('invoice_accent', '#1f3a5f')) }}">
                                @error('invoice_accent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group mb-3">
                                <label for="invoice_paper" class="font-weight-bold text-primary">{{ $fields['invoice_paper'] }}</label>
                                <select id="invoice_paper" name="invoice_paper" class="form-select form-select-sm">
                                    @foreach(\ME\Ecom\Services\InvoiceService::PAPERS as $value => $label)
                                        <option value="{{ $value }}" @selected(old('invoice_paper', $settings->get('invoice_paper', 'a4')) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-6">@include('ecom::partials.field', ['name' => 'invoice_signature', 'label' => $fields['invoice_signature'], 'value' => $settings->get('invoice_signature', 'Authorized Signature'), 'help' => 'Empty = no signature line'])</div>
                        <div class="col-6">@include('ecom::partials.field', ['name' => 'invoice_tax_label', 'label' => $fields['invoice_tax_label'], 'value' => $settings->get('invoice_tax_label'), 'attrs' => 'placeholder="BIN / VAT Reg. No"'])</div>
                        <div class="col-6">@include('ecom::partials.field', ['name' => 'invoice_tax_number', 'label' => $fields['invoice_tax_number'], 'value' => $settings->get('invoice_tax_number')])</div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="invoice_notes" class="font-weight-bold text-primary">{{ $fields['invoice_notes'] }}</label>
                        <textarea id="invoice_notes" name="invoice_notes" rows="2" class="form-control form-control-sm" placeholder="e.g. 7 days easy return with the original box">{{ old('invoice_notes', $settings->get('invoice_notes')) }}</textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label for="invoice_terms" class="font-weight-bold text-primary">{{ $fields['invoice_terms'] }}</label>
                        <textarea id="invoice_terms" name="invoice_terms" rows="3" class="form-control form-control-sm" placeholder="Warranty and return terms printed at the bottom">{{ old('invoice_terms', $settings->get('invoice_terms')) }}</textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="invoice_show_sku" value="0">
                        <input class="form-check-input" type="checkbox" id="invoice_show_sku" name="invoice_show_sku" value="1" @checked(old('invoice_show_sku', $settings->get('invoice_show_sku', '1')) == '1')>
                        <label class="form-check-label small" for="invoice_show_sku">{{ $fields['invoice_show_sku'] }}</label>
                    </div>
                    <small class="text-muted d-block mt-2">The footer note is set under Store &amp; Contact. The logo comes from Branding.</small>
                </div>
            </div>
            <button type="submit" class="btn btn-encodex-save w-100"><i class="fas fa-save me-1"></i> Save</button>
        </div>
    </div>
</form>
@endsection
