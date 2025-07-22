// 울진 아파트 홈페이지 메인 JavaScript

// 히어로 롤링 배너
// Django static 경로로 이미지 경로 수정
const heroImages = [
  '/static/main/images/jogam.jpg',
  '/static/main/images/jogam01.jpg',
  '/static/main/images/jogam02.jpg',
  '/static/main/images/jogam03.jpg'
];
document.addEventListener('DOMContentLoaded', function() {
  // 히어로 롤링
  const slider = document.querySelector('.hero-bg-slider');
  let currentIdx = 0;
  function showHeroImage(idx) {
    slider.querySelectorAll('.hero-bg-slider-img').forEach(img => {
      img.classList.remove('active');
      setTimeout(() => {
        if (img.parentNode) img.parentNode.removeChild(img);
      }, 1000);
    });
    const img = document.createElement('img');
    img.src = heroImages[idx];
    img.className = 'hero-bg-slider-img';
    img.alt = '배경 이미지';
    slider.appendChild(img);
    setTimeout(() => {
      img.classList.add('active');
    }, 30);
  }
  if (slider) {
    showHeroImage(currentIdx);
    setInterval(function() {
      currentIdx = (currentIdx + 1) % heroImages.length;
      showHeroImage(currentIdx);
    }, 4000);
  }

  // 공통 모달 함수
  function openModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'flex';
  }
  function closeModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
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

  // 프로젝트 개요
  bindModalTrigger('.overview-card', 'overview-modal', function(trigger) {
    var section = trigger.getAttribute('data-section') || trigger.querySelector('h3').textContent;
    document.getElementById('overview-modal-title').textContent = section;
  });
  // 입지환경
  bindModalTrigger('.feature-item', 'features-modal', function(trigger) {
    var section = trigger.getAttribute('data-section') || trigger.querySelector('h4').textContent;
    document.getElementById('features-modal-title').textContent = section;
  });
  // 단지배치도 이미지 보기
  bindModalTrigger('.facility-image-view', 'facility-image-modal');
  // 분양정보 평면도
  bindModalTrigger('.btn-plan-small', 'plan-modal', function(trigger) {
    var type = trigger.getAttribute('data-type') || trigger.textContent;
    document.getElementById('plan-modal-title').textContent = type + ' 평면도';
    var body = document.getElementById('plan-modal-body');
    body.innerHTML = '';
    if(type.indexOf('84A') !== -1) {
      for(let i=1;i<=3;i++) {
        var img = document.createElement('img');
        img.src = '/static/main/images/map_84-' + i + '.png';
        img.style.width = '100%';
        img.style.maxWidth = '100%';
        img.style.height = 'auto';
        img.style.margin = '0';
        img.style.display = 'block';
        img.alt = '84A 평면도 ' + i;
        body.appendChild(img);
      }
      setTimeout(() => { body.scrollTop = 0; }, 10);
    } else {
      body.innerHTML = '<p style="font-size:1.2rem;">작업중</p>';
    }
  });
  // 갤러리
  bindModalTrigger('.gallery-item', 'gallery-modal', function(trigger) {
    var sectionName = trigger.getAttribute('data-section') || '갤러리';
    document.getElementById('gallery-modal-title').textContent = sectionName;
  });
  // 온라인 상담 모달 (카드 아이콘 클릭)
  bindModalTrigger('.contact-item .icon', 'consult-modal', function(trigger) {
    openModal('consult-modal');
  });
  if(document.getElementById('consult-modal-close')) {
    document.getElementById('consult-modal-close').onclick = function() {
      closeModal('consult-modal');
    };
  }
  if(document.getElementById('consult-modal')) {
    document.getElementById('consult-modal').onclick = function(e) {
      if (e.target === this) closeModal('consult-modal');
    };
  }
  // 상담 폼 제출
  if(document.getElementById('consult-form')) {
    document.getElementById('consult-form').addEventListener('submit', async function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      const agree = this.querySelector('input[name="agree_privacy"]').checked;
      const name = this.querySelector('input[name="name"]').value.trim();
      const email = this.querySelector('input[name="email"]').value.trim();
      const phone = this.querySelector('input[name="phone"]').value.trim();
      const title = this.querySelector('input[name="title"]').value.trim();
      const content = this.querySelector('textarea[name="content"]').value.trim();
      const reply_type = this.querySelector('input[name="reply_type"]:checked')?.value;
      const errorDiv = document.getElementById('consult-error');
      errorDiv.style.display = 'none';
      errorDiv.textContent = '';
      document.getElementById('consult-success').style.display = 'none';
      // 필수값 체크
      if (!name || !email || !phone || !title || !content) {
        errorDiv.textContent = '모든 항목을 입력해 주세요.';
        errorDiv.style.display = 'block';
        return;
      }
      // 이메일 유효성
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailPattern.test(email)) {
        errorDiv.textContent = '이메일 주소가 올바르지 않습니다.';
        errorDiv.style.display = 'block';
        return;
      }
      // 핸드폰번호 10자리 이상, 숫자만
      const phoneDigits = phone.replace(/\D/g, '');
      if (phoneDigits.length < 10) {
        errorDiv.textContent = '핸드폰번호는 숫자 10자리 이상 입력해 주세요.';
        errorDiv.style.display = 'block';
        return;
      }
      if (!agree) {
        errorDiv.textContent = '개인정보 활용동의가 있어야 접수 가능합니다.';
        errorDiv.style.display = 'block';
        return;
      }
      // 실제 전송 (fetch)
      try {
        const res = await fetch('/api/consult', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            name, email, phone, title, content,
            agree_privacy: agree,
            reply_type
          })
        });
        const data = await res.json();
        if (data.success) {
          closeModal('consult-modal');
          this.reset();
          this.querySelector('input[name="agree_privacy"]').checked = true;
          this.querySelector('input[name="reply_type"][value="email"]').checked = true;
          setTimeout(() => { openModal('consult-complete-modal'); }, 200);
        } else {
          errorDiv.textContent = data.error || '저장 중 오류가 발생했습니다.';
          errorDiv.style.display = 'block';
        }
      } catch (err) {
        errorDiv.textContent = '서버와 통신 중 오류가 발생했습니다.';
        errorDiv.style.display = 'block';
      }
    });
  }
  // 접수완료 안내 모달 닫기
  if(document.getElementById('consult-complete-close')) {
    document.getElementById('consult-complete-close').onclick = function() {
      closeModal('consult-complete-modal');
    };
  }
  if(document.getElementById('consult-complete-modal')) {
    document.getElementById('consult-complete-modal').onclick = function(e) {
      if (e.target === this) closeModal('consult-complete-modal');
    };
  }
  // 페이지 로드 완료 로그
  console.log('울진 아파트 홈페이지 로드 완료');
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