<div class="form-check form-switch mb-3">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" class="form-check-input" id="{{ $id ?? $name }}" name="{{ $name }}" value="1" {{ old($name, $checked ?? false) ? 'checked' : '' }}>
    <label class="form-check-label font-weight-bold" for="{{ $id ?? $name }}">{{ $label }}</label>
    @if(!empty($help))<small class="form-text text-muted d-block">{{ $help }}</small>@endif
</div>
