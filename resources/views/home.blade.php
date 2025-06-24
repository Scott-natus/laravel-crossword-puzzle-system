@extends('layouts.app')

@section('content')
<div class="welcome-container fade-in">
    <div class="welcome-icon">🎉</div>
    
    <h1 class="welcome-title">환영합니다!</h1>
    
    <p class="welcome-subtitle">
        @if(session('welcome_message'))
            {{ session('welcome_message') }}
        @else
            natus 작업소에 오신 것을 환영합니다!
        @endif
    </p>
    
    <div class="user-name">
        <i class="fas fa-user me-2"></i>{{ Auth::user()->name }}님
    </div>
    
    <div class="redirect-message">
        잠시 후 메인 페이지로 이동합니다...
    </div>
    
    <div class="countdown" id="countdown">5</div>
    
    <div class="loading-spinner"></div>
    
    <a href="/" class="manual-link">
        <i class="fas fa-home me-2"></i>지금 바로 메인으로 이동
    </a>
</div>

<!-- 배경 플로팅 요소들 -->
<div class="floating-elements">
    <div class="floating-element" style="top: 10%; left: 10%; animation-delay: 0s;">🎉</div>
    <div class="floating-element" style="top: 20%; right: 15%; animation-delay: 1s;">✨</div>
    <div class="floating-element" style="top: 60%; left: 5%; animation-delay: 2s;">🎊</div>
    <div class="floating-element" style="top: 80%; right: 10%; animation-delay: 3s;">🌟</div>
    <div class="floating-element" style="top: 40%; left: 80%; animation-delay: 4s;">🎈</div>
    <div class="floating-element" style="top: 70%; right: 80%; animation-delay: 5s;">💫</div>
</div>
@endsection

@push('styles')
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .welcome-container {
        text-align: center;
        color: white;
        max-width: 600px;
        padding: 2rem;
    }
    
    .welcome-icon {
        font-size: 8rem;
        margin-bottom: 2rem;
        animation: bounce 2s infinite;
    }
    
    .welcome-title {
        font-size: 3rem;
        font-weight: bold;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }
    
    .welcome-subtitle {
        font-size: 1.5rem;
        margin-bottom: 2rem;
        opacity: 0.9;
    }
    
    .user-name {
        font-size: 2rem;
        font-weight: bold;
        color: #ffd700;
        margin-bottom: 2rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }
    
    .redirect-message {
        font-size: 1.2rem;
        margin-bottom: 2rem;
        opacity: 0.8;
    }
    
    .countdown {
        font-size: 2rem;
        font-weight: bold;
        color: #ffd700;
        margin-bottom: 2rem;
    }
    
    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid rgba(255,255,255,0.3);
        border-top: 5px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 2rem;
    }
    
    .manual-link {
        display: inline-block;
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 1rem 2rem;
        border-radius: 50px;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 2px solid rgba(255,255,255,0.3);
    }
    
    .manual-link:hover {
        background: rgba(255,255,255,0.3);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-20px);
        }
        60% {
            transform: translateY(-10px);
        }
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .fade-in {
        animation: fadeIn 1s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .floating-elements {
        position: absolute;
        width: 100%;
        height: 100%;
        overflow: hidden;
        pointer-events: none;
    }
    
    .floating-element {
        position: absolute;
        font-size: 2rem;
        opacity: 0.1;
        animation: float 6s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }
</style>
@endpush

@push('scripts')
<script>
    // 카운트다운 및 자동 리다이렉트
    let countdown = 5;
    const countdownElement = document.getElementById('countdown');
    
    const timer = setInterval(function() {
        countdown--;
        countdownElement.textContent = countdown;
        
        if (countdown <= 0) {
            clearInterval(timer);
            window.location.href = '/';
        }
    }, 1000);
</script>
@endpush
