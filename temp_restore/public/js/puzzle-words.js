let currentWordId = null;

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

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 검색 타입 변경 이벤트 리스너
    const searchTypeSelect = document.getElementById('searchType');
    if (searchTypeSelect) {
        searchTypeSelect.addEventListener('change', updateSearchUI);
        // 초기 상태 설정
        updateSearchUI();
    }
});

// 기존 검색 기능 (호환성을 위해 유지)
function searchWords() {
    const searchField = document.getElementById('searchField').value;
    const searchValue = document.getElementById('searchInput').value;
    const params = new URLSearchParams();
    if (searchValue) {
        params.append('search_field', searchField);
        params.append('search_value', searchValue);
    }
    window.location.href = '/puzzle/words?' + params.toString();
}

// 사용 여부 토글
function toggleActive(wordId) {
    fetch(`/puzzle/words/${wordId}/toggle-active`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', '오류가 발생했습니다.');
    });
}

// 힌트 토글
function toggleHints(wordId) {
    const hintsRow = document.getElementById(`hints-${wordId}`);
    const isVisible = hintsRow.style.display !== 'none';
    
    if (!isVisible) {
        loadHints(wordId);
    }
    
    hintsRow.style.display = isVisible ? 'none' : 'table-row';
}

// 힌트 로드
function loadHints(wordId) {
    fetch(`/puzzle/words/${wordId}/hints`)
        .then(response => response.json())
        .then(hints => {
            const container = document.getElementById(`hints-list-${wordId}`);
            container.innerHTML = renderHintsList(hints, wordId);
        })
        .catch(error => {
            console.error('힌트 로드 오류:', error);
        });
}

// 힌트 목록 렌더링
function renderHintsList(hints, wordId) {
    if (hints.length === 0) {
        return '<div class="text-muted">등록된 힌트가 없습니다.</div>';
    }
    
    return hints.map(hint => `
        <div class="hint-item ${hint.is_primary ? 'primary' : ''}" data-hint-id="${hint.id}">
            <div class="d-flex justify-content-between align-items-start">
                <div class="hint-content flex-grow-1">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-${hint.difficulty === 'easy' ? 'success' : (hint.difficulty === 'medium' ? 'warning' : 'danger')} me-2">
                            ${hint.difficulty === 'easy' ? '쉬움' : (hint.difficulty === 'medium' ? '보통' : '어려움')}
                        </span>
                        ${hint.is_primary ? '<span class="badge bg-primary me-2">Primary</span>' : ''}
                    </div>
                    ${hint.hint_type === 'text' ? `<span class="fw-bold text-primary" style="font-size:1.1em;">${hint.hint_text}</span>` : `
                        <div class="d-flex align-items-center">
                            <span class="badge bg-secondary me-2">${hint.hint_type === 'image' ? '이미지' : '사운드'}</span>
                            <span>${hint.original_name || hint.hint_text}</span>
                            <button type="button" class="btn btn-sm btn-outline-info ms-2" 
                                    onclick="previewFile('${hint.file_url}', '${hint.hint_type}')">
                                <i class="fas fa-eye"></i> 미리보기
                            </button>
                        </div>
                    `}
                </div>
                <div class="hint-actions">
                    ${hint.is_primary ? '<span class="badge-primary">Primary</span>' : 
                        `<button type="button" class="btn btn-sm btn-outline-primary" 
                                 onclick="setPrimary(${hint.id}, ${wordId})">Primary 설정</button>`}
                    <button type="button" class="btn btn-sm btn-outline-warning" 
                            onclick="editHint(${hint.id}, ${wordId})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" 
                            onclick="deleteHint(${hint.id}, ${wordId})">
                        삭제
                    </button>
                </div>
            </div>
            <div class="hint-meta">
                타입: ${hint.hint_type} | 난이도: ${hint.difficulty === 'easy' ? '쉬움' : (hint.difficulty === 'medium' ? '보통' : '어려움')} | 
                입력일: ${new Date(hint.created_at).toLocaleDateString()} | 
                수정일: ${new Date(hint.updated_at).toLocaleDateString()}
            </div>
        </div>
    `).join('');
}

