@extends('layouts.app')

@section('title', '그리드 템플릿 생성')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">그리드 템플릿 생성</h4>
                    <p class="card-text">레벨에 맞는 조건에 따라 그리드를 직접 생성하고 템플릿으로 저장할 수 있습니다.</p>
                </div>
                <div class="card-body">
                    <form id="gridTemplateForm">
                        @csrf
                        
                        <!-- 레벨 선택 -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="level_id" class="form-label">레벨 선택</label>
                                <select class="form-select" id="level_id" name="level_id" required>
                                    <option value="">레벨을 선택하세요</option>
                                    @foreach($levels as $level)
                                        <option value="{{ $level->id }}">레벨 {{ $level->level }} - {{ $level->level_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- 레벨 조건 정보 -->
                        <div id="levelConditions" class="row mb-4" style="display: none;">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6>레벨 조건</h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>단어 개수:</strong> <span id="requiredWordCount">-</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>교차점 개수:</strong> <span id="requiredIntersectionCount">-</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>단어 난이도:</strong> <span id="wordDifficulty">-</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>힌트 난이도:</strong> <span id="hintDifficulty">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 기존 템플릿 목록 -->
                        <div id="existingTemplates" class="row mb-4" style="display: none;">
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <h6>기존 템플릿 목록 <small class="text-muted">(클릭하여 상세보기)</small></h6>
                                    <div id="templatesList"></div>
                                </div>
                            </div>
                        </div>

                        <!-- 그리드 크기 설정 -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="grid_size" class="form-label">그리드 크기 (정방형)</label>
                                <input type="number" class="form-control" id="grid_size" name="grid_size" min="3" max="20" value="5" required>
                                <div class="form-text">3x3부터 20x20까지 설정 가능합니다.</div>
                            </div>
                        </div>

                        <!-- 그리드 에디터 -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="form-label">그리드 편집</label>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="clearGrid">전체 지우기 (흰색)</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="fillGrid">전체 채우기 (검은색)</button>
                                    </div>
                                    <div>
                                        <span class="badge bg-dark">■ 검은색 칸 (단어 입력 공간)</span>
                                        <span class="badge bg-light text-dark ms-2">□ 흰색 칸 (빈 공간) - 기본값</span>
                                    </div>
                                </div>
                                <div id="gridEditor" class="border p-3 bg-light" style="display: inline-block;">
                                    <!-- 그리드가 여기에 동적으로 생성됩니다 -->
                                </div>
                            </div>
                        </div>

                        <!-- 조건 확인 -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div id="conditionCheck" class="alert" style="display: none;">
                                    <h6>조건 확인</h6>
                                    <div id="conditionDetails"></div>
                                </div>
                            </div>
                        </div>

                        <!-- 템플릿 정보 -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6>템플릿 정보</h6>
                                    <p class="mb-0">선택한 레벨에 맞는 그리드 템플릿이 자동으로 생성됩니다.</p>
                                </div>
                            </div>
                        </div>

                        <!-- 저장 버튼 -->
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary" id="saveTemplate" disabled>
                                    <i class="fas fa-save"></i> 템플릿 저장
                                </button>
                                <a href="{{ route('puzzle.grid-templates.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-list"></i> 목록으로
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 결과 모달 -->
<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">결과</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="resultMessage">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<!-- 단어 추출 결과 모달 -->
<div class="modal fade" id="wordExtractionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">템플릿 단어 추출 결과</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="wordExtractionContent">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<!-- 기존 템플릿 상세보기 모달 -->
<div class="modal fade" id="templateDetailModal" tabindex="-1" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">템플릿 상세보기</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="templateDetailContent">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let currentGrid = [];
let gridSize = 5;
let levelConditions = null;

// 그리드 초기화
function initializeGrid(size) {
    gridSize = size;
    currentGrid = [];
    
    for (let i = 0; i < size; i++) {
        currentGrid[i] = [];
        for (let j = 0; j < size; j++) {
            currentGrid[i][j] = 1; // 1: 흰색 칸 (빈 공간), 2: 검은색 칸 (단어 입력 공간)
        }
    }
    
    renderGrid();
    checkConditions();
}

// 그리드 렌더링
function renderGrid() {
    const editor = document.getElementById('gridEditor');
    editor.innerHTML = '';
    
    for (let i = 0; i < gridSize; i++) {
        const row = document.createElement('div');
        row.className = 'd-flex';
        
        for (let j = 0; j < gridSize; j++) {
            const cell = document.createElement('div');
            cell.className = 'grid-cell border d-flex align-items-center justify-content-center';
            cell.style.width = '40px';
            cell.style.height = '40px';
            cell.style.cursor = 'pointer';
            cell.style.userSelect = 'none';
            cell.style.backgroundColor = currentGrid[i][j] === 2 ? 'black' : 'white';
            cell.style.color = currentGrid[i][j] === 2 ? 'white' : 'black';
            cell.textContent = currentGrid[i][j] === 2 ? '■' : '□';
            
            cell.addEventListener('click', () => toggleCell(i, j));
            cell.addEventListener('mouseenter', () => {
                if (event.buttons === 1) { // 마우스 드래그
                    toggleCell(i, j);
                }
            });
            
            row.appendChild(cell);
        }
        
        editor.appendChild(row);
    }
}

// 셀 토글
function toggleCell(row, col) {
    currentGrid[row][col] = currentGrid[row][col] === 2 ? 1 : 2; // 2: 검은색(단어 공간) ↔ 1: 흰색(빈 공간)
    renderGrid();
    checkConditions();
}

// 전체 지우기
document.getElementById('clearGrid').addEventListener('click', () => {
    for (let i = 0; i < gridSize; i++) {
        for (let j = 0; j < gridSize; j++) {
            currentGrid[i][j] = 1; // 흰색 칸 (빈 공간)
        }
    }
    renderGrid();
    checkConditions();
});

// 전체 채우기
document.getElementById('fillGrid').addEventListener('click', () => {
    for (let i = 0; i < gridSize; i++) {
        for (let j = 0; j < gridSize; j++) {
            currentGrid[i][j] = 2; // 검은색 칸 (단어 입력 공간)
        }
    }
    renderGrid();
    checkConditions();
});

// 그리드 크기 변경
document.getElementById('grid_size').addEventListener('change', (e) => {
    initializeGrid(parseInt(e.target.value));
});

// 레벨 선택
document.getElementById('level_id').addEventListener('change', (e) => {
    const levelId = e.target.value;
    if (levelId) {
        fetchLevelConditions(levelId);
    } else {
        hideLevelInfo();
    }
});

// 레벨 조건 가져오기
function fetchLevelConditions(levelId) {
    fetch('{{ route("puzzle.grid-templates.level-conditions") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ level_id: levelId })
    })
    .then(async response => {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.indexOf('application/json') !== -1) {
            const data = await response.json();
            if (data.success) {
                levelConditions = data.level;
                showLevelInfo(data.level, data.existing_templates);
            } else {
                alert(data.message);
            }
        } else {
            const text = await response.text();
            throw new Error('서버에서 JSON이 아닌 응답이 반환되었습니다.\n' + text.substring(0, 200));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('레벨 정보를 가져오는 중 오류가 발생했습니다.');
    });
}

// 레벨 정보 표시
function showLevelInfo(level, templates) {
    document.getElementById('requiredWordCount').textContent = level.word_count;
    document.getElementById('requiredIntersectionCount').textContent = level.intersection_count;
    document.getElementById('wordDifficulty').textContent = level.word_difficulty;
    document.getElementById('hintDifficulty').textContent = level.hint_difficulty;
    document.getElementById('levelConditions').style.display = 'block';
    window.existingTemplates = templates || [];
    
    // 기존 템플릿 목록 표시
    if (templates && templates.length > 0) {
        const templatesList = document.getElementById('templatesList');
        templatesList.innerHTML = templates.map(template => 
            `<div class="mb-2">
                <a href="#" class="text-decoration-none template-link" data-template='${JSON.stringify(template)}' style="color: #0d6efd; cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='#0a58ca'" onmouseout="this.style.color='#0d6efd'">
                    <strong>${template.template_name}</strong> 
                    (${template.grid_width}x${template.grid_height}, ${template.word_count}단어, ${template.intersection_count}교차점)
                    <small class="text-muted">- ${new Date(template.created_at).toLocaleDateString()}</small>
                </a>
                <button type="button" class="btn btn-sm btn-outline-success ms-2" onclick="extractWords(${template.id})">
                    <i class="fas fa-magic"></i> 단어 추출
                </button>
            </div>`
        ).join('');
        
        // 템플릿 링크에 클릭 이벤트 추가
        document.querySelectorAll('.template-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const template = JSON.parse(e.target.closest('.template-link').dataset.template);
                showTemplateDetail(template);
            });
        });
        
        document.getElementById('existingTemplates').style.display = 'block';
    } else {
        document.getElementById('existingTemplates').style.display = 'none';
    }
}

