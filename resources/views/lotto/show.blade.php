@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0">
                    <i class="fas fa-ticket-alt text-primary"></i> 로또 티켓 상세
                </h1>
                <a href="{{ route('lotto.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 돌아가기
                </a>
            </div>
            <hr>
        </div>
    </div>

    <div class="row">
        <!-- 티켓 정보 -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> 티켓 정보
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <strong>작성자:</strong>
                        </div>
                        <div class="col-6">
                            {{ $ticket->user->name }}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <strong>업로드 날짜:</strong>
                        </div>
                        <div class="col-6">
                            {{ $ticket->upload_date->format('Y-m-d H:i') }}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <strong>똥손력:</strong>
                        </div>
                        <div class="col-6">
                            <span class="badge bg-{{ $ticket->ddongsun_power >= 80 ? 'danger' : ($ticket->ddongsun_power >= 60 ? 'warning' : 'success') }} fs-6">
                                {{ $ticket->ddongsun_power }}
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <strong>작성자 레벨:</strong>
                        </div>
                        <div class="col-6">
                            <span class="badge bg-{{ $ticket->user->current_level == '플래티넘' ? 'warning' : ($ticket->user->current_level == '골드' ? 'warning' : ($ticket->user->current_level == '실버' ? 'secondary' : 'dark')) }}">
                                {{ $ticket->user->current_level }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 선택된 번호 -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list-ol"></i> 선택된 번호
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        @foreach($ticket->numbers as $number)
                            <span class="badge bg-primary fs-4 me-2 mb-2">{{ $number }}</span>
                        @endforeach
                    </div>
                    
                    <!-- 똥손력 분석 -->
                    <div class="mt-4">
                        <h6 class="text-muted">똥손력 분석:</h6>
                        <ul class="list-unstyled">
                            @php
                                $numbers = $ticket->numbers;
                                sort($numbers);
                                $consecutive = 0;
                                $sameEndDigits = 0;
                                
                                // 연속된 번호 체크
                                for ($i = 0; $i < count($numbers) - 1; $i++) {
                                    if ($numbers[$i + 1] - $numbers[$i] == 1) {
                                        $consecutive++;
                                    }
                                }
                                
                                // 끝자리 같은 번호 체크
                                $lastDigits = array_map(function($num) {
                                    return $num % 10;
                                }, $numbers);
                                $digitCounts = array_count_values($lastDigits);
                                foreach ($digitCounts as $count) {
                                    if ($count >= 2) {
                                        $sameEndDigits += ($count - 1);
                                    }
                                }
                            @endphp
                            
                            @if($consecutive > 0)
                                <li class="text-warning">
                                    <i class="fas fa-arrow-up"></i> 연속된 번호: {{ $consecutive }}쌍 (+{{ $consecutive * 10 }}점)
                                </li>
                            @endif
                            
                            @if($sameEndDigits > 0)
                                <li class="text-info">
                                    <i class="fas fa-equals"></i> 끝자리 같은 번호: {{ $sameEndDigits }}쌍 (+{{ $sameEndDigits * 15 }}점)
                                </li>
                            @endif
                            
                            <li class="text-success">
                                <i class="fas fa-star"></i> 기본 똥손력: +{{ $ticket->ddongsun_power - ($consecutive * 10) - ($sameEndDigits * 15) }}점
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 업로드된 이미지 -->
    @if($ticket->image_path)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-image"></i> 업로드된 로또 용지
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="{{ asset('storage/' . $ticket->image_path) }}" 
                             alt="로또 용지" 
                             class="img-fluid rounded" 
                             style="max-height: 500px;">
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- 똥손력 등급 설명 -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-question-circle"></i> 똥손력 등급 설명
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="h4 text-success">1-40</div>
                            <span class="badge bg-success fs-6">초보 똥손</span>
                            <p class="small text-muted mt-2">아직 똥손의 길이 멀었습니다.</p>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="h4 text-warning">41-60</div>
                            <span class="badge bg-warning fs-6">중급 똥손</span>
                            <p class="small text-muted mt-2">똥손의 기운이 느껴집니다.</p>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="h4 text-danger">61-80</div>
                            <span class="badge bg-danger fs-6">고급 똥손</span>
                            <p class="small text-muted mt-2">강력한 똥손의 기운!</p>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="h4 text-danger">81-100</div>
                            <span class="badge bg-danger fs-6">전설의 똥손</span>
                            <p class="small text-muted mt-2">전설적인 똥손력!</p>
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

.badge {
    font-size: 0.75em;
}

.img-fluid {
    max-width: 100%;
    height: auto;
}

.list-unstyled li {
    margin-bottom: 0.5rem;
}
</style>
@endsection
