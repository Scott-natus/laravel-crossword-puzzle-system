// 페이지 로드 시 통계 정보 로드
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    setupEventListeners();
    // 검색 타입 변경 이벤트 리스너
    const searchTypeSelect = document.getElementById('searchType');
    if (searchTypeSelect) {
        searchTypeSelect.addEventListener('change', updateSearchUI);
        // 초기 상태 설정
        updateSearchUI();
    }
    
    // 페이지 로드 시 현재 검색 타입에 따라 카테고리 드롭다운 표시/숨김 설정
    const currentSearchType = searchTypeSelect ? searchTypeSelect.value : 'keyword';
    const categoryDropdown = document.getElementById('categoryDropdown');
    if (categoryDropdown) {
        if (currentSearchType === 'category' || currentSearchType === 'word') {
            categoryDropdown.style.display = 'block';
        } else {
            categoryDropdown.style.display = 'none';
        }
    }
});

// 이벤트 리스너 설정
function setupEventListeners() {
    // 배치 타입 변경 이벤트
    document.querySelectorAll('input[name="batchType"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'selected') {
                document.getElementById('selectedWordsSection').style.display = 'block';
                document.getElementById('categorySection').style.display = 'none';
            } else {
                document.getElementById('selectedWordsSection').style.display = 'none';
                document.getElementById('categorySection').style.display = 'block';
            }
        });
    });

    // 체크박스 변경 이벤트
    document.querySelectorAll('.word-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedWords);
    });
}

// 통계 정보 로드
function loadStats() {
    fetch('/puzzle/hint-generator/stats')
        .then(response => response.json())
        .then(data => {
            document.getElementById('totalWords').textContent = data.total_words;
            document.getElementById('wordsWithHints').textContent = data.words_with_hints;
            document.getElementById('wordsWithoutHints').textContent = data.words_without_hints;
            document.getElementById('totalHints').textContent = data.total_hints;
        })
        .catch(error => {
            console.error('통계 로드 실패:', error);
        });
}

// API 연결 테스트
function testConnection() {
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 테스트 중...';
    
    fetch('/puzzle/hint-generator/test-connection')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'API 연결 성공!');
            } else {
                showAlert('error', 'API 연결 실패: ' + data.message);
            }
        })
        .catch(error => {
            showAlert('error', 'API 연결 테스트 중 오류가 발생했습니다.');
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = originalText;
        });
}

