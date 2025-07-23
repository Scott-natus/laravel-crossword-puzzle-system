from django.db import models
from django.contrib.auth.models import User
from django.utils import timezone
import os

# 이미지 업로드 경로 함수
def get_upload_path(instance, filename):
    return f'uploads/{instance.__class__.__name__.lower()}/{filename}'

# 히어로 배너 관리
class HeroBanner(models.Model):
    title = models.CharField(max_length=200, verbose_name='메인 타이틀')
    subtitle = models.CharField(max_length=200, verbose_name='서브 타이틀')
    description = models.TextField(verbose_name='설명')
    primary_button_text = models.CharField(max_length=50, default='분양정보 보기', verbose_name='주요 버튼 텍스트')
    primary_button_link = models.CharField(max_length=200, default='#sales', verbose_name='주요 버튼 링크')
    secondary_button_text = models.CharField(max_length=50, default='상담 신청', verbose_name='보조 버튼 텍스트')
    secondary_button_link = models.CharField(max_length=200, default='#contact', verbose_name='보조 버튼 링크')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        verbose_name = '히어로 배너'
        verbose_name_plural = '히어로 배너'

    def __str__(self):
        return f"히어로 배너 - {self.title}"

class HeroImage(models.Model):
    banner = models.ForeignKey(HeroBanner, on_delete=models.CASCADE, related_name='images')
    image = models.ImageField(upload_to=get_upload_path, verbose_name='배너 이미지')
    order = models.IntegerField(default=0, verbose_name='순서')
    is_active = models.BooleanField(default=True, verbose_name='활성화')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = '히어로 이미지'
        verbose_name_plural = '히어로 이미지'
        ordering = ['order']

    def __str__(self):
        return f"히어로 이미지 {self.order}"

# 섹션별 콘텐츠 관리
class SectionContent(models.Model):
    SECTION_CHOICES = [
        ('overview', '프로젝트 개요'),
        ('features', '입지환경'),
        ('site_plan', '단지배치도'),
        ('sales', '분양정보'),
        ('gallery', '갤러리'),
    ]
    
    section = models.CharField(max_length=20, choices=SECTION_CHOICES, verbose_name='섹션')
    title = models.CharField(max_length=200, verbose_name='제목')
    subtitle = models.TextField(verbose_name='서브 제목')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        verbose_name = '섹션 콘텐츠'
        verbose_name_plural = '섹션 콘텐츠'
        unique_together = ['section']

    def __str__(self):
        return f"{self.get_section_display()} - {self.title}"

class SectionCard(models.Model):
    section_content = models.ForeignKey(SectionContent, on_delete=models.CASCADE, related_name='cards')
    title = models.CharField(max_length=100, verbose_name='카드 제목')
    subtitle = models.TextField(verbose_name='카드 서브 제목')
    icon = models.CharField(max_length=50, default='📋', verbose_name='아이콘')
    order = models.IntegerField(default=0, verbose_name='순서')
    is_active = models.BooleanField(default=True, verbose_name='활성화')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = '섹션 카드'
        verbose_name_plural = '섹션 카드'
        ordering = ['order']

    def __str__(self):
        return f"{self.section_content.get_section_display()} - {self.title}"

class SectionImage(models.Model):
    section_content = models.ForeignKey(SectionContent, on_delete=models.CASCADE, related_name='images')
    card = models.ForeignKey(SectionCard, on_delete=models.CASCADE, related_name='modal_images', null=True, blank=True, verbose_name='연결된 카드')
    title = models.CharField(max_length=100, verbose_name='이미지 제목')
    image = models.ImageField(upload_to=get_upload_path, verbose_name='이미지')
    description = models.TextField(blank=True, verbose_name='설명')
    order = models.IntegerField(default=0, verbose_name='순서')
    is_active = models.BooleanField(default=True, verbose_name='활성화')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = '섹션 이미지'
        verbose_name_plural = '섹션 이미지'
        ordering = ['order']

    def __str__(self):
        return f"{self.section_content.get_section_display()} - {self.title}"

