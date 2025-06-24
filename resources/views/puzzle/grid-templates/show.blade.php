@extends('layouts.app')

@section('title', '그리드 템플릿 상세')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title">{{ $template->template_name }}</h4>
                            <p class="card-text">그리드 템플릿 상세 정보</p>
                        </div>
                        <div>
                            <a href="{{ route('puzzle.grid-templates.index') }}" class="btn btn-secondary">
                                <i class="fas fa-list"></i> 목록으로
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- 기본 정보 -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>기본 정보</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th width="30%">템플릿 ID:</th>
                                    <td>{{ $template->id }}</td>
                                </tr>
                                <tr>
                                    <th>레벨:</th>
                                    <td>
                                        <span class="badge bg-info">레벨 {{ $template->level }}</span>
                                        {{ $template->level_name }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>그리드 크기:</th>
                                    <td>{{ $template->grid_width }}×{{ $template->grid_height }}</td>
                                </tr>
                                <tr>
                                    <th>단어 수:</th>
                                    <td><span class="badge bg-success">{{ $template->word_count }}</span></td>
                                </tr>
                                <tr>
                                    <th>교차점 수:</th>
                                    <td><span class="badge bg-warning">{{ $template->intersection_count }}</span></td>
                                </tr>
                                <tr>
                                    <th>난이도:</th>
                                    <td>
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $template->difficulty_rating)
                                                <i class="fas fa-star text-warning"></i>
                                            @else
                                                <i class="far fa-star text-muted"></i>
                                            @endif
                                        @endfor
                                        ({{ $template->difficulty_rating }}/5)
                                    </td>
                                </tr>
                                <tr>
                                    <th>카테고리:</th>
                                    <td>
                                        @if($template->category === 'custom')
                                            <span class="badge bg-primary">사용자 정의</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $template->category }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>생성일:</th>
                                    <td>
                                        @if(is_string($template->created_at))
                                            {{ \Carbon\Carbon::parse($template->created_at)->format('Y-m-d H:i:s') }}
                                        @else
                                            {{ $template->created_at->format('Y-m-d H:i:s') }}
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>그리드 미리보기</h6>
                            <div id="gridPreview" class="border p-3 bg-light" style="display: inline-block;">
                                <!-- 그리드가 여기에 렌더링됩니다 -->
                            </div>
                        </div>
                    </div>

                    <!-- 단어 위치 정보 -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6>단어 위치 정보</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>방향</th>
                                            <th>시작 위치</th>
                                            <th>끝 위치</th>
                                            <th>길이</th>
                                            <th>좌표</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($wordPositions as $position)
                                            <tr>
                                                <td>{{ $position['id'] }}</td>
                                                <td>
                                                    @if($position['direction'] === 'horizontal')
                                                        <span class="badge bg-primary">가로</span>
                                                    @else
                                                        <span class="badge bg-success">세로</span>
                                                    @endif
                                                </td>
                                                <td>({{ $position['start_x'] }}, {{ $position['start_y'] }})</td>
                                                <td>({{ $position['end_x'] }}, {{ $position['end_y'] }})</td>
                                                <td>{{ $position['length'] }}</td>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ $position['start_x'] }},{{ $position['start_y'] }} → {{ $position['end_x'] }},{{ $position['end_y'] }}
                                                    </small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- 교차점 정보 -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6>교차점 정보</h6>
                            <div id="intersectionInfo">
                                <!-- 교차점 정보가 여기에 표시됩니다 -->
                            </div>
                        </div>
                    </div>

                    <!-- 그리드 패턴 데이터 -->
                    <div class="row">
                        <div class="col-12">
                            <h6>그리드 패턴 데이터</h6>
                            <div class="card">
                                <div class="card-body">
                                    <pre class="mb-0"><code>{{ json_encode($gridPattern, JSON_PRETTY_PRINT) }}</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 그리드 렌더링
