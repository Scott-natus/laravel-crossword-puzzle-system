@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">레벨 {{ $game->current_level }}</h5>
                    <div>
                        <span class="badge bg-primary me-2">정답: {{ $game->current_level_correct_answers }}</span>
                        <span class="badge bg-danger me-2">오답: {{ $game->current_level_wrong_answers }}</span>
                        @if($level && $level->time_limit > 0)
                            <span class="badge bg-warning" id="timer">남은시간: <span id="time-left">{{ $level->time_limit }}</span>초</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div id="puzzle-grid" class="text-center">
                        <!-- 퍼즐 그리드가 여기에 렌더링됩니다 -->
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">단어 입력</h6>
                </div>
                <div class="card-body">
                    <div id="word-input-section" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">힌트:</label>
                            <div id="current-hint" class="alert alert-info"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">정답 입력:</label>
                            <input type="text" id="answer-input" class="form-control" placeholder="단어를 입력하세요">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" id="check-answer-btn" class="btn btn-primary">확인</button>
                            <button type="button" id="show-hint-btn" class="btn btn-secondary">힌트보기</button>
                        </div>
                        <div id="result-message" class="mt-3"></div>
                        <div id="additional-hints" class="mt-3" style="display: none;">
                            <h6>추가 힌트:</h6>
                            <div id="hints-list"></div>
                        </div>
                    </div>
                    <div id="game-info">
                        <p>번호를 클릭하여 단어를 입력하세요.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 게임 완료 모달 -->
<div class="modal fade" id="levelCompleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">축하합니다!</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>모든 단어를 맞추셨습니다!</p>
                <p>다음 레벨로 진행하시겠습니까?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" id="next-level-btn" class="btn btn-primary">다음 레벨</button>
            </div>
        </div>
    </div>
</div>

<!-- 게임오버 모달 -->
<div class="modal fade" id="gameOverModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">게임오버</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>시간이 초과되었습니다.</p>
                <p>5분 후 재시도 가능합니다.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="location.href='{{ route('main') }}'">메인으로</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.puzzle-cell {
    width: 40px;
    height: 40px;
    border: 1px solid #ccc;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 1px;
    cursor: pointer;
    font-weight: bold;
    position: relative;
}

.puzzle-cell.black {
    background-color: #000;
    color: white;
}

.puzzle-cell.answered {
    background-color: #6c757d;
    color: white;
}

.puzzle-cell:hover {
    background-color: #e9ecef;
}

.puzzle-cell.black:hover {
    background-color: #333;
}

.cell-number {
    position: absolute;
    top: 2px;
    left: 2px;
    font-size: 10px;
    color: #666;
}

.puzzle-cell.black .cell-number {
    color: #fff;
}

.puzzle-cell.answered .cell-number {
    color: #fff;
}

/* 템플릿 관리와 동일한 그리드 스타일 */
.grid-cell-number {
    position: relative;
    transition: all 0.2s ease;
}

.grid-cell-number:hover {
    transform: scale(1.05);
    z-index: 10;
}

.word-number {
    background: #ff6b6b;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.grid-container {
    background: white;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}
</style>
@endpush

@push('scripts')
<script>
let currentWordId = null;
let currentHintId = null;
let baseHintId = null;
let currentTemplateWords = [];
let answeredWords = new Set();
let gameTimer = null;
let timeLeft = {{ $level ? $level->time_limit : 0 }};

$(document).ready(function() {
    loadTemplate();
    
    if (timeLeft > 0) {
        startTimer();
    }
});

function loadTemplate() {
    $.get('{{ route("puzzle-game.get-template") }}')
        .done(function(data) {
            currentTemplateWords = data.template.words;
            renderPuzzleGrid(data.template);
        })
        .fail(function(xhr) {
            alert('템플릿을 불러올 수 없습니다.');
        });
}

