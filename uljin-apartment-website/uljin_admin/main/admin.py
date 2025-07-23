from django.contrib import admin
from django.utils.html import format_html
from django.urls import reverse
from django.utils.safestring import mark_safe
from .models import *

# 히어로 배너 관리
class HeroImageInline(admin.TabularInline):
    model = HeroImage
    extra = 1
    fields = ['image', 'order', 'is_active']

@admin.register(HeroBanner)
class HeroBannerAdmin(admin.ModelAdmin):
    list_display = ['title', 'subtitle', 'primary_button_text', 'secondary_button_text', 'updated_at']
    list_editable = ['primary_button_text', 'secondary_button_text']
    fieldsets = (
        ('기본 정보', {
            'fields': ('title', 'subtitle', 'description')
        }),
        ('버튼 설정', {
            'fields': ('primary_button_text', 'primary_button_link', 'secondary_button_text', 'secondary_button_link')
        }),
    )
    inlines = [HeroImageInline]

# 섹션 콘텐츠 관리
class SectionCardInline(admin.TabularInline):
    model = SectionCard
    extra = 1
    fields = ['title', 'subtitle', 'icon', 'order', 'is_active']

class SectionImageInline(admin.TabularInline):
    model = SectionImage
    extra = 1
    fields = ['title', 'image', 'description', 'order', 'is_active']

@admin.register(SectionContent)
class SectionContentAdmin(admin.ModelAdmin):
    list_display = ['section', 'title', 'updated_at']
    list_filter = ['section']
    fieldsets = (
        ('섹션 정보', {
            'fields': ('section', 'title', 'subtitle')
        }),
    )
    inlines = [SectionCardInline, SectionImageInline]

# 분양정보 관리
class SalesImageInline(admin.TabularInline):
    model = SalesImage
    extra = 1
    fields = ['title', 'image', 'order', 'is_active']

@admin.register(SalesInfo)
class SalesInfoAdmin(admin.ModelAdmin):
    list_display = ['type', 'price_per_pyeong', 'area', 'households', 'is_active']
    list_editable = ['price_per_pyeong', 'area', 'households', 'is_active']
    fieldsets = (
        ('기본 정보', {
            'fields': ('type', 'price_per_pyeong', 'area', 'households', 'floors', 'parking', 'is_active')
        }),
    )
    inlines = [SalesImageInline]

# 상담문의 관리
@admin.register(Consultation)
class ConsultationAdmin(admin.ModelAdmin):
    list_display = ['name', 'email', 'phone', 'title', 'reply_type', 'is_replied', 'is_deleted', 'created_at']
    list_filter = ['is_replied', 'is_deleted', 'reply_type', 'created_at']
    search_fields = ['name', 'email', 'phone', 'title', 'content']
    readonly_fields = ['created_at']
    fieldsets = (
        ('상담 정보', {
            'fields': ('name', 'email', 'phone', 'title', 'content', 'reply_type', 'agree_privacy')
        }),
        ('답변 정보', {
            'fields': ('reply_content', 'is_replied', 'replied_at')
        }),
        ('관리 정보', {
            'fields': ('is_deleted', 'created_at')
        }),
    )
    actions = ['mark_as_replied', 'mark_as_deleted']

    def mark_as_replied(self, request, queryset):
        from django.utils import timezone
        updated = queryset.update(is_replied=True, replied_at=timezone.now())
        self.message_user(request, f'{updated}개의 상담문의를 답변완료로 표시했습니다.')
    mark_as_replied.short_description = "선택된 상담문의를 답변완료로 표시"

    def mark_as_deleted(self, request, queryset):
        updated = queryset.update(is_deleted=True)
        self.message_user(request, f'{updated}개의 상담문의를 삭제로 표시했습니다.')
    mark_as_deleted.short_description = "선택된 상담문의를 삭제로 표시"

# 설문조사 관리
class SurveyQuestionInline(admin.TabularInline):
    model = SurveyQuestion
    extra = 1
    fields = ['question', 'question_type', 'allow_multiple', 'is_required', 'order']

class SurveyOptionInline(admin.TabularInline):
    model = SurveyOption
    extra = 2
    fields = ['option_text', 'order']

@admin.register(Survey)
class SurveyAdmin(admin.ModelAdmin):
    list_display = ['title', 'start_date', 'end_date', 'is_active', 'response_count', 'created_at']
    list_filter = ['is_active', 'start_date', 'end_date']
    search_fields = ['title', 'description']
    fieldsets = (
        ('설문 정보', {
            'fields': ('title', 'description', 'start_date', 'end_date', 'is_active')
        }),
    )
    inlines = [SurveyQuestionInline]

    def response_count(self, obj):
        return obj.responses.count()
    response_count.short_description = '응답 수'

@admin.register(SurveyQuestion)
class SurveyQuestionAdmin(admin.ModelAdmin):
    list_display = ['survey', 'question', 'question_type', 'is_required', 'order']
    list_filter = ['survey', 'question_type', 'is_required']
    search_fields = ['question']
    inlines = [SurveyOptionInline]

@admin.register(SurveyResponse)
class SurveyResponseAdmin(admin.ModelAdmin):
    list_display = ['survey', 'respondent_name', 'respondent_email', 'created_at']
    list_filter = ['survey', 'created_at']
    search_fields = ['respondent_name', 'respondent_email']
    readonly_fields = ['created_at']

@admin.register(SurveyAnswer)
class SurveyAnswerAdmin(admin.ModelAdmin):
    list_display = ['response', 'question', 'answer_text', 'selected_options_display']
    list_filter = ['question__survey', 'created_at']
    search_fields = ['answer_text']

    def selected_options_display(self, obj):
        return ', '.join([opt.option_text for opt in obj.selected_options.all()])
    selected_options_display.short_description = '선택된 옵션'

# 팝업 관리
@admin.register(Popup)
class PopupAdmin(admin.ModelAdmin):
    list_display = ['title', 'width', 'height', 'start_date', 'end_date', 'is_active']
    list_filter = ['is_active', 'start_date', 'end_date']
    search_fields = ['title', 'content']
    fieldsets = (
        ('팝업 정보', {
            'fields': ('title', 'content', 'image')
        }),
        ('팝업 설정', {
            'fields': ('width', 'height', 'button_text', 'button_position')
        }),
        ('링크 설정', {
            'fields': ('link_url', 'open_in_new_tab')
        }),
        ('표시 설정', {
            'fields': ('start_date', 'end_date', 'is_active')
        }),
    )

# 방문자 통계
@admin.register(VisitorLog)
class VisitorLogAdmin(admin.ModelAdmin):
    list_display = ['ip_address', 'page_visited', 'visited_at']
    list_filter = ['visited_at', 'page_visited']
    search_fields = ['ip_address', 'page_visited']
    readonly_fields = ['ip_address', 'user_agent', 'page_visited', 'visited_at']
    date_hierarchy = 'visited_at'

    def has_add_permission(self, request):
        return False

    def has_change_permission(self, request, obj=None):
        return False

# 관리자 사이트 설정
admin.site.site_header = "울진 아파트 관리자"
admin.site.site_title = "울진 아파트 관리"
admin.site.index_title = "관리자 대시보드"
