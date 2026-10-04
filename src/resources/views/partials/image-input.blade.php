{{-- Image upload with preview of the current file. $name, $label, $current (path|null), $help --}}
<div class="form-group mb-3">
    <label for="{{ $name }}" class="font-weight-bold text-primary"><i class="fas fa-image me-1"></i> {{ $label }}</label>
    @if(!empty($current))
        <div class="d-flex align-items-center gap-3 mb-2">
            <img src="{{ ecom_image($current) }}" alt="" class="rounded border" style="height:60px;max-width:160px;object-fit:contain;background:#fff">
            @unless(!empty($required))
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remove_{{ $name }}" value="1" id="remove_{{ $name }}">
                    <label class="form-check-label small text-danger" for="remove_{{ $name }}">Remove</label>
                </div>
            @endunless
        </div>
    @endif
    <input type="file" class="form-control form-control-sm @error($name) is-invalid @enderror" id="{{ $name }}" name="{{ $name }}" accept="image/*" {{ !empty($required) && empty($current) ? 'required' : '' }}>
    @if(!empty($help))<small class="form-text text-muted">{{ $help }}</small>@endif
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
