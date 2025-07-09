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

// 페이징 파라미터 처리
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 30;
$offset = ($page - 1) * $perPage;

// 검색 파라미터 처리
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// 전체 개수 조회 쿼리
$countSql = "SELECT COUNT(*) FROM pz_words WHERE is_active = true";
$countParams = [];

if (!empty($search)) {
    $countSql .= " AND (word ILIKE ? OR category ILIKE ?)";
    $countParams[] = "%$search%";
    $countParams[] = "%$search%";
}

if (!empty($category)) {
    $countSql .= " AND category = ?";
    $countParams[] = $category;
}

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

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
$params[] = $perPage;
$params[] = $offset;

// 카테고리 목록 가져오기
$categorySql = "SELECT DISTINCT category FROM pz_words WHERE is_active = true ORDER BY category";
$categoryStmt = $pdo->prepare($categorySql);
$categoryStmt->execute();
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

// 단어 목록 가져오기
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$words = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>단순 단어 리스트</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1200px;
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
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4 text-center">📚 단어 리스트</h1>
        
        <!-- 검색 폼 -->
        <div class="search-box">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">검색어</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?= htmlspecialchars($search) ?>" placeholder="단어 또는 카테고리 검색">
                </div>
                <div class="col-md-4">
                    <label for="category" class="form-label">카테고리</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">전체 카테고리</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" 
                                    <?= $category === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">🔍 검색</button>
                    <a href="simple-list.php" class="btn btn-secondary">🔄 초기화</a>
                </div>
            </form>
        </div>

        <!-- 결과 통계 -->
        <div class="mb-3">
            <span class="badge bg-info">총 <?= $totalRecords ?>개 단어</span>
            <span class="badge bg-primary">페이지 <?= $page ?>/<?= $totalPages ?></span>
            <?php if (!empty($search) || !empty($category)): ?>
                <span class="badge bg-warning">필터 적용됨</span>
            <?php endif; ?>
        </div>

        <!-- 단어 테이블 -->
        <div class="word-table">
            <table class="table table-hover mb-0">
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
                    <?php if (empty($words)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                검색 결과가 없습니다.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($words as $word): ?>
                            <tr>
                                <td><?= $word['id'] ?></td>
                                <td><strong><?= htmlspecialchars($word['word']) ?></strong></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars($word['category']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $difficultyClass = 'difficulty-' . $word['difficulty'];
                                    $difficultyText = ['', '쉬움', '보통', '어려움', '매우어려움', '최고난이도'][$word['difficulty']] ?? '알수없음';
                                    ?>
                                    <span class="badge <?= $difficultyClass ?>">
                                        <?= $difficultyText ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('Y-m-d H:i', strtotime($word['created_at'])) ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 페이징 -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="페이지 네비게이션" class="mt-4">
                <ul class="pagination justify-content-center">
                    <!-- 이전 페이지 -->
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                &laquo; 이전
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- 페이지 번호들 -->
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    if ($startPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                        </li>
                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">
                                <?= $totalPages ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- 다음 페이지 -->
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                다음 &raquo;
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <!-- 페이지 정보 -->
        <div class="mt-3 text-center text-muted">
            <small>
                마지막 업데이트: <?= date('Y-m-d H:i:s') ?>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 