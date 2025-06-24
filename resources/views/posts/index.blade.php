@extends('layouts.app')

@section('content')
<div class="container">
    <h1>게시글 목록</h1>
    @if(session('success'))
        <div style="color: green;">{{ session('success') }}</div>
    @endif
    <a href="{{ route('posts.create') }}">글쓰기</a>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>제목</th>
                <th>작성자</th>
                <th>작성일</th>
            </tr>
        </thead>
        <tbody>
            @foreach($posts as $post)
                <tr>
                    <td>{{ $post->id }}</td>
                    <td>{{ $post->title }}</td>
                    <td>{{ $post->user->name ?? '알 수 없음' }}</td>
                    <td>{{ $post->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
