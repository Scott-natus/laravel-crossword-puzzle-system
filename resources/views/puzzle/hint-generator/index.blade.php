@extends('layouts.app')

@section('title', 'AI 힌트 생성 관리')

@push('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/scroller/2.2.0/css/scroller.bootstrap5.min.css">
@endpush

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
                    <a href="{{ route('puzzle.grid-templates.index') }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-th me-1"></i>그리드 템플릿관리
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
                            <li><a class="dropdown-item" href="{{ route('puzzle.grid-templates.index') }}">
                                <i class="fas fa-th me-2"></i>그리드 템플릿관리
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
                        <button type="button" class="btn btn-warning me-2" onclick="showCorrectionModal()">
                            <i class="fas fa-edit"></i> 힌트 보정
                        </button>
                        <button type="button" class="btn btn-primary" onclick="showBatchModal()">
                            <i class="fas fa-magic"></i> 일괄 힌트 생성
                        </button>
                    </div>
                </div>
                
                <div class="card-body">


                    <!-- 단어 목록 -->
                    <div class="table-responsive">
                        <table id="wordsDataTable" class="table table-hover">
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
                            <tbody>
                                <!-- Ajax로 데이터 로드 -->
                            </tbody>
                        </table>
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

<!-- 힌트 보정 모달 -->
<div class="modal fade" id="correctionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>힌트 보정 관리
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select class="form-select" id="correctionDifficulty">
                            <option value="">전체 난이도</option>
                            <option value="1">쉬움</option>
                            <option value="2">보통</option>
                            <option value="3">어려움</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="correctionCategory">
                            <option value="">전체 카테고리</option>
                            @foreach($categories ?? [] as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary" onclick="loadHintsForCorrection()">
                            <i class="fas fa-search"></i> 조회
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAllCorrection" onchange="toggleSelectAllCorrection()">
                                </th>
                                <th>단어</th>
                                <th>카테고리</th>
                                <th>난이도</th>
                                <th>현재 힌트</th>
                                <th>보정 상태</th>
                            </tr>
                        </thead>
                        <tbody id="correctionTableBody">
                            <!-- 보정할 힌트들이 여기에 표시됩니다 -->
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <span class="text-muted">선택된 힌트: <span id="selectedCorrectionCount">0</span>개</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-warning" onclick="regenerateSelectedHints()">
                            <i class="fas fa-redo"></i> 선택된 힌트 보정
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<!-- 힌트 보정 결과 모달 -->
<div class="modal fade" id="correctionResultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">힌트 보정 결과</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="correctionResultContent">
                    <!-- 보정 결과가 여기에 표시됩니다 -->
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
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
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

/* DataTables 그리드 외곽선 */
#wordsDataTable {
    border: 2px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

#wordsDataTable thead th {
    border: 1px solid #dee2e6;
    background-color: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

#wordsDataTable tbody td {
    border: 1px solid #dee2e6;
    vertical-align: middle;
}

#wordsDataTable tbody tr:hover {
    background-color: #f8f9fa;
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
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/scroller/2.2.0/js/dataTables.scroller.min.js"></script>

<script src="{{ asset('js/hint-generator.js') }}"></script>
<script>
    // DataTables 초기화 (Ajax + 무한 스크롤)
    $(document).ready(function() {
        var table = $('#wordsDataTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("puzzle.hint-generator.words-ajax") }}',
                type: 'GET',
                data: function(d) {
                    // 추가 파라미터 전달
                    d.status = $('#statusFilter').val();
                    return d;
                }
            },
            pageLength: 30, // 한 번에 30개씩
            deferRender: true, // 성능 최적화
            order: [[0, 'desc']], // 최근 등록순 정렬 (id 컬럼 기준 내림차순)
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ko.json'
            },
            columnDefs: [
                { orderable: false, targets: [0, 6] }, // 체크박스, 관리 컬럼 정렬 비활성화
                { width: '5%', targets: 0 },
                { width: '15%', targets: 1 },
                { width: '20%', targets: 2 },
                { width: '10%', targets: 3 },
                { width: '10%', targets: 4 },
                { width: '10%', targets: 5 },
                { width: '30%', targets: 6 }
            ],
            // 무한 스크롤 설정
            scrollY: '60vh',
            scroller: {
                loadingIndicator: true,
                rowHeight: 50 // 행 높이 설정
            },
            // 데이터 로드 후 이벤트 연결
            drawCallback: function() {
                // 체크박스 이벤트 연결
                $('.word-checkbox').off('change').on('change', function() {
                    updateSelectedWords();
                });
                
                // 전체 선택 체크박스 이벤트 연결
                $('#selectAll').off('change').on('change', function() {
                    toggleSelectAll();
                });
                
                // 힌트 생성 버튼 이벤트 연결
                $('.btn-primary[data-word-id]').off('click').on('click', function(e) {
                    e.preventDefault();
                    const wordId = $(this).data('word-id');
                    generateHint(wordId);
                });
                
                // 힌트 재생성 버튼 이벤트 연결
                $('.btn-warning[data-word-id]').off('click').on('click', function(e) {
                    e.preventDefault();
                    const wordId = $(this).data('word-id');
                    regenerateHint(wordId);
                });
                
                // 힌트 보기 버튼 이벤트 연결
                $('.btn-outline-primary[data-word-id]').off('click').on('click', function(e) {
                    e.preventDefault();
                    const wordId = $(this).data('word-id');
                    toggleHintsView(wordId);
                });
            }
        });
    });

    function toggleHintsView(wordId) {
        // Ajax로 힌트 데이터를 가져와서 모달로 표시
        fetch(`/puzzle/hint-generator/word/${wordId}/hints`)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || '힌트를 불러오는데 실패했습니다.');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showHintsModal(data.hints, data.word);
                } else {
                    showAlert('error', data.message || '힌트를 불러오는데 실패했습니다.');
                }
            })
            .catch(error => {
                console.error('힌트 로드 실패:', error);
                showAlert('error', error.message || '힌트를 불러오는 중 오류가 발생했습니다.');
            });
    }
    
    function showHintsModal(hints, word) {
        let hintsHtml = '';
        hints.forEach(hint => {
            const badgeColor = hint.difficulty === 1 ? 'bg-primary' : 
                              hint.difficulty === 2 ? 'bg-success' : 'bg-danger';
            const iconClass = hint.difficulty === 1 ? 'fa-laugh-beam' : 
                             hint.difficulty === 2 ? 'fa-meh' : 'fa-dizzy';
            
            hintsHtml += `
                <div class="hint-result-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="hint-content">${hint.hint_text}</span>
                        <span class="badge ${badgeColor} hint-difficulty-badge">
                            <i class="fas ${iconClass} me-1"></i>
                            ${hint.difficulty_text}
                        </span>
                    </div>
                </div>
            `;
        });
        
        document.getElementById('resultWord').textContent = word.word;
        document.getElementById('resultCategory').textContent = word.category;
        document.getElementById('generatedHintsList').innerHTML = hintsHtml;
        
        new bootstrap.Modal(document.getElementById('singleHintResultModal')).show();
    }
</script>
@endpush
@endsection 