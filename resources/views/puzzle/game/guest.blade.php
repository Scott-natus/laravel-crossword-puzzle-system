@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header text-center">
                    <h4 class="mb-0">
                        <i class="fas fa-puzzle-piece me-2"></i>크로스워드 퍼즐 게임
                    </h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-lock fa-3x text-muted mb-3"></i>
                        <h5>로그인이 필요한 게임입니다</h5>
                        <p class="text-muted">
                            크로스워드 퍼즐 게임을 즐기려면 로그인이 필요합니다.<br>
                            계정이 없으시다면 회원가입을, 계정이 있으시다면 로그인을 해주세요.
                        </p>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ route('register') }}?redirect={{ urlencode(request()->url()) }}" 
                           class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>회원가입
                        </a>
                        <a href="{{ route('login') }}?redirect={{ urlencode(request()->url()) }}" 
                           class="btn btn-success btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>로그인
                        </a>
                    </div>
                    
                    <div class="mt-4">
                        <a href="{{ route('main') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>메인으로 돌아가기
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.card {
    border: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-radius: 15px;
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0 !important;
    border: none;
}

.btn-lg {
    padding: 12px 30px;
    font-size: 1.1rem;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.btn-lg:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.fa-3x {
    color: #6c757d;
    opacity: 0.7;
}
</style>
@endpush 