# 분양정보 관리
class SalesInfo(models.Model):
    TYPE_CHOICES = [
        ('84A', '84A 타입'),
        ('84B', '84B 타입'),
    ]
    
    type = models.CharField(max_length=10, choices=TYPE_CHOICES, verbose_name='타입')
    price_per_pyeong = models.CharField(max_length=50, verbose_name='평당 가격')
    area = models.CharField(max_length=50, verbose_name='전용면적')
    households = models.CharField(max_length=50, verbose_name='세대수')
    floors = models.CharField(max_length=50, verbose_name='층수')
    parking = models.CharField(max_length=50, verbose_name='주차')
    is_active = models.BooleanField(default=True, verbose_name='활성화')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        verbose_name = '분양정보'
        verbose_name_plural = '분양정보'
        unique_together = ['type']

    def __str__(self):
        return f"{self.get_type_display()} - {self.price_per_pyeong}"

class SalesImage(models.Model):
    sales_info = models.ForeignKey(SalesInfo, on_delete=models.CASCADE, related_name='images')
    title = models.CharField(max_length=100, verbose_name='이미지 제목')
    image = models.ImageField(upload_to=get_upload_path, verbose_name='평면도 이미지')
    order = models.IntegerField(default=0, verbose_name='순서')
    is_active = models.BooleanField(default=True, verbose_name='활성화')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = '분양 이미지'
        verbose_name_plural = '분양 이미지'
        ordering = ['order']

    def __str__(self):
        return f"{self.sales_info.get_type_display()} - {self.title}"

# 갤러리 카드(3개 고정)
class GalleryCard(models.Model):
    title = models.CharField(max_length=100, verbose_name='카드 타이틀')
    subtitle = models.CharField(max_length=200, verbose_name='카드 서브타이틀')
    order = models.IntegerField(default=0, verbose_name='순서')
    is_active = models.BooleanField(default=True, verbose_name='활성화')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = '갤러리 카드'
        verbose_name_plural = '갤러리 카드'
        ordering = ['order']

    def __str__(self):
        return f"갤러리카드-{self.order}: {self.title}"

# 갤러리 이미지(카드별 연결)
class GalleryImage(models.Model):
    card = models.ForeignKey(GalleryCard, on_delete=models.CASCADE, related_name='images', verbose_name='연결 카드', null=True, blank=True)
    title = models.CharField(max_length=100, verbose_name='이미지 제목', blank=True)
    image = models.ImageField(upload_to=get_upload_path, verbose_name='갤러리 이미지')
    order = models.IntegerField(default=0, verbose_name='순서')
    is_active = models.BooleanField(default=True, verbose_name='활성화')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = '갤러리 이미지'
        verbose_name_plural = '갤러리 이미지'
        ordering = ['order']

    def __str__(self):
        return f"{self.card.title} - {self.title}"

# 상담문의 관리
class Consultation(models.Model):
    REPLY_TYPE_CHOICES = [
        ('email', '메일'),
        ('sms', '문자'),
    ]
    
    name = models.CharField(max_length=100, verbose_name='이름')
    email = models.EmailField(verbose_name='이메일')
    phone = models.CharField(max_length=20, verbose_name='핸드폰번호')
    title = models.CharField(max_length=200, verbose_name='제목')
    content = models.TextField(verbose_name='내용')
    reply_type = models.CharField(max_length=10, choices=REPLY_TYPE_CHOICES, default='email', verbose_name='답변받기 선택')
    agree_privacy = models.BooleanField(default=True, verbose_name='개인정보 동의')
    reply_content = models.TextField(blank=True, verbose_name='답변 내용')
    is_replied = models.BooleanField(default=False, verbose_name='답변 완료')
    is_deleted = models.BooleanField(default=False, verbose_name='삭제 여부')
    created_at = models.DateTimeField(auto_now_add=True)
    replied_at = models.DateTimeField(null=True, blank=True, verbose_name='답변일')

    class Meta:
        verbose_name = '상담문의'
        verbose_name_plural = '상담문의'
        ordering = ['-created_at']

    def __str__(self):
        return f"{self.name} - {self.title}"

# 설문조사 관리
class Survey(models.Model):
    title = models.CharField(max_length=200, verbose_name='설문 제목')
    description = models.TextField(verbose_name='설문 설명')
    start_date = models.DateTimeField(verbose_name='시작일')
    end_date = models.DateTimeField(verbose_name='종료일')
    is_active = models.BooleanField(default=True, verbose_name='활성화')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        verbose_name = '설문조사'
        verbose_name_plural = '설문조사'
        ordering = ['-created_at']

    def __str__(self):
        return self.title

