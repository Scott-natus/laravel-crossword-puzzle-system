// 울진 아파트 홈페이지 메인 JavaScript

// 히어로 롤링 배너
// Django static 경로로 이미지 경로 수정
const heroImages = window.heroImages || [];
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
    var section = trigger.getAttribute('data-section') || (trigger.querySelector('h3') ? trigger.querySelector('h3').textContent : '');
    var titleEl = document.getElementById('overview-modal-title');
    if (titleEl) titleEl.textContent = section;
  });
  // 입지환경
  bindModalTrigger('.feature-item', 'features-modal', function(trigger) {
    var section = trigger.getAttribute('data-section') || (trigger.querySelector('h4') ? trigger.querySelector('h4').textContent : '');
    var titleEl = document.getElementById('features-modal-title');
    if (titleEl) titleEl.textContent = section;
  });
  // 단지배치도 이미지 보기
  bindModalTrigger('.facility-image-view', 'facility-image-modal');
  // 분양정보 평면도
  bindModalTrigger('.btn-plan-small', 'plan-modal', function(trigger) {
    var type = trigger.getAttribute('data-type') || trigger.textContent;
    var titleEl = document.getElementById('plan-modal-title');
    if (titleEl) titleEl.textContent = type + ' 평면도';
    var body = document.getElementById('plan-modal-body');
    if (!body) return;
    
    // 모든 모달 이미지 숨기기
    const allImageGroups = body.querySelectorAll('.modal-images');
    allImageGroups.forEach(group => group.style.display = 'none');
    
    // 해당 타입의 이미지 그룹 찾기
    const targetImageGroup = body.querySelector(`[data-type="${type}"]`);
    if (targetImageGroup) {
      targetImageGroup.style.display = 'block';
    } else {
      // 이미지가 없으면 기본 메시지 표시
      body.innerHTML = '<p style="font-size:1.2rem;">작업중</p>';
    }
  });

  // 갤러리
  bindModalTrigger('.gallery-item', 'gallery-modal', function(trigger) {
    var overlay = trigger.querySelector('.gallery-overlay');
    var section = trigger.getAttribute('data-section') || (overlay ? overlay.textContent : '');
    var galleryModalTitle = document.getElementById('gallery-modal-title');
    if (galleryModalTitle) {
      galleryModalTitle.textContent = section;
    }
  });

  // 상담하기 모달
  bindModalTrigger('.contact-btn, .cta-secondary', 'consult-modal');
  bindModalTrigger('#consult-modal .modal-close', 'consult-modal', function() {
    closeModal('consult-modal');
  });

  // 상담 폼 제출 처리
  const consultForm = document.getElementById('consult-form');
  if (consultForm) {
    consultForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const formData = new FormData(consultForm);
      const data = {
        name: formData.get('name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        title: formData.get('title'),
        content: formData.get('content'),
        reply_type: formData.get('reply_type'),
        agree_privacy: formData.get('agree_privacy') === 'on'
      };
      
      try {
        const response = await fetch('/submit-consultation/', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
          // 성공 시 완료 모달 표시
          closeModal('consult-modal');
          openModal('consult-complete-modal');
          consultForm.reset();
        } else {
          // 에러 메시지 표시
          document.getElementById('consult-error').textContent = result.message;
          document.getElementById('consult-error').style.display = 'block';
          document.getElementById('consult-success').style.display = 'none';
        }
      } catch (error) {
        console.error('상담 폼 제출 오류:', error);
        document.getElementById('consult-error').textContent = '오류가 발생했습니다. 다시 시도해주세요.';
        document.getElementById('consult-error').style.display = 'block';
        document.getElementById('consult-success').style.display = 'none';
      }
    });
  }

  // 상담 완료 모달 닫기
  const consultCompleteClose = document.getElementById('consult-complete-close');
  if (consultCompleteClose) {
    consultCompleteClose.addEventListener('click', function() {
      closeModal('consult-complete-modal');
    });
  }

  // 프로젝트 개요 카드 클릭 이벤트
  document.querySelectorAll('.overview-card').forEach(card => {
    card.addEventListener('click', function() {
      const section = this.getAttribute('data-section');
      if (section) {
        openModal('overview-modal', section);
      }
    });
  });

  // 프로젝트 개요 모달 닫기
  const overviewModalClose = document.getElementById('overview-modal-close');
  if (overviewModalClose) {
    overviewModalClose.addEventListener('click', function() {
      closeModal('overview-modal');
    });
  }

  // 입지환경 카드 클릭 이벤트
  document.querySelectorAll('.feature-item').forEach(card => {
    card.addEventListener('click', function() {
      const section = this.getAttribute('data-section');
      if (section) {
        openModal('features-modal', section);
      }
    });
  });

  // 입지환경 모달 닫기
  const featuresModalClose = document.getElementById('features-modal-close');
  if (featuresModalClose) {
    featuresModalClose.addEventListener('click', function() {
      closeModal('features-modal');
    });
  }

  // 갤러리 모달
  function openGalleryModal(imageSrc, title) {
    document.getElementById('gallery-modal-image').src = imageSrc;
    document.getElementById('gallery-modal-title').textContent = title;
    document.getElementById('gallery-modal').style.display = 'flex';
  }
  
  // 갤러리 모달 닫기
  var galleryModalClose = document.getElementById('gallery-modal-close');
  if (galleryModalClose) {
    galleryModalClose.addEventListener('click', function() {
      var galleryModal = document.getElementById('gallery-modal');
      if (galleryModal) galleryModal.style.display = 'none';
    });
  }
  
  // 갤러리 모달 외부 클릭 시 닫기
  var galleryModal = document.getElementById('gallery-modal');
  if (galleryModal) {
    galleryModal.addEventListener('click', function(e) {
      if (e.target === this) {
        this.style.display = 'none';
      }
    });
  }

  // 갤러리 3카드 구조 - 카드별 모달 (window에 전역 선언)
  window.openGalleryCardModal = function(cardId) {
    document.querySelectorAll('.gallery-card-modal-images').forEach(function(el) {
      el.style.display = 'none';
    });
    var target = document.querySelector('.gallery-card-modal-images[data-card-id="' + cardId + '"]');
    if(target) target.style.display = 'block';
    var modal = document.getElementById('gallery-card-modal');
    if (modal) modal.style.display = 'flex';
  };

  var closeBtn = document.getElementById('gallery-card-modal-close');
  if (closeBtn) {
    closeBtn.onclick = function() {
      var modal = document.getElementById('gallery-card-modal');
      if (modal) modal.style.display = 'none';
    }
  }
  var modalEl = document.getElementById('gallery-card-modal');
  if (modalEl) {
    modalEl.addEventListener('click', function(e) {
      if(e.target === this) this.style.display = 'none';
    });
  }

  // 갤러리 카드 클릭 모달 (점검용 로그 포함, 이미지 그룹 표시 추가)
  bindModalTrigger('.gallery-item[data-card-id]', 'gallery-card-modal', function(trigger) {
    var cardId = trigger.getAttribute('data-card-id');
    var overlay = trigger.querySelector('.gallery-overlay');
    var section = trigger.getAttribute('data-section') || (overlay ? overlay.textContent : '');
    var galleryModalTitle = document.getElementById('gallery-modal-title');
    // 점검용 로그 추가
    console.log('[DEBUG] 카드 클릭:', cardId);
    var modal = document.getElementById('gallery-card-modal');
    if (modal) {
      console.log('[DEBUG] 모달 DOM 있음, display:', modal.style.display, 'z-index:', modal.style.zIndex);
    } else {
      console.log('[DEBUG] 모달 DOM 없음 (#gallery-card-modal)');
    }
    if (galleryModalTitle) {
      galleryModalTitle.textContent = section;
    }
    // 모든 이미지 그룹 숨기기
    document.querySelectorAll('.gallery-card-modal-images').forEach(function(el) {
      el.style.display = 'none';
    });
    // 해당 카드만 표시
    var target = document.querySelector('.gallery-card-modal-images[data-card-id="' + cardId + '"]');
    if (target) target.style.display = 'block';
  });

  // 모바일 메뉴 초기화
  initMobileMenu();
  
  // 헤더 스크롤 효과 초기화
  initHeaderScroll();
  
  // 스크롤 애니메이션
  initScrollAnimations();
  
  // 이미지 지연 로딩 초기화
  initLazyLoading();
});

