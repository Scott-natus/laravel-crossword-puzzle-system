<?php
// 데이터베이스 연결 (기존 simple-list2.php와 동일)
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

// AJAX 요청 처리 (API 엔드포인트)
if (isset($_GET['api'])) {
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 30);
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'desc';
    $category = $_GET['category'] ?? '';
    $offset = ($page - 1) * $limit;

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
    $allowedSort = ['id','word','category','difficulty','created_at'];
    if (!in_array($sort, $allowedSort)) $sort = 'created_at';
    $sql .= " ORDER BY $sort ".($order === 'asc' ? 'ASC' : 'DESC')." LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response = ['data' => $words];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>단어 리스트 (Reusable Infinity Grid)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .infinity-grid-table { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; padding: 20px; }
        .infinity-grid-table th { background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; font-weight: 600; }
        .infinity-grid-table td { border: 1px solid #dee2e6; padding: 10px 8px; vertical-align: middle; }
        .infinity-grid-table tr:hover { background-color: #f8f9fa; }
        .infinity-grid-scroll { height: 600px; overflow-y: auto; border: 2px solid #dee2e6; border-radius: 8px; }
        .loading-spinner { text-align: center; padding: 20px; color: #6c757d; }
        .badge { font-size: 0.8rem; }
        .difficulty-1 { background-color: #28a745; }
        .difficulty-2 { background-color: #ffc107; }
        .difficulty-3 { background-color: #fd7e14; }
        .difficulty-4 { background-color: #dc3545; }
        .difficulty-5 { background-color: #6c757d; }
    </style>
</head>
<body>
<div class="container">
    <h1 class="mb-4 text-center">📚 단어 리스트 (재사용 가능한 인피니티 그리드)</h1>
    <div id="myGrid"></div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// 재사용 가능한 인피니티 그리드 함수
function createInfinityGrid(target, options) {
    var $container = $(target);
    var settings = $.extend({
        width: '100%',
        fetchData: null, // function(params, callback)
        pageSize: 30,
        enableCSV: true,
        searchFields: ['word','category'],
        sortField: 'created_at',
        sortOrder: 'desc',
        columns: [
            { field: 'id', title: 'ID', width: '8%' },
            { field: 'word', title: '단어', width: '20%' },
            { field: 'category', title: '카테고리', width: '20%' },
            { field: 'difficulty', title: '난이도', width: '15%' },
            { field: 'created_at', title: '생성일', width: '20%' }
        ]
    }, options);

    // 1. 검색폼/버튼/테이블 구조 생성
    var html = '';
    html += '<form class="row g-2 mb-2 infinity-grid-search-form">';
    settings.searchFields.forEach(function(field) {
        html += '<div class="col-auto"><input type="text" class="form-control" name="'+field+'" placeholder="'+field+' 검색"></div>';
    });
    html += '<div class="col-auto"><button type="submit" class="btn btn-primary">검색</button></div>';
    if (settings.enableCSV) {
        html += '<div class="col-auto"><button type="button" class="btn btn-success btn-csv">CSV 다운로드</button></div>';
    }
    html += '</form>';
    html += '<div class="infinity-grid-scroll" style="width:'+settings.width+'">';
    html += '<table class="table infinity-grid-table"><thead><tr>';
    settings.columns.forEach(function(col) {
        html += '<th style="width:'+col.width+'">'+col.title+'</th>';
    });
    html += '</tr></thead><tbody></tbody></table>';
    html += '</div>';
    $container.html(html);

    var $scroll = $container.find('.infinity-grid-scroll');
    var $tbody = $container.find('tbody');
    var $form = $container.find('form');
    var $csvBtn = $container.find('.btn-csv');
    var currentPage = 0;
    var isLoading = false;
    var hasMoreData = true;
    var allData = [];
    var searchParams = {};

    function renderRow(row) {
        var diffText = ['', '쉬움', '보통', '어려움', '매우어려움', '최고난이도'][row.difficulty] || '알수없음';
        var diffClass = 'difficulty-' + row.difficulty;
        return '<tr>' +
            '<td>' + row.id + '</td>' +
            '<td><strong>' + (row.word || '') + '</strong></td>' +
            '<td><span class="badge bg-secondary">' + (row.category || '') + '</span></td>' +
            '<td><span class="badge ' + diffClass + '">' + diffText + '</span></td>' +
            '<td><small class="text-muted">' + (row.created_at ? row.created_at.substr(0,16) : '') + '</small></td>' +
            '</tr>';
    }

    function loadMoreData() {
        if (isLoading || !hasMoreData) return;
        isLoading = true;
        currentPage++;
        $tbody.append('<tr class="loading-row"><td colspan="'+settings.columns.length+'" class="text-center"><div class="loading-spinner"><div class="spinner-border" role="status"><span class="visually-hidden">로딩중...</span></div><div class="mt-2">추가 데이터를 불러오는 중...</div></div></td></tr>');
        var params = $.extend({}, searchParams, {
            page: currentPage,
            limit: settings.pageSize,
            sort: settings.sortField,
            order: settings.sortOrder
        });
        if (typeof settings.fetchData === 'function') {
            settings.fetchData(params, function(res) {
                $container.find('.loading-row').remove();
                if (res.data && res.data.length > 0) {
                    res.data.forEach(function(row) {
                        allData.push(row);
                        $tbody.append(renderRow(row));
                    });
                    if (res.data.length < settings.pageSize) hasMoreData = false;
                } else {
                    hasMoreData = false;
                }
                isLoading = false;
            });
        }
    }

    // 스크롤 이벤트
    $scroll.on('scroll', function() {
        var scrollTop = $scroll.scrollTop();
        var containerHeight = $scroll.height();
        var scrollHeight = $scroll[0].scrollHeight;
        if (scrollHeight - (scrollTop + containerHeight) <= 200) {
            loadMoreData();
        }
    });

    // 검색폼 이벤트
    $form.on('submit', function(e) {
        e.preventDefault();
        searchParams = {};
        settings.searchFields.forEach(function(field) {
            searchParams[field] = $form.find('[name="'+field+'"]').val();
        });
        // 초기화
        currentPage = 0;
        allData = [];
        hasMoreData = true;
        $tbody.empty();
        loadMoreData();
    });

    // CSV 다운로드
    if (settings.enableCSV) {
        $csvBtn.on('click', function() {
            if (allData.length === 0) { alert('내보낼 데이터가 없습니다.'); return; }
            var csvContent = settings.columns.map(function(col){return col.title;}).join(',') + '\n';
            allData.forEach(function(row) {
                var arr = settings.columns.map(function(col){
                    var val = row[col.field] || '';
                    if (typeof val === 'string') val = val.replace(/<[^>]*>/g, '');
                    if (String(val).includes(',')) val = '"'+val+'"';
                    return val;
                });
                csvContent += arr.join(',') + '\n';
            });
            var blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = '단어리스트_' + new Date().toISOString().slice(0, 10) + '.csv';
            link.click();
        });
    }

    // 최초 데이터 로드
    loadMoreData();
}

// 실제 사용 예시
$(function() {
    createInfinityGrid('#myGrid', {
        width: '100%',
        fetchData: function(params, callback) {
            // 서버에 AJAX 요청
            $.get('simple-list3.php?api=1', params, function(res) {
                callback(res);
            });
        },
        pageSize: 30,
        enableCSV: true,
        searchFields: ['word','category'],
        sortField: 'created_at',
        sortOrder: 'desc'
    });
});
</script>
</body>
</html> 