class SurveyQuestion(models.Model):
    QUESTION_TYPE_CHOICES = [
        ('multiple_choice', '객관식'),
        ('checkbox', '다중선택'),
        ('text', '주관식'),
    ]
    
    survey = models.ForeignKey(Survey, on_delete=models.CASCADE, related_name='questions')
    question = models.CharField(max_length=500, verbose_name='질문')
    question_type = models.CharField(max_length=20, choices=QUESTION_TYPE_CHOICES, verbose_name='질문 타입')
    allow_multiple = models.BooleanField(default=False, verbose_name='다중 답변 허용')
    order = models.IntegerField(default=0, verbose_name='순서')
    is_required = models.BooleanField(default=True, verbose_name='필수 여부')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = '설문 질문'
        verbose_name_plural = '설문 질문'
        ordering = ['order']

    def __str__(self):
        return f"{self.survey.title} - {self.question}"

class SurveyOption(models.Model):
    question = models.ForeignKey(SurveyQuestion, on_delete=models.CASCADE, related_name='options')
    option_text = models.CharField(max_length=200, verbose_name='선택지')
    order = models.IntegerField(default=0, verbose_name='순서')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = '설문 선택지'
        verbose_name_plural = '설문 선택지'
        ordering = ['order']

    def __str__(self):
        return f"{self.question.question} - {self.option_text}"

class SurveyResponse(models.Model):
    survey = models.ForeignKey(Survey, on_delete=models.CASCADE, related_name='responses')
    respondent_name = models.CharField(max_length=100, verbose_name='응답자 이름')
    respondent_email = models.EmailField(blank=True, verbose_name='응답자 이메일')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = '설문 응답'
        verbose_name_plural = '설문 응답'
        ordering = ['-created_at']

    def __str__(self):
        return f"{self.survey.title} - {self.respondent_name}"

class SurveyAnswer(models.Model):
    response = models.ForeignKey(SurveyResponse, on_delete=models.CASCADE, related_name='answers')
    question = models.ForeignKey(SurveyQuestion, on_delete=models.CASCADE)
    answer_text = models.TextField(blank=True, verbose_name='답변 텍스트')
    selected_options = models.ManyToManyField(SurveyOption, blank=True, verbose_name='선택된 옵션')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = '설문 답변'
        verbose_name_plural = '설문 답변'

    def __str__(self):
        return f"{self.response.respondent_name} - {self.question.question}"

# 팝업 관리
class Popup(models.Model):
    title = models.CharField(max_length=200, verbose_name='팝업 제목')
    content = models.TextField(verbose_name='팝업 내용')
    image = models.ImageField(upload_to=get_upload_path, blank=True, verbose_name='팝업 이미지')
    width = models.IntegerField(default=400, verbose_name='팝업 너비')
    height = models.IntegerField(default=300, verbose_name='팝업 높이')
    button_text = models.CharField(max_length=50, default='닫기', verbose_name='버튼 텍스트')
    button_position = models.CharField(max_length=20, default='center', verbose_name='버튼 위치')
    link_url = models.CharField(max_length=200, blank=True, verbose_name='링크 URL')
    open_in_new_tab = models.BooleanField(default=False, verbose_name='새 탭에서 열기')
    start_date = models.DateTimeField(verbose_name='시작일')
    end_date = models.DateTimeField(verbose_name='종료일')
    is_active = models.BooleanField(default=True, verbose_name='활성화')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        verbose_name = '팝업'
        verbose_name_plural = '팝업'
        ordering = ['-created_at']

    def __str__(self):
        return self.title

# 방문자 통계
class VisitorLog(models.Model):
    ip_address = models.GenericIPAddressField(verbose_name='IP 주소')
    user_agent = models.TextField(verbose_name='사용자 에이전트')
    page_visited = models.CharField(max_length=200, verbose_name='방문 페이지')
    visited_at = models.DateTimeField(auto_now_add=True, verbose_name='방문 시간')

    class Meta:
        verbose_name = '방문자 로그'
        verbose_name_plural = '방문자 로그'
        ordering = ['-visited_at']

    def __str__(self):
        return f"{self.ip_address} - {self.page_visited}"
