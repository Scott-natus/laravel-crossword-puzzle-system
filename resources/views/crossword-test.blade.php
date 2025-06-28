<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>크로스워드 퍼즐 테스트 - 제미나이 알고리즘</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .crossword-grid {
            display: inline-block;
            border: 2px solid #333;
            background: #fff;
            margin: 20px 0;
        }
        .grid-cell {
            width: 30px;
            height: 30px;
            border: 1px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            background: #fff;
        }
        .grid-cell.empty {
            background: #f0f0f0;
        }
        .word-list {
            margin: 20px 0;
        }
        .word-item {
            padding: 5px 10px;
            margin: 2px;
            background: #e9ecef;
            border-radius: 3px;
            display: inline-block;
        }
        .loading {
            color: #007bff;
            font-style: italic;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🎯 크로스워드 퍼즐 테스트 - 제미나이 알고리즘</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>퍼즐 생성</h5>
                    </div>
                    <div class="card-body">
                        <form id="puzzleForm">
                            <div class="mb-3">
                                <label for="level" class="form-label">레벨 선택:</label>
                                <select class="form-select" id="level" name="level">
                                    <option value="1">레벨 1 (단어 5개, 교차점 2개)</option>
                                    <option value="2">레벨 2 (단어 5개, 교차점 2개)</option>
                                    <option value="3">레벨 3 (단어 6개, 교차점 2개)</option>
                                    <option value="4">레벨 4 (단어 6개, 교차점 2개)</option>
                                    <option value="5">레벨 5 (단어 6개, 교차점 2개)</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" id="generateBtn">
                                🎲 퍼즐 생성하기
                            </button>
                        </form>
                        
                        <div id="loading" class="loading mt-3" style="display: none;">
                            퍼즐을 생성하고 있습니다... 잠시만 기다려주세요.
                        </div>
                        
                        <div id="error" class="error mt-3" style="display: none;"></div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>📊 통계 정보</h5>
                    </div>
                    <div class="card-body">
                        <div id="stats">
                            <p>레벨을 선택하고 퍼즐을 생성하면 통계 정보가 표시됩니다.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>🎮 생성된 퍼즐</h5>
                    </div>
                    <div class="card-body">
                        <div id="puzzleResult">
                            <p>퍼즐을 생성해주세요.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>🔍 알고리즘 특징</h5>
                    </div>
                    <div class="card-body">
                        <h6>✅ 제미나이가 제안한 개선사항:</h6>
                        <ul>
                            <li><strong>단어 독립성 유지:</strong> '생사', '간호사업' 같은 의도치 않은 단어 생성 방지</li>
                            <li><strong>동적 그리드 크기:</strong> 단어 개수와 길이에 따라 최적 크기 자동 계산</li>
                            <li><strong>백트래킹 알고리즘:</strong> 모든 가능한 배치를 시도하여 최적 해답 찾기</li>
                            <li><strong>교차점 조건 검증:</strong> 레벨별 필수 교차점 개수 만족 확인</li>
                        </ul>
                        
                        <h6>🎯 핵심 규칙:</h6>
                        <ul>
                            <li>단어의 시작/끝은 검은색 칸 또는 그리드 경계와 인접</li>
                            <li>교차점은 오직 두 단어만이 공유</li>
                            <li>그리드 크기 = max(단어 길이) + (교차점/2) + 2</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('puzzleForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const level = document.getElementById('level').value;
            const generateBtn = document.getElementById('generateBtn');
            const loading = document.getElementById('loading');
            const error = document.getElementById('error');
            const puzzleResult = document.getElementById('puzzleResult');
            const stats = document.getElementById('stats');
            
            // UI 상태 변경
            generateBtn.disabled = true;
            loading.style.display = 'block';
            error.style.display = 'none';
            puzzleResult.innerHTML = '<p>퍼즐을 생성하고 있습니다...</p>';
            
            try {
                // 통계 정보 먼저 가져오기
                const statsResponse = await fetch(`/api/crossword-generator/stats?level=${level}`);
                const statsData = await statsResponse.json();
                
                if (statsData.success) {
                    const levelInfo = statsData.data.level;
                    stats.innerHTML = `
                        <h6>레벨 ${level} 정보:</h6>
                        <ul>
                            <li>단어 개수: ${levelInfo.word_count}개</li>
                            <li>교차점 개수: ${levelInfo.intersection_count}개</li>
                            <li>단어 난이도: ${levelInfo.word_difficulty}</li>
                            <li>힌트 난이도: ${levelInfo.hint_difficulty}</li>
                            <li>시간 제한: ${levelInfo.time_limit}초</li>
                        </ul>
                        <h6>사용 가능한 단어:</h6>
                        <p>총 ${statsData.data.available_words}개 단어 중 ${levelInfo.word_count}개 선택</p>
                        <p>예상 조합 수: ${statsData.data.estimated_combinations.toLocaleString()}개</p>
                    `;
                }
                
                // 퍼즐 생성
                const response = await fetch('/api/crossword-generator/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ level: parseInt(level) })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayPuzzle(data.data);
                } else {
                    throw new Error(data.message || '퍼즐 생성에 실패했습니다.');
                }
                
            } catch (err) {
                console.error('퍼즐 생성 오류:', err);
                error.textContent = `오류: ${err.message}`;
                error.style.display = 'block';
                puzzleResult.innerHTML = '<p class="text-danger">퍼즐 생성에 실패했습니다.</p>';
            } finally {
                generateBtn.disabled = false;
                loading.style.display = 'none';
            }
        });
        
        function displayPuzzle(puzzle) {
            const puzzleResult = document.getElementById('puzzleResult');
            
            // 그리드 HTML 생성
            let gridHtml = '<div class="crossword-grid">';
            puzzle.grid.forEach((row, y) => {
                gridHtml += '<div style="display: flex;">';
                row.forEach((cell, x) => {
                    const cellClass = cell === '' ? 'grid-cell empty' : 'grid-cell';
                    gridHtml += `<div class="${cellClass}">${cell || ''}</div>`;
                });
                gridHtml += '</div>';
            });
            gridHtml += '</div>';
            
            // 단어 목록 생성
            let wordsHtml = '<div class="word-list"><h6>출제된 단어들:</h6>';
            puzzle.words.forEach((word, index) => {
                wordsHtml += `<span class="word-item">${word.word}</span>`;
            });
            wordsHtml += '</div>';
            
            // 힌트 목록 생성
            let hintsHtml = '<div class="word-list"><h6>힌트:</h6>';
            puzzle.hints.forEach((hint, index) => {
                hintsHtml += `<div class="word-item"><strong>${hint.word}:</strong> ${hint.hint}</div>`;
            });
            hintsHtml += '</div>';
            
            // 통계 정보
            const statsHtml = `
                <div class="alert alert-success">
                    <h6>✅ 퍼즐 생성 성공!</h6>
                    <ul>
                        <li>그리드 크기: ${puzzle.stats.grid_size}×${puzzle.stats.grid_size}</li>
                        <li>단어 개수: ${puzzle.stats.word_count}개</li>
                        <li>교차점 개수: ${puzzle.stats.intersection_count}개</li>
                    </ul>
                </div>
            `;
            
            puzzleResult.innerHTML = statsHtml + gridHtml + wordsHtml + hintsHtml;
        }
    </script>
</body>
</html> 