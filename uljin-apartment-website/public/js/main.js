// 울진 아파트 홈페이지 메인 JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // 모바일 메뉴 토글
    initMobileMenu();
    
    // 헤더 스크롤 효과
    initHeaderScroll();
    
    // 스크롤 애니메이션
    initScrollAnimations();
    
    // 이미지 지연 로딩
    initLazyLoading();

    // 부드러운 스크롤 적용
    document.querySelectorAll('a[href^="#"]').forEach(function(link) {
      link.addEventListener('click', function(e) {
        const target = link.getAttribute('href');
        if (target.length > 1 && document.querySelector(target)) {
          e.preventDefault();
          smoothScroll(target);
        }
      });
    });
});

// 모바일 메뉴 초기화
function initMobileMenu() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
            mobileMenu.classList.toggle('active');
        });
        
        // 메뉴 외부 클릭 시 닫기
        document.addEventListener('click', function(e) {
            if (!mobileMenuBtn.contains(e.target) && !mobileMenu.contains(e.target)) {
                mobileMenu.classList.add('hidden');
                mobileMenu.classList.remove('active');
            }
        });
    }
}

// 헤더 스크롤 효과
function initHeaderScroll() {
    const header = document.querySelector('header');
    let lastScrollTop = 0;
    
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // 스크롤 시 헤더 배경 변경
        if (scrollTop > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        // 스크롤 방향에 따른 헤더 숨김/표시
        if (scrollTop > lastScrollTop && scrollTop > 200) {
            header.style.transform = 'translateY(-100%)';
        } else {
            header.style.transform = 'translateY(0)';
        }
        
        lastScrollTop = scrollTop;
    });
}

// 스크롤 애니메이션 초기화
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // 애니메이션 대상 요소들
    const animatedElements = document.querySelectorAll('.bg-white, .text-center');
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        observer.observe(el);
    });
}

// 이미지 지연 로딩 초기화
function initLazyLoading() {
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

// 로딩 상태 표시
function showLoading(element) {
    element.classList.add('loading');
}

function hideLoading(element) {
    element.classList.remove('loading');
}

// 토스트 메시지
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        'bg-blue-500'
    } text-white`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// 페이지 로드 완료 시 초기화
window.addEventListener('load', function() {
    // 페이지 로드 완료 후 추가 초기화 작업
    console.log('울진 아파트 홈페이지 로드 완료');
}); 

// ===================== 갤러리 카드 모달 기능 추가 =====================
// 공통 모달 함수
function closeModal(modalId) {
  var modal = document.getElementById(modalId);
  if (modal) modal.style.display = 'none';
}
function openModal(modalId) {
  var modal = document.getElementById(modalId);
  if (modal) modal.style.display = 'flex';
}
function bindModalTrigger(triggerSelector, modalId, beforeOpen) {
  document.querySelectorAll(triggerSelector).forEach(function(trigger) {
    trigger.addEventListener('click', function(e) {
      if (beforeOpen) beforeOpen(trigger, modalId, e);
      openModal(modalId);
    });
  });
  var closeBtn = document.querySelector('#' + modalId + ' .modal-close');
  if (closeBtn) {
    closeBtn.onclick = function() { closeModal(modalId); };
  }
  var modal = document.getElementById(modalId);
  if (modal) {
    modal.onclick = function(e) {
      if (e.target === modal) closeModal(modalId);
    };
  }
}

document.addEventListener('DOMContentLoaded', function() {
  // 갤러리 카드 클릭 시 모달 띄우기
  bindModalTrigger('.gallery-item[data-card-id]', 'gallery-card-modal', function(trigger) {
    var cardId = trigger.getAttribute('data-card-id');
    // 모든 카드별 이미지 그룹 숨기기
    document.querySelectorAll('.gallery-card-modal-images').forEach(function(el) {
      el.style.display = 'none';
    });
    // 해당 카드만 표시
    var target = document.querySelector('.gallery-card-modal-images[data-card-id="' + cardId + '"]');
    if(target) target.style.display = 'block';
  });

  // 갤러리 모달 title 안전하게 처리
  var galleryModalTitle = document.getElementById('gallery-modal-title');
  if (galleryModalTitle) {
    galleryModalTitle.textContent = '';
  }

  // 갤러리 모달 닫기 버튼 안전하게 처리
  var galleryModalClose = document.getElementById('gallery-modal-close');
  if (galleryModalClose) {
    galleryModalClose.addEventListener('click', function() {
      var galleryModal = document.getElementById('gallery-modal');
      if (galleryModal) galleryModal.style.display = 'none';
    });
  }

  // 갤러리 모달 외부 클릭 시 닫기 안전하게 처리
  var galleryModal = document.getElementById('gallery-modal');
  if (galleryModal) {
    galleryModal.addEventListener('click', function(e) {
      if (e.target === this) {
        this.style.display = 'none';
      }
    });
  }
}); 