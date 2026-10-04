@extends('me::master')
@section('title', $faq->exists ? 'Edit FAQ' : 'Add FAQ')

@push('buttons')
    @component('me::components.btn.add-button', ['route' => route('ecom.faqs.index'), 'text' => 'All FAQs', 'class' => 'btn-encodex-list'])
    @endcomponent
@endpush

@section('content')
<form action="{{ $faq->exists ? route('ecom.faqs.update', $faq) : route('ecom.faqs.store') }}" method="POST">
    @csrf
    @if($faq->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card glass-card">
                <div class="card-body">
                    @include('ecom::partials.field', ['name' => 'question', 'label' => 'Question', 'value' => $faq->question, 'required' => true])
                    <label class="font-weight-bold text-primary">Answer <span class="text-danger">*</span></label>
                    <textarea name="answer" class="summernote" data-height="260">{{ old('answer', $faq->answer) }}</textarea>
                    @error('answer')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card glass-card">
                <div class="card-body">
                    @include('ecom::partials.field', ['name' => 'category', 'label' => 'Category', 'value' => $faq->category, 'help' => 'e.g. Orders, Payment, Delivery. Empty = General', 'attrs' => 'list="faqCategories"'])
                    <datalist id="faqCategories">
                        @foreach($categories as $category)<option value="{{ $category }}">@endforeach
                    </datalist>
                    @include('ecom::partials.field', ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'value' => $faq->sort_order, 'help' => 'Smaller numbers come first'])
                    @include('ecom::partials.switch', ['name' => 'is_active', 'label' => 'Published', 'checked' => $faq->is_active])
                    <button type="submit" class="btn btn-encodex-save w-100"><i class="fas fa-save me-1"></i> Save</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
