@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-3">{{ $boardType->name }} - 게시글 작성</h2>
            <div class="card">
                <div class="card-header">게시글 작성</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('board.store', ['boardType' => $boardType->slug]) }}" enctype="multipart/form-data" id="boardForm">
                        @csrf

                        <div class="mb-3">
                            <label for="title" class="form-label">제목</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                id="title" name="title" value="{{ old('title') }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">내용</label>
                            <div id="editor" class="form-control @error('content') is-invalid @enderror" 
                                contenteditable="true" style="min-height: 300px; overflow-y: auto; padding: 10px;"
                                data-placeholder="내용을 입력하세요...">{{ old('content') }}</div>
                            <textarea id="content" name="content" style="display: none;"></textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">비밀번호</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                id="password" name="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="comment_notify" 
                                    name="comment_notify" {{ old('comment_notify') ? 'checked' : '' }}>
                                <label class="form-check-label" for="comment_notify">댓글 알림 받기</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="attachments" class="form-label">첨부파일</label>
                            <input type="file" class="form-control @error('attachments.*') is-invalid @enderror" 
                                id="attachments" name="attachments[]" multiple>
                            <div class="form-text">이미지(10MB), 동영상(100MB) 파일만 업로드 가능합니다.</div>
                            @error('attachments.*')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('board.index', ['boardType' => $boardType->slug]) }}" class="btn btn-secondary">취소</a>
                            <button type="submit" class="btn btn-primary">작성하기</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    #editor {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        background-color: #fff;
    }
    #editor:empty:before {
        content: attr(data-placeholder);
        color: #6c757d;
        font-style: italic;
    }
    #editor:focus {
        border-color: #86b7fe;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('boardForm');
    const editor = document.getElementById('editor');
    const contentTextarea = document.getElementById('content');

    // 폼 제출 시 contenteditable div의 내용을 textarea로 복사
    form.addEventListener('submit', function(e) {
        // 디버깅을 위해 콘솔에 내용 출력
        console.log('Editor content:', editor.innerHTML);
        contentTextarea.value = editor.innerHTML;
        console.log('Textarea content:', contentTextarea.value);
        if (!contentTextarea.value.trim()) {
            e.preventDefault();
            alert('내용을 입력해주세요.');
            return;
        }
    });

    // 붙여넣기 이벤트 처리 (StartFragment/EndFragment 제거)
    editor.addEventListener('paste', function(e) {
        e.preventDefault();
        let html = (e.clipboardData || window.clipboardData).getData('text/html');
        let text = (e.clipboardData || window.clipboardData).getData('text/plain');

        if (html) {
            // StartFragment/EndFragment 사이만 추출
            const start = html.indexOf('<!--StartFragment-->');
            const end = html.indexOf('<!--EndFragment-->');
            if (start !== -1 && end !== -1) {
                html = html.substring(start + 20, end);
            }
            document.execCommand('insertHTML', false, html);
        } else {
            document.execCommand('insertText', false, text);
        }
    });
});
</script>
@endpush
@endsection 