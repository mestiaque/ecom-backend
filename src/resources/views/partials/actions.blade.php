{{-- Edit / delete buttons. $edit: url|null, $delete: url|null, $confirm: text --}}
<div class="d-inline-flex align-items-center gap-1">
    @isset($show)
        <a href="{{ $show }}" class="btn btn-sm btn-encodex-show" title="View"><i class="fas fa-eye"></i></a>
    @endisset
    @if(!empty($edit))
        <a href="{{ $edit }}" class="btn btn-sm btn-encodex-edit" title="Edit"><i class="fas fa-edit"></i></a>
    @endif
    @if(!empty($delete))
        <form action="{{ $delete }}" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-encodex-delete" title="Delete"
                onclick="return confirm('{{ $confirm ?? 'Are you sure you want to delete this?' }}')">
                <i class="fas fa-trash"></i>
            </button>
        </form>
    @endif
</div>
