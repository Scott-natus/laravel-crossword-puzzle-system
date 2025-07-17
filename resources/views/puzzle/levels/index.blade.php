@extends('layouts.app')

@section('title', '퍼즐 레벨 관리')

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
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-layer-group me-2"></i>퍼즐 레벨 관리
                    </h4>
                    <div>
                        <button type="button" class="btn btn-success" onclick="generateDefaultData()">
                            <i class="fas fa-database me-1"></i>기본 데이터 생성
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>등급별 난이도 조절:</strong> 각 레벨의 퍼즐 설정을 관리합니다. 
                        교차점 개수는 단어 개수보다 적어야 합니다.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">레벨</th>
                                    <th width="12%">레벨 명칭</th>
                                    <th width="7%">단어 개수</th>
                                    <th width="7%">단어 난이도</th>
                                    <th width="7%">힌트 난이도</th>
                                    <th width="7%">교차점 개수</th>
                                    <th width="7%">실행시간(초)</th>
                                    <th width="7%">클리어조건</th>
                                    <th width="8%">그리드 템플릿</th>
                                    <th width="10%">수정일시</th>
                                    <th width="10%">수정자</th>
                                    <th width="12%">작업</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($levels as $level)
                                <tr data-level-id="{{ $level->id }}">
                                    <td class="text-center fw-bold">{{ $level->level }}</td>
                                    <td>{{ $level->level_name }}</td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm word-count" 
                                               value="{{ $level->word_count }}" min="1" 
                                               data-original="{{ $level->word_count }}">
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm word-difficulty" 
                                                data-original="{{ $level->word_difficulty }}">
                                            @for($i = 1; $i <= 5; $i++)
                                                <option value="{{ $i }}" {{ $level->word_difficulty == $i ? 'selected' : '' }}>
                                                    {{ $i }}
                                                </option>
                                            @endfor
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm hint-difficulty" 
                                                data-original="{{ $level->hint_difficulty }}">
                                            <option value="1" {{ $level->hint_difficulty == 1 ? 'selected' : '' }}>쉬움</option>
                                            <option value="2" {{ $level->hint_difficulty == 2 ? 'selected' : '' }}>보통</option>
                                            <option value="3" {{ $level->hint_difficulty == 3 ? 'selected' : '' }}>어려움</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm intersection-count" 
                                               value="{{ $level->intersection_count }}" min="1" 
                                               data-original="{{ $level->intersection_count }}">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm time-limit" 
                                               value="{{ $level->time_limit }}" min="0" 
                                               data-original="{{ $level->time_limit }}">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm clear-condition" 
                                               value="{{ $level->clear_condition ?? 1 }}" min="1" 
                                               data-original="{{ $level->clear_condition ?? 1 }}"
                                               title="동일 레벨을 클리어해야 하는 횟수">
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $level->template_count ?? 0 }}</span>
                                        @if(($level->template_count ?? 0) > 0)
                                            <button type="button" class="btn btn-outline-info btn-sm ms-1" 
                                                    onclick="viewTemplates({{ $level->id }}, {{ $level->level }})" 
                                                    title="템플릿 조회">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        @endif
                                    </td>
                                    <td class="text-muted small">
                                        {{ $level->updated_at ? $level->updated_at->format('Y-m-d H:i') : '-' }}
                                    </td>
                                    <td class="text-muted small">
                                        {{ $level->updated_by ?: '-' }}
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm save-btn" 
                                                onclick="saveLevel({{ $level->id }}, this)" disabled>
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-3"></i>
                                        <p>등록된 레벨이 없습니다.</p>
                                        <button type="button" class="btn btn-success" onclick="generateDefaultData()">
                                            <i class="fas fa-database me-1"></i>기본 데이터 생성
                                        </button>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 알림 모달 -->
<div class="modal fade" id="alertModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="alertModalTitle">알림</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="alertModalBody">
                <!-- 알림 내용이 여기에 표시됩니다 -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<!-- 템플릿 조회 모달 -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="templateModalTitle">그리드 템플릿 목록</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="templateModalBody">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">로딩 중...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.table th {
    white-space: nowrap;
    vertical-align: middle;
}

.table td {
    vertical-align: middle;
}

.form-control-sm, .form-select-sm {
    font-size: 0.875rem;
}

.save-btn:disabled {
    opacity: 0.5;
}

.changed {
    background-color: #fff3cd !important;
    border-color: #ffeaa7 !important;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

/* 현재 페이지 강조 */
.btn-success.btn-sm {
    background-color: #198754;
    border-color: #198754;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/puzzle-levels.js') }}"></script>
<script>
// 템플릿 조회 함수
function viewTemplates(levelId, levelNumber) {
    const modal = new bootstrap.Modal(document.getElementById('templateModal'));
    const modalTitle = document.getElementById('templateModalTitle');
    const modalBody = document.getElementById('templateModalBody');
    
    modalTitle.textContent = `레벨 ${levelNumber} 그리드 템플릿 목록`;
    modalBody.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">로딩 중...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // AJAX로 템플릿 목록 가져오기
    fetch(`/puzzle/grid-templates/level/${levelId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>템플릿명</th>
                                    <th>그리드 크기</th>
                                    <th>단어 수</th>
                                    <th>교차점 수</th>
                                    <th>생성일</th>
                                    <th>작업</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                if (data.templates.length > 0) {
                    data.templates.forEach(template => {
                        html += `
                            <tr>
                                <td>${template.template_name}</td>
                                <td>${template.grid_width}×${template.grid_height}</td>
                                <td>${template.word_count}</td>
                                <td>${template.intersection_count}</td>
                                <td>${new Date(template.created_at).toLocaleDateString()}</td>
                                <td>
                                    <a href="/puzzle/grid-templates/${template.id}" class="btn btn-primary btn-sm" target="_blank">
                                        <i class="fas fa-eye"></i> 상세보기
                                    </a>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    html += `
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                등록된 템플릿이 없습니다.
                            </td>
                        </tr>
                    `;
                }
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                modalBody.innerHTML = html;
            } else {
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        템플릿 목록을 불러오는 중 오류가 발생했습니다: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    템플릿 목록을 불러오는 중 오류가 발생했습니다: ${error.message}
                </div>
            `;
        });
}
</script>
@endpush 