// 힌트 추가 모달 표시
function showAddHintModal(wordId) {
    currentWordId = wordId;
    document.getElementById('hintModalTitle').textContent = '힌트 추가';
    document.getElementById('hintForm').reset();
    document.getElementById('hintWordId').value = wordId;
    document.getElementById('hintId').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('hintModal'));
    modal.show();
}

// 힌트 수정
function editHint(hintId, wordId) {
    currentWordId = wordId;
    
    fetch(`/puzzle/words/${wordId}/hints/${hintId}`)
        .then(response => response.json())
        .then(hint => {
            document.getElementById('hintModalTitle').textContent = '힌트 수정';
            document.getElementById('hintWordId').value = wordId;
            document.getElementById('hintId').value = hintId;
            document.getElementById('hintType').value = hint.hint_type;
            document.getElementById('hintDifficulty').value = hint.difficulty || 'medium';
            document.getElementById('hintContent').value = hint.hint_text;
            
            // 힌트 타입에 따른 섹션 표시
            const type = hint.hint_type;
            const textSection = document.getElementById('textHintSection');
            const fileSection = document.getElementById('fileHintSection');
            
            textSection.style.display = type === 'text' ? 'block' : 'none';
            fileSection.style.display = (type === 'image' || type === 'sound') ? 'block' : 'none';
            
            const modal = new bootstrap.Modal(document.getElementById('hintModal'));
            modal.show();
        });
}

// 힌트 삭제
function deleteHint(hintId, wordId) {
    if (!confirm('이 힌트를 삭제하시겠습니까?')) return;
    fetch(`/puzzle/words/${wordId}/hints/${hintId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            loadHints(wordId);
        } else {
            showAlert('error', data.message);
        }
    });
}

// Primary 힌트 설정
function setPrimary(hintId, wordId) {
    fetch(`/puzzle/words/${wordId}/hints/${hintId}/primary`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            loadHints(wordId);
        } else {
            showAlert('error', data.message);
        }
    });
}

// 파일 미리보기
function previewFile(fileUrl, type) {
    const modal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
    const content = document.getElementById('filePreviewContent');
    
    if (type === 'image') {
        content.innerHTML = `<img src="${fileUrl}" class="img-fluid" alt="이미지 미리보기">`;
    } else if (type === 'sound') {
        content.innerHTML = `
            <audio controls class="w-100">
                <source src="${fileUrl}" type="audio/mpeg">
                브라우저가 오디오를 지원하지 않습니다.
            </audio>
        `;
    }
    
    modal.show();
}

// 페이지 로드 시 이벤트 리스너 등록
document.addEventListener('DOMContentLoaded', function() {
    // 힌트 타입 변경 시 폼 업데이트
    const hintTypeSelect = document.getElementById('hintType');
    if (hintTypeSelect) {
        hintTypeSelect.addEventListener('change', function() {
            const type = this.value;
            const textSection = document.getElementById('textHintSection');
            const fileSection = document.getElementById('fileHintSection');
            
            textSection.style.display = type === 'text' ? 'block' : 'none';
            fileSection.style.display = (type === 'image' || type === 'sound') ? 'block' : 'none';
            
            // 필수 필드 설정
            document.getElementById('hintContent').required = type === 'text';
            document.getElementById('hintFile').required = (type === 'image' || type === 'sound');
        });
    }

    // 힌트 폼 제출
    const hintForm = document.getElementById('hintForm');
    if (hintForm) {
        hintForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const hintId = formData.get('hint_id');
            const method = hintId ? 'PUT' : 'POST';
            const url = hintId ? 
                `/puzzle/words/${currentWordId}/hints/${hintId}` : 
                `/puzzle/words/${currentWordId}/hints`;
            
            fetch(url, {
                method: method,
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    bootstrap.Modal.getInstance(document.getElementById('hintModal')).hide();
                    loadHints(currentWordId);
                } else {
                    showAlert('error', data.message);
                }
            });
        });
    }

    // 단어 추가 폼 제출
    const addWordForm = document.getElementById('addWordForm');
    if (addWordForm) {
        addWordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/puzzle/words', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    bootstrap.Modal.getInstance(document.getElementById('addWordModal')).hide();
                    location.reload();
                } else {
                    showAlert('error', data.message);
                }
            });
        });
    }
});

// 알림 표시 함수
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 3000);
} 