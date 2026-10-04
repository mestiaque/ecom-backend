@if($active)
    <span class="badge bg-success">{{ $on ?? 'Active' }}</span>
@else
    <span class="badge bg-secondary">{{ $off ?? 'Inactive' }}</span>
@endif