// 템플릿 상세보기 대신, 템플릿 클릭 시 그리드에 바로 적용
function showTemplateDetail(template) {
    // 1. 템플릿의 그리드 패턴을 현재 그리드에 적용
    const gridPattern = JSON.parse(template.grid_pattern);
    const size = gridPattern.length;
    let isSame = true;
    if (size !== gridSize) isSame = false;
    for (let i = 0; i < size && isSame; i++) {
        for (let j = 0; j < size; j++) {
            if (!currentGrid[i] || currentGrid[i][j] !== gridPattern[i][j]) {
                isSame = false;
                break;
            }
        }
    }
    if (isSame) {
        alert('현재 그리드와 동일한 템플릿입니다.');
        return;
    }
    // 2. 그리드 크기와 패턴 적용
    initializeGrid(size);
    for (let i = 0; i < size; i++) {
        for (let j = 0; j < size; j++) {
            currentGrid[i][j] = gridPattern[i][j];
        }
    }
    renderGrid();
    checkConditions();
    // 3. 하단에 미리보기 정보 표시 (선택적으로 구현)
    // 4. 안내 메시지
    alert('템플릿이 그리드에 적용되었습니다.');
}

// 레벨 정보 숨기기
function hideLevelInfo() {
    document.getElementById('levelConditions').style.display = 'none';
    document.getElementById('existingTemplates').style.display = 'none';
    levelConditions = null;
}