// 단일 단어 힌트 생성
function generateHint(wordId) {
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    const row = button.closest('tr');
    const word = row.querySelector('td:nth-child(3)').textContent;
    const category = row.querySelector('td:nth-child(2)').textContent;
    
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 생성 중...';
    
    fetch(`/puzzle/hint-generator/word/${wordId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSingleHintResultModal(data, word, category);
        } else {
            showAlert('error', data.message || '힌트 생성에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('힌트 생성 실패:', error);
        showAlert('error', '힌트 생성 중 오류가 발생했습니다.');
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

// 힌트 재생성
function regenerateHint(wordId) {
    if (confirm('기존 힌트를 삭제하고 새로운 힌트를 생성하시겠습니까?')) {
        generateHint(wordId);
    }
}

// 전체 선택/해제
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.word-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateSelectedWords();
}

// 선택된 단어 업데이트
function updateSelectedWords() {
    const checkboxes = document.querySelectorAll('.word-checkbox:checked');
    const selectedCount = document.getElementById('selectedCount');
    const selectedWordsList = document.getElementById('selectedWordsList');
    
    selectedCount.textContent = checkboxes.length;
    
    if (checkboxes.length > 0) {
        const words = Array.from(checkboxes).map(checkbox => {
            const row = checkbox.closest('tr');
            return row.querySelector('td:nth-child(3)').textContent;
        });
        
        selectedWordsList.innerHTML = words.map(word => 
            `<span class="badge bg-primary me-1 mb-1">${word}</span>`
        ).join('');
    } else {
        selectedWordsList.innerHTML = '<span class="text-muted">선택된 단어가 없습니다.</span>';
    }
}

// 일괄 생성 모달 표시
function showBatchModal() {
    const selectedWords = document.querySelectorAll('.word-checkbox:checked');
    
    if (selectedWords.length === 0) {
        showAlert('warning', '일괄 생성을 위해 단어를 선택해주세요.');
        return;
    }
    
    updateSelectedWords();
    new bootstrap.Modal(document.getElementById('batchModal')).show();
}

// 일괄 생성 실행
function executeBatchGeneration() {
    const batchType = document.querySelector('input[name="batchType"]:checked').value;
    const overwrite = document.getElementById('overwriteHints').checked;
    
    let requestData = {
        overwrite: overwrite
    };
    
    if (batchType === 'selected') {
        const selectedWords = document.querySelectorAll('.word-checkbox:checked');
        if (selectedWords.length === 0) {
            showAlert('warning', '선택된 단어가 없습니다.');
            return;
        }
        
        requestData.word_ids = Array.from(selectedWords).map(checkbox => checkbox.value);
    } else {
        const category = document.getElementById('batchCategory').value;
        if (!category) {
            showAlert('warning', '카테고리를 선택해주세요.');
            return;
        }
        
        requestData.category = category;
    }
    
    // 모달 닫기
    bootstrap.Modal.getInstance(document.getElementById('batchModal')).hide();
    
    // 진행 상황 표시
    showProgressModal();
    
    // API 호출
    const url = batchType === 'selected' ? '/puzzle/hint-generator/batch' : '/puzzle/hint-generator/category';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        hideProgressModal();
        showResultModal(data);
    })
    .catch(error => {
        hideProgressModal();
        showAlert('error', '일괄 생성 중 오류가 발생했습니다.');
    });
}

// 진행 상황 모달 표시
function showProgressModal() {
    const modal = document.createElement('div');
    modal.className = 'modal fade show';
    modal.style.display = 'block';
    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5>힌트 생성 중...</h5>
                    <p class="text-muted">잠시만 기다려주세요.</p>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.classList.add('modal-open');
}

// 진행 상황 모달 숨기기
function hideProgressModal() {
    const modal = document.querySelector('.modal.show');
    if (modal) {
        modal.remove();
        document.body.classList.remove('modal-open');
    }
}

// 결과 모달 표시
function showResultModal(data) {
    const resultContent = document.getElementById('resultContent');
    
    if (data.success) {
        let html = `
            <div class="alert alert-success">
                <h6>${data.message}</h6>
                <p>총 ${data.summary.total}개 중 성공 ${data.summary.success}개, 실패 ${data.summary.error}개</p>
            </div>
        `;
        
        if (data.results && data.results.length > 0) {
            html += '<div class="results-list">';
            data.results.forEach(result => {
                const statusClass = result.status === 'success' ? 'result-success' : 
                                  result.status === 'error' ? 'result-error' : 'result-skipped';
                
                html += `
                    <div class="result-item ${statusClass}">
                        <strong>${result.word}</strong>
                        <span class="badge bg-${result.status === 'success' ? 'success' : 
                                              result.status === 'error' ? 'danger' : 'warning'} ms-2">
                            ${result.status === 'success' ? '성공' : 
                              result.status === 'error' ? '실패' : '스킵'}
                        </span>
                        ${result.hint_count ? `<br><small>생성된 힌트: ${result.hint_count}개 (쉬움, 보통, 어려움)</small>` : ''}
                        ${result.hints ? `
                            <div class="mt-2">
                                ${result.hints.map(hint => `
                                    <div class="small">
                                        <span class="badge bg-${hint.difficulty === 'easy' ? 'success' : (hint.difficulty === 'medium' ? 'warning' : 'danger')} me-1">
                                            ${hint.difficulty === 'easy' ? '쉬움' : (hint.difficulty === 'medium' ? '보통' : '어려움')}
                                        </span>
                                        ${hint.hint_text}
                                    </div>
                                `).join('')}
                            </div>
                        ` : ''}
                        ${result.message ? `<br><small>메시지: ${result.message}</small>` : ''}
                    </div>
                `;
            });
            html += '</div>';
        }
        
        resultContent.innerHTML = html;
    } else {
        resultContent.innerHTML = `
            <div class="alert alert-danger">
                <h6>오류 발생</h6>
                <p>${data.message}</p>
            </div>
        `;
    }
    
    // 모달 표시
    const modalElement = document.getElementById('resultModal');
    const modal = new bootstrap.Modal(modalElement);
    
    // 기존 이벤트 리스너 제거 (중복 방지)
    modalElement.removeEventListener('hidden.bs.modal', handleBatchModalClose);
    
    // 모달 닫힘 이벤트 리스너 추가
    function handleBatchModalClose() {
        location.reload();
    }
    
    modalElement.addEventListener('hidden.bs.modal', handleBatchModalClose);
    
    // 모달 표시
    modal.show();
    
    // 5초 후 자동으로 모달 닫기 (선택사항)
    setTimeout(() => {
        if (modalElement.classList.contains('show')) {
            modal.hide();
        }
    }, 5000);
}

// 단어 필터링
function filterWords() {
    const category = document.getElementById('categoryFilter').value;
    const hintFilter = document.getElementById('hintFilter').value;
    const search = document.getElementById('searchInput').value.toLowerCase();
    
    const rows = document.querySelectorAll('#wordsTableBody tr');
    
    rows.forEach(row => {
        const wordCategory = row.dataset.category;
        const hintCount = parseInt(row.dataset.hintCount);
        const word = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
        
        let show = true;
        
        // 카테고리 필터
        if (category && wordCategory !== category) {
            show = false;
        }
        
        // 힌트 필터
        if (hintFilter === 'with_hints' && hintCount === 0) {
            show = false;
        } else if (hintFilter === 'without_hints' && hintCount > 0) {
            show = false;
        }
        
        // 검색 필터
        if (search && !word.includes(search)) {
            show = false;
        }
        
        row.style.display = show ? '' : 'none';
    });
}

// 알림 표시
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // 5초 후 자동 제거
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// 단일 힌트 생성 결과 모달 표시
function showSingleHintResultModal(data, word, category) {
    // 대상 정보 설정
    document.getElementById('resultCategory').textContent = category;
    document.getElementById('resultWord').textContent = word;
    
    // 사용빈도 정보 표시
    if (data.frequency) {
        const frequencyText = `사용빈도: ${data.frequency} (${data.frequency <= 2 ? '자주 사용' : data.frequency <= 3 ? '보통 사용' : '적게 사용'})`;
        document.getElementById('resultFrequency').textContent = frequencyText;
        document.getElementById('frequencyInfo').style.display = 'block';
    } else {
        document.getElementById('frequencyInfo').style.display = 'none';
    }
    
    // 생성된 힌트 목록 렌더링
    const hintsList = document.getElementById('generatedHintsList');
    
    if (data.hints && data.hints.length > 0) {
        const hintsHtml = data.hints.map(hint => {
            const difficultyClass = hint.difficulty === 'easy' ? 'success' : 
                                   hint.difficulty === 'medium' ? 'warning' : 'danger';
            const difficultyText = hint.difficulty === 'easy' ? '쉬움' : 
                                  hint.difficulty === 'medium' ? '보통' : '어려움';
            
            return `
                <div class="hint-result-item ${hint.is_primary ? 'primary' : ''}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-${difficultyClass} hint-difficulty-badge me-2">
                                    ${difficultyText}
                                </span>
                                ${hint.is_primary ? '<span class="badge bg-primary hint-difficulty-badge">Primary</span>' : ''}
                            </div>
                            <div class="hint-content">
                                ${hint.hint_text}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        hintsList.innerHTML = hintsHtml;
    } else {
        hintsList.innerHTML = '<div class="text-muted">생성된 힌트가 없습니다.</div>';
    }
    
    // 모달 표시
    const modalElement = document.getElementById('singleHintResultModal');
    const modal = new bootstrap.Modal(modalElement);
    
    // 기존 이벤트 리스너 제거 (중복 방지)
    modalElement.removeEventListener('hidden.bs.modal', handleModalClose);
    
    // 모달 닫힘 이벤트 리스너 추가
    function handleModalClose() {
        location.reload();
    }
    
    modalElement.addEventListener('hidden.bs.modal', handleModalClose);
    
    // 모달 표시
    modal.show();
    
    // 3초 후 자동으로 모달 닫기 (선택사항)
    setTimeout(() => {
        if (modalElement.classList.contains('show')) {
            modal.hide();
        }
    }, 3000);
}

