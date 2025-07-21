// DOM이 로드된 후 실행
document.addEventListener('DOMContentLoaded', function() {
    // 모바일 메뉴 토글
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    
    if (hamburger) {
        hamburger.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            hamburger.classList.toggle('active');
        });
    }
    
    // 최신 공지사항 로드
    loadLatestNotices();
    
    // 스크롤 이벤트 처리
    handleScroll();
});

// 최신 공지사항 로드
async function loadLatestNotices() {
    try {
        const response = await fetch('/api/notices?limit=3');
        const notices = await response.json();
        
        const noticesContainer = document.getElementById('notices-list');
        if (noticesContainer && notices.data) {
            noticesContainer.innerHTML = notices.data.map(notice => `
                <div class="notice-card">
                    <h3>${notice.title}</h3>
                    <p>${notice.content.substring(0, 100)}...</p>
                    <div class="date">${new Date(notice.created_at).toLocaleDateString('ko-KR')}</div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('공지사항 로드 실패:', error);
    }
}

// 스크롤 이벤트 처리
function handleScroll() {
    const header = document.querySelector('.header');
    let lastScrollTop = 0;
    
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // 헤더 숨김/표시 효과
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            header.style.transform = 'translateY(-100%)';
        } else {
            header.style.transform = 'translateY(0)';
        }
        
        lastScrollTop = scrollTop;
    });
}

// 부드러운 스크롤
function smoothScroll(target) {
    const element = document.querySelector(target);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// 폼 제출 처리
function handleFormSubmit(formId, endpoint) {
    const form = document.getElementById(formId);
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    alert('성공적으로 제출되었습니다.');
                    form.reset();
                } else {
                    alert('오류가 발생했습니다: ' + result.message);
                }
            } catch (error) {
                console.error('폼 제출 오류:', error);
                alert('오류가 발생했습니다.');
            }
        });
    }
}

// 이미지 지연 로딩
function lazyLoadImages() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// 애니메이션 효과
function animateOnScroll() {
    const elements = document.querySelectorAll('.feature-card, .overview-item, .notice-card');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });
    
    elements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
}

// 페이지 로드 시 초기화
window.addEventListener('load', function() {
    lazyLoadImages();
    animateOnScroll();
}); 