// 조건 확인
function checkConditions() {
    if (!levelConditions) return;
    
    const wordPositions = analyzeGrid();
    const wordCount = wordPositions.length;
    const intersectionCount = countIntersections(wordPositions);
    
    const conditionCheck = document.getElementById('conditionCheck');
    const conditionDetails = document.getElementById('conditionDetails');
    const saveButton = document.getElementById('saveTemplate');
    
    let isValid = true;
    let details = [];
    
    // 단어 개수 확인
    if (wordCount === levelConditions.word_count) {
        details.push(`✅ 단어 개수: ${wordCount}/${levelConditions.word_count}`);
    } else {
        details.push(`❌ 단어 개수: ${wordCount}/${levelConditions.word_count}`);
        isValid = false;
    }
    
    // 교차점 개수 확인
    if (intersectionCount === levelConditions.intersection_count) {
        details.push(`✅ 교차점 개수: ${intersectionCount}/${levelConditions.intersection_count}`);
    } else {
        details.push(`❌ 교차점 개수: ${intersectionCount}/${levelConditions.intersection_count}`);
        isValid = false;
    }
    
    conditionDetails.innerHTML = details.join('<br>');
    conditionCheck.className = `alert ${isValid ? 'alert-success' : 'alert-danger'}`;
    conditionCheck.style.display = 'block';
    
    saveButton.disabled = !isValid;
}

