@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0">
                    <i class="fas fa-chart-bar text-info"></i> 번호별 통계
                </h1>
                <a href="{{ route('lotto.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 돌아가기
                </a>
            </div>
            <hr>
        </div>
    </div>

    <div class="row">
        <!-- 이번 주 인기 번호 -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-fire"></i> 이번 주 인기 번호 TOP 20
                    </h5>
                </div>
                <div class="card-body">
                    @if($numberStats->count() > 0)
                        <div class="row">
                            @foreach($numberStats->take(20) as $stat)
                                <div class="col-3 mb-3">
                                    <div class="text-center p-2 border rounded">
                                        <div class="h5 mb-1">{{ $stat->number }}</div>
                                        <div class="text-primary fw-bold">{{ $stat->selection_count }}회</div>
                                        <small class="text-muted">선택됨</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-muted">아직 통계 데이터가 없습니다.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- 전체 기간 인기 번호 -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line"></i> 전체 기간 인기 번호 TOP 20
                    </h5>
                </div>
                <div class="card-body">
                    @if($weeklyTrends->count() > 0)
                        <div class="row">
                            @foreach($weeklyTrends->take(20) as $trend)
                                <div class="col-3 mb-3">
                                    <div class="text-center p-2 border rounded">
                                        <div class="h5 mb-1">{{ $trend->number }}</div>
                                        <div class="text-success fw-bold">{{ $trend->total_selections }}회</div>
                                        <small class="text-muted">총 선택</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-muted">아직 통계 데이터가 없습니다.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 번호별 상세 통계 -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-table"></i> 번호별 상세 통계
                    </h5>
                </div>
                <div class="card-body">
                    @if($numberStats->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>번호</th>
                                        <th>이번 주 선택 횟수</th>
                                        <th>전체 기간 선택 횟수</th>
                                        <th>똥손 랭커 선택 횟수</th>
                                        <th>인기도</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($numberStats->take(30) as $stat)
                                        @php
                                            $totalSelections = $weeklyTrends->where('number', $stat->number)->first()->total_selections ?? 0;
                                            $popularity = $stat->selection_count > 10 ? '🔥' : ($stat->selection_count > 5 ? '⚡' : '📊');
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary fs-6">{{ $stat->number }}</span>
                                            </td>
                                            <td>
                                                <strong>{{ $stat->selection_count }}</strong>회
                                            </td>
                                            <td>
                                                <strong>{{ $totalSelections }}</strong>회
                                            </td>
                                            <td>
                                                {{ $stat->ddongsun_rankers_count }}회
                                            </td>
                                            <td>
                                                <span class="fs-5">{{ $popularity }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-muted">아직 통계 데이터가 없습니다.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 통계 요약 -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> 통계 요약
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="h4 text-primary">{{ $numberStats->sum('selection_count') }}</div>
                            <small class="text-muted">이번 주 총 선택 횟수</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="h4 text-success">{{ $weeklyTrends->sum('total_selections') }}</div>
                            <small class="text-muted">전체 기간 총 선택 횟수</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="h4 text-info">{{ $numberStats->where('selection_count', '>', 5)->count() }}</div>
                            <small class="text-muted">인기 번호 (5회 이상)</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="h4 text-warning">{{ $numberStats->where('selection_count', '>', 10)->count() }}</div>
                            <small class="text-muted">초인기 번호 (10회 이상)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.075);
}

.badge {
    font-size: 0.75em;
}

.border.rounded {
    transition: all 0.2s ease;
}

.border.rounded:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}
</style>
@endsection
