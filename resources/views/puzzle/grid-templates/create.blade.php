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
                                        <button type="button" class="btn btn-sm btn-outline-success" id="sortWordPositions" title="단어 위치 정보를 번호 순서대로 정렬">
                                            <i class="fas fa-sort-numeric-up"></i> 번호순 정렬
                                        </button>
                                    </div>
                                    <div>
                                        <span class="badge bg-dark">■ 검은색 칸 (단어 입력 공간)</span>
                                        <span class="badge bg-light text-dark ms-2">□ 흰색 칸 (빈 공간) - 기본값</span>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div id="gridEditor" class="border p-3 bg-light" style="display: inline-block;">
                                            <!-- 그리드가 여기에 동적으로 생성됩니다 -->
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="card-title mb-0">단어 위치 정보</h6>
                                            </div>
                                            <div class="card-body">
                                                <div id="wordPositionsList" style="max-height: 400px; overflow-y: auto;">
                                                    <p class="text-muted text-center">검은색 칸을 그려주세요</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                                    <i class="fas fa-save"></i> <span id="saveButtonText">템플릿 저장</span>
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
            <div class="modal-body">
                <div id="wordExtractionContent">
                </div>
                <hr>
                <div class="mt-3">
                    <h6>실행된 데이터베이스 쿼리</h6>
                    <div id="queryLog" class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 12px;">
                        <p class="text-muted">쿼리 로그가 여기에 표시됩니다...</p>
                    </div>
                </div>
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
let currentTemplateId = null; // 현재 수정 중인 템플릿 ID
let isEditMode = false; // 수정 모드 여부
let savedScrollPosition = 0; // 모달 열기 전 스크롤 위치 저장

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

