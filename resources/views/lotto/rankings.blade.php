@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0">
                    <i class="fas fa-medal text-warning"></i> 똥손 랭킹
                </h1>
                <a href="{{ route('lotto.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 돌아가기
                </a>
            </div>
            <hr>
        </div>
    </div>

    <div class="row">
        <!-- 주간 랭킹 -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-week"></i> 이번 주 랭킹
                    </h5>
                </div>
                <div class="card-body">
                    @if($weeklyRankings->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>순위</th>
                                        <th>사용자</th>
                                        <th>똥손력</th>
                                        <th>레벨</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($weeklyRankings as $index => $ranking)
                                        <tr class="{{ $ranking->user_id == Auth::id() ? 'table-warning' : '' }}">
                                            <td>
                                                @if($index < 3)
                                                    <i class="fas fa-medal text-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'danger') }}"></i>
                                                @endif
                                                {{ $weeklyRankings->firstItem() + $index }}
                                            </td>
                                            <td>{{ $ranking->user->name }}</td>
                                            <td><strong>{{ $ranking->ddongsun_power }}</strong></td>
                                            <td>
                                                <span class="badge bg-{{ $ranking->user->current_level == '플래티넘' ? 'warning' : ($ranking->user->current_level == '골드' ? 'warning' : ($ranking->user->current_level == '실버' ? 'secondary' : 'dark')) }}">
                                                    {{ $ranking->user->current_level }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center">
                            {{ $weeklyRankings->links() }}
                        </div>
                    @else
                        <p class="text-center text-muted">아직 랭킹 데이터가 없습니다.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- 전체 랭킹 -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy"></i> 전체 랭킹
                    </h5>
                </div>
                <div class="card-body">
                    @if($allTimeRankings->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>순위</th>
                                        <th>사용자</th>
                                        <th>총 똥손력</th>
                                        <th>레벨</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allTimeRankings as $index => $user)
                                        <tr class="{{ $user->id == Auth::id() ? 'table-warning' : '' }}">
                                            <td>
                                                @if($index < 3)
                                                    <i class="fas fa-crown text-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'danger') }}"></i>
                                                @endif
                                                {{ $index + 1 }}
                                            </td>
                                            <td>{{ $user->name }}</td>
                                            <td><strong>{{ number_format($user->total_ddongsun_power) }}</strong></td>
                                            <td>
                                                <span class="badge bg-{{ $user->current_level == '플래티넘' ? 'warning' : ($user->current_level == '골드' ? 'warning' : ($user->current_level == '실버' ? 'secondary' : 'dark')) }}">
                                                    {{ $user->current_level }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-muted">아직 랭킹 데이터가 없습니다.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 레벨별 통계 -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie"></i> 레벨별 통계
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="h4 text-dark">{{ $allTimeRankings->where('current_level', '브론즈')->count() }}</div>
                            <span class="badge bg-dark fs-6">브론즈</span>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="h4 text-secondary">{{ $allTimeRankings->where('current_level', '실버')->count() }}</div>
                            <span class="badge bg-secondary fs-6">실버</span>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="h4 text-warning">{{ $allTimeRankings->where('current_level', '골드')->count() }}</div>
                            <span class="badge bg-warning fs-6">골드</span>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="h4 text-warning">{{ $allTimeRankings->where('current_level', '플래티넘')->count() }}</div>
                            <span class="badge bg-warning fs-6">플래티넘</span>
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
</style>
@endsection
