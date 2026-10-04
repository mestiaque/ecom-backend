{{-- One attribute value as a small chip; colour values show their swatch. --}}
<span class="badge bg-light text-dark border fw-normal me-1 mb-1" title="{{ isset($value->variants_count) ? $value->variants_count . ' variants' : '' }}">
    @if($attribute->isColor() && $value->color_code)
        <span class="d-inline-block rounded-circle border align-middle me-1" style="width:12px;height:12px;background:{{ $value->color_code }}"></span>
    @endif
    {{ $value->value }}
</span>
