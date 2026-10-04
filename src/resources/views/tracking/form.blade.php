@extends('ecom::tracking.layout')
@section('title', 'Track Order')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card ec-card">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success mb-3" style="width:64px;height:64px">
                        <i class="fas fa-shipping-fast fa-lg"></i>
                    </div>
                    <h4 class="fw-bold mb-1">Track your order</h4>
                    <p class="text-muted mb-0">Enter the order number from your SMS or invoice and the phone number you ordered with.</p>
                </div>

                <form method="POST" action="{{ route('ecom.track.submit') }}" novalidate>
                    @csrf
                    @if($errors->any())
                        <div class="alert alert-danger small py-2">{{ $errors->first() }}</div>
                    @endif
                    <div class="mb-3">
                        <label for="order_number" class="form-label fw-semibold">Order number</label>
                        <input type="text" id="order_number" name="order_number" value="{{ old('order_number', request('order')) }}" class="form-control form-control-lg" placeholder="e.g. {{ ecom_setting('order_prefix', 'ORD-') }}000123" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="phone" class="form-label fw-semibold">Phone number</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" class="form-control form-control-lg" placeholder="01XXXXXXXXX" required>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg w-100"><i class="fas fa-search me-2"></i>Track order</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
