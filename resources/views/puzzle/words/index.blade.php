@extends('layouts.app')

@section('title', '단어 관리')

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
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">전체 단어</h5>
                    <h3>{{ number_format($stats['total_words']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">활성 단어</h5>
                    <h3>{{ number_format($stats['active_words']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">힌트 보유</h5>
                    <h3>{{ number_format($stats['words_with_hints']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">전체 힌트</h5>
                    <h3>{{ number_format($stats['total_hints']) }}</h3>
                </div>
            </div>
        </div>
    </div>

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
                    <!-- 검색 및 필터 -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" id="keywordSearch" placeholder="키워드 입력 (카테고리, 단어)">
                                <button class="btn btn-outline-secondary" type="button" onclick="performSearch()">
                                    <i class="fas fa-search"></i> 검색
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="difficultyFilter">
                                <option value="">전체 난이도</option>
                                <option value="1">쉬움</option>
                                <option value="2">보통</option>
                                <option value="3">어려움</option>
                                <option value="4">매우 어려움</option>
                                <option value="5">극도 어려움</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-warning w-100" onclick="showBatchUpdateModal()">
                                <i class="fas fa-edit"></i> 일괄변경
                            </button>
                        </div>
                    </div>

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
                                    <th>사용여부</th>
                                    <th>힌트 생성일자</th>
                                    <th>입력일자</th>
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

@include('puzzle.words.partials.add-word-modal')
@include('puzzle.words.partials.hint-modal')
@include('puzzle.words.partials.file-preview-modal')

<!-- 일괄변경 모달 -->
<div class="modal fade" id="batchUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">일괄변경</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    선택된 단어: <strong id="selectedCount">0</strong>개
                </div>
                
                <form id="batchUpdateForm">
                    <div class="mb-3">
                        <label class="form-label">변경할 항목</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="updateDifficulty" name="update_difficulty">
                            <label class="form-check-label" for="updateDifficulty">
                                난이도 변경
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="updateActive" name="update_active">
                            <label class="form-check-label" for="updateActive">
                                사용여부 변경
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="difficultySection" style="display: none;">
                        <label for="batchDifficulty" class="form-label">난이도</label>
                        <select class="form-select" id="batchDifficulty" name="difficulty">
                            <option value="1">쉬움</option>
                            <option value="2">보통</option>
                            <option value="3">어려움</option>
                            <option value="4">매우 어려움</option>
                            <option value="5">극도 어려움</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="activeSection" style="display: none;">
                        <label class="form-label">사용여부</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="activeTrue" name="is_active" value="1">
                            <label class="form-check-label" for="activeTrue">
                                활성화
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="activeFalse" name="is_active" value="0">
                            <label class="form-check-label" for="activeFalse">
                                비활성화
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-warning" onclick="executeBatchUpdate()">
                    <i class="fas fa-save"></i> 변경 실행
                </button>
            </div>
        </div>
    </div>
</div>

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
<!-- DataTables JavaScript -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/scroller/2.2.0/js/dataTables.scroller.min.js"></script>

<script>
$(document).ready(function() {
    // DataTables 초기화
    var table = $('#wordsDataTable').DataTable({
        processing: true,
        serverSide: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"p>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        ajax: {
            url: '{{ route("puzzle.words.data") }}',
            type: 'GET',
            data: function(d) {
                // 난이도 필터 추가
                d.difficulty_filter = $('#difficultyFilter').val();
            }
        },
        columns: [
            { 
                data: null,
                name: 'checkbox',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return '<input type="checkbox" class="word-checkbox" value="' + row.id + '">';
                }
            },
            { data: 'category', name: 'category' },
            { data: 'word', name: 'word' },
            { data: 'length', name: 'length' },
            { 
                data: 'difficulty', 
                name: 'difficulty',
                render: function(data, type, row) {
                    var badgeColor = 'success';
                    if (data === 2) badgeColor = 'warning';
                    else if (data === 3) badgeColor = 'danger';
                    else if (data === 4) badgeColor = 'dark';
                    else if (data === 5) badgeColor = 'secondary';
                    
                    var difficultyText = '';
                    switch(data) {
                        case 1: difficultyText = '쉬움'; break;
                        case 2: difficultyText = '보통'; break;
                        case 3: difficultyText = '어려움'; break;
                        case 4: difficultyText = '매우 어려움'; break;
                        case 5: difficultyText = '극도 어려움'; break;
                    }
                    
                    return '<span class="badge bg-' + badgeColor + '">' + difficultyText + '</span>';
                }
            },
            { data: 'hints_count', name: 'hints_count' },
            { 
                data: 'is_active', 
                name: 'is_active',
                render: function(data, type, row) {
                    var checked = data ? 'checked' : '';
                    return '<div class="form-check form-switch">' +
                           '<input class="form-check-input" type="checkbox" ' + checked + 
                           ' onchange="toggleActive(' + row.id + ')">' +
                           '</div>';
                }
            },
            { 
                data: 'latest_hint_date', 
                name: 'latest_hint_date',
                render: function(data, type, row) {
                    return data ? data : '';
                }
            },
            { 
                data: 'created_at', 
                name: 'created_at' 
            },
            { 
                data: null,
                name: 'actions',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return '<button type="button" class="btn btn-sm btn-outline-primary" ' +
                           'onclick="toggleHints(' + row.id + ')">' +
                           '<i class="fas fa-eye"></i> 힌트</button>';
                }
            }
        ],
        pageLength: 25,
        order: [[7, 'desc']], // 입력일자 내림차순
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ko.json'
        },
        responsive: true,
        scroller: true,
        scrollY: 400,
        deferRender: true
    });

    // 검색 기능 강화
    table.on('draw', function() {
        // 힌트 토글 기능 재설정
        setupHintToggles();
        
        // 체크박스 상태 초기화
        $('#selectAll').prop('indeterminate', false).prop('checked', false);
        updateSelectedCount();
    });

    // 전역 변수로 테이블 저장
    window.wordsTable = table;
});

// 힌트 토글 기능 설정
function setupHintToggles() {
    $('.btn-outline-primary').off('click').on('click', function() {
        var wordId = $(this).data('word-id') || $(this).attr('onclick').match(/\d+/)[0];
        toggleHints(wordId);
    });
}

// 단어 활성화/비활성화 토글
function toggleActive(wordId) {
    $.ajax({
        url: '{{ route("puzzle.words.toggle-active", ":id") }}'.replace(':id', wordId),
        type: 'PUT',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                // DataTables 새로고침
                $('#wordsDataTable').DataTable().ajax.reload();
                showAlert('success', response.message);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', '상태 변경 중 오류가 발생했습니다.');
        }
    });
}

// 힌트 토글
function toggleHints(wordId) {
    var hintsRow = $('#hints-' + wordId);
    if (hintsRow.length === 0) {
        // 힌트 행이 없으면 생성
        var newRow = '<tr class="hints-row" id="hints-' + wordId + '">' +
                    '<td colspan="9">' +
                    '<div class="hints-container">' +
                    '<div class="d-flex justify-content-between align-items-center mb-2">' +
                    '<h6 class="mb-0">힌트 관리</h6>' +
                    '<button type="button" class="btn btn-sm btn-success" onclick="showAddHintModal(' + wordId + ')">' +
                    '<i class="fas fa-plus"></i> 힌트 추가</button>' +
                    '</div>' +
                    '<div id="hints-list-' + wordId + '" class="hints-list">' +
                    '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> 로딩 중...</div>' +
                    '</div>' +
                    '</div>' +
                    '</td>' +
                    '</tr>';
        
        // 현재 행 다음에 삽입
        $('tr[data-word-id="' + wordId + '"]').after(newRow);
        hintsRow = $('#hints-' + wordId);
    }
    
    hintsRow.toggle();
    
    if (hintsRow.is(':visible')) {
        loadHints(wordId);
    }
}

// 힌트 로드
function loadHints(wordId) {
    $.get('{{ route("puzzle.hints.index", ":wordId") }}'.replace(':wordId', wordId), function(response) {
        $('#hints-list-' + wordId).html(response);
    });
}

// 검색 실행
function performSearch() {
    var keyword = $('#keywordSearch').val();
    var difficulty = $('#difficultyFilter').val();
    
    // DataTables 테이블 가져오기
    var table = window.wordsTable;
    
    // 키워드 검색
    if (keyword) {
        table.search(keyword).draw();
    } else {
        table.search('').draw();
    }
    
    // 난이도 필터는 서버사이드에서 처리되므로 테이블 새로고침
    if (difficulty !== '') {
        table.ajax.reload();
    }
}

// 필터 초기화
function clearFilters() {
    $('#keywordSearch').val('');
    $('#difficultyFilter').val('');
    
    var table = window.wordsTable;
    table.search('').ajax.reload();
}

// 엔터키로 검색 실행
$(document).on('keypress', '#keywordSearch', function(e) {
    if (e.which == 13) { // Enter key
        performSearch();
    }
});

// 난이도 필터 변경 시 자동 검색
$(document).on('change', '#difficultyFilter', function() {
    performSearch();
});

// 전체 선택/해제
function toggleSelectAll() {
    var isChecked = $('#selectAll').is(':checked');
    $('.word-checkbox').prop('checked', isChecked);
    updateSelectedCount();
}

// 개별 체크박스 변경 시
$(document).on('change', '.word-checkbox', function() {
    updateSelectAllState();
    updateSelectedCount();
});

// 전체 선택 상태 업데이트
function updateSelectAllState() {
    var totalCheckboxes = $('.word-checkbox').length;
    var checkedCheckboxes = $('.word-checkbox:checked').length;
    
    if (checkedCheckboxes === 0) {
        $('#selectAll').prop('indeterminate', false).prop('checked', false);
    } else if (checkedCheckboxes === totalCheckboxes) {
        $('#selectAll').prop('indeterminate', false).prop('checked', true);
    } else {
        $('#selectAll').prop('indeterminate', true);
    }
}

// 선택된 개수 업데이트
function updateSelectedCount() {
    var selectedCount = $('.word-checkbox:checked').length;
    // 선택된 개수를 표시할 수 있는 요소가 있다면 업데이트
    if ($('#selectedCount').length) {
        $('#selectedCount').text(selectedCount);
    }
}

// 선택된 단어 ID 목록 가져오기
function getSelectedWordIds() {
    var selectedIds = [];
    $('.word-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });
    return selectedIds;
}

// 일괄변경 모달 표시
function showBatchUpdateModal() {
    var selectedIds = getSelectedWordIds();
    if (selectedIds.length === 0) {
        showAlert('warning', '변경할 단어를 선택해주세요.');
        return;
    }
    
    $('#selectedCount').text(selectedIds.length);
    $('#batchUpdateModal').modal('show');
}

// 체크박스 변경 시 섹션 표시/숨김
$(document).on('change', '#updateDifficulty', function() {
    if ($(this).is(':checked')) {
        $('#difficultySection').show();
    } else {
        $('#difficultySection').hide();
    }
});

$(document).on('change', '#updateActive', function() {
    if ($(this).is(':checked')) {
        $('#activeSection').show();
    } else {
        $('#activeSection').hide();
    }
});

// 일괄변경 실행
function executeBatchUpdate() {
    var selectedIds = getSelectedWordIds();
    if (selectedIds.length === 0) {
        showAlert('warning', '변경할 단어를 선택해주세요.');
        return;
    }
    
    var updateData = {
        word_ids: selectedIds,
        _token: '{{ csrf_token() }}'
    };
    
    // 난이도 변경
    if ($('#updateDifficulty').is(':checked')) {
        updateData.update_difficulty = true;
        updateData.difficulty = $('#batchDifficulty').val();
    }
    
    // 사용여부 변경
    if ($('#updateActive').is(':checked')) {
        updateData.update_active = true;
        updateData.is_active = $('input[name="is_active"]:checked').val();
    }
    
    // 변경할 항목이 없으면 경고
    if (!updateData.update_difficulty && !updateData.update_active) {
        showAlert('warning', '변경할 항목을 선택해주세요.');
        return;
    }
    
    // 확인 메시지
    var message = selectedIds.length + '개의 단어를 변경하시겠습니까?';
    if (!confirm(message)) {
        return;
    }
    
    // AJAX 요청
    $.ajax({
        url: '{{ route("puzzle.words.batch-update") }}',
        type: 'POST',
        data: updateData,
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                $('#batchUpdateModal').modal('hide');
                
                // 폼 초기화
                $('#batchUpdateForm')[0].reset();
                $('#difficultySection, #activeSection').hide();
                
                // 테이블 새로고침
                window.wordsTable.ajax.reload();
                
                // 체크박스 초기화
                $('#selectAll').prop('indeterminate', false).prop('checked', false);
                updateSelectedCount();
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', '일괄변경 중 오류가 발생했습니다.');
        }
    });
}

// 알림 표시
function showAlert(type, message) {
    var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                   message +
                   '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                   '</div>';
    
    // 기존 알림 제거
    $('.alert').remove();
    
    // 새 알림 추가
    $('.card-body').prepend(alertHtml);
    
    // 3초 후 자동 제거
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 3000);
}
</script>
@endpush
@endsection 