// 그리드 분석
function analyzeGrid() {
    const wordPositions = [];
    let wordId = 1;
    
    // 가로 단어 찾기 (검은색 칸들)
    for (let i = 0; i < gridSize; i++) {
        let start = -1;
        for (let j = 0; j < gridSize; j++) {
            if (currentGrid[i][j] === 2 && start === -1) { // 검은색 칸 (단어 공간)
                start = j;
            } else if (currentGrid[i][j] === 1 && start !== -1) { // 흰색 칸 (빈 공간)
                if (j - start >= 2) {
                    wordPositions.push({
                        id: wordId++,
                        start_x: start,
                        start_y: i,
                        end_x: j - 1,
                        end_y: i,
                        direction: 'horizontal',
                        length: j - start
                    });
                }
                start = -1;
            }
        }
        if (start !== -1 && gridSize - start >= 2) {
            wordPositions.push({
                id: wordId++,
                start_x: start,
                start_y: i,
                end_x: gridSize - 1,
                end_y: i,
                direction: 'horizontal',
                length: gridSize - start
            });
        }
    }
    
    // 세로 단어 찾기 (검은색 칸들)
    for (let j = 0; j < gridSize; j++) {
        let start = -1;
        for (let i = 0; i < gridSize; i++) {
            if (currentGrid[i][j] === 2 && start === -1) { // 검은색 칸 (단어 공간)
                start = i;
            } else if (currentGrid[i][j] === 1 && start !== -1) { // 흰색 칸 (빈 공간)
                if (i - start >= 2) {
                    wordPositions.push({
                        id: wordId++,
                        start_x: j,
                        start_y: start,
                        end_x: j,
                        end_y: i - 1,
                        direction: 'vertical',
                        length: i - start
                    });
                }
                start = -1;
            }
        }
        if (start !== -1 && gridSize - start >= 2) {
            wordPositions.push({
                id: wordId++,
                start_x: j,
                start_y: start,
                end_x: j,
                end_y: gridSize - 1,
                direction: 'vertical',
                length: gridSize - start
            });
        }
    }
    
    return wordPositions;
}

// 교차점 개수 계산
function countIntersections(wordPositions) {
    let intersections = 0;
    
    for (let i = 0; i < wordPositions.length; i++) {
        for (let j = i + 1; j < wordPositions.length; j++) {
            const word1 = wordPositions[i];
            const word2 = wordPositions[j];
            
            if (word1.direction !== word2.direction) {
                if (word1.direction === 'horizontal' && word2.direction === 'vertical') {
                    if (word1.start_y >= word2.start_y && word1.start_y <= word2.end_y &&
                        word2.start_x >= word1.start_x && word2.start_x <= word1.end_x) {
                        intersections++;
                    }
                } else if (word1.direction === 'vertical' && word2.direction === 'horizontal') {
                    if (word2.start_y >= word1.start_y && word2.start_y <= word1.end_y &&
                        word1.start_x >= word2.start_x && word1.start_x <= word2.end_x) {
                        intersections++;
                    }
                }
            }
        }
    }
    
    return intersections;
}

// 기존 템플릿과 동일한지 체크
function isSameTemplate(existingTemplates, gridPattern) {
    for (const template of existingTemplates) {
        const tGrid = JSON.parse(template.grid_pattern);
        if (tGrid.length !== gridPattern.length) continue;
        let same = true;
        for (let i = 0; i < tGrid.length && same; i++) {
            for (let j = 0; j < tGrid.length; j++) {
                if (tGrid[i][j] !== gridPattern[i][j]) {
                    same = false;
                    break;
                }
            }
        }
        if (same) return true;
    }
    return false;
}

