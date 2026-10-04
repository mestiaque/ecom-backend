@php
    $storeName = ecom_setting('store_name', get_setting('app_name', config('app.name')));
    $logo = ecom_setting('store_logo');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Track Order') | {{ $storeName }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('backend/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    @if($favicon = ecom_setting('store_favicon'))<link rel="icon" href="{{ ecom_image($favicon) }}">@endif
    <style>
        body { background: #f4f6fb; color: #1f2937; }
        .ec-brand img { max-height: 44px; }
        .ec-card { border: 0; border-radius: 16px; box-shadow: 0 6px 24px rgba(15, 23, 42, .06); }
        .ec-steps { display: flex; justify-content: space-between; position: relative; margin: 8px 0 4px; }
        .ec-steps::before { content: ""; position: absolute; top: 19px; left: 10%; right: 10%; height: 4px; background: #e5e7eb; border-radius: 4px; }
        .ec-steps .ec-progress { position: absolute; top: 19px; left: 10%; height: 4px; background: #16a34a; border-radius: 4px; transition: width .4s; }
        .ec-step { position: relative; z-index: 1; text-align: center; width: 20%; }
        .ec-step .ec-dot { width: 42px; height: 42px; border-radius: 50%; margin: 0 auto 6px; display: flex; align-items: center; justify-content: center; background: #fff; border: 3px solid #e5e7eb; color: #9ca3af; }
        .ec-step.done .ec-dot { background: #16a34a; border-color: #16a34a; color: #fff; }
        .ec-step.current .ec-dot { box-shadow: 0 0 0 6px rgba(22, 163, 74, .18); }
        .ec-step .ec-label { font-size: .82rem; font-weight: 600; }
        .ec-step .ec-time { font-size: .72rem; color: #6b7280; min-height: 1em; }
        @media (max-width: 575.98px) { .ec-step .ec-label { font-size: .7rem; } .ec-step .ec-dot { width: 34px; height: 34px; } .ec-steps::before, .ec-steps .ec-progress { top: 15px; } }
    </style>
</head>
<body>
    <header class="bg-white border-bottom mb-4">
        <div class="container py-3 d-flex justify-content-between align-items-center" style="max-width: 860px">
            <a href="{{ url('/') }}" class="ec-brand text-decoration-none text-dark fw-bold fs-5">
                @if($logo)<img src="{{ ecom_image($logo) }}" alt="{{ $storeName }}">@else{{ $storeName }}@endif
            </a>
            <a href="{{ route('ecom.track.form') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-search me-1"></i>Track another order</a>
        </div>
    </header>

    <main class="container pb-5" style="max-width: 860px">
        @yield('content')
    </main>

    <footer class="text-center text-muted small pb-4">
        @if(ecom_setting('store_phone'))<i class="fas fa-phone me-1"></i>{{ ecom_setting('store_phone') }}@endif
        @if(ecom_setting('store_hotline_hours')) · {{ ecom_setting('store_hotline_hours') }}@endif
        @if(ecom_setting('store_email')) · {{ ecom_setting('store_email') }}@endif
        <div class="mt-1">&copy; {{ date('Y') }} {{ $storeName }}</div>
    </footer>
</body>
</html>