function renderPuzzleGrid(template) {
    const grid = $('#puzzle-grid');
    grid.empty();
    
    const gridData = template.grid_pattern;
    const words = template.words;
    
    let html = '<div class="grid-container" style="display: inline-block; border: 2px solid #333;">';
    
    for (let y = 0; y < gridData.length; y++) {
        html += '<div class="grid-row" style="display: flex;">';
        for (let x = 0; x < gridData[y].length; x++) {
            const cellValue = gridData[y][x];
            const wordInfo = getWordInfoAtPosition(x, y, words);
            const hasWords = getWordsAtPosition(x, y, words).length > 0;
            
            let cellClass = 'grid-cell-number';
            let cellContent = '';
            let clickEvent = '';
            
            if (cellValue === 2) { // 검은색칸 (단어가 있는 칸)
                cellClass += ' bg-dark text-white';
                
                // 번호는 시작 위치에만 표시
                if (wordInfo) {
                    if (wordInfo.isIntersection) {
                        // 교차점인 경우: 가로 단어는 큰 번호, 세로 단어는 작은 번호로 표시
                        cellContent = `
                            <div style="position: relative; width: 100%; height: 100%;">
                                <span class="word-number" style="position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; font-size: 8px; background: #ff6b6b;">${wordInfo.horizontalWord.word_id}</span>
                                <span class="word-number" style="position: absolute; bottom: 2px; right: 2px; width: 16px; height: 16px; font-size: 8px; background: #4ecdc4;">${wordInfo.verticalWord.word_id}</span>
                            </div>
                        `;
                    } else {
                        // 단일 단어인 경우
                        cellContent = `<span class="word-number">${wordInfo.word_id}</span>`;
                    }
                }
                
                // 클릭 이벤트는 모든 검은색 칸에 추가
                if (hasWords) {
                    clickEvent = ` onclick="handleCellClick(${y}, ${x}, ${JSON.stringify(words).replace(/"/g, '&quot;')})" style="cursor: pointer;"`;
                }
            } else { // 흰색칸 (빈 칸)
                cellClass += ' bg-light';
                cellContent = '□';
            }
            
            html += `<div class="${cellClass}" style="width: 40px; height: 40px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;"${clickEvent}>${cellContent}</div>`;
        }
        html += '</div>';
    }
    
    html += '</div>';
    grid.html(html);
}

// 특정 위치의 단어 정보 찾기 (교차점 우선순위 적용 - 템플릿 관리와 동일)
function getWordInfoAtPosition(x, y, words) {
    let horizontalWord = null;
    let verticalWord = null;
    
    for (const wordInfo of words) {
        const word = wordInfo.position;
        
        // 가로 방향 단어
        if (word.direction === 'horizontal') {
            if (y === word.start_y && x >= word.start_x && x <= word.end_x) {
                // 단어의 시작 위치인지 확인
                if (x === word.start_x) {
                    horizontalWord = wordInfo;
                }
            }
        }
        // 세로 방향 단어
        else if (word.direction === 'vertical') {
            if (x === word.start_x && y >= word.start_y && y <= word.end_y) {
                // 단어의 시작 위치인지 확인
                if (y === word.start_y) {
                    verticalWord = wordInfo;
                }
            }
        }
    }
    
    // 교차점인 경우: 가로 단어 우선, 세로 단어는 작은 번호로 표시
    if (horizontalWord && verticalWord) {
        // 가로 단어를 메인으로 반환하되, 교차점 정보 포함
        return {
            ...horizontalWord,
            isIntersection: true,
            horizontalWord: horizontalWord,
            verticalWord: verticalWord
        };
    }
    
    // 단일 단어인 경우
    return horizontalWord || verticalWord;
}

// 클릭 처리용 - 해당 위치의 모든 단어 찾기 (교차점 처리)
function getWordsAtPosition(x, y, words) {
    let foundWords = [];
    
    for (const wordInfo of words) {
        const word = wordInfo.position;
        
        // 가로 방향 단어
        if (word.direction === 'horizontal') {
            if (y === word.start_y && x >= word.start_x && x <= word.end_x) {
                foundWords.push(wordInfo);
            }
        }
        // 세로 방향 단어
        else if (word.direction === 'vertical') {
            if (x === word.start_x && y >= word.start_y && y <= word.end_y) {
                foundWords.push(wordInfo);
            }
        }
    }
    
    return foundWords;
}

