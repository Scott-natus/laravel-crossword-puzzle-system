<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>게시판</title>
        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
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
            
            /* 게시판과 동일한 히어로 배너 스타일 */
            .hero-banner {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                position: relative;
                padding: 4rem 0;
                color: white;
                overflow: hidden;
                margin-bottom: 2rem;
            }
            .hero-banner::before {
                content: '';
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                background-image: 
                    radial-gradient(circle at 20% 20%, rgba(255,255,255,0.1) 1px, transparent 1px),
                    radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 1px, transparent 1px),
                    radial-gradient(circle at 40% 40%, rgba(255,255,255,0.05) 1px, transparent 1px);
                background-size: 50px 50px, 60px 60px, 40px 40px;
            }
            .tech-icons { 
                position: absolute; 
                top: 0; 
                left: 0; 
                right: 0; 
                bottom: 0; 
                overflow: hidden; 
            }
            .tech-icon { 
                position: absolute; 
                font-size: 2rem; 
                opacity: 0.3; 
                animation: float 6s ease-in-out infinite; 
                color: #fff; 
            }
            .tech-icon:nth-child(1) { top: 10%; left: 10%; animation-delay: 0s; }
            .tech-icon:nth-child(2) { top: 20%; right: 15%; animation-delay: 1s; }
            .tech-icon:nth-child(3) { top: 60%; left: 20%; animation-delay: 2s; }
            .tech-icon:nth-child(4) { bottom: 20%; right: 20%; animation-delay: 3s; }
            .tech-icon:nth-child(5) { bottom: 10%; left: 50%; animation-delay: 4s; }
            .tech-icon:nth-child(6) { top: 30%; left: 60%; animation-delay: 5s; }
            @keyframes float { 
                0%, 100% { transform: translateY(0px) rotate(0deg); } 
                50% { transform: translateY(-20px) rotate(5deg); } 
            }
            .hero-content { 
                position: relative; 
                z-index: 2; 
                text-align: center; 
            }
            .hero-title { 
                font-size: 3rem; 
                font-weight: 700; 
                margin-bottom: 1rem; 
                text-shadow: 2px 2px 4px rgba(0,0,0,0.3); 
            }
            .hero-subtitle { 
                font-size: 1.2rem; 
                opacity: 0.9; 
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
            
            @media (max-width: 768px) {
                .hero-title { font-size: 2rem; }
            }
        </style>
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand" href="/main">natus 작업소</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <!-- Board Navigation Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdownBoard" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                바로가기
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdownBoard">
                                <li><h6 class="dropdown-header">게시판</h6></li>
                                @if(isset($boardTypes) && $boardTypes->count() > 0)
                                    @foreach ($boardTypes as $boardType)
                                        <li>
                                            <a class="dropdown-item" href="{{ route('board.index', ['boardType' => $boardType->slug]) }}">
                                                {{ $boardType->name }}
                                                @if($boardType->requires_auth)
                                                    <i class="fas fa-lock text-warning ms-1" title="로그인 필요"></i>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                @else
                                    <li><span class="dropdown-item-text">게시판이 없습니다</span></li>
                                @endif

                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('puzzle-game.index') }}">퍼즐게임</a></li>

                                @auth
                                    @if(Auth::user()->is_admin)
                                        <li><hr class="dropdown-divider"></li>
                                        <li><h6 class="dropdown-header">관리자메뉴</h6></li>
                                        <li><a class="dropdown-item" href="{{ route('puzzle.words.index') }}">퍼즐관리</a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.users.index') }}">회원관리</a></li>
                                    @endif
                                @endauth
                            </ul>
                        </li>
                    </ul>
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

        <section class="hero-banner">
            <div class="tech-icons">
                <div class="tech-icon" title="PHP">🐘</div>
                <div class="tech-icon" title="PostgreSQL">🐘</div>
                <div class="tech-icon" title="Laravel">🔥</div>
                <div class="tech-icon" title="Composer">🎼</div>
                <div class="tech-icon" title="Apache2">🪶</div>
                <div class="tech-icon" title="GitLab">🪶</div>
                <div class="tech-icon" title="Ubuntu">🐧</div>
            </div>
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title">Natus Tech Lab</h1>
                    <p class="hero-subtitle">CURSOR AI 로 만들어진 Tech Lab</p>
                    <div class="d-flex justify-content-center">
                        <span class="badge bg-light text-dark me-2">Ubuntu</span>
                        <span class="badge bg-light text-dark me-2">PHP</span>
                        <span class="badge bg-light text-dark me-2">PostgreSQL</span>
                        <span class="badge bg-light text-dark me-2">Apache2</span>
                        <span class="badge bg-light text-dark me-2">Laravel</span>
                        <span class="badge bg-light text-dark me-2">Composer</span>
                        <span class="badge bg-light text-dark me-2">GitHub</span>
                        <span class="badge bg-light text-dark me-2">Gemini</span>
                        <span class="badge bg-light text-dark">CURSOR</span>
                    </div>
                    @guest
                        <div class="mt-4">
                            <a href="{{ route('register') }}" class="btn btn-light btn-lg me-3">회원가입</a>
                            <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg">로그인</a>
                        </div>
                    @else
                        <div class="mt-4">
                            <a href="{{ route('puzzle-game.index') }}" class="btn btn-light btn-lg me-3">퍼즐게임 시작</a>
                            @if(Auth::user()->isSpecificAdmin('rainynux@gmail.com') || Auth::user()->isAdmin())
                                <a href="{{ route('puzzle.words.index') }}" class="btn btn-light btn-lg me-3">퍼즐관리</a>
                                <a href="{{ route('admin.users.index') }}" class="btn btn-light btn-lg">회원관리</a>
                            @endif
                        </div>
                    @endguest
                </div>
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
