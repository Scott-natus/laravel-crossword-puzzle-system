<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- title>{{ config('app.name', 'Laravel') }}</title -->
    <title> Home server BBS </title>
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    @stack('styles')
</head>
<body class="custom-board">
    <!-- 네비게이션: 로고, 로그인/사용자명 -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="/main">
                <i class="fas fa-brain me-2"></i>natus 작업소
            </a>

            <!-- Board Navigation Dropdown -->
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdownBoard" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    게시판 바로가기
                </a>
                <ul class="dropdown-menu" aria-labelledby="navbarDropdownBoard">
                    @if(isset($sharedBoardTypes))
                        @foreach ($sharedBoardTypes as $boardType)
                            <li>
                                <a class="dropdown-item" href="{{ route('board.index', ['boardType' => $boardType->slug]) }}">
                                    {{ $boardType->name }}
                                </a>
                            </li>
                        @endforeach
                    @endif

                    @auth
                        @if(Auth::user()->is_admin)
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">관리자 메뉴</h6></li>
                            <li><a class="dropdown-item" href="{{ route('puzzle.words.index') }}">단어 관리</a></li>
                            <li><a class="dropdown-item" href="{{ route('puzzle.hint-generator.index') }}">AI 힌트 생성</a></li>
                            <li><a class="dropdown-item" href="{{ route('puzzle.levels.index') }}">레벨 관리</a></li>
                            <li><a class="dropdown-item" href="{{ route('puzzle.grid-templates.index') }}">그리드 템플릿 관리</a></li>
                        @endif
                    @endauth
                </ul>
            </div>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto"></ul>
                <div class="d-flex align-items-center">
                    @auth
                        <span class="me-3 fw-bold">{{ Auth::user()->name }}</span>
                        <a href="{{ route('logout') }}" class="btn btn-outline-primary btn-sm" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-primary me-2">Login</a>
                        <a href="{{ route('register') }}" class="btn btn-primary-custom">Register</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>
    <!-- Hero Banner with Tech Icons -->
    <div class="hero-banner">
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
            </div>
        </div>
    </div>
    @if (Route::is('board.index'))
        <!-- Statistics Cards -->
        <div class="container">
            <div class="row stats-cards">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number">{{ $totalPosts ?? 0 }}</div>
                        <div class="stat-label">총 게시글</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number">{{ $totalComments ?? 0 }}</div>
                        <div class="stat-label">댓글</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number">{{ $activeUsers ?? 0 }}</div>
                        <div class="stat-label">활성 사용자</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number">{{ $totalAttachments ?? 0 }}</div>
                        <div class="stat-label">첨부파일</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 검색창: 조건선택-키워드-검색-글쓰기 -->
        <div class="container">
            <div class="search-section">
                <form method="GET" action="{{ route('board.index', ['boardType' => $boardType->slug]) }}" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <select class="form-select search-box" name="search_type">
                            <option value="all">전체</option>
                            <option value="title">제목</option>
                            <option value="author">작성자</option>
                            <option value="content">내용</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control search-box" placeholder="키워드를 입력하세요...">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-search w-100 py-2 px-3" type="submit">
                            <i class="fas fa-search"></i> 검색
                        </button>
                    </div>
                    <div class="col-md-1 text-end">
                        @auth
                            @php
                                $currentBoardTypeSlug = request()->route('boardType');
                            @endphp
                            @if($currentBoardTypeSlug)
                                <!-- Debug: Current slug from URL: {{ $currentBoardTypeSlug }} -->
                                <a href="{{ route('board.create', ['boardType' => $currentBoardTypeSlug]) }}" class="btn btn-search w-100 py-2 px-3">글쓰기</a>
                            @else
                                <!-- Debug: No boardType in URL -->
                                <span class="text-danger">글쓰기 버튼 오류</span>
                            @endif
                        @endauth
                    </div>
                </form>
            </div>
        </div>
    @endif
    <div id="app">
        <main class="py-4">
            @yield('content')
        </main>
    </div>
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6> Generated Lab by Cursor AI</h6>
                    <p class="text-muted mb-0">natus's laboraty</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">
                        Powered by 
                        <span class="text-info">This is natus  Studio </span>                      
                    </p>
                </div>
            </div>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