function renderGrid() {
    const gridPattern = @json($gridPattern);
    const wordPositions = @json($wordPositions);
    const preview = document.getElementById('gridPreview');
    
    preview.innerHTML = '';
    
    for (let i = 0; i < gridPattern.length; i++) {
        const row = document.createElement('div');
        row.className = 'd-flex';
        
        for (let j = 0; j < gridPattern[i].length; j++) {
            const cell = document.createElement('div');
            cell.className = 'border d-flex align-items-center justify-content-center';
            cell.style.width = '30px';
            cell.style.height = '30px';
            cell.style.fontSize = '12px';
            cell.style.backgroundColor = gridPattern[i][j] === 2 ? 'black' : 'white';
            cell.style.color = gridPattern[i][j] === 2 ? 'white' : 'black';
            
            // 단어 번호 표시
            const wordNumber = getWordNumber(i, j, wordPositions);
            if (wordNumber) {
                cell.innerHTML = `<small>${wordNumber}</small>`;
            } else {
                cell.textContent = gridPattern[i][j] === 2 ? '■' : '□';
            }
            
            row.appendChild(cell);
        }
        
        preview.appendChild(row);
    }
}

// 단어 번호 찾기
function getWordNumber(row, col, wordPositions) {
    for (let pos of wordPositions) {
        if (pos.direction === 'horizontal') {
            if (pos.start_y === row && col >= pos.start_x && col <= pos.end_x) {
                if (col === pos.start_x) {
                    return pos.id;
                }
            }
        } else {
            if (pos.start_x === col && row >= pos.start_y && row <= pos.end_y) {
                if (row === pos.start_y) {
                    return pos.id;
                }
            }
        }
    }
    return null;
}

// 교차점 정보 표시
function showIntersections() {
    const wordPositions = @json($wordPositions);
    const intersectionInfo = document.getElementById('intersectionInfo');
    
    let intersections = [];
    
    for (let i = 0; i < wordPositions.length; i++) {
        for (let j = i + 1; j < wordPositions.length; j++) {
            const word1 = wordPositions[i];
            const word2 = wordPositions[j];
            
            if (word1.direction !== word2.direction) {
                const intersection = findIntersection(word1, word2);
                if (intersection) {
                    intersections.push({
                        position: intersection,
                        word1: word1,
                        word2: word2
                    });
                }
            }
        }
    }
    
    if (intersections.length > 0) {
        let html = '<div class="table-responsive"><table class="table table-sm table-striped">';
        html += '<thead><tr><th>#</th><th>위치</th><th>가로 단어</th><th>세로 단어</th></tr></thead><tbody>';
        
        intersections.forEach((intersection, index) => {
            const horizontal = intersection.word1.direction === 'horizontal' ? intersection.word1 : intersection.word2;
            const vertical = intersection.word1.direction === 'vertical' ? intersection.word1 : intersection.word2;
            
            html += `<tr>
                <td>${index + 1}</td>
                <td>(${intersection.position.x}, ${intersection.position.y})</td>
                <td>단어 ${horizontal.id} (${horizontal.start_x},${horizontal.start_y} → ${horizontal.end_x},${horizontal.end_y})</td>
                <td>단어 ${vertical.id} (${vertical.start_x},${vertical.start_y} → ${vertical.end_x},${vertical.end_y})</td>
            </tr>`;
        });
        
        html += '</tbody></table></div>';
        intersectionInfo.innerHTML = html;
    } else {
        intersectionInfo.innerHTML = '<div class="alert alert-info">교차점이 없습니다.</div>';
    }
}

// 교차점 찾기
function findIntersection(word1, word2) {
    if (word1.direction === 'horizontal' && word2.direction === 'vertical') {
        const horizontal = word1;
        const vertical = word2;
        
        if (horizontal.start_y >= vertical.start_y && horizontal.start_y <= vertical.end_y &&
            vertical.start_x >= horizontal.start_x && vertical.start_x <= horizontal.end_x) {
            return {
                x: vertical.start_x,
                y: horizontal.start_y
            };
        }
    } else if (word1.direction === 'vertical' && word2.direction === 'horizontal') {
        const vertical = word1;
        const horizontal = word2;
        
        if (horizontal.start_y >= vertical.start_y && horizontal.start_y <= vertical.end_y &&
            vertical.start_x >= horizontal.start_x && vertical.start_x <= horizontal.end_x) {
            return {
                x: vertical.start_x,
                y: horizontal.start_y
            };
        }
    }
    
    return null;
}

// 초기화
document.addEventListener('DOMContentLoaded', () => {
    renderGrid();
    showIntersections();
});
</script>

<style>
pre {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 1rem;
    font-size: 0.875rem;
}
</style>
@endpush 