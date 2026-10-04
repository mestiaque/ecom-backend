{{-- Text-like input. $name, $label, $value, $type, $icon, $required, $help, $attrs, $step --}}
<div class="form-group mb-3">
    <label for="{{ $id ?? $name }}" class="font-weight-bold text-primary">
        @isset($icon)<i class="{{ $icon }} me-1"></i>@endisset {{ $label }} @if(!empty($required))<span class="text-danger">*</span>@endif
    </label>
    <input type="{{ $type ?? 'text' }}" class="form-control form-control-sm @error($name) is-invalid @enderror"
           id="{{ $id ?? $name }}" name="{{ $name }}" value="{{ old($name, $value ?? '') }}"
           @if(!empty($required)) required @endif @isset($step) step="{{ $step }}" @endisset {!! $attrs ?? '' !!}>
    @if(!empty($help))<small class="form-text text-muted">{{ $help }}</small>@endif
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
