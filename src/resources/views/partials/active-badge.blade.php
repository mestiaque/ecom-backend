@if($active)
    <span class="badge bg-success-subtle text-success">{{ $on ?? 'Active' }}</span>
@else
    <span class="badge bg-secondary-subtle text-secondary">{{ $off ?? 'Inactive' }}</span>
@endif
