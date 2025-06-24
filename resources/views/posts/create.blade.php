@extends('layouts.app')

@section('content')
<div class="container">
    <h1>게시글 작성</h1>
    <form action="{{ route('posts.store') }}" method="POST">
        @csrf
        <div>
            <label for="title">제목</label>
            <input type="text" name="title" id="title" required>
        </div>
        <div>
            <label for="content">내용</label>
            <textarea name="content" id="content" required></textarea>
        </div>
        <button type="submit">작성</button>
    </form>
</div>
@endsection 