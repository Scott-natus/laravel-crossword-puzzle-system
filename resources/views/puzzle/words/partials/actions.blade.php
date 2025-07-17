<div class="btn-group btn-group-sm">
    <button type="button" class="btn btn-outline-primary" onclick="showHints({{ $word->id }})">
        <i class="fas fa-eye"></i>
    </button>
    <button type="button" class="btn btn-outline-warning" onclick="editWord({{ $word->id }})">
        <i class="fas fa-edit"></i>
    </button>
    <button type="button" class="btn btn-outline-danger" onclick="deleteWord({{ $word->id }})">
        <i class="fas fa-trash"></i>
    </button>
</div> 