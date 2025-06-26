@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $template->template_name }}</h4>
                    <a href="{{ route('puzzle.grids.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> 목록으로
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <!-- 템플릿 정보 -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5><i class="fas fa-info-circle"></i> 템플릿 정보</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th width="120">ID:</th>
                                    <td>{{ $template->id }}</td>
                                </tr>
                                <tr>
                                    <th>레벨:</th>
                                    <td><span class="badge bg-primary">Level {{ $template->level_id }}</span></td>
                                </tr>
                                <tr>
                                    <th>크기:</th>
                                    <td>{{ $template->grid_width }}×{{ $template->grid_height }}</td>
                                </tr>
                                <tr>
                                    <th>단어 수:</th>
                                    <td>{{ $template->word_count }}</td>
                                </tr>
                                <tr>
                                    <th>교차점:</th>
                                    <td>{{ $template->intersection_count }}</td>
                                </tr>
                                <tr>
                                    <th>난이도:</th>
                                    <td>
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star {{ $i <= $template->difficulty_rating ? 'text-warning' : 'text-muted' }}"></i>
                                        @endfor
                                    </td>
                                </tr>
                                <tr>
                                    <th>카테고리:</th>
                                    <td><span class="badge bg-{{ $template->category === 'beginner' ? 'success' : 'info' }}">{{ ucfirst($template->category) }}</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-link"></i> 교차점 정보</h5>
                            @if(count($intersections) > 0)
                                <div class="list-group list-group-flush">
                                    @foreach($intersections as $intersection)
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>
                                                <strong>({{ $intersection['position']['x'] }}, {{ $intersection['position']['y'] }})</strong>
                                                - 단어 {{ $intersection['word1_id'] }} ↔ 단어 {{ $intersection['word2_id'] }}
                                            </span>
                                            <span class="badge bg-primary rounded-pill">
                                                {{ ucfirst($intersection['word1_direction']) }} ↔ {{ ucfirst($intersection['word2_direction']) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted">교차점이 없습니다.</p>
                            @endif
                        </div>
                    </div>

                    <!-- 그리드 시각화 -->
                    <div class="row">
                        <div class="col-md-8">
                            <h5><i class="fas fa-th"></i> 그리드 패턴</h5>
                            <div class="grid-container mb-3">
                                @for($y = 0; $y < count($gridPattern); $y++)
                                    <div class="grid-row">
                                        @for($x = 0; $x < count($gridPattern[$y]); $x++)
                                            @php
                                                $cellValue = $gridPattern[$y][$x];
                                                $cellClass = $cellValue == 1 ? 'grid-cell' : 'grid-cell-black';
                                                $cellNumber = '';
                                                
                                                // 번호 표시 확인
                                                foreach($wordPositions as $pos) {
                                                    if ($pos['start_x'] == $x && $pos['start_y'] == $y) {
                                                        $cellNumber = $pos['clue_number'];
                                                        break;
                                                    }
                                                }
                                            @endphp
                                            <div class="{{ $cellClass }}">
                                                @if($cellValue == 1)
                                                    @if($cellNumber)
                                                        <span class="cell-number">{{ $cellNumber }}</span>
                                                    @endif
                                                @endif
                                            </div>
                                        @endfor
                                    </div>
                                @endfor
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <h5><i class="fas fa-list"></i> 단어 위치 정보</h5>
                            <div class="list-group">
                                @foreach($wordPositions as $position)
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">단어 {{ $position['id'] }}</h6>
                                                <small class="text-muted">
                                                    ({{ $position['start_x'] }}, {{ $position['start_y'] }}) → 
                                                    ({{ $position['end_x'] }}, {{ $position['end_y'] }})
                                                </small>
                                            </div>
                                            <span class="badge bg-{{ $position['direction'] === 'horizontal' ? 'success' : 'info' }}">
                                                {{ ucfirst($position['direction']) }}
                                            </span>
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                길이: {{ $position['length'] }}글자 | 
                                                번호: {{ $position['clue_number'] }}
                                            </small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- 설명 -->
                    @if($template->description)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5><i class="fas fa-file-alt"></i> 설명</h5>
                                <div class="alert alert-light">
                                    {{ $template->description }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.grid-container {
    display: inline-block;
    border: 2px solid #333;
    background: #fff;
}

.grid-row {
    display: flex;
}

.grid-cell {
    width: 40px;
    height: 40px;
    border: 1px solid #ccc;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    background: #fff;
}

.grid-cell-black {
    width: 40px;
    height: 40px;
    background: #333;
    border: 1px solid #333;
}

.cell-number {
    position: absolute;
    top: 2px;
    left: 2px;
    font-size: 10px;
    font-weight: bold;
    color: #666;
}

@media (max-width: 768px) {
    .grid-cell, .grid-cell-black {
        width: 30px;
        height: 30px;
    }
    
    .cell-number {
        font-size: 8px;
    }
}
</style>
@endsection 