// 폼 제출
const gridTemplateForm = document.getElementById('gridTemplateForm');
gridTemplateForm.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!levelConditions) {
        alert('레벨을 선택해주세요.');
        return;
    }
    const wordPositions = analyzeGrid();
    const intersectionCount = countIntersections(wordPositions);
    const formData = {
        level_id: document.getElementById('level_id').value,
        grid_size: gridSize,
        grid_pattern: currentGrid,
        word_positions: wordPositions,
        word_count: wordPositions.length,
        intersection_count: intersectionCount
    };
    // 동일 템플릿 체크
    if (window.existingTemplates && isSameTemplate(window.existingTemplates, currentGrid)) {
        alert('동일한 템플릿이 이미 존재합니다.');
        return;
    }
    // 저장 요청
    fetch('{{ route("puzzle.grid-templates.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(formData)
    })
    .then(async response => {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.indexOf('application/json') !== -1) {
            const data = await response.json();
            const modal = new bootstrap.Modal(document.getElementById('resultModal'));
            const resultMessage = document.getElementById('resultMessage');
            if (data.success) {
                resultMessage.innerHTML = `
                    <div class="alert alert-success">
                        <h6>✅ 성공!</h6>
                        <p>${data.message}</p>
                        <p><strong>템플릿 ID:</strong> ${data.template_id}</p>
                    </div>
                `;
            } else {
                resultMessage.innerHTML = `
                    <div class="alert alert-danger">
                        <h6>❌ 오류!</h6>
                        <p>${data.message}</p>
                    </div>
                `;
            }
            modal.show();
        } else {
            const text = await response.text();
            throw new Error('서버에서 JSON이 아닌 응답이 반환되었습니다.\n' + text.substring(0, 200));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('템플릿 저장 중 오류가 발생했습니다.');
    });
});

// 마우스 드래그 이벤트
document.addEventListener('mousedown', () => {
    document.addEventListener('mouseover', handleDrag);
});

document.addEventListener('mouseup', () => {
    document.removeEventListener('mouseover', handleDrag);
});

function handleDrag(e) {
    if (e.target.classList.contains('grid-cell') && e.buttons === 1) {
        const rect = e.target.parentElement.getBoundingClientRect();
        const row = Array.from(e.target.parentElement.children).indexOf(e.target);
        const col = Array.from(e.target.parentElement.parentElement.children).indexOf(e.target.parentElement);
        toggleCell(col, row);
    }
}

// 초기화
document.addEventListener('DOMContentLoaded', () => {
    initializeGrid(5);
});

// 단어 추출 함수
function extractWords(templateId) {
    // 로딩 표시
    const modal = new bootstrap.Modal(document.getElementById('wordExtractionModal'));
    const content = document.getElementById('wordExtractionContent');
    content.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div><p class="mt-2">단어 추출 중...</p></div>';
    modal.show();

    fetch('{{ route("puzzle.grid-templates.extract-words") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ template_id: templateId })
    })
    .then(async response => {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.indexOf('application/json') !== -1) {
            const data = await response.json();
            showWordExtractionResult(data);
        } else {
            const text = await response.text();
            throw new Error('서버에서 JSON이 아닌 응답이 반환되었습니다.\n' + text.substring(0, 200));
        }
    })
    .catch(error => {
        const errorModal = new bootstrap.Modal(document.getElementById('wordExtractionModal'));
        const errorContent = document.getElementById('wordExtractionContent');
        errorContent.innerHTML = `
            <div class="alert alert-danger">
                <h6>❌ 오류 발생!</h6>
                <p>단어 추출 중 오류가 발생했습니다.</p>
                <p><strong>오류 내용:</strong> ${error.message}</p>
                <button class="btn btn-secondary" onclick="closeWordExtractionModalAndFocus()">닫기</button>
            </div>
        `;
        errorModal.show();
    });
}

