@extends('me::master')
@section('title', $warranty->exists ? 'Edit Warranty' : 'Add Warranty')

@push('buttons')
    @component('me::components.btn.add-button', ['route' => route('ecom.warranties.index'), 'text' => 'All Warranties', 'class' => 'btn-encodex-list'])
    @endcomponent
@endpush

@section('content')
<div class="card glass-card">
    <div class="card-body">
        <form action="{{ $warranty->exists ? route('ecom.warranties.update', $warranty) : route('ecom.warranties.store') }}" method="POST">
            @csrf
            @if($warranty->exists) @method('PUT') @endif
            <div class="row">
                <div class="col-md-6">
                    @include('ecom::partials.field', ['name' => 'name', 'label' => 'Warranty Name', 'value' => $warranty->name, 'required' => true, 'icon' => 'fas fa-shield-alt', 'help' => 'Shown to customers, e.g. "1 Year Official Warranty"'])

                    <label class="font-weight-bold text-primary">Warranty Period <span class="text-danger">*</span></label>
                    <div class="row g-2 mb-1">
                        <div class="col-4">
                            <input type="number" min="1" max="1000" name="duration" id="duration" value="{{ old('duration', $warranty->duration) }}" class="form-control form-control-sm @error('duration') is-invalid @enderror" required>
                        </div>
                        <div class="col-4">
                            <select name="duration_unit" id="durationUnit" class="form-select form-select-sm">
                                @foreach(\ME\Ecom\Models\Warranty::UNITS as $unit => $days)
                                    <option value="{{ $unit }}" data-days="{{ $days }}" @selected(old('duration_unit', $warranty->duration_unit) === $unit)>{{ ucfirst($unit) }}(s)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <input type="number" min="1" name="days" id="days" value="{{ old('days', $warranty->days) }}" class="form-control @error('days') is-invalid @enderror">
                                <span class="input-group-text">days</span>
                            </div>
                        </div>
                    </div>
                    @error('duration')<div class="text-danger small">{{ $message }}</div>@enderror
                    @error('days')<div class="text-danger small">{{ $message }}</div>@enderror
                    <small class="form-text text-muted d-block mb-3">
                        Days are calculated automatically (1 month = 30 days, 1 year = 365 days). You can type a different number if needed, e.g. 366.
                    </small>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-primary">Description / Terms</label>
                        <textarea name="description" rows="4" class="form-control form-control-sm" placeholder="e.g. Covers manufacturing defects. Physical or water damage not covered.">{{ old('description', $warranty->description) }}</textarea>
                    </div>
                    @include('ecom::partials.switch', ['name' => 'is_active', 'label' => 'Active (can be picked on products)', 'checked' => $warranty->is_active])
                </div>
            </div>
            <div class="text-end"><button type="submit" class="btn btn-encodex-save"><i class="fas fa-save me-1"></i> Save</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const duration = document.getElementById('duration');
    const unit = document.getElementById('durationUnit');
    const days = document.getElementById('days');
    const name = document.getElementById('name');
    // Keep auto-filling until the user types their own day count
    let manualDays = days.value !== '' && Number(days.value) !== (Number(duration.value) || 0) * Number(unit.selectedOptions[0].dataset.days);

    const suggestedName = () => {
        const n = Number(duration.value) || 0;
        return n + ' ' + unit.value.charAt(0).toUpperCase() + unit.value.slice(1) + (n === 1 ? '' : 's') + ' Warranty';
    };
    // Suggest a name like "6 Months Warranty" until the user writes their own
    let manualName = name.value.trim() !== '' && name.value !== suggestedName();

    function calculate() {
        if (!manualDays) days.value = (Number(duration.value) || 0) * Number(unit.selectedOptions[0].dataset.days) || '';
        if (!manualName) name.value = suggestedName();
    }

    duration.addEventListener('input', calculate);
    unit.addEventListener('change', calculate);
    days.addEventListener('input', () => manualDays = days.value !== '');
    name.addEventListener('input', () => manualName = name.value.trim() !== '');
    calculate();
});
</script>
@endpush
