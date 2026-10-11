<div class="footer {{ ($fixed ?? false) ? 'fixed' : '' }}">
    {{ $options['footer'] }}<br>
    <span class="small">This is a computer-generated invoice &middot; {{ $store['name'] }}@if($store['phone']) &middot; Hotline {{ $store['phone'] }}@endif &middot; Printed {{ now()->format('d M Y, h:i A') }}</span>
</div>
