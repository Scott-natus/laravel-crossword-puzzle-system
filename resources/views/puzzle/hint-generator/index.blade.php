@extends('layouts.app')

@section('title', 'AI 힌트 생성 관리')

@section('content')
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
    {{-- 통계 정보 --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <a href="{{ route('puzzle.hint-generator.index') }}" class="text-decoration-none">
                <div id="card-total-words" class="card bg-primary text-white clickable-card {{ !isset($status) ? 'active-card' : '' }}">
                    <div class="card-body text-center">
                        <h5 class="card-title">전체 단어</h5>
                        <h3 id="totalWords">-</h3>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('puzzle.hint-generator.index', ['status' => 'with_hints']) }}" class="text-decoration-none">
                <div id="card-with-hints" class="card bg-success text-white clickable-card {{ (isset($status) && $status == 'with_hints') ? 'active-card' : '' }}">
                    <div class="card-body text-center">
                        <h5 class="card-title">힌트 보유</h5>
                        <h3 id="wordsWithHints">-</h3>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('puzzle.hint-generator.index', ['status' => 'without_hints']) }}" class="text-decoration-none">
                <div id="card-without-hints" class="card bg-warning text-white clickable-card {{ (isset($status) && $status == 'without_hints') ? 'active-card' : '' }}">
                    <div class="card-body text-center">
                        <h5 class="card-title">힌트 없음</h5>
                        <h3 id="wordsWithoutHints">-</h3>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">전체 힌트</h5>
                    <h3 id="totalHints">-</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">AI 힌트 생성 관리</h4>
                    <div>
                        <button type="button" class="btn btn-info me-2" onclick="testConnection()">
                            <i class="fas fa-wifi"></i> API 연결 테스트
                        </button>
                        <button type="button" class="btn btn-primary" onclick="showBatchModal()">
                            <i class="fas fa-magic"></i> 일괄 힌트 생성
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- 필터 -->
                    <form action="{{ route('puzzle.hint-generator.index') }}" method="GET" id="searchForm">
                        <div class="row mb-3">
                            <div class="col-md-2">
                                <select class="form-select" name="search_type" id="searchType">
                                    <option value="keyword" {{ !isset($searchType) || $searchType == 'keyword' ? 'selected' : '' }}>키워드 검색</option>
                                    <option value="category" {{ isset($searchType) && $searchType == 'category' ? 'selected' : '' }}>카테고리</option>
                                    <option value="word" {{ isset($searchType) && $searchType == 'word' ? 'selected' : '' }}>단어검색</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="categoryDropdown" style="display: none;">
                                <select class="form-select" name="search_category" id="searchCategory">
                                    <option value="전체 카테고리" {{ (!isset($searchCategory) || $searchCategory == '' || $searchCategory == '전체 카테고리') ? 'selected' : '' }}>전체 카테고리</option>
                                    @if(isset($categories))
                                        @foreach($categories as $category)
                                            <option value="{{ $category }}" {{ (isset($searchCategory) && $searchCategory == $category) ? 'selected' : '' }}>{{ $category }}</option>
                                        @endforeach
                                    @endif
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
                                    <th>
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                    </th>
                                    <th>카테고리</th>
                                    <th>단어</th>
                                    <th>글자수</th>
                                    <th>난이도</th>
                                    <th>힌트 개수</th>
                                    <th>관리</th>
                                </tr>
                            </thead>
                            <tbody id="wordsTableBody">
                                @foreach($words as $word)
                                    <tr data-word-id="{{ $word->id }}" data-category="{{ $word->category }}" data-hint-count="{{ $word->hints_count }}">
                                        <td>
                                            <input type="checkbox" class="word-checkbox" value="{{ $word->id }}">
                                        </td>
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
                                        <td>
                                            <span class="badge bg-{{ $word->hints_count > 0 ? 'success' : 'secondary' }}">
                                                {{ $word->hints_count }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($word->hints_count > 0)
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="toggleHintsView({{ $word->id }})">
                                                    <i class="fas fa-eye"></i> 힌트 보기
                                                </button>
                                                <button type="button" class="btn btn-sm btn-warning ms-1" 
                                                        onclick="regenerateHint({{ $word->id }})">
                                                    <i class="fas fa-redo"></i> 재생성
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        onclick="generateHint({{ $word->id }})">
                                                    <i class="fas fa-magic"></i> 힌트 생성
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                    {{-- 힌트 표시를 위한 아코디언 행 --}}
                                    @if($word->hints_count > 0)
                                    <tr class="hints-row" id="hints-view-{{ $word->id }}" style="display: none;">
                                        <td colspan="7">
                                            <div class="hints-container p-3">
                                                <h6 class="mb-2"><strong>'{{ $word->word }}'</strong> 힌트 목록</h6>
                                                <ul class="list-group">
                                                    @php
                                                        $difficultyOrder = ['easy' => 1, 'medium' => 2, 'hard' => 3];
                                                        $sortedHints = $word->hints->sortBy(function($hint) use ($difficultyOrder) {
                                                            return $difficultyOrder[$hint->difficulty] ?? 99;
                                                        });
                                                    @endphp
                                                    @foreach($sortedHints as $hint)
                                                        @php
                                                            $badgeColor = 'bg-info';
                                                            $iconClass = 'fa-question-circle';
                                                            if ($hint->difficulty === 'easy') {
                                                                $badgeColor = 'bg-primary';
                                                                $iconClass = 'fa-laugh-beam';
                                                            } elseif ($hint->difficulty === 'medium') {
                                                                $badgeColor = 'bg-success';
                                                                $iconClass = 'fa-meh';
                                                            } elseif ($hint->difficulty === 'hard') {
                                                                $badgeColor = 'bg-danger';
                                                                $iconClass = 'fa-dizzy';
                                                            }
                                                        @endphp
                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                            <span>{{ $hint->hint_text }}</span>
                                                            <span class="badge {{ $badgeColor }}">
                                                                <i class="fas {{ $iconClass }} me-1"></i>
                                                                {{ $hint->difficulty_text }}
                                                            </span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- 페이징 -->
                    <div class="d-flex justify-content-center">
                        @if ($words->lastPage() > 1)
                            <nav>
                                <ul class="pagination">
                                    @php
                                        $blockSize = 10;
                                        $currentPage = $words->currentPage();
                                        $lastPage = $words->lastPage();
                                        $currentBlock = intval(ceil($currentPage / $blockSize));
                                        $start = ($currentBlock - 1) * $blockSize + 1;
                                        $end = min($start + $blockSize - 1, $lastPage);
                                        $prevBlockPage = $start - $blockSize > 0 ? $start - $blockSize : 1;
                                        $nextBlockPage = $end + 1 <= $lastPage ? $end + 1 : $lastPage;
                                    @endphp
                                    {{-- Previous Block Link --}}
                                    <li class="page-item {{ $start == 1 ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $start == 1 ? '#' : $words->url($prevBlockPage) }}" tabindex="-1">Previous</a>
                                    </li>

                                    {{-- Pagination Elements --}}
                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $words->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor

                                    {{-- Next Block Link --}}
                                    <li class="page-item {{ $end == $lastPage ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $end == $lastPage ? '#' : $words->url($nextBlockPage) }}">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 일괄 생성 모달 -->
<div class="modal fade" id="batchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">일괄 힌트 생성</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">생성 방식</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="batchType" id="batchTypeSelected" value="selected" checked>
                            <label class="form-check-label" for="batchTypeSelected">
                                선택된 단어들
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="batchType" id="batchTypeCategory" value="category">
                            <label class="form-check-label" for="batchTypeCategory">
                                카테고리별
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="overwriteHints">
                            <label class="form-check-label" for="overwriteHints">
                                기존 힌트 덮어쓰기
                            </label>
                        </div>
                    </div>
                </div>
                
                <div id="selectedWordsSection">
                    <label class="form-label">선택된 단어: <span id="selectedCount">0</span>개</label>
                    <div id="selectedWordsList" class="border p-2" style="max-height: 200px; overflow-y: auto;">
                        <!-- 선택된 단어들이 여기에 표시됩니다 -->
                    </div>
                </div>
                
                <div id="categorySection" style="display: none;">
                    <label for="batchCategory" class="form-label">카테고리 선택</label>
                    <select class="form-select" id="batchCategory">
                        <option value="">카테고리를 선택하세요</option>
                        @foreach($categories ?? [] as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" onclick="executeBatchGeneration()">
                    <i class="fas fa-magic"></i> 힌트 생성 시작
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 결과 모달 -->
<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">힌트 생성 결과</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="resultContent">
                    <!-- 결과가 여기에 표시됩니다 -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<!-- 단일 힌트 생성 결과 모달 -->
<div class="modal fade" id="singleHintResultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>힌트 생성 완료
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="location.reload()"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <h6 class="alert-heading">성공적으로 힌트가 생성되었습니다!</h6>
                    <p class="mb-0">아래에서 생성된 힌트들을 확인하세요.</p>
                </div>
                
                <div class="word-info mb-4">
                    <h6>대상 정보</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>카테고리:</strong> <span id="resultCategory" class="badge bg-primary"></span>
                        </div>
                        <div class="col-md-4">
                            <strong>단어:</strong> <span id="resultWord" class="fw-bold text-primary"></span>
                        </div>
                        <div class="col-md-4" id="frequencyInfo" style="display: none;">
                            <strong>사용빈도:</strong> <span id="resultFrequency" class="badge bg-info"></span>
                        </div>
                    </div>
                </div>
                
                <div class="hints-section">
                    <h6>생성된 힌트</h6>
                    <div id="generatedHintsList">
                        <!-- 생성된 힌트들이 여기에 표시됩니다 -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="location.reload()">
                    <i class="fas fa-times"></i> 닫기
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.word-checkbox:checked + td {
    background-color: #e3f2fd;
}

.result-item {
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 5px;
}

.result-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
}

.result-error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
}

