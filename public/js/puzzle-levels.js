// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
});

// 이벤트 리스너 초기화
function initializeEventListeners() {
    // 입력 필드 변경 감지
    document.querySelectorAll('.word-count, .word-difficulty, .hint-difficulty, .intersection-count, .time-limit, .clear-condition').forEach(function(element) {
        element.addEventListener('change', function() {
            checkRowChanges(this);
        });
        
        element.addEventListener('input', function() {
            checkRowChanges(this);
        });
    });
}

// 행 변경사항 확인
function checkRowChanges(element) {
    const row = element.closest('tr');
    const saveBtn = row.querySelector('.save-btn');
    const wordCount = parseInt(row.querySelector('.word-count').value) || 0;
    const intersectionCount = parseInt(row.querySelector('.intersection-count').value) || 0;
    
    // 교차점 개수 유효성 검사
    if (intersectionCount >= wordCount) {
        showAlert('error', '교차점 개수는 단어 개수보다 적어야 합니다.');
        row.querySelector('.intersection-count').classList.add('is-invalid');
        saveBtn.disabled = true;
        return;
    } else {
        row.querySelector('.intersection-count').classList.remove('is-invalid');
    }
    
    // 변경사항 확인
    const hasChanges = checkFieldChanges(row);
    
    if (hasChanges) {
        row.classList.add('changed');
        saveBtn.disabled = false;
    } else {
        row.classList.remove('changed');
        saveBtn.disabled = true;
    }
}

// 필드 변경사항 확인
function checkFieldChanges(row) {
    const fields = [
        { element: '.word-count', attr: 'data-original' },
        { element: '.word-difficulty', attr: 'data-original' },
        { element: '.hint-difficulty', attr: 'data-original' },
        { element: '.intersection-count', attr: 'data-original' },
        { element: '.time-limit', attr: 'data-original' },
        { element: '.clear-condition', attr: 'data-original' }
    ];
    
    for (let field of fields) {
        const element = row.querySelector(field.element);
        const originalValue = element.getAttribute(field.attr);
        const currentValue = element.value;
        
        if (originalValue !== currentValue) {
            return true;
        }
    }
    
    return false;
}

// 레벨 저장
function saveLevel(levelId, button) {
    const row = button.closest('tr');
    const originalText = button.innerHTML;
    
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    const data = {
        word_count: parseInt(row.querySelector('.word-count').value) || 0,
        word_difficulty: parseInt(row.querySelector('.word-difficulty').value) || 1,
        hint_difficulty: parseInt(row.querySelector('.hint-difficulty').value) || 1,
        intersection_count: parseInt(row.querySelector('.intersection-count').value) || 0,
        time_limit: parseInt(row.querySelector('.time-limit').value) || 0,
        clear_condition: parseInt(row.querySelector('.clear-condition').value) || 1
    };
    
    console.log('Sending data:', data);
    
    fetch(`/puzzle/levels/${levelId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            
            // 원본 값 업데이트
            updateOriginalValues(row, data.level);
            
            // 변경 상태 초기화
            row.classList.remove('changed');
            button.disabled = true;
            
            // 수정일시 업데이트
            const now = new Date();
            const timeCell = row.querySelector('td:nth-child(8)');
            timeCell.textContent = now.getFullYear() + '-' + 
                                 String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                                 String(now.getDate()).padStart(2, '0') + ' ' + 
                                 String(now.getHours()).padStart(2, '0') + ':' + 
                                 String(now.getMinutes()).padStart(2, '0');
            
            // 수정자 업데이트
            const userCell = row.querySelector('td:nth-child(9)');
            userCell.textContent = '{{ Auth::user()->email }}';
            
        } else {
            showAlert('error', data.message);
            if (data.errors) {
                console.log('Validation errors:', data.errors);
                console.log('Error details:', JSON.stringify(data.errors, null, 2));
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', '저장 중 오류가 발생했습니다.');
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

// 원본 값 업데이트
function updateOriginalValues(row, levelData) {
    row.querySelector('.word-count').setAttribute('data-original', levelData.word_count);
    row.querySelector('.word-difficulty').setAttribute('data-original', levelData.word_difficulty);
    row.querySelector('.hint-difficulty').setAttribute('data-original', levelData.hint_difficulty);
    row.querySelector('.intersection-count').setAttribute('data-original', levelData.intersection_count);
    row.querySelector('.time-limit').setAttribute('data-original', levelData.time_limit);
    row.querySelector('.clear-condition').setAttribute('data-original', levelData.clear_condition);
}

// 기본 데이터 생성
function generateDefaultData() {
    if (!confirm('기본 데이터를 생성하시겠습니까? 기존 데이터는 모두 삭제됩니다.')) {
        return;
    }
    
    fetch('/puzzle/levels/generate-default', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', '데이터 생성 중 오류가 발생했습니다.');
    });
}

// 알림 표시
function showAlert(type, message) {
    const modal = new bootstrap.Modal(document.getElementById('alertModal'));
    const title = document.getElementById('alertModalTitle');
    const body = document.getElementById('alertModalBody');
    
    if (type === 'success') {
        title.textContent = '성공';
        title.className = 'modal-title text-success';
        body.innerHTML = `<div class="alert alert-success mb-0">${message}</div>`;
    } else {
        title.textContent = '오류';
        title.className = 'modal-title text-danger';
        body.innerHTML = `<div class="alert alert-danger mb-0">${message}</div>`;
    }
    
    modal.show();
} 