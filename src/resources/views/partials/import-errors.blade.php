@if(session('import_errors'))
    <div class="alert alert-warning small">
        <b>Some rows were skipped:</b>
        <ul class="mb-0">
            @foreach(array_slice(session('import_errors'), 0, 20) as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