// 검은색 칸 클릭 처리 함수
function handleCellClick(row, col, words) {
    // 해당 위치의 모든 단어 찾기 (교차점 처리)
    let foundWords = getWordsAtPosition(col, row, words);
    
    if (foundWords.length === 0) {
        return;
    }
    
    let selectedWord = foundWords[0]; // 기본적으로 첫 번째 단어 선택
    
    // 교차점인 경우 (여러 단어가 있는 경우) 배지가 있는 단어 우선 선택
    if (foundWords.length > 1) {
        // 현재 클릭한 위치에 배지가 있는지 확인
        const wordInfo = getWordInfoAtPosition(col, row, words);
        if (wordInfo) {
            // 배지가 있는 단어를 우선 선택
            selectedWord = wordInfo;
        } else {
            // 배지가 없으면 첫 번째 단어 선택
            selectedWord = foundWords[0];
        }
    }
    
    // 선택된 단어 처리
    currentWordId = selectedWord.pz_word_id || selectedWord.word_id;
    currentHintId = null; // 새로운 단어 선택 시 힌트 ID 초기화
    baseHintId = selectedWord.hint_id || null; // 기본 힌트 ID 설정
    
    // 힌트 영역 완전 초기화
    $('#hints-list').empty(); // 기존 힌트 목록 삭제
    $('#additional-hints').hide(); // 힌트 영역 숨기기
    
    // 힌트보기 버튼 재활성화 (새로운 단어 선택 시)
    $('#show-hint-btn').prop('disabled', false).text('힌트보기');
    
    // 우측 힌트 영역에 기본 힌트만 표시
    $('#current-hint').html(`
        <strong>힌트:</strong> ${selectedWord.hint || '힌트가 없습니다.'}
    `);
    
    $('#answer-input').val('').focus();
    $('#word-input-section').show();
    $('#result-message').empty();
}

$('#check-answer-btn').click(function() {
    if (!currentWordId) return;
    
    const answer = $('#answer-input').val().trim();
    if (!answer) {
        alert('정답을 입력해주세요.');
        return;
    }
    
    $.post('{{ route("puzzle-game.check-answer") }}', {
        word_id: currentWordId,
        answer: answer,
        _token: '{{ csrf_token() }}'
    })
    .done(function(data) {
        $('#result-message').html(`<div class="alert alert-${data.is_correct ? 'success' : 'danger'}">${data.message}</div>`);
        
        if (data.is_correct) {
            // 정답인 경우
            
            // 그리드에 정답 표시
            updateGridWithAnswer(data.correct_answer);
            
            // 입력 영역 숨기기
            $('#word-input-section').hide();
            
            // 정답 메시지 표시 후 2초 뒤 숨기기
            setTimeout(function() {
                $('#result-message').empty();
            }, 2000);
            
            // 레벨 완료 체크 (모든 단어를 맞췄는지 확인)
            checkLevelCompletion();
        } else {
            // 오답인 경우 입력 필드 초기화
            $('#answer-input').val('').focus();
        }
    })
    .fail(function(xhr) {
        alert('오류가 발생했습니다.');
    });
});

