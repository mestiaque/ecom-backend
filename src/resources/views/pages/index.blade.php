@extends('me::master')
@section('title', 'Pages')

@push('buttons')
    @component('me::components.btn.add-button', ['route' => route('ecom.pages.create'), 'text' => 'Add Page', 'class' => 'btn-encodex-create'])
    @endcomponent
@endpush

@section('content')
<div class="card glass-card w-100">
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover table-striped table-encodex align-middle">
            <thead class="text-center"><tr><th>Title</th><th>Slug</th><th>Updated</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($pages as $page)
                    <tr>
                        <td><b>{{ $page->title }}</b></td>
                        <td class="small text-muted">/{{ $page->slug }}</td>
                        <td class="small">{{ $page->updated_at->format('d M Y') }}</td>
                        <td class="text-center">@include('ecom::partials.active-badge', ['active' => $page->is_active, 'on' => 'Published', 'off' => 'Draft'])</td>
                        <td class="text-center">@include('ecom::partials.actions', ['edit' => route('ecom.pages.edit', $page), 'delete' => route('ecom.pages.destroy', $page)])</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No pages yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
