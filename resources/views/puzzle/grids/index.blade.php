@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">퍼즐 그리드 템플릿 관리</h4>
                    <a href="{{ route('puzzle.grids.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 새 그리드 생성
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($templates->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>레벨</th>
                                        <th>템플릿 이름</th>
                                        <th>크기</th>
                                        <th>단어 수</th>
                                        <th>교차점</th>
                                        <th>난이도</th>
                                        <th>카테고리</th>
                                        <th>생성일</th>
                                        <th>작업</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($templates as $template)
                                        <tr>
                                            <td>{{ $template->id }}</td>
                                            <td>
                                                <span class="badge bg-primary">Level {{ $template->level_id }}</span>
                                            </td>
                                            <td>{{ $template->template_name }}</td>
                                            <td>{{ $template->grid_width }}×{{ $template->grid_height }}</td>
                                            <td>{{ $template->word_count }}</td>
                                            <td>{{ $template->intersection_count }}</td>
                                            <td>
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i class="fas fa-star {{ $i <= $template->difficulty_rating ? 'text-warning' : 'text-muted' }}"></i>
                                                @endfor
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $template->category === 'beginner' ? 'success' : 'info' }}">
                                                    {{ ucfirst($template->category) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($template->created_at instanceof \Carbon\Carbon)
                                                    {{ $template->created_at->format('Y-m-d H:i') }}
                                                @else
                                                    {{ \Carbon\Carbon::parse($template->created_at)->format('Y-m-d H:i') }}
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('puzzle.grids.show', $template->level_id) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> 보기
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-puzzle-piece fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">등록된 그리드 템플릿이 없습니다.</h5>
                            <p class="text-muted">새로운 그리드 템플릿을 생성해보세요.</p>
                            <a href="{{ route('puzzle.grids.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> 첫 번째 그리드 생성
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 