function updateGridWithAnswer(correctWord) {
    // 현재 선택된 단어의 위치 정보를 사용하여 그리드에 정답 표시
    if (!currentWordId) return;
    
    // 현재 선택된 단어 정보 찾기
    let selectedWord = null;
    for (let word of currentTemplateWords) {
        if ((word.pz_word_id || word.word_id) == currentWordId) {
            selectedWord = word;
            break;
        }
    }
    
    if (!selectedWord) return;
    
    // 정답을 맞춘 단어를 추적에 추가
    answeredWords.add(currentWordId);
    
    const position = selectedWord.position;
    const direction = position.direction;
    const startX = position.start_x;
    const startY = position.start_y;
    const endX = position.end_x;
    const endY = position.end_y;
    
    // 그리드에서 해당 단어 위치에 정답 표시
    for (let i = 0; i < correctWord.length; i++) {
        let x, y;
        
        if (direction === 'horizontal') {
            x = startX + i;
            y = startY;
        } else {
            x = startX;
            y = startY + i;
        }
        
        // 그리드 셀 찾기 (0부터 시작하는 인덱스)
        const cell = $(`.grid-row:eq(${y}) .grid-cell-number:eq(${x})`);
        if (cell.length > 0) {
            const isStartPosition = (direction === 'horizontal' && i === 0) || (direction === 'vertical' && i === 0);
            
            if (isStartPosition) {
                // 시작 위치: 배지를 정답 뒤로 숨기고 정답만 표시
                cell.html(`
                    <div style="background-color: #6c757d; color: white; padding: 2px 4px; border-radius: 2px; font-size: 10px; font-weight: bold; z-index: 10;">${correctWord[i]}</div>
                    <span class="word-number" style="position: absolute; top: 2px; left: 2px; z-index: 5; opacity: 0.3;">${selectedWord.word_id}</span>
                `);
            } else {
                // 중간 위치: 정답 문자만 표시
                cell.html(`
                    <div style="background-color: #6c757d; color: white; padding: 2px 4px; border-radius: 2px; font-size: 10px; font-weight: bold;">${correctWord[i]}</div>
                `);
            }
            
            // 칸 자체를 회색으로 변경
            cell.css('background-color', '#6c757d');
            cell.addClass('answered');
            cell.css('position', 'relative'); // 절대 위치를 위한 relative 설정
        }
    }
}

function checkLevelCompletion() {
    // 모든 단어가 정답인지 확인
    
    // 모든 단어의 정답을 맞췄는지 확인
    if (answeredWords.size >= currentTemplateWords.length) {
        // 레벨 완료 모달 표시
        setTimeout(function() {
            $('#levelCompleteModal').modal('show');
        }, 1000);
    }
}

$('#show-hint-btn').click(function() {
    if (!currentWordId) return;
    
    // 버튼 비활성화 (한 번만 클릭 가능)
    $(this).prop('disabled', true).text('힌트 사용됨');
    
    $.get('{{ route("puzzle-game.get-hints") }}', {
        word_id: currentWordId,
        current_hint_id: currentHintId, // 현재 힌트 ID 전달
        base_hint_id: baseHintId // 기본 힌트 ID 전달
    })
    .done(function(data) {
        if (data.hint) {
            // 힌트 목록을 초기화하고 새로운 힌트만 표시
            const hintsList = $('#hints-list');
            hintsList.empty(); // 기존 힌트 목록 삭제
            hintsList.append(`<div class="alert alert-info">${data.hint.hint}</div>`);
            
            // 현재 힌트 ID를 새로 받은 힌트의 ID로 업데이트
            currentHintId = data.hint.id;
            
            $('#additional-hints').show();
        } else {
            // 더 이상 힌트가 없는 경우
            alert(data.message || '더 이상 사용할 수 있는 힌트가 없습니다.');
            // 힌트가 없으면 버튼 다시 활성화
            $('#show-hint-btn').prop('disabled', false).text('힌트보기');
        }
    })
    .fail(function(xhr) {
        alert('힌트를 불러올 수 없습니다.');
        // 오류 발생 시 버튼 다시 활성화
        $('#show-hint-btn').prop('disabled', false).text('힌트보기');
    });
});

$('#next-level-btn').click(function() {
    $.post('{{ route("puzzle-game.complete-level") }}', {
        _token: '{{ csrf_token() }}'
    })
    .done(function(data) {
        $('#levelCompleteModal').modal('hide');
        location.reload();
    })
    .fail(function(xhr) {
        alert('오류가 발생했습니다.');
    });
});

function startTimer() {
    gameTimer = setInterval(function() {
        timeLeft--;
        $('#time-left').text(timeLeft);
        
        if (timeLeft <= 0) {
            clearInterval(gameTimer);
            gameOver();
        }
    }, 1000);
}

function gameOver() {
    $.post('{{ route("puzzle-game.game-over") }}', {
        _token: '{{ csrf_token() }}'
    })
    .done(function(data) {
        $('#gameOverModal').modal('show');
    })
    .fail(function(xhr) {
        alert('오류가 발생했습니다.');
    });
}
</script>
@endpush 