function closeWordExtractionModalAndFocus() {
    const modalEl = document.getElementById('wordExtractionModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
    
    // 바로 새로고침
    window.location.reload();
}

// 단어 추출 결과 표시 함수
function showWordExtractionResult(data) {
    console.log('showWordExtractionResult 호출됨:', data);
    
    const modal = document.getElementById('wordExtractionModal');
    const content = document.getElementById('wordExtractionContent');
    
    if (!modal || !content) {
        console.error('Modal elements not found');
        alert('팝업 요소를 찾을 수 없습니다.');
        return;
    }
    
    let html = '';
    
    if (data.success) {
        html = `
            <div class="word-extraction-result">
                <h5>🎯 단어 배치 순서 결정 결과</h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>📊 템플릿 분석</h6>
                        <table class="table table-sm">
                            <tr><th>템플릿:</th><td>${data.template.template_name}</td></tr>
                            <tr><th>그리드 크기:</th><td>${data.extracted_words.grid_info.width}×${data.extracted_words.grid_info.height}</td></tr>
                            <tr><th>총 단어 수:</th><td><span class="badge bg-primary">${data.word_analysis.total_words}</span></td></tr>
                            <tr><th>배치 순서:</th><td><span class="badge bg-success">${data.extracted_words.word_order.length}개</span></td></tr>
                        </table>
                        
                        <h6>🔍 교차점 분석</h6>
                        <div class="alert alert-info">
                            <p><strong>독립 단어:</strong> ${data.extracted_words.word_order.filter(item => item.type === 'no_intersection').length}개</p>
                            <p><strong>연결된 단어:</strong> ${data.extracted_words.word_order.filter(item => item.type !== 'no_intersection').length}개</p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>📝 결정된 단어 배치 순서</h6>
                        <div class="word-order-list" style="max-height: 300px; overflow-y: auto;">
                            ${data.extracted_words.word_order.map((item, index) => `
                                <div class="card mb-2">
                                    <div class="card-body p-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-primary me-2">${item.order}</span>
                                                <strong>단어 ${item.word_id}</strong>
                                                <small class="text-muted ms-2">(${item.position.length}글자)</small>
                                            </div>
                                            <div>
                                                <span class="badge ${item.type === 'no_intersection' ? 'bg-secondary' : item.type === 'intersection_start' ? 'bg-warning' : 'bg-success'}">${getTypeLabel(item.type)}</span>
                                            </div>
                                        </div>
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                ${item.position.direction === 'horizontal' ? '가로' : '세로'} 
                                                (${item.position.start_x},${item.position.start_y}) → (${item.position.end_x},${item.position.end_y})
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <h6>🔲 그리드 패턴 시각화</h6>
                        <div class="grid-visualization mb-3">
                            ${renderGridWithWordOrder(data.extracted_words.grid_info.pattern, data.extracted_words.word_order)}
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <button class="btn btn-secondary" onclick="closeWordExtractionModalAndFocus()">닫기</button>
                </div>
            </div>
        `;
    } else {
        // 오류 발생 시 상세 정보 표시
        html = `
            <div class="alert alert-warning">
                <h6>⚠️ 단어 배치 순서 결정 결과</h6>
                <p><strong>메시지:</strong> ${data.message || '알 수 없는 오류'}</p>
                
                ${data.debug_info ? `
                <div class="mt-3">
                    <h6>🔍 디버그 정보</h6>
                    <div class="card">
                        <div class="card-body">
                            <pre class="mb-0" style="font-size: 0.8em;">${JSON.stringify(data.debug_info, null, 2)}</pre>
                        </div>
                    </div>
                </div>
                ` : ''}
                
                <div class="text-center mt-3">
                    <button class="btn btn-secondary" onclick="closeWordExtractionModalAndFocus()">닫기</button>
                </div>
            </div>
        `;
    }
    
    content.innerHTML = html;
    
    // Bootstrap 모달 열기
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();

    // 닫기 버튼에 포커스 이동 함수 연결
    setTimeout(() => {
        const closeBtns = document.querySelectorAll('#wordExtractionModal .btn-secondary');
        closeBtns.forEach(btn => {
            btn.onclick = function() {
                const modal = bootstrap.Modal.getInstance(document.getElementById('wordExtractionModal'));
                if (modal) modal.hide();
                setTimeout(() => { document.body.focus(); }, 200);
            };
        });
    }, 500);
}

// 타입 라벨 반환 함수
function getTypeLabel(type) {
    switch(type) {
        case 'no_intersection': return '독립 단어';
        case 'intersection_start': return '교차 시작';
        case 'chain_middle': return '연결 중간';
        default: return type;
    }
}

// 추출된 단어로 그리드 렌더링
function renderGridWithWordOrder(gridPattern, wordOrder) {
    let html = '<div class="grid-container" style="display: inline-block; border: 2px solid #333;">';
    
    for (let y = 0; y < gridPattern.length; y++) {
        html += '<div class="grid-row" style="display: flex;">';
        for (let x = 0; x < gridPattern[y].length; x++) {
            const cellValue = gridPattern[y][x];
            const wordInfo = getWordInfoAtPosition(x, y, wordOrder);
            
            let cellClass = 'grid-cell-number';
            let cellContent = '';
            
            if (cellValue === 2) { // 검은색칸 (단어가 있는 칸)
                cellClass += ' bg-dark text-white';
                if (wordInfo) {
                    cellContent = `<span class="word-number">${wordInfo.order}</span>`;
                }
            } else { // 흰색칸 (빈 칸)
                cellClass += ' bg-light';
                cellContent = '□';
            }
            
            html += `<div class="${cellClass}" style="width: 40px; height: 40px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">${cellContent}</div>`;
        }
        html += '</div>';
    }
    
    html += '</div>';
    return html;
}

// 특정 위치의 단어 정보 찾기
function getWordInfoAtPosition(x, y, wordOrder) {
    for (const wordInfo of wordOrder) {
        const word = wordInfo.position;
        
        // 가로 방향 단어
        if (word.direction === 'horizontal') {
            if (y === word.start_y && x >= word.start_x && x <= word.end_x) {
                // 단어의 시작 위치인지 확인
                if (x === word.start_x) {
                    return wordInfo;
                }
            }
        }
        // 세로 방향 단어
        else if (word.direction === 'vertical') {
            if (x === word.start_x && y >= word.start_y && y <= word.end_y) {
                // 단어의 시작 위치인지 확인
                if (y === word.start_y) {
                    return wordInfo;
                }
            }
        }
    }
    
    return null;
}

// 모달 닫힐 때 백드롭 강제 제거 (중복 방지)
document.addEventListener('DOMContentLoaded', function() {
    // 모든 모달에 대해 백드롭 제거 이벤트 추가
    const modals = ['resultModal', 'wordExtractionModal', 'templateDetailModal'];
    
    modals.forEach(modalId => {
        const modalElement = document.getElementById(modalId);
        if (modalElement) {
            modalElement.addEventListener('hidden.bs.modal', function() {
                // 모든 백드롭 강제 제거
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.paddingRight = '';
            });
        }
    });
});
</script>

<style>
.grid-cell:hover {
    opacity: 0.8;
}

.grid-cell:active {
    opacity: 0.6;
}

/* 그리드 넘버링 스타일 */
.grid-cell-number {
    position: relative;
    transition: all 0.2s ease;
}

.grid-cell-number:hover {
    transform: scale(1.05);
    z-index: 10;
}

.word-number {
    background: #ff6b6b;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.grid-container {
    background: white;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

.word-order-list {
    max-height: 400px;
    overflow-y: auto;
}

.word-order-list .card {
    transition: all 0.2s ease;
}

.word-order-list .card:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>
@endpush 