// DOM 로드 완료 후 초기화
document.addEventListener('DOMContentLoaded', function() {
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

    // 초기 그리드 생성
    initializeGrid(5);
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
    const wordPositions = JSON.parse(template.word_positions);
    
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
    
    // 3. 수정 모드 설정
    currentTemplateId = template.id;
    isEditMode = true;
    
    // 템플릿 정보를 templateSelect에 저장 (하이라이트 기능을 위해)
    const templateSelect = document.getElementById('templateSelect');
    if (templateSelect) {
        templateSelect.value = JSON.stringify({
            id: template.id,
            template_name: template.template_name,
            grid_pattern: template.grid_pattern,
            word_positions: template.word_positions
        });
    }
    
    // 저장 버튼 텍스트 변경
    document.getElementById('saveButtonText').textContent = '템플릿 수정';
    
    // 4. 단어 위치 정보와 번호 정보 적용
    setTimeout(() => {
        updateWordPositionsList(wordPositions);
        
        // word_positions의 id 값이 사용자가 설정한 번호이므로 그대로 사용
        wordPositions.forEach(word => {
            const select = document.querySelector(`.word-number-select[data-word-id="${word.id}"]`);
            if (select) {
                select.value = word.id;
            }
        });
        
        // 저장 버튼 상태 업데이트
        updateSaveButtonState();
    }, 100);
    
    // 5. 안내 메시지
    alert('템플릿이 그리드에 적용되었습니다. 번호를 수정할 수 있습니다.');
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
    
    // 수정 모드이고 저장된 템플릿이 있는 경우 저장된 word_positions 사용
    let wordPositions;
    if (isEditMode && currentTemplateId) {
        const templateSelect = document.getElementById('templateSelect');
        if (templateSelect && templateSelect.value) {
            const selectedTemplate = JSON.parse(templateSelect.value);
            wordPositions = JSON.parse(selectedTemplate.word_positions);
        }
    }
    
    // 저장된 데이터가 없으면 현재 그리드 분석
    if (!wordPositions) {
        wordPositions = analyzeGrid();
    }
    
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
    
    // 교차점 개수 확인 (최소값 체크)
    if (intersectionCount >= levelConditions.intersection_count) {
        details.push(`✅ 교차점 개수: ${intersectionCount}개 (최소 ${levelConditions.intersection_count}개 필요)`);
    } else {
        details.push(`❌ 교차점 개수: ${intersectionCount}개 (최소 ${levelConditions.intersection_count}개 필요)`);
        isValid = false;
    }
    
    conditionDetails.innerHTML = details.join('<br>');
    conditionCheck.className = `alert ${isValid ? 'alert-success' : 'alert-danger'}`;
    conditionCheck.style.display = 'block';
    
    // 신규 생성 시에는 항상 단어 위치 정보 업데이트, 수정 모드일 때는 기존 정보 유지
    if (!isEditMode) {
        updateWordPositionsList(wordPositions);
    } else {
        // 수정 모드에서도 단어 위치 정보가 없으면 업데이트
        const wordPositionsList = document.getElementById('wordPositionsList');
        if (wordPositionsList.innerHTML.includes('검은색 칸을 그려주세요') || wordPositionsList.children.length === 0) {
            updateWordPositionsList(wordPositions);
        }
    }
    
    // 번호 선택 validation 체크
    const numberValidation = validateWordNumbers();
    saveButton.disabled = !isValid || !numberValidation;
}

// 단어 위치 정보 목록 업데이트
function updateWordPositionsList(wordPositions) {
    const container = document.getElementById('wordPositionsList');
    
    if (wordPositions.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">검은색 칸을 그려주세요</p>';
        return;
    }
    
    // 기존 선택된 번호들 저장
    const existingSelections = {};
    document.querySelectorAll('.word-number-select').forEach(select => {
        if (select.value) {
            existingSelections[select.dataset.wordId] = select.value;
        }
    });
    
    let html = '';
    wordPositions.forEach((word, index) => {
        const direction = word.direction === 'horizontal' ? '가로' : '세로';
        const length = word.length;
        const startPos = `(${word.start_x},${word.start_y})`;
        const endPos = `(${word.end_x},${word.end_y})`;
        
        // 기존 선택된 번호가 있으면 유지
        const selectedValue = existingSelections[word.id] || '';
        
        html += `
            <div class="word-position-item mb-2 p-2 border rounded" data-word-id="${word.id}">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1">
                        <small class="text-muted">${startPos} 에서 ${endPos}</small><br>
                        <strong>${direction} ${length}칸</strong>
                    </div>
                    <div class="ms-2">
                        <select class="form-select form-select-sm word-number-select" 
                                data-word-id="${word.id}" 
                                style="width: 80px;">
                            <option value="">번호</option>
                            ${generateNumberOptions(wordPositions, selectedValue)}
                        </select>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    // 번호 선택 이벤트 추가
    document.querySelectorAll('.word-number-select').forEach(select => {
        select.addEventListener('change', function() {
            const wordId = this.dataset.wordId;
            const selectedNumber = this.value;
            
            // 다른 번호와 중복 체크
            if (selectedNumber && isDuplicateNumber(selectedNumber, wordId)) {
                alert('이미 사용된 번호입니다.');
                this.value = '';
                return;
            }
            
            // 그리드에서 해당 단어 하이라이트
            highlightWordInGrid(parseInt(wordId), selectedNumber);
            
            // 저장 버튼 상태 업데이트 (단어 위치 정보는 다시 업데이트하지 않음)
            updateSaveButtonState();
        });
        
        // 포커스 시 하이라이트
        select.addEventListener('focus', function() {
            const wordId = this.dataset.wordId;
            highlightWordInGrid(parseInt(wordId), this.value || 'focus');
        });
        
        // 포커스 아웃 시 하이라이트 제거
        select.addEventListener('blur', function() {
            const wordId = this.dataset.wordId;
            if (!this.value) {
                removeWordHighlight(parseInt(wordId));
            }
        });
        
        // 단어 위치 아이템 클릭 시에도 포커스
        const wordItem = select.closest('.word-position-item');
        if (wordItem) {
            wordItem.addEventListener('click', function() {
                select.focus();
            });
        }
    });
}

// 번호 옵션 생성 (word_positions의 id 값 사용)
function generateNumberOptions(wordPositions, selectedValue = '') {
    let options = '';
    wordPositions.forEach(word => {
        const selected = word.id.toString() === selectedValue ? 'selected' : '';
        options += `<option value="${word.id}" ${selected}>${word.id}</option>`;
    });
    return options;
}

// 중복 번호 체크
function isDuplicateNumber(number, currentWordId) {
    const selects = document.querySelectorAll('.word-number-select');
    for (const select of selects) {
        if (select.dataset.wordId !== currentWordId && select.value === number) {
            return true;
        }
    }
    return false;
}

// 번호 선택 validation
function validateWordNumbers() {
    const selects = document.querySelectorAll('.word-number-select');
    const selectedNumbers = [];
    
    for (const select of selects) {
        if (select.value) {
            selectedNumbers.push(parseInt(select.value));
        }
    }
    
    // 모든 번호가 선택되었는지 확인
    if (selectedNumbers.length !== selects.length) {
        return false;
    }
    
    // 중복 번호가 없는지 확인
    const uniqueNumbers = [...new Set(selectedNumbers)];
    return uniqueNumbers.length === selectedNumbers.length;
}

// 그리드에서 단어 하이라이트
function highlightWordInGrid(wordId, number) {
    // 단어 위치 정보에서 해당 wordId를 가진 요소 찾기
    const wordItem = document.querySelector(`.word-position-item[data-word-id="${wordId}"]`);
    if (!wordItem) return;
    
    // 단어 위치 정보에서 좌표 추출
    const text = wordItem.querySelector('small').textContent;
    const match = text.match(/\((\d+),(\d+)\) 에서 \((\d+),(\d+)\)/);
    if (!match) return;
    
    const startX = parseInt(match[1]);
    const startY = parseInt(match[2]);
    const endX = parseInt(match[3]);
    const endY = parseInt(match[4]);
    
    // 기존 하이라이트 제거
    removeAllHighlights();
    
    // 해당 단어 하이라이트
    const cells = document.querySelectorAll('.grid-cell');
    for (let y = startY; y <= endY; y++) {
        for (let x = startX; x <= endX; x++) {
            const cellIndex = y * gridSize + x;
            if (cells[cellIndex]) {
                if (number === 'focus') {
                    cells[cellIndex].classList.add('highlighted-focus');
                } else {
                    cells[cellIndex].classList.add('highlighted');
                }
            }
        }
    }
    
    // 단어 위치 아이템도 하이라이트
    wordItem.classList.add('selected');
}

// 단어 하이라이트 제거
function removeWordHighlight(wordId) {
    // 단어 위치 정보에서 해당 wordId를 가진 요소 찾기
    const wordItem = document.querySelector(`.word-position-item[data-word-id="${wordId}"]`);
    if (!wordItem) return;
    
    // 단어 위치 정보에서 좌표 추출
    const text = wordItem.querySelector('small').textContent;
    const match = text.match(/\((\d+),(\d+)\) 에서 \((\d+),(\d+)\)/);
    if (!match) return;
    
    const startX = parseInt(match[1]);
    const startY = parseInt(match[2]);
    const endX = parseInt(match[3]);
    const endY = parseInt(match[4]);
    
    const cells = document.querySelectorAll('.grid-cell');
    for (let y = startY; y <= endY; y++) {
        for (let x = startX; x <= endX; x++) {
            const cellIndex = y * gridSize + x;
            if (cells[cellIndex]) {
                cells[cellIndex].classList.remove('highlighted', 'highlighted-focus');
            }
        }
    }
    
    // 단어 위치 아이템 하이라이트 제거
    wordItem.classList.remove('selected');
}

// 모든 하이라이트 제거
function removeAllHighlights() {
    const cells = document.querySelectorAll('.grid-cell');
    cells.forEach(cell => {
        cell.classList.remove('highlighted', 'highlighted-focus');
    });
    
    // 단어 위치 아이템 하이라이트 제거
    document.querySelectorAll('.word-position-item').forEach(item => {
        item.classList.remove('selected');
    });
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

// 기존 템플릿과 동일한지 체크 (활성화된 템플릿만 체크)
function isSameTemplate(existingTemplates, gridPattern) {
    for (const template of existingTemplates) {
        // 활성화된 템플릿만 체크 (is_active = true)
        if (template.is_active !== true && template.is_active !== 1) {
            continue;
        }
        
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
        if (same) {
            return {
                isSame: true,
                templateName: template.template_name,
                templateId: template.id
            };
        }
    }
    return { isSame: false };
}

// 폼 제출
const gridTemplateForm = document.getElementById('gridTemplateForm');
gridTemplateForm.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!levelConditions) {
        alert('레벨을 선택해주세요.');
        return;
    }
    
    // 수정 모드일 때는 기존 word_positions 사용, 신규 생성일 때는 analyzeGrid() 사용
    let wordPositions;
    if (isEditMode && currentTemplateId) {
        const templateSelect = document.getElementById('templateSelect');
        if (templateSelect && templateSelect.value) {
            const selectedTemplate = JSON.parse(templateSelect.value);
            wordPositions = JSON.parse(selectedTemplate.word_positions);
        }
    }
    
    if (!wordPositions) {
        wordPositions = analyzeGrid();
    }
    
    const intersectionCount = countIntersections(wordPositions);
    
    // 번호 정보 수집
    const wordNumbering = [];
    document.querySelectorAll('.word-number-select').forEach(select => {
        if (select.value) {
            wordNumbering.push({
                word_id: parseInt(select.dataset.wordId),
                order: parseInt(select.value)
            });
        }
    });
    
    // 수정 모드에서 사용자가 선택한 번호로 word_positions의 id 값 업데이트
    if (isEditMode && wordNumbering.length > 0) {
        // 번호 매핑 생성
        const numberMapping = {};
        wordNumbering.forEach(item => {
            numberMapping[item.word_id] = item.order;
        });
        
        // word_positions의 id 값을 선택된 번호로 변경
        wordPositions.forEach(word => {
            if (numberMapping[word.id]) {
                word.id = numberMapping[word.id];
            }
        });
    }
    
    const formData = {
        level_id: document.getElementById('level_id').value,
        grid_size: gridSize,
        grid_pattern: currentGrid,
        word_positions: wordPositions,
        word_numbering: wordNumbering,
        word_count: wordPositions.length,
        intersection_count: intersectionCount
    };
    
    // 수정 모드가 아닐 때만 동일 템플릿 체크
    if (!isEditMode && window.existingTemplates) {
        const duplicateCheck = isSameTemplate(window.existingTemplates, currentGrid);
        if (duplicateCheck.isSame) {
            alert(`동일한 템플릿이 이미 존재합니다.\n\n템플릿명: ${duplicateCheck.templateName}\n템플릿 ID: ${duplicateCheck.templateId}\n\n다른 그리드 패턴을 사용하거나 기존 템플릿을 수정해주세요.`);
            return;
        }
    }
    
    // 요청 URL 결정 (수정 모드인지 신규 생성인지)
    const requestUrl = isEditMode 
        ? `{{ route("puzzle.grid-templates.update", ":id") }}`.replace(':id', currentTemplateId)
        : '{{ route("puzzle.grid-templates.store") }}';
    
    const requestMethod = isEditMode ? 'PUT' : 'POST';
    
    // 저장 요청
    fetch(requestUrl, {
        method: requestMethod,
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
                
                // 수정 모드였다면 수정 모드 해제
                if (isEditMode) {
                    isEditMode = false;
                    currentTemplateId = null;
                    document.getElementById('saveButtonText').textContent = '템플릿 저장';
                }
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

// 단어 추출 함수
function extractWords(templateId) {
    // 현재 스크롤 위치 저장
    savedScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
    
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
    
    // 페이지 새로고침 제거 - 스크롤 위치 유지를 위해
    // window.location.reload();
    
    // 모달 닫힌 후 포커스 복원 및 스크롤 위치 복원
    setTimeout(() => {
        document.body.focus();
        // 저장된 스크롤 위치로 복원
        if (savedScrollPosition > 0) {
            window.scrollTo(0, savedScrollPosition);
        }
    }, 200);
}

// 단어 추출 결과 표시 함수
function showWordExtractionResult(data) {
    const modal = document.getElementById('wordExtractionModal');
    const content = document.getElementById('wordExtractionContent');
    const queryLogDiv = document.getElementById('queryLog');
    
    let html = '';
    
    if (data.success) {
        // 원본 데이터를 DOM에 저장 (정렬 기능을 위해)
        content.dataset.wordOrder = JSON.stringify(data.extracted_words.word_order);
        content.dataset.gridPattern = JSON.stringify(data.extracted_words.grid_info.pattern);
        
        html = `
            <div class="alert alert-success mb-3">
                <h6>✅ 단어 배치 순서 결정 완료!</h6>
                <p>총 ${data.extracted_words.word_order.length}개의 단어가 성공적으로 배치되었습니다.</p>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <h6>📊 배치 통계</h6>
                    <table class="table table-sm">
                        <tr><th>총 단어 수:</th><td><span class="badge bg-primary">${data.word_analysis.total_words}</span></td></tr>
                        <tr><th>배치 순서:</th><td><span class="badge bg-success">${data.extracted_words.word_order.length}개</span></td></tr>
                    </table>
                    
                    <h6>🔍 교차점 분석</h6>
                    <div class="alert alert-info">
                        <p><strong>독립 단어:</strong> ${calculateIndependentWords(data.extracted_words.word_order)}개</p>
                        <p><strong>연결된 단어:</strong> ${calculateConnectedWords(data.extracted_words.word_order)}개</p>
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
                                            <span class="badge bg-primary me-2">${item.word_id}</span>
                                            <strong>단어 ${item.word_id}</strong>
                                            <small class="text-muted ms-2">(${item.position.length}글자)</small>
                                        </div>
                                        <div>
                                            <span class="badge ${item.type === 'no_intersection' || item.type === 'sequential_word' || item.type === 'remaining_word' ? 'bg-secondary' : 
                                                                item.type === 'intersection_start' || item.type === 'first_word' ? 'bg-warning' : 
                                                                item.type === 'chain_middle' || item.type === 'intersection_connected' ? 'bg-success' : 
                                                                item.type === 'intersection_horizontal' || item.type === 'intersection_vertical' ? 'bg-info' : 'bg-primary'}">${getTypeLabel(item.type)}</span>
                                        </div>
                                    </div>
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            ${item.position.direction === 'horizontal' ? '가로' : '세로'} 
                                            (${item.position.start_x},${item.position.start_y}) → (${item.position.end_x},${item.position.end_y})
                                        </small>
                                    </div>
                                    ${item.extracted_word ? `
                                    <div class="mt-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-success">추출된 단어: ${item.extracted_word}</strong>
                                            <button class="btn btn-sm btn-outline-info" onclick="showHint('${item.hint || '힌트 없음'}')">힌트 보기</button>
                                        </div>
                                    </div>
                                    ` : ''}
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
    
    // 쿼리 로그 표시
    if (data.query_log && data.query_log.length > 0) {
        let queryLogHtml = '';
        data.query_log.forEach((log, index) => {
            const bindingsStr = log.bindings.map(b => typeof b === 'string' ? `'${b}'` : b).join(', ');
            const finalSql = log.sql.replace(/\?/g, () => {
                const binding = log.bindings.shift();
                return typeof binding === 'string' ? `'${binding}'` : binding;
            });
            
            queryLogHtml += `
                <div class="mb-3 p-2 border-start border-3 border-primary">
                    <div class="fw-bold text-primary">${index + 1}. ${log.description}</div>
                    <div class="mt-1">
                        <strong>SQL:</strong>
                        <pre class="bg-dark text-light p-2 rounded mt-1" style="font-size: 11px; overflow-x: auto;">${finalSql}</pre>
                    </div>
                    <div class="mt-1">
                        <strong>바인딩:</strong> [${bindingsStr}]
                    </div>
                </div>
            `;
        });
        queryLogDiv.innerHTML = queryLogHtml;
    } else {
        queryLogDiv.innerHTML = '<p class="text-muted">실행된 쿼리가 없습니다.</p>';
    }
    
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
        case 'sequential_word': return '순차 단어';
        case 'remaining_word': return '남은 단어';
        case 'intersection_start': return '교차 시작';
        case 'chain_middle': return '연결 중간';
        case 'intersection_connected': return '교차 연결';
        case 'intersection_horizontal': return '교차 가로';
        case 'intersection_vertical': return '교차 세로';
        case 'first_word': return '첫 번째 단어';
        default: return type;
    }
}

// 독립 단어 개수 계산 (교차점이 없는 단어)
function calculateIndependentWords(wordOrder) {
    const independentWords = [];
    
    for (let i = 0; i < wordOrder.length; i++) {
        const word1 = wordOrder[i];
        let hasIntersection = false;
        
        // 다른 모든 단어와 교차점 확인
        for (let j = 0; j < wordOrder.length; j++) {
            if (i === j) continue;
            
            const word2 = wordOrder[j];
            if (hasIntersectionWith(word1, word2)) {
                hasIntersection = true;
                break;
            }
        }
        
        if (!hasIntersection) {
            independentWords.push(word1);
        }
    }
    
    return independentWords.length;
}

// 연결된 단어 개수 계산 (교차점이 있는 단어)
function calculateConnectedWords(wordOrder) {
    const connectedWords = [];
    
    for (let i = 0; i < wordOrder.length; i++) {
        const word1 = wordOrder[i];
        let hasIntersection = false;
        
        // 다른 모든 단어와 교차점 확인
        for (let j = 0; j < wordOrder.length; j++) {
            if (i === j) continue;
            
            const word2 = wordOrder[j];
            if (hasIntersectionWith(word1, word2)) {
                hasIntersection = true;
                break;
            }
        }
        
        if (hasIntersection) {
            connectedWords.push(word1);
        }
    }
    
    return connectedWords.length;
}

// 두 단어가 교차점을 가지는지 확인
function hasIntersectionWith(word1, word2) {
    const pos1 = word1.position;
    const pos2 = word2.position;
    
    // 가로-세로 교차만 고려
    if (pos1.direction === pos2.direction) {
        return false;
    }
    
    const horizontal = pos1.direction === 'horizontal' ? pos1 : pos2;
    const vertical = pos1.direction === 'vertical' ? pos1 : pos2;
    
    // 교차점 좌표 계산
    const intersectX = vertical.start_x;
    const intersectY = horizontal.start_y;
    
    // 교차점이 두 단어 범위 내에 있는지 확인
    if (intersectX >= horizontal.start_x && intersectX <= horizontal.end_x &&
        intersectY >= vertical.start_y && intersectY <= vertical.end_y) {
        return true;
    }
    
    return false;
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
                    // word_positions의 id 값으로 표시
                    cellContent = `<span class="word-number">${wordInfo.word_id}</span>`;
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

// 저장 버튼 상태만 업데이트 (단어 위치 정보는 다시 생성하지 않음)
function updateSaveButtonState() {
    if (!levelConditions) return;
    
    // 수정 모드이고 저장된 템플릿이 있는 경우 저장된 word_positions 사용
    let wordPositions;
    if (isEditMode && currentTemplateId) {
        const templateSelect = document.getElementById('templateSelect');
        if (templateSelect && templateSelect.value) {
            const selectedTemplate = JSON.parse(templateSelect.value);
            wordPositions = JSON.parse(selectedTemplate.word_positions);
        }
    }
    
    // 저장된 데이터가 없으면 현재 그리드 분석
    if (!wordPositions) {
        wordPositions = analyzeGrid();
    }
    
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
    
    // 교차점 개수 확인 (최소값 체크)
    if (intersectionCount >= levelConditions.intersection_count) {
        details.push(`✅ 교차점 개수: ${intersectionCount}개 (최소 ${levelConditions.intersection_count}개 필요)`);
    } else {
        details.push(`❌ 교차점 개수: ${intersectionCount}개 (최소 ${levelConditions.intersection_count}개 필요)`);
        isValid = false;
    }
    
    // 동일 템플릿 체크 (신규 생성 모드에서만)
    if (!isEditMode && window.existingTemplates) {
        const duplicateCheck = isSameTemplate(window.existingTemplates, currentGrid);
        if (duplicateCheck.isSame) {
            details.push(`⚠️ 동일한 템플릿이 이미 존재합니다 (ID: ${duplicateCheck.templateId})`);
            isValid = false;
        }
    }
    
    conditionDetails.innerHTML = details.join('<br>');
    conditionCheck.className = `alert ${isValid ? 'alert-success' : 'alert-danger'}`;
    conditionCheck.style.display = 'block';
    
    // 번호 선택 validation 체크
    const numberValidation = validateWordNumbers();
    saveButton.disabled = !isValid || !numberValidation;
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
                
                // body 스크롤 관련 스타일 완전 복원
                document.body.classList.remove('modal-open');
                document.body.style.paddingRight = '';
                document.body.style.overflow = '';
                
                // 단어 추출 모달인 경우 저장된 스크롤 위치로 복원
                if (modalId === 'wordExtractionModal' && savedScrollPosition > 0) {
                    setTimeout(() => {
                        window.scrollTo(0, savedScrollPosition);
                    }, 100);
                }
            });
        }
    });
    
    // 번호순 정렬 버튼 이벤트 리스너 추가
    const sortButton = document.getElementById('sortWordPositions');
    if (sortButton) {
        sortButton.addEventListener('click', function() {
            sortWordPositionsByNumber();
        });
    }
});