// 모달 열기
function openModal(modalId) {
  var modal = document.getElementById(modalId);
  if (modal) {
    modal.removeAttribute('style'); // 인라인 스타일 완전 제거
    modal.style.setProperty('display', 'flex', 'important');
    console.log('[DEBUG] openModal 호출:', modalId);
  }
}

// 모바일 메뉴 초기화
function initMobileMenu() {
  const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
  const navMenu = document.querySelector('.nav-menu');
  
  if (mobileMenuBtn && navMenu) {
    mobileMenuBtn.addEventListener('click', function() {
      navMenu.classList.toggle('active');
      mobileMenuBtn.classList.toggle('active');
    });
  }
}

// 헤더 스크롤 효과
function initHeaderScroll() {
  const header = document.querySelector('.header');
  let lastScrollTop = 0;
  
  window.addEventListener('scroll', function() {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    
    if (scrollTop > 100) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
    
    lastScrollTop = scrollTop;
  });
}

// 스크롤 애니메이션
function initScrollAnimations() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('fade-in');
      }
    });
  }, {
    threshold: 0.1
  });
  
  document.querySelectorAll('.overview-card, .feature-item, .sales-card, .gallery-item, .contact-item').forEach(el => {
    // el.style.opacity = '0';
    // el.style.transform = 'translateY(20px)';
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

// 페이지 로드 완료 시 초기화
window.addEventListener('load', function() {
  // 페이지 로드 완료 후 추가 초기화 작업
  console.log('울진 아파트 홈페이지 로드 완료');
}); 

// 온라인 상담 모달 열기/닫기 기능 추가 (8000 포트용)
document.addEventListener('DOMContentLoaded', function() {
  // 상담문의(문의) 버튼 클릭 시 모달 열기
  document.querySelectorAll('.contact-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      var modal = document.getElementById('consult-modal');
      if (modal) modal.style.display = 'flex';
    });
  });
  // 모달 닫기 (X 버튼)
  var closeBtn = document.querySelector('#consult-modal .modal-close');
  if (closeBtn) {
    closeBtn.addEventListener('click', function() {
      var modal = document.getElementById('consult-modal');
      if (modal) modal.style.display = 'none';
    });
  }
  // 모달 배경 클릭 시 닫기
  var consultModal = document.getElementById('consult-modal');
  if (consultModal) {
    consultModal.addEventListener('click', function(e) {
      if (e.target === this) this.style.display = 'none';
    });
  }
  // 하단 [온라인상담] 카드 클릭 시에도 모달 열기
  document.querySelectorAll('.contact-item h4, .contact-item .icon, .contact-item').forEach(function(el) {
    if (el.textContent && el.textContent.includes('온라인 상담')) {
      el.addEventListener('click', function(e) {
        var modal = document.getElementById('consult-modal');
        if (modal) modal.style.display = 'flex';
      });
    }
  });
}); 