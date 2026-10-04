@extends('me::master')
@section('title', 'FAQ')

@push('buttons')
    @component('me::components.btn.add-button', ['route' => route('ecom.faqs.create'), 'text' => 'Add FAQ', 'class' => 'btn-encodex-create'])
    @endcomponent
@endpush

@section('content')
<div class="card glass-card w-100 mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All categories</option>
                    @foreach([\ME\Ecom\Models\Faq::GENERAL, ...$categories] as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8 small text-muted">Shown on the storefront FAQ page (<code>/faq</code>) by category, in "Order" sequence. Drafts are hidden.</div>
        </form>
    </div>
</div>

@forelse($faqs as $category => $items)
    <div class="card glass-card w-100 mb-3">
        <div class="card-header fw-semibold">{{ $category }} <span class="badge bg-secondary ms-1">{{ $items->count() }}</span></div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover table-striped table-encodex align-middle mb-0">
                <thead class="text-center"><tr><th style="width:70px">Order</th><th>Question</th><th style="width:110px">Status</th><th style="width:110px">Actions</th></tr></thead>
                <tbody>
                    @foreach($items as $faq)
                        <tr>
                            <td class="text-center">{{ $faq->sort_order }}</td>
                            <td>
                                <b>{{ $faq->question }}</b>
                                <div class="small text-muted text-truncate" style="max-width:640px">{{ \Illuminate\Support\Str::limit(strip_tags($faq->answer), 140) }}</div>
                            </td>
                            <td class="text-center">@include('ecom::partials.active-badge', ['active' => $faq->is_active, 'on' => 'Published', 'off' => 'Draft'])</td>
                            <td class="text-center">@include('ecom::partials.actions', ['edit' => route('ecom.faqs.edit', $faq), 'delete' => route('ecom.faqs.destroy', $faq)])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="card glass-card w-100"><div class="card-body text-center text-muted py-4">No FAQs yet</div></div>
@endforelse
@endsection
