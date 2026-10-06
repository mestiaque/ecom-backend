@extends('me::master')
@section('title', 'Flash Sale / Campaigns')

@push('buttons')
    @if(can('ecom_campaign.create'))
        @component('me::components.btn.add-button', ['route' => route('ecom.campaigns.create'), 'text' => 'Add Campaign', 'class' => 'btn-encodex-create'])
        @endcomponent
    @endif
@endpush

@section('content')
<div class="card glass-card w-100">
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover table-striped table-encodex align-middle">
            <thead class="text-center"><tr><th>Banner</th><th>Campaign</th><th>Discount</th><th>Products</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($campaigns as $campaign)
                    @php($label = $campaign->status_label)
                    <tr>
                        <td class="text-center" style="width:110px">@if($campaign->banner_url)<img src="{{ $campaign->banner_url }}" alt="" class="rounded" style="height:40px;max-width:100px;object-fit:cover">@else<i class="fas fa-bolt text-warning fa-lg"></i>@endif</td>
                        <td><b>{{ $campaign->title }}</b></td>
                        <td class="text-center">{{ $campaign->discount_type === 'percent' ? rtrim(rtrim($campaign->discount_value, '0'), '.') . '% off' : ecom_money($campaign->discount_value) . ' off' }}</td>
                        <td class="text-center">{{ $campaign->products_count }}</td>
                        <td class="small">{{ $campaign->starts_at->format('d M Y h:i A') }}<br>→ {{ $campaign->ends_at->format('d M Y h:i A') }}</td>
                        <td class="text-center"><span class="badge bg-{{ ['Running' => 'success', 'Upcoming' => 'info'][$label] ?? 'secondary' }}">{{ $label }}</span></td>
                        <td class="text-center">
                            @include('ecom::partials.actions', [
                                'edit' => can('ecom_campaign.edit') ? route('ecom.campaigns.edit', $campaign) : null,
                                'delete' => can('ecom_campaign.delete') ? route('ecom.campaigns.destroy', $campaign) : null,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No campaigns yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($campaigns->hasPages())<div class="mt-3">{{ $campaigns->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
