@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">댓글 수정</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('board-comments.update', $boardComment->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="content" class="form-label">내용</label>
                            <textarea class="form-control @error('content') is-invalid @enderror" 
                                id="content" name="content" rows="3" required>{{ old('content', $boardComment->content) }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('board.show', ['boardType' => $boardComment->board->boardType->slug, 'board' => $boardComment->board_id]) }}" class="btn btn-secondary">취소</a>
                            <button type="submit" class="btn btn-primary">수정하기</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 