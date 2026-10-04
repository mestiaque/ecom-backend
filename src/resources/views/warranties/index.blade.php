@extends('me::master')
@section('title', 'Warranties')

@push('buttons')
    @if(can('ecom_warranty.create'))
        @component('me::components.btn.add-button', ['route' => route('ecom.warranties.create'), 'text' => 'Add Warranty', 'class' => 'btn-encodex-create'])
        @endcomponent
    @endif
@endpush

@section('content')
<div class="card glass-card w-100">
    <p class="small text-muted mb-3">Set up warranties once and pick one on each product. Each order keeps the warranty it was sold with; it runs from the delivery date.</p>
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover table-striped table-encodex align-middle">
            <thead class="text-center"><tr><th>Warranty</th><th>Period</th><th>Days</th><th>Products</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($warranties as $warranty)
                    <tr>
                        <td><b><i class="fas fa-shield-alt text-success me-1"></i>{{ $warranty->name }}</b>@if($warranty->description)<div class="small text-muted">{{ $warranty->description }}</div>@endif</td>
                        <td class="text-center">{{ $warranty->period }}</td>
                        <td class="text-center"><span class="badge bg-info-subtle text-info">{{ number_format($warranty->days) }} days</span></td>
                        <td class="text-center">{{ $warranty->products_count }}</td>
                        <td class="text-center">@include('ecom::partials.active-badge', ['active' => $warranty->is_active])</td>
                        <td class="text-center">
                            @include('ecom::partials.actions', [
                                'edit' => can('ecom_warranty.edit') ? route('ecom.warranties.edit', $warranty) : null,
                                'delete' => can('ecom_warranty.delete') ? route('ecom.warranties.destroy', $warranty) : null,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No warranties yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
