<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>게시판</title>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Font Awesome -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            body {
                font-family: 'Figtree', sans-serif;
                background-color: #f8f9fa;
            }
            .hero-section {
                background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
                color: white;
                padding: 4rem 0;
                margin-bottom: 2rem;
            }
            .feature-card {
                background: white;
                border-radius: 10px;
                padding: 2rem;
                margin-bottom: 2rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                transition: transform 0.3s ease;
            }
            .feature-card:hover {
                transform: translateY(-5px);
            }
            .feature-icon {
                font-size: 2.5rem;
                margin-bottom: 1rem;
                color: #4f46e5;
            }
            
            /* 네비게이션 바 간격 조정 */
            .navbar-nav .nav-item {
                margin-right: 1rem;
                display: flex;
                align-items: center;
            }
            
            .navbar-nav .nav-item:last-child {
                margin-right: 0;
            }
            
            .navbar-text {
                padding: 0.5rem 0;
                font-weight: 500;
                margin: 0;
                line-height: 1.5;
                display: flex;
                align-items: center;
            }
            
            .nav-link {
                padding: 0.5rem 1rem !important;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
            }
            
            .nav-link:hover {
                background-color: rgba(255, 255, 255, 0.1);
                border-radius: 4px;
            }
            
            .nav-link.disabled {
                color: rgba(255, 255, 255, 0.75) !important;
                pointer-events: none;
                opacity: 1;
            }
        </style>
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand" href="/">natus 작업소</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                    @auth
                            <li class="nav-item me-4">
                                <span class="nav-link disabled">
                                    <i class="fas fa-user me-1"></i>{{ Auth::user()->name }}
                                </span>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-danger" href="{{ route('logout') }}" 
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt me-1"></i>로그아웃
                                </a>
                            </li>
                    @else
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">로그인</a>
                            </li>
                        @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">회원가입</a>
                                </li>
                        @endif
                    @endauth
                    </ul>
                </div>
                </div>
        </nav>

        <section class="hero-section">
            <div class="container text-center">
                <h1 class="display-4 mb-4">natus's Tech Lab</h1>
                <p class="lead mb-4">게시판이 있는 공간</p>
                @guest
                    <a href="{{ route('register') }}" class="btn btn-light btn-lg me-3">회원가입</a>
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg">로그인</a>
                @else
                    @if(Auth::user()->isSpecificAdmin('rainynux@gmail.com') || Auth::user()->isAdmin())
                        <a href="http://222.100.103.227/puzzle/words" class="btn btn-light btn-lg">퍼즐관리</a>
                        <a href="http://222.100.103.227/pgadmin4/" class="btn btn-light btn-lg">DB관리</a>
                    @endif
                @endguest
                                </div>
        </section>

        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">📝</div>
                        <h3>자유로운 글쓰기</h3>
                        <p>다양한 주제로 자유롭게 글을 작성하고 공유하세요.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">💬</div>
                        <h3>댓글 기능</h3>
                        <p>다른 사용자들과 의견을 나누고 소통하세요.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">🔔</div>
                        <h3>알림 기능</h3>
                        <p>댓글이 달리면 이메일이나 앱으로 알림을 받으세요.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="container text-center mt-4">
            <div class="row justify-content-center">
                @foreach($boardTypes as $type)
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('board.index', ['boardType' => $type->slug]) }}" class="btn btn-primary w-100">
                            {{ $type->name }} 
                        </a>
                    </div>
                @endforeach
            </div>
        </div>

        <footer class="bg-dark text-light mt-5 py-4">
            <div class="container text-center">
                <p class="mb-0">&copy; {{ date('Y') }} 게시판. All rights reserved.</p>
            </div>
        </footer>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- 로그아웃 폼 -->
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </body>
</html>
