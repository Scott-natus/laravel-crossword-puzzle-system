@extends('layouts.app')

@section('content')
<div class="container">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>제목</th>
                <th>작성자</th>
                <th>작성일</th>
                <th>첨부</th>
                <th>조회수</th>
            </tr>
        </thead>
        <tbody>
            @foreach($boards as $board)
                @include('board.partials.board_row', ['board' => $board, 'depth' => $board->depth ?? 0])
            @endforeach
        </tbody>
    </table>
    <div class="d-flex justify-content-center">
        {{ $boards->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>
@endsection 