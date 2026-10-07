<div class="footer {{ ($fixed ?? false) ? 'fixed' : '' }}">
    {{ $options['footer'] }}<br>
    <span class="small">{{ $store['name'] }}@if($store['phone']) &middot; {{ $store['phone'] }}@endif &middot; Generated {{ now()->format('d M Y, h:i A') }}</span>
</div>