// 단어 위치 정보를 번호 순서대로 정렬하는 함수
function sortWordPositionsByNumber() {
    const wordPositionsList = document.getElementById('wordPositionsList');
    const wordItems = wordPositionsList.querySelectorAll('.word-position-item');
    
    if (wordItems.length === 0) {
        alert('정렬할 단어 위치 정보가 없습니다.');
        return;
    }
    
    // 현재 선택된 번호들을 수집
    const wordData = [];
    wordItems.forEach(item => {
        const wordId = item.dataset.wordId;
        const select = item.querySelector('.word-number-select');
        const selectedNumber = select ? select.value : '';
        
        // 좌표 정보 추출
        const text = item.querySelector('small').textContent;
        const match = text.match(/\((\d+),(\d+)\) 에서 \((\d+),(\d+)\)/);
        const direction = item.querySelector('strong').textContent;
        
        wordData.push({
            element: item,
            wordId: wordId,
            selectedNumber: selectedNumber,
            startX: match ? parseInt(match[1]) : 0,
            startY: match ? parseInt(match[2]) : 0,
            endX: match ? parseInt(match[3]) : 0,
            endY: match ? parseInt(match[4]) : 0,
            direction: direction
        });
    });
    
    // 번호가 선택된 항목들을 번호 순서대로 정렬
    const sortedData = wordData.sort((a, b) => {
        const aNum = a.selectedNumber ? parseInt(a.selectedNumber) : 999;
        const bNum = b.selectedNumber ? parseInt(b.selectedNumber) : 999;
        return aNum - bNum;
    });
    
    // 정렬된 순서로 DOM 재구성
    wordItems.forEach(item => item.remove());
    
    sortedData.forEach(data => {
        wordPositionsList.appendChild(data.element);
    });
    
    // 정렬 완료 메시지
    const sortedCount = sortedData.filter(item => item.selectedNumber).length;
    if (sortedCount > 0) {
        alert(`${sortedCount}개의 단어 위치 정보가 번호 순서대로 정렬되었습니다.`);
    } else {
        alert('선택된 번호가 없어 정렬할 수 없습니다. 번호를 먼저 선택해주세요.');
    }
}

