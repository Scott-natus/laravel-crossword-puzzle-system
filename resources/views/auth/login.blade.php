@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">로그인</div>

                <div class="card-body">
                    <!-- SNS 로그인 버튼들 -->
                    {{-- 
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="text-center mb-3">
                                <h6 class="text-muted">소셜 계정으로 로그인</h6>
                            </div>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('auth.google') }}" class="btn btn-outline-danger">
                                    <i class="fab fa-google"></i> Google
                                </a>
                                <a href="{{ route('auth.kakao') }}" class="btn btn-warning">
                                    <i class="fas fa-comment"></i> Kakao
                                </a>
                                <a href="{{ route('auth.naver') }}" class="btn btn-success">
                                    <i class="fas fa-n"></i> Naver
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <hr>
                            <div class="text-center">
                                <span class="text-muted">또는</span>
                            </div>
                        </div>
                    </div>
                    --}}

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        @if(request()->get('redirect'))
                            <input type="hidden" name="redirect" value="{{ request()->get('redirect') }}">
                        @endif

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">이메일 주소</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', request()->cookie('remember_email')) }}" required autocomplete="email">

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">비밀번호</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" autofocus>

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 offset-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember', request()->cookie('remember_me') ? 'checked' : '') }}>

                                    <label class="form-check-label" for="remember">
                                        로그인 정보 기억하기
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary me-2">
                                    로그인
                                </button>

                                @if (Route::has('password.request'))
                                    <a class="btn btn-outline-secondary" href="{{ route('password.request') }}">
                                        비밀번호 찾기
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 로그인 정보 기억하기가 체크되어 있으면 비밀번호 필드에 포커스
    const rememberMe = document.getElementById('remember');
    const passwordField = document.getElementById('password');
    
    if (rememberMe.checked) {
        passwordField.focus();
    } else {
        // 체크되지 않았으면 이메일 필드에 포커스
        document.getElementById('email').focus();
    }
});
</script>
@endsection