.result-skipped {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
}

.progress-container {
    margin: 20px 0;
}

/* 단일 힌트 결과 모달 스타일 */
.hint-result-item {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.hint-result-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.hint-result-item.primary {
    border-left: 4px solid #007bff;
    background-color: #f8f9ff;
}

.hint-difficulty-badge {
    font-size: 0.8rem;
    padding: 4px 8px;
}

.hint-content {
    font-size: 1.1rem;
    font-weight: 500;
    color: #495057;
    margin-top: 8px;
}

.word-info {
    background: #e9ecef;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

/* 현재 페이지 강조 */
.btn-info.btn-sm {
    background-color: #0aa2c0;
    border-color: #0aa2c0;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.clickable-card {
    cursor: pointer;
    transition: transform 0.2s ease-in-out, box-shadow 0.2s;
    border: 3px solid transparent;
}
.clickable-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.active-card {
    border: 3px solid #ffc107; /* 밝은 노란색 테두리로 활성화 표시 */
    box-shadow: 0 0 15px rgba(255, 193, 7, 0.5);
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/hint-generator.js') }}"></script>
<script>
    function toggleHintsView(wordId) {
        const hintsRow = document.getElementById(`hints-view-${wordId}`);
        if (hintsRow) {
            hintsRow.style.display = hintsRow.style.display === 'none' ? 'table-row' : 'none';
        }
    }
</script>
@endpush
@endsection 