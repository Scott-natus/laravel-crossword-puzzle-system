<?php
// 데이터베이스 연결
$host = '127.0.0.1';
$dbname = 'mydb';
$username = 'myuser';
$password = 'tngkrrhk';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("데이터베이스 연결 실패: " . $e->getMessage());
}

// AJAX 요청 처리 (인피니티 로딩용)
if (isset($_GET['draw'])) {
    // 인피니티 로딩 서버 사이드 처리
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 30);
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    
    // 오프셋 계산
    $offset = ($page - 1) * $limit;
    
    // 데이터 조회 쿼리
    $sql = "SELECT id, word, category, difficulty, created_at FROM pz_words WHERE is_active = true";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (word ILIKE ? OR category ILIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($category)) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 응답 데이터 준비
    $response = [
        'data' => []
    ];
    
    foreach ($words as $word) {
        $difficultyText = ['', '쉬움', '보통', '어려움', '매우어려움', '최고난이도'][$word['difficulty']] ?? '알수없음';
        $difficultyClass = 'difficulty-' . $word['difficulty'];
        
        $response['data'][] = [
            $word['id'],
            '<strong>' . htmlspecialchars($word['word']) . '</strong>',
            '<span class="badge bg-secondary">' . htmlspecialchars($word['category']) . '</span>',
            '<span class="badge ' . $difficultyClass . '">' . $difficultyText . '</span>',
            '<small class="text-muted">' . date('Y-m-d H:i', strtotime($word['created_at'])) . '</small>'
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// 카테고리 목록 가져오기 (필터용)
$categorySql = "SELECT DISTINCT category FROM pz_words WHERE is_active = true ORDER BY category";
$categoryStmt = $pdo->prepare($categorySql);
$categoryStmt->execute();
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>단어 리스트 (DataTable)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .search-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .word-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            padding: 20px;
        }
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
        .badge {
            font-size: 0.8rem;
        }
        .difficulty-1 { background-color: #28a745; }
        .difficulty-2 { background-color: #ffc107; }
        .difficulty-3 { background-color: #fd7e14; }
        .difficulty-4 { background-color: #dc3545; }
        .difficulty-5 { background-color: #6c757d; }
        
        /* 내보내기 버튼 스타일 */
        .export-buttons {
            margin-bottom: 20px;
        }
        
        .export-buttons .btn {
            margin-right: 10px;
            margin-bottom: 5px;
        }
        
        /* 그리드 외곽선 스타일 */
        .table {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table thead th {
            border: 1px solid #dee2e6;
            background-color: #f8f9fa;
            font-weight: 600;
            padding: 12px 8px;
        }
        
        .table tbody td {
            border: 1px solid #dee2e6;
            padding: 10px 8px;
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* 인피니티 로딩 스타일 */
        .loading-spinner {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }
        
        .loading-spinner .spinner-border {
            width: 2rem;
            height: 2rem;
        }
        
        /* 스크롤바 커스터마이징 */
        .word-table {
            height: 600px;
            overflow-y: auto;
            border: 2px solid #dee2e6;
            border-radius: 8px;
        }
        
        .word-table::-webkit-scrollbar {
            width: 8px;
        }
        
        .word-table::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .word-table::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .word-table::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4 text-center">📚 단어 리스트 (인피니티 로딩)</h1>
        
        <!-- 검색 폼 -->
        <div class="search-box">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">검색어</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="단어 또는 카테고리 검색">
                </div>
                <div class="col-md-4">
                    <label for="category" class="form-label">카테고리</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">전체 카테고리</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" 
                                    <?= ($_GET['category'] ?? '') === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">🔍 검색</button>
                    <a href="simple-list2.php" class="btn btn-secondary">🔄 초기화</a>
                </div>
            </form>
        </div>

        <!-- 내보내기 버튼 -->
        <div class="export-buttons">
            <button class="btn btn-outline-primary btn-sm btn-export" data-type="csv">
                📊 CSV 내보내기
            </button>
            <button class="btn btn-outline-success btn-sm btn-export" data-type="excel">
                📈 Excel 내보내기
            </button>
            <button class="btn btn-outline-info btn-sm btn-export" data-type="print">
                🖨️ 인쇄
            </button>
        </div>

        <!-- 단어 테이블 -->
        <div class="word-table">
            <table id="wordTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>단어</th>
                        <th>카테고리</th>
                        <th>난이도</th>
                        <th>생성일</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- 인피니티 로딩으로 동적으로 데이터를 로드합니다 -->
                </tbody>
            </table>
        </div>

        <!-- 페이지 정보 -->
        <div class="mt-3 text-center text-muted">
            <small>
                마지막 업데이트: <?= date('Y-m-d H:i:s') ?>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        $(document).ready(function() {
            // 인피니티 로딩을 위한 변수들
            var currentPage = 0;
            var isLoading = false;
            var hasMoreData = true;
            var allData = [];
            var searchTerm = '';
            var selectedCategory = '';
            
            // 데이터 로딩 함수
            function loadMoreData() {
                console.log('loadMoreData 호출됨 - isLoading:', isLoading, 'hasMoreData:', hasMoreData);
                
                if (isLoading || !hasMoreData) {
                    console.log('로딩 중이거나 더 이상 데이터가 없음');
                    return;
                }
                
                isLoading = true;
                currentPage++;
                console.log('페이지 로딩 시작:', currentPage);
                
                // 로딩 스피너 표시
                if (currentPage === 1) {
                    $('#wordTable tbody').html('<tr><td colspan="5" class="text-center"><div class="loading-spinner"><div class="spinner-border" role="status"><span class="visually-hidden">로딩중...</span></div><div class="mt-2">데이터를 불러오는 중...</div></div></td></tr>');
                } else {
                    $('#wordTable tbody').append('<tr id="loading-row"><td colspan="5" class="text-center"><div class="loading-spinner"><div class="spinner-border" role="status"><span class="visually-hidden">로딩중...</span></div><div class="mt-2">추가 데이터를 불러오는 중...</div></div></td></tr>');
                }
                
                // AJAX 요청
                $.ajax({
                    url: 'simple-list2.php',
                    type: 'GET',
                    data: {
                        page: currentPage,
                        limit: 30,
                        search: searchTerm,
                        category: selectedCategory,
                        draw: 1
                    },
                    success: function(response) {
                        console.log('AJAX 응답 받음:', response);
                        $('#loading-row').remove();
                        
                        if (response.data && response.data.length > 0) {
                            console.log('데이터 개수:', response.data.length);
                            // 기존 데이터에 새 데이터 추가
                            allData = allData.concat(response.data);
                            console.log('전체 데이터 개수:', allData.length);
                            
                            // 테이블 업데이트
                            updateTable();
                            
                            // 더 많은 데이터가 있는지 확인
                            if (response.data.length < 30) {
                                hasMoreData = false;
                                console.log('더 이상 데이터가 없음');
                            }
                        } else {
                            hasMoreData = false;
                            console.log('응답 데이터가 없음');
                            if (currentPage === 1) {
                                $('#wordTable tbody').html('<tr><td colspan="5" class="text-center text-muted py-4">검색 결과가 없습니다.</td></tr>');
                            }
                        }
                        
                        isLoading = false;
                        isLoadingScroll = false; // 스크롤 로딩 플래그도 리셋
                        console.log('로딩 완료 - isLoading:', isLoading, 'isLoadingScroll:', isLoadingScroll);
                    },
                    error: function() {
                        $('#loading-row').remove();
                        if (currentPage === 1) {
                            $('#wordTable tbody').html('<tr><td colspan="5" class="text-center text-danger py-4">데이터 로딩 중 오류가 발생했습니다.</td></tr>');
                        }
                        isLoading = false;
                        isLoadingScroll = false; // 스크롤 로딩 플래그도 리셋
                        console.log('로딩 오류 - isLoading:', isLoading, 'isLoadingScroll:', isLoadingScroll);
                    }
                });
            }
            
            // 테이블 업데이트 함수
            function updateTable() {
                var tbody = $('#wordTable tbody');
                tbody.empty();
                
                allData.forEach(function(item) {
                    var row = '<tr>' +
                        '<td>' + item[0] + '</td>' +
                        '<td>' + item[1] + '</td>' +
                        '<td>' + item[2] + '</td>' +
                        '<td>' + item[3] + '</td>' +
                        '<td>' + item[4] + '</td>' +
                        '</tr>';
                    tbody.append(row);
                });
                
                // 테이블 업데이트 후 스크롤 높이 확인
                setTimeout(function() {
                    var container = $('.word-table');
                    console.log('테이블 업데이트 완료, 컨테이너 스크롤 높이:', container[0].scrollHeight);
                }, 100);
            }
            
            // 테이블 컨테이너 스크롤 이벤트 처리
            var scrollTimeout;
            var lastScrollTop = 0;
            var isLoadingScroll = false;
            
            $('.word-table').scroll(function() {
                if (isLoadingScroll) return; // 로딩 중이면 스크롤 이벤트 무시
                
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(function() {
                    var container = $('.word-table');
                    var scrollTop = container.scrollTop();
                    var containerHeight = container.height();
                    var scrollHeight = container[0].scrollHeight;
                    
                    // 디버깅용 로그
                    console.log('컨테이너 스크롤 위치:', scrollTop, '컨테이너 높이:', containerHeight, '스크롤 높이:', scrollHeight);
                    console.log('스크롤 남은 거리:', scrollHeight - (scrollTop + containerHeight));
                    
                    // 스크롤이 하단 200px 전에 도달하면 추가 로딩
                    var remainingDistance = scrollHeight - (scrollTop + containerHeight);
                    if (remainingDistance <= 200) {
                        console.log('추가 데이터 로딩 시도... (남은 거리: ' + remainingDistance + 'px)');
                        isLoadingScroll = true;
                        loadMoreData();
                    }
                    
                    lastScrollTop = scrollTop;
                }, 150); // 150ms 디바운싱
            });
            
            // 검색 폼 제출 처리
            $('form').on('submit', function(e) {
                e.preventDefault();
                searchTerm = $('#search').val();
                selectedCategory = $('#category').val();
                
                // 초기화
                currentPage = 0;
                allData = [];
                hasMoreData = true;
                
                // 첫 번째 데이터 로드
                loadMoreData();
            });
            
            // 카테고리 변경 처리
            $('#category').on('change', function() {
                selectedCategory = $(this).val();
                
                // 초기화
                currentPage = 0;
                allData = [];
                hasMoreData = true;
                
                // 첫 번째 데이터 로드
                loadMoreData();
            });
            
            // 검색어 입력 시 자동 새로고침 (디바운싱)
            var searchTimeout;
            $('#search').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    searchTerm = $(this).val();
                    
                    // 초기화
                    currentPage = 0;
                    allData = [];
                    hasMoreData = true;
                    
                    // 첫 번째 데이터 로드
                    loadMoreData();
                }, 500);
            });
            
            // 초기 데이터 로드
            console.log('페이지 로드 완료, 초기 데이터 로딩 시작');
            loadMoreData();
            
            // 내보내기 버튼들
            $('.btn-export').on('click', function() {
                var type = $(this).data('type');
                exportData(type);
            });
            
            // 데이터 내보내기 함수
            function exportData(type) {
                if (allData.length === 0) {
                    alert('내보낼 데이터가 없습니다.');
                    return;
                }
                
                var csvContent = "ID,단어,카테고리,난이도,생성일\n";
                allData.forEach(function(item) {
                    // HTML 태그 제거
                    var cleanData = item.map(function(cell) {
                        return cell.replace(/<[^>]*>/g, '');
                    });
                    csvContent += cleanData.join(',') + '\n';
                });
                
                var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                var link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = '단어리스트_' + new Date().toISOString().slice(0, 10) + '.csv';
                link.click();
            }
        });
    </script>
</body>
</html> 