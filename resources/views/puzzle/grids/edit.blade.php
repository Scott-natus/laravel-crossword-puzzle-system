@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">그리드 편집기 - Level {{ $level }}</h4>
                    <div>
                        <button type="button" class="btn btn-outline-secondary me-2" id="clearGrid">
                            <i class="fas fa-eraser"></i> 초기화
                        </button>
                        <button type="button" class="btn btn-success" id="saveGrid">
                            <i class="fas fa-save"></i> 저장
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- 그리드 설정 -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="gridWidth" class="form-label">그리드 너비</label>
                            <input type="number" class="form-control" id="gridWidth" value="{{ $gridWidth ?? 5 }}" min="3" max="20">
                        </div>
                        <div class="col-md-3">
                            <label for="gridHeight" class="form-label">그리드 높이</label>
                            <input type="number" class="form-control" id="gridHeight" value="{{ $gridHeight ?? 5 }}" min="3" max="20">
                        </div>
                        <div class="col-md-3">
                            <label for="wordCount" class="form-label">단어 수</label>
                            <input type="number" class="form-control" id="wordCount" value="{{ $wordCount ?? 5 }}" min="1" max="50">
                        </div>
                        <div class="col-md-3">
                            <label for="intersectionCount" class="form-label">교차점 수</label>
                            <input type="number" class="form-control" id="intersectionCount" value="{{ $intersectionCount ?? 2 }}" min="0" max="20">
                        </div>
                    </div>

                    <!-- 그리드 편집기 -->
                    <div class="grid-editor-container">
                        <div class="grid-controls mb-3">
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="editMode" id="whiteMode" value="white" checked>
                                <label class="btn btn-outline-primary" for="whiteMode">
                                    <i class="fas fa-square"></i> 흰색 칸
                                </label>
                                
                                <input type="radio" class="btn-check" name="editMode" id="blackMode" value="black">
                                <label class="btn btn-outline-dark" for="blackMode">
                                    <i class="fas fa-square"></i> 검은색 칸
                                </label>
                            </div>
                        </div>

                        <div class="grid-container" id="gridContainer">
                            <!-- 그리드가 여기에 동적으로 생성됩니다 -->
                        </div>

                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                흰색 칸을 선택하고 클릭하여 단어 배치를 설정하세요. 
                                검은색 칸은 단어 구분용 빈 칸입니다.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">단어 배치 정보</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>현재 설정</h6>
                        <ul class="list-unstyled">
                            <li><strong>그리드 크기:</strong> <span id="currentSize">5×5</span></li>
                            <li><strong>단어 수:</strong> <span id="currentWordCount">0</span></li>
                            <li><strong>교차점 수:</strong> <span id="currentIntersectionCount">0</span></li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <h6>단어 위치</h6>
                        <div id="wordPositions" class="list-group">
                            <!-- 단어 위치 정보가 여기에 표시됩니다 -->
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6>교차점</h6>
                        <div id="intersections" class="list-group">
                            <!-- 교차점 정보가 여기에 표시됩니다 -->
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb"></i> 사용법</h6>
                        <ol class="mb-0">
                            <li>그리드 크기를 설정하세요</li>
                            <li>흰색 칸 모드에서 단어를 배치하세요</li>
                            <li>검은색 칸 모드에서 단어 구분선을 그리세요</li>
                            <li>단어 수와 교차점이 자동으로 계산됩니다</li>
                            <li>저장 버튼을 눌러 템플릿을 저장하세요</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.grid-editor-container {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.grid-controls {
    text-align: center;
}

.grid-container {
    display: inline-block;
    border: 3px solid #333;
    background: #fff;
    padding: 10px;
    border-radius: 5px;
}

.grid-row {
    display: flex;
}

.grid-cell {
    width: 40px;
    height: 40px;
    border: 1px solid #ccc;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    background: #fff;
    transition: all 0.2s ease;
}

.grid-cell:hover {
    background: #f0f0f0;
    border-color: #999;
}

.grid-cell.black {
    background: #333;
    border-color: #333;
}

.grid-cell.black:hover {
    background: #555;
}

.grid-cell.selected {
    background: #007bff;
    color: white;
    border-color: #0056b3;
}

.grid-cell.word-start {
    background: #28a745;
    color: white;
    border-color: #1e7e34;
}

.cell-number {
    position: absolute;
    top: 2px;
    left: 2px;
    font-size: 10px;
    font-weight: bold;
    color: #666;
}

.word-start .cell-number {
    color: white;
}

@media (max-width: 768px) {
    .grid-cell {
        width: 30px;
        height: 30px;
    }
    
    .cell-number {
        font-size: 8px;
    }
}
</style>

<script>
class GridEditor {
    constructor() {
        this.grid = [];
        this.wordPositions = [];
        this.intersections = [];
        this.currentMode = 'white';
        this.selectedCell = null;
        this.wordCounter = 1;
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.createGrid();
        this.updateInfo();
    }

    setupEventListeners() {
        // 그리드 크기 변경
        document.getElementById('gridWidth').addEventListener('change', () => this.createGrid());
        document.getElementById('gridHeight').addEventListener('change', () => this.createGrid());
        
        // 단어 수 변경
        document.getElementById('wordCount').addEventListener('change', () => this.updateInfo());
        document.getElementById('intersectionCount').addEventListener('change', () => this.updateInfo());
        
        // 편집 모드 변경
        document.querySelectorAll('input[name="editMode"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.currentMode = e.target.value;
            });
        });
        
        // 버튼 이벤트
        document.getElementById('clearGrid').addEventListener('click', () => this.clearGrid());
        document.getElementById('saveGrid').addEventListener('click', () => this.saveGrid());
    }

    createGrid() {
        const width = parseInt(document.getElementById('gridWidth').value);
        const height = parseInt(document.getElementById('gridHeight').value);
        
        this.grid = [];
        for (let y = 0; y < height; y++) {
            this.grid[y] = [];
            for (let x = 0; x < width; x++) {
                this.grid[y][x] = 'white';
            }
        }
        
        this.renderGrid();
        this.updateInfo();
    }

    renderGrid() {
        const container = document.getElementById('gridContainer');
        container.innerHTML = '';
        
        const width = this.grid[0].length;
        const height = this.grid.length;
        
        for (let y = 0; y < height; y++) {
            const row = document.createElement('div');
            row.className = 'grid-row';
            
            for (let x = 0; x < width; x++) {
                const cell = document.createElement('div');
                cell.className = 'grid-cell';
                cell.dataset.x = x;
                cell.dataset.y = y;
                
                if (this.grid[y][x] === 'black') {
                    cell.classList.add('black');
                }
                
                cell.addEventListener('click', (e) => this.handleCellClick(x, y));
                cell.addEventListener('mousedown', (e) => this.handleMouseDown(x, y));
                cell.addEventListener('mouseover', (e) => this.handleMouseOver(x, y));
                
                row.appendChild(cell);
            }
            
            container.appendChild(row);
        }
    }

    handleCellClick(x, y) {
        if (this.currentMode === 'white') {
            this.toggleWordStart(x, y);
        } else {
            this.toggleBlackCell(x, y);
        }
        this.updateInfo();
    }

    handleMouseDown(x, y) {
        this.selectedCell = { x, y };
    }

    handleMouseOver(x, y) {
        if (this.selectedCell && this.currentMode === 'black') {
            this.grid[y][x] = 'black';
            this.renderGrid();
        }
    }

    toggleWordStart(x, y) {
        // 이미 단어 시작점인지 확인
        const existingWord = this.wordPositions.find(word => 
            word.start_x === x && word.start_y === y
        );
        
        if (existingWord) {
            // 단어 시작점 제거
            this.wordPositions = this.wordPositions.filter(word => 
                !(word.start_x === x && word.start_y === y)
            );
        } else {
            // 새 단어 시작점 추가
            const direction = this.detectDirection(x, y);
            if (direction) {
                this.wordPositions.push({
                    id: this.wordCounter++,
                    start_x: x,
                    start_y: y,
                    direction: direction,
                    length: this.calculateWordLength(x, y, direction)
                });
            }
        }
        
        this.renderGrid();
        this.calculateIntersections();
    }

    toggleBlackCell(x, y) {
        this.grid[y][x] = this.grid[y][x] === 'black' ? 'white' : 'black';
        this.renderGrid();
    }

    detectDirection(x, y) {
        // 가로 방향 확인
        let horizontalLength = 0;
        for (let i = x; i < this.grid[0].length && this.grid[y][i] !== 'black'; i++) {
            horizontalLength++;
        }
        
        // 세로 방향 확인
        let verticalLength = 0;
        for (let i = y; i < this.grid.length && this.grid[i][x] !== 'black'; i++) {
            verticalLength++;
        }
        
        if (horizontalLength >= 3) return 'horizontal';
        if (verticalLength >= 3) return 'vertical';
        return null;
    }

    calculateWordLength(x, y, direction) {
        let length = 0;
        if (direction === 'horizontal') {
            for (let i = x; i < this.grid[0].length && this.grid[y][i] !== 'black'; i++) {
                length++;
            }
        } else {
            for (let i = y; i < this.grid.length && this.grid[i][x] !== 'black'; i++) {
                length++;
            }
        }
        return length;
    }

    calculateIntersections() {
        this.intersections = [];
        
        for (let i = 0; i < this.wordPositions.length; i++) {
            for (let j = i + 1; j < this.wordPositions.length; j++) {
                const word1 = this.wordPositions[i];
                const word2 = this.wordPositions[j];
                
                if (word1.direction !== word2.direction) {
                    const intersection = this.findIntersection(word1, word2);
                    if (intersection) {
                        this.intersections.push({
                            position: intersection,
                            word1_id: word1.id,
                            word2_id: word2.id
                        });
                    }
                }
            }
        }
    }

    findIntersection(word1, word2) {
        if (word1.direction === 'horizontal' && word2.direction === 'vertical') {
            const horizontal = word1;
            const vertical = word2;
            
            if (horizontal.start_y >= vertical.start_y && 
                horizontal.start_y < vertical.start_y + vertical.length &&
                vertical.start_x >= horizontal.start_x && 
                vertical.start_x < horizontal.start_x + horizontal.length) {
                
                return {
                    x: vertical.start_x,
                    y: horizontal.start_y
                };
            }
        } else if (word1.direction === 'vertical' && word2.direction === 'horizontal') {
            return this.findIntersection(word2, word1);
        }
        
        return null;
    }

    updateInfo() {
        const width = parseInt(document.getElementById('gridWidth').value);
        const height = parseInt(document.getElementById('gridHeight').value);
        const wordCount = this.wordPositions.length;
        const intersectionCount = this.intersections.length;
        
        document.getElementById('currentSize').textContent = `${width}×${height}`;
        document.getElementById('currentWordCount').textContent = wordCount;
        document.getElementById('currentIntersectionCount').textContent = intersectionCount;
        
        this.renderWordPositions();
        this.renderIntersections();
    }

    renderWordPositions() {
        const container = document.getElementById('wordPositions');
        container.innerHTML = '';
        
        this.wordPositions.forEach(word => {
            const item = document.createElement('div');
            item.className = 'list-group-item d-flex justify-content-between align-items-center';
            item.innerHTML = `
                <div>
                    <strong>단어 ${word.id}</strong><br>
                    <small class="text-muted">
                        (${word.start_x}, ${word.start_y}) - ${word.direction} - ${word.length}글자
                    </small>
                </div>
                <span class="badge bg-${word.direction === 'horizontal' ? 'success' : 'info'}">
                    ${word.direction}
                </span>
            `;
            container.appendChild(item);
        });
        
        if (this.wordPositions.length === 0) {
            container.innerHTML = '<div class="text-muted">단어가 배치되지 않았습니다.</div>';
        }
    }

    renderIntersections() {
        const container = document.getElementById('intersections');
        container.innerHTML = '';
        
        this.intersections.forEach(intersection => {
            const item = document.createElement('div');
            item.className = 'list-group-item';
            item.innerHTML = `
                <strong>(${intersection.position.x}, ${intersection.position.y})</strong><br>
                <small class="text-muted">단어 ${intersection.word1_id} ↔ 단어 ${intersection.word2_id}</small>
            `;
            container.appendChild(item);
        });
        
        if (this.intersections.length === 0) {
            container.innerHTML = '<div class="text-muted">교차점이 없습니다.</div>';
        }
    }

    clearGrid() {
        if (confirm('그리드를 초기화하시겠습니까?')) {
            this.wordPositions = [];
            this.intersections = [];
            this.wordCounter = 1;
            this.createGrid();
        }
    }

    saveGrid() {
        const templateData = {
            level_id: {{ $level }},
            grid_width: parseInt(document.getElementById('gridWidth').value),
            grid_height: parseInt(document.getElementById('gridHeight').value),
            word_count: this.wordPositions.length,
            intersection_count: this.intersections.length,
            grid_pattern: this.grid,
            word_positions: this.wordPositions,
            _token: '{{ csrf_token() }}'
        };
        
        fetch('{{ route("puzzle.grids.update", $level) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(templateData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('그리드가 성공적으로 저장되었습니다!');
                window.location.href = '{{ route("puzzle.grids.show", $level) }}';
            } else {
                alert('저장 중 오류가 발생했습니다: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('저장 중 오류가 발생했습니다.');
        });
    }
}

// 페이지 로드 시 그리드 편집기 초기화
document.addEventListener('DOMContentLoaded', function() {
    new GridEditor();
});
</script>
@endsection 