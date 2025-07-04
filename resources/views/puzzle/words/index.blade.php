@extends('layouts.app')

@section('content')
@php
    // 정렬 링크를 생성하기 위한 헬퍼 클로저
    $sortLink = function ($field, $displayName) use ($sortBy, $sortDir) {
        $dir = ($sortBy == $field && $sortDir == 'asc') ? 'desc' : 'asc';
        $arrow = '';
        if ($sortBy == $field) {
            $arrow = $sortDir == 'asc' ? '▲' : '▼';
        }
        return '<a href="' . request()->fullUrlWithQuery(['sort_by' => $field, 'sort_dir' => $dir]) . '">' . $displayName . ' ' . $arrow . '</a>';
    };
@endphp
<!-- 퍼즐 관리 바로가기 네비게이션 -->
<div class="container-fluid mb-4">
    <div class="card">
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-primary">
                    <i class="fas fa-puzzle-piece me-2"></i>퍼즐 관리 바로가기
                </h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('puzzle.words.index') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-list me-1"></i>단어 관리
                    </a>
                    <a href="{{ route('puzzle.hint-generator.index') }}" class="btn btn-info btn-sm">
                        <i class="fas fa-magic me-1"></i>AI 힌트 생성
                    </a>
                    <a href="{{ route('puzzle.levels.index') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-layer-group me-1"></i>레벨 관리
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('puzzle.words.index') }}">
                                <i class="fas fa-list me-2"></i>단어 관리
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('puzzle.hint-generator.index') }}">
                                <i class="fas fa-magic me-2"></i>AI 힌트 생성
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('puzzle.levels.index') }}">
                                <i class="fas fa-layer-group me-2"></i>레벨 관리
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.users.index') }}">
                                <i class="fas fa-users me-2"></i>회원 관리
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">크로스워드 퍼즐 단어 관리</h4>
                    <div>
                        <a href="{{ route('puzzle.hint-generator.index') }}" class="btn btn-info me-2">
                            <i class="fas fa-magic"></i> AI 힌트 생성
                        </a>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWordModal">
                            <i class="fas fa-plus"></i> 단어 추가
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- 검색 필터 -->
                    <form action="{{ route('puzzle.words.index') }}" method="GET" id="searchForm">
                        <div class="row mb-3">
                            <div class="col-md-2">
                                <select class="form-select" name="search_type" id="searchType">
                                    <option value="keyword" {{ ($searchType ?? 'keyword') == 'keyword' ? 'selected' : '' }}>키워드 검색</option>
                                    <option value="category" {{ ($searchType ?? '') == 'category' ? 'selected' : '' }}>카테고리</option>
                                    <option value="word" {{ ($searchType ?? '') == 'word' ? 'selected' : '' }}>단어검색</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="categoryDropdown" style="display: none;">
                                <select class="form-select" name="search_category" id="searchCategory">
                                    <option value="전체 카테고리" {{ ($searchCategory ?? '') == '전체 카테고리' || !($searchCategory ?? '') ? 'selected' : '' }}>전체 카테고리</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}" {{ ($searchCategory ?? '') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="search_word" id="searchInput" 
                                       placeholder="키워드 검색..." value="{{ $searchWord ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-secondary w-100" onclick="return validateSearch()">
                                    <i class="fas fa-search"></i> 검색
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- 단어 목록 -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>카테고리</th>
                                    <th>단어</th>
                                    <th>글자수</th>
                                    <th>{!! $sortLink('difficulty', '난이도') !!}</th>
                                    <th>{!! $sortLink('hints_count', '힌트 개수') !!}</th>
                                    <th>사용여부</th>
                                    <th>{!! $sortLink('latest_hint_date', '힌트 생성일자') !!}</th>
                                    <th>{!! $sortLink('created_at', '입력일자') !!}</th>
                                    <th>관리</th>
                                </tr>
                            </thead>
                            <tbody id="wordsTableBody">
                                @foreach($words as $word)
                                    <tr data-word-id="{{ $word->id }}">
                                        <td>{{ $word->category }}</td>
                                        <td>{{ $word->word }}</td>
                                        <td>{{ $word->length }}</td>
                                        <td>
                                            @php
                                                $badgeColor = 'success';
                                                if ($word->difficulty === 2) $badgeColor = 'warning';
                                                elseif ($word->difficulty === 3) $badgeColor = 'danger';
                                                elseif ($word->difficulty === 4) $badgeColor = 'dark';
                                                elseif ($word->difficulty === 5) $badgeColor = 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $badgeColor }}">
                                                {{ $word->difficulty_text }}
                                            </span>
                                        </td>
                                        <td>{{ $word->hints_count }}</td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" 
                                                       {{ $word->is_active ? 'checked' : '' }}
                                                       onchange="toggleActive({{ $word->id }})">
                                            </div>
                                        </td>
                                        <td>
                                            @if($word->latest_hint_date)
                                                {{ \Carbon\Carbon::parse($word->latest_hint_date)->format('Y-m-d H:i') }}
                                            @endif
                                        </td>
                                        <td>{{ $word->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="toggleHints({{ $word->id }})">
                                                <i class="fas fa-eye"></i> 힌트
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="hints-row" id="hints-{{ $word->id }}" style="display: none;">
                                        <td colspan="9">
                                            <div class="hints-container">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0">힌트 관리</h6>
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            onclick="showAddHintModal({{ $word->id }})">
                                                        <i class="fas fa-plus"></i> 힌트 추가
                                                    </button>
                                                </div>
                                                <div id="hints-list-{{ $word->id }}" class="hints-list">
                                                    <!-- 힌트 목록이 여기에 로드됩니다 -->
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- 페이징 -->
                    <div class="d-flex justify-content-center">
                        {{ $words->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('puzzle.words.partials.add-word-modal')
@include('puzzle.words.partials.hint-modal')
@include('puzzle.words.partials.file-preview-modal')

@push('styles')
<style>
.hints-container {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}

.hint-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 10px;
    margin-bottom: 10px;
}

.hint-item.primary {
    border-left: 4px solid #007bff;
    background-color: #f8f9ff;
}

.hint-content {
    margin-bottom: 10px;
}

.hint-meta {
    font-size: 0.875rem;
    color: #6c757d;
}

.hint-actions {
    display: flex;
    gap: 5px;
}

.badge-primary {
    background-color: #007bff;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.75rem;
}

/* 현재 페이지 강조 */
.btn-primary.btn-sm {
    background-color: #0056b3;
    border-color: #0056b3;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

thead th a {
    text-decoration: none;
    color: inherit;
}
thead th a:hover {
    color: #0056b3;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/puzzle-words.js') }}"></script>
@endpush
@endsection 