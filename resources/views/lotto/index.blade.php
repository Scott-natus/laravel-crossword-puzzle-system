@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0">
                    <i class="fas fa-trophy text-warning"></i> 똥손 로또 분석 시스템
                </h1>
                <a href="{{ route('lotto.upload') }}" class="btn btn-primary">
                    <i class="fas fa-upload"></i> 로또 용지 업로드
                </a>
            </div>
            <hr>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- 사용자 정보 -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user"></i> 내 정보</h5>
                </div>
                <div class="card-body">
                    @if($user)
                        <div class="text-center mb-3">
                            <div class="h4 mb-2">{{ $user->name }}</div>
                            <span class="badge bg-{{ $user->current_level == '플래티넘' ? 'warning' : ($user->current_level == '골드' ? 'warning' : ($user->current_level == '실버' ? 'secondary' : 'dark')) }} fs-6">
                                {{ $user->current_level }}
                            </span>
                        </div>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="h5 text-primary">{{ number_format($user->total_ddongsun_power) }}</div>
                                <small class="text-muted">총 똥손력</small>
                            </div>
                            <div class="col-6">
                                <div class="h5 text-success">{{ $tickets->count() }}</div>
                                <small class="text-muted">업로드 용지</small>
                            </div>
                        </div>
                    @else
                        <p class="text-center text-muted">로그인이 필요합니다.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- 주간 랭킹 -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-medal"></i> 이번 주 똥손 랭킹</h5>
                </div>
                <div class="card-body">
                    @if($weeklyRankings->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
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
                                        <tr class="{{ $ranking->user_id == ($user->id ?? 0) ? 'table-warning' : '' }}">
                                            <td>
                                                @if($index < 3)
                                                    <i class="fas fa-medal text-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'danger') }}"></i>
                                                @endif
                                                {{ $index + 1 }}
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
                        <div class="text-end">
                            <a href="{{ route('lotto.rankings') }}" class="btn btn-sm btn-outline-warning">전체 랭킹 보기</a>
                        </div>
                    @else
                        <p class="text-center text-muted">아직 랭킹 데이터가 없습니다.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- 인기 번호 -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> 이번 주 인기 번호</h5>
                </div>
                <div class="card-body">
                    @if($numberStats->count() > 0)
                        <div class="row">
                            @foreach($numberStats->take(10) as $stat)
                                <div class="col-2 mb-2">
                                    <div class="text-center">
                                        <div class="h6 mb-1">{{ $stat->number }}</div>
                                        <small class="text-muted">{{ $stat->selection_count }}회</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="text-end mt-3">
                            <a href="{{ route('lotto.statistics') }}" class="btn btn-sm btn-outline-info">상세 통계 보기</a>
                        </div>
                    @else
                        <p class="text-center text-muted">아직 통계 데이터가 없습니다.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- 최근 로또 결과 -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> 최근 로또 결과</h5>
                </div>
                <div class="card-body">
                    @if($recentResults->count() > 0)
                        @foreach($recentResults as $result)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>{{ $result->draw_number }}회</strong>
                                    <small class="text-muted">{{ $result->draw_date->format('Y-m-d') }}</small>
                                </div>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($result->numbers as $number)
                                        <span class="badge bg-primary">{{ $number }}</span>
                                    @endforeach
                                    <span class="badge bg-warning text-dark">+{{ $result->bonus_number }}</span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-center text-muted">로또 결과 데이터가 없습니다.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 내 로또 티켓 -->
    @if($user && $tickets->count() > 0)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-ticket-alt"></i> 내 로또 티켓</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>업로드 날짜</th>
                                        <th>선택 번호</th>
                                        <th>똥손력</th>
                                        <th>액션</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tickets as $ticket)
                                        <tr>
                                            <td>{{ $ticket->upload_date->format('Y-m-d H:i') }}</td>
                                            <td>
                                                @foreach($ticket->numbers as $number)
                                                    <span class="badge bg-primary me-1">{{ $number }}</span>
                                                @endforeach
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $ticket->ddongsun_power >= 80 ? 'danger' : ($ticket->ddongsun_power >= 60 ? 'warning' : 'success') }}">
                                                    {{ $ticket->ddongsun_power }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('lotto.show', $ticket->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> 보기
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center">
                            {{ $tickets->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.badge {
    font-size: 0.75em;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.075);
}
</style>
@endsection