// 힌트 보기 함수
function showHint(hint) {
    alert('힌트: ' + hint);
}
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

/* 단어 위치 정보 스타일 */
.word-position-item {
    transition: all 0.2s ease;
    border: 1px solid #dee2e6 !important;
}

.word-position-item:hover {
    border-color: #007bff !important;
    box-shadow: 0 2px 4px rgba(0,123,255,0.1);
}

.word-position-item.selected {
    border-color: #28a745 !important;
    background-color: #f8fff9;
}

.word-number-select {
    transition: all 0.2s ease;
}

.word-number-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

/* 그리드 하이라이트 효과 */
.grid-cell.highlighted {
    border: 2px solid red !important;
    box-shadow: 0 0 8px rgba(255,0,0,0.3);
    z-index: 5;
}

.grid-cell.highlighted-focus {
    border: 2px solid #ff6b6b !important;
    background-color: #ffebee !important;
    box-shadow: 0 0 8px rgba(255,107,107,0.3);
    z-index: 5;
}

/* 단어 위치 정보 카드 스타일 */
#wordPositionsList {
    scrollbar-width: thin;
    scrollbar-color: #007bff #f8f9fa;
}

#wordPositionsList::-webkit-scrollbar {
    width: 6px;
}

#wordPositionsList::-webkit-scrollbar-track {
    background: #f8f9fa;
    border-radius: 3px;
}

#wordPositionsList::-webkit-scrollbar-thumb {
    background: #007bff;
    border-radius: 3px;
}

#wordPositionsList::-webkit-scrollbar-thumb:hover {
    background: #0056b3;
}
</style>
@endpush 