// 검색 타입 변경 시 동적 UI 업데이트
function updateSearchUI() {
    const searchType = document.getElementById('searchType').value;
    const categoryDropdown = document.getElementById('categoryDropdown');
    const searchInput = document.getElementById('searchInput');
    
    // 카테고리 드롭다운 표시/숨김
    if (searchType === 'category' || searchType === 'word') {
        categoryDropdown.style.display = 'block';
    } else {
        categoryDropdown.style.display = 'none';
    }
    
    // 검색어 입력 필드 플레이스홀더 변경
    switch (searchType) {
        case 'keyword':
            searchInput.placeholder = '키워드 검색...';
            break;
        case 'category':
            searchInput.placeholder = '전체검색';
            break;
        case 'word':
            searchInput.placeholder = '단어검색';
            break;
    }
}

// 검색 유효성 검사
function validateSearch() {
    const searchType = document.getElementById('searchType').value;
    const searchCategory = document.getElementById('searchCategory').value;
    const searchWord = document.getElementById('searchInput').value.trim();
    
    if (searchType === 'category') {
        if ((searchCategory === '전체 카테고리' || !searchCategory) && !searchWord) {
            alert('카테고리나 검색어를 입력하셔야 합니다');
            return false;
        }
    } else if (searchType === 'word') {
        if (searchCategory === '전체 카테고리' && !searchWord) {
            alert('단어나 검색어를 입력하셔야 합니다');
            return false;
        }
    }
    
    return true;
} 