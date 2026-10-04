@extends('me::master')
@section('title', 'Coupons')

@push('buttons')
    @if(can('ecom_coupon.create'))
        @component('me::components.btn.add-button', ['route' => route('ecom.coupons.create'), 'text' => 'Add Coupon', 'class' => 'btn-encodex-create'])
        @endcomponent
    @endif
@endpush

@section('content')
<div class="card glass-card w-100">
    <form method="GET" action="{{ url()->current() }}" class="mb-3">
        <div class="row g-2">
            <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Coupon code" value="{{ request('search') }}"></div>
            <div class="col-md-auto"><button type="submit" class="btn btn-sm btn-encodex-search rounded"><i class="fas fa-search"></i> Search</button></div>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover table-striped table-encodex align-middle">
            <thead class="text-center"><tr><th>Code</th><th>Discount</th><th>Min Order</th><th>Used</th><th>Valid</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($coupons as $coupon)
                    @php($label = $coupon->status_label)
                    <tr>
                        <td><code class="fs-6 fw-bold">{{ $coupon->code }}</code><div class="small text-muted">{{ $coupon->description }}</div></td>
                        <td class="text-center">
                            {{ $coupon->type === 'percent' ? rtrim(rtrim($coupon->value, '0'), '.') . '%' : ecom_money($coupon->value) }}
                            @if($coupon->type === 'percent' && $coupon->max_discount)<div class="small text-muted">max {{ ecom_money($coupon->max_discount) }}</div>@endif
                        </td>
                        <td class="text-end">{{ $coupon->min_order_amount ? ecom_money($coupon->min_order_amount) : '—' }}</td>
                        <td class="text-center">{{ $coupon->used_count }} / {{ $coupon->usage_limit ?? '∞' }}</td>
                        <td class="small">{{ $coupon->starts_at?->format('d M Y') ?? 'Now' }} → {{ $coupon->expires_at?->format('d M Y') ?? 'No expiry' }}</td>
                        <td class="text-center"><span class="badge bg-{{ $label === 'Active' ? 'success' : ($label === 'Scheduled' ? 'info' : 'secondary') }}">{{ $label }}</span></td>
                        <td class="text-center">
                            @include('ecom::partials.actions', [
                                'edit' => can('ecom_coupon.edit') ? route('ecom.coupons.edit', $coupon) : null,
                                'delete' => can('ecom_coupon.delete') ? route('ecom.coupons.destroy', $coupon) : null,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No coupons yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($coupons->hasPages())<div class="mt-3">{{ $coupons->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
