from django.shortcuts import render, redirect
from django.http import JsonResponse
from django.views.decorators.csrf import csrf_exempt
from django.utils import timezone
from django.contrib import messages
from .models import (
    HeroBanner, HeroImage, SectionContent, SectionCard, SectionImage,
    SalesInfo, SalesImage, GalleryImage, Consultation, Survey, SurveyQuestion,
    SurveyOption, SurveyResponse, SurveyAnswer, Popup, VisitorLog, GalleryCard
)
import json
from django.db import models
from django.utils import timezone
from django.shortcuts import get_object_or_404, redirect
from django.contrib.auth.decorators import login_required

def get_client_ip(request):
    x_forwarded_for = request.META.get('HTTP_X_FORWARDED_FOR')
    if x_forwarded_for:
        ip = x_forwarded_for.split(',')[0]
    else:
        ip = request.META.get('REMOTE_ADDR')
    return ip

def index(request):
    # 방문자 로그 기록
    client_ip = get_client_ip(request)
    user_agent = request.META.get('HTTP_USER_AGENT', '')
    
    # 중복 방문 체크 (같은 IP에서 1시간 내 방문은 제외)
    one_hour_ago = timezone.now() - timezone.timedelta(hours=1)
    existing_visit = VisitorLog.objects.filter(
        ip_address=client_ip,
        visited_at__gte=one_hour_ago
    ).first()
    
    if not existing_visit:
        VisitorLog.objects.create(
            ip_address=client_ip,
            user_agent=user_agent,
            page_visited=request.path
        )
    
    # 동적 콘텐츠 가져오기
    hero_banner = HeroBanner.objects.first()
    hero_images = HeroImage.objects.filter(is_active=True).order_by('order')
    overview_section = SectionContent.objects.filter(section='overview').first()
    overview_cards = SectionCard.objects.filter(section_content=overview_section).order_by('order') if overview_section else []
    
    # 카드별 이미지 데이터 준비
    overview_cards_with_images = []
    if overview_section:
        for card in overview_cards:
            card_images = list(SectionImage.objects.filter(card=card).order_by('order'))
            card.images = card_images  # 카드 객체에 이미지 리스트 추가
            overview_cards_with_images.append(card)
    
    # 입지환경 데이터 준비
    features_section = SectionContent.objects.filter(section='features').first()
    features_cards_with_images = []
    if features_section:
        features_cards = SectionCard.objects.filter(section_content=features_section).order_by('order')
        for card in features_cards:
            card_images = list(SectionImage.objects.filter(card=card).order_by('order'))
            card.images = card_images  # 카드 객체에 이미지 리스트 추가
            features_cards_with_images.append(card)
    
    # 단지배치도 데이터 준비
    site_plan_cards_with_images = []
    site_plan_section = SectionContent.objects.filter(section='site_plan').first()
    site_plan_images = []  # 이미지보기 버튼용 이미지
    if site_plan_section:
        site_plan_cards = SectionCard.objects.filter(section_content=site_plan_section).order_by('order')
        for card in site_plan_cards:
            card_images = list(SectionImage.objects.filter(card=card).order_by('order'))
            card.images = card_images  # 카드 객체에 이미지 리스트 추가
            site_plan_cards_with_images.append(card)
        
        # 이미지보기 버튼용 이미지 (card=None인 이미지들)
        site_plan_images = list(SectionImage.objects.filter(section_content=site_plan_section, card__isnull=True).order_by('order'))
    
    gallery_section = SectionContent.objects.filter(section='gallery').first()
    gallery_images = GalleryImage.objects.all().order_by('order')
    sales_section = SectionContent.objects.filter(section='sales').first()
    sales_infos = SalesInfo.objects.all()
    sales_images = {}
    for sales_info in sales_infos:
        sales_images[sales_info.type] = list(SalesImage.objects.filter(sales_info=sales_info).order_by('order'))
    active_popup = Popup.objects.filter(is_active=True, start_date__lte=timezone.now(), end_date__gte=timezone.now()).first()
    
    gallery_cards = GalleryCard.objects.order_by('order')
    gallery_images_by_card = {card.id: list(card.images.order_by('order')) for card in gallery_cards}
    card_colors = ['#C94A36', '#4BAA4B', '#7B4BAA']
    context = {
        'hero_banner': hero_banner,
        'hero_images': hero_images,
        'overview_section': overview_section,
        'overview_cards': overview_cards_with_images,
        'features_section': features_section,
        'features_cards': features_cards_with_images,
        'site_plan_section': site_plan_section,
        'site_plan_cards': site_plan_cards_with_images,
        'site_plan_images': site_plan_images,
        'sales_section': sales_section,
        'sales_infos': sales_infos,
        'sales_images': sales_images,
        'gallery_section': gallery_section,
        'gallery_images': gallery_images,
        'active_popup': active_popup,
        'gallery_cards': gallery_cards,
        'gallery_images_by_card': gallery_images_by_card,
        'gallery_card_colors': card_colors,
    }
    
    return render(request, 'main/index.html', context)

@csrf_exempt
def submit_consultation(request):
    if request.method == 'POST':
        try:
            data = json.loads(request.body)
            
            consultation = Consultation.objects.create(
                name=data.get('name'),
                email=data.get('email'),
                phone=data.get('phone'),
                title=data.get('title'),
                content=data.get('content'),
                reply_type=data.get('reply_type', 'email'),
                agree_privacy=data.get('agree_privacy', True)
            )
            
            return JsonResponse({
                'success': True,
                'message': '상담 신청이 접수되었습니다.'
            })
            
        except Exception as e:
            return JsonResponse({
                'success': False,
                'message': f'오류가 발생했습니다: {str(e)}'
            })
    
    return JsonResponse({
        'success': False,
        'message': '잘못된 요청입니다.'
    })

def admin_dashboard(request):
    """관리자 대시보드 뷰"""
    if not request.user.is_authenticated:
        return redirect('admin:login')
    
    # 최근 상담문의
    recent_consultations = Consultation.objects.filter(
        is_deleted=False
    ).order_by('-created_at')[:5]
    
    # 진행중인 설문조사
    active_surveys = Survey.objects.filter(
        is_active=True,
        start_date__lte=timezone.now(),
        end_date__gte=timezone.now()
    )
    
    # 지난 일주일 방문자 통계
    from datetime import timedelta
    week_ago = timezone.now() - timedelta(days=7)
    visitor_stats = VisitorLog.objects.filter(
        visited_at__gte=week_ago
    ).count()
    
    context = {
        'recent_consultations': recent_consultations,
        'active_surveys': active_surveys,
        'visitor_stats': visitor_stats,
    }
    
    return render(request, 'admin/dashboard.html', context)

# ====== 커스텀 관리자 대시보드 ======
@login_required(login_url='/manage/login/')
def manage_welcome(request):
    # 추후: 진행중인 설문조사, 방문자 추이 등 데이터 context에 추가
    return render(request, 'manage/welcome.html')

# ====== 히어로 배너 관리 ======
@login_required(login_url='/manage/login/')
def manage_hero_banner(request):
    from django.forms import modelform_factory, modelformset_factory
    HeroBannerForm = modelform_factory(HeroBanner, fields=[
        'title', 'subtitle', 'description',
        'primary_button_text', 'primary_button_link',
        'secondary_button_text', 'secondary_button_link',
    ])
    HeroImageFormSet = modelformset_factory(HeroImage, fields=['image', 'order', 'is_active'], extra=0, can_delete=True)
    banner = HeroBanner.objects.first()
    # 배너 정보 저장
    if request.method == 'POST' and 'save_banner' in request.POST:
        form = HeroBannerForm(request.POST, instance=banner)
        if form.is_valid():
            form.save()
            messages.success(request, '히어로 배너 정보가 저장되었습니다.')
            return redirect('manage_hero_banner')
    else:
        form = HeroBannerForm(instance=banner)
    # 이미지 업로드
    if request.method == 'POST' and 'upload_image' in request.POST and banner:
        files = request.FILES.getlist('images')
        for idx, f in enumerate(files):
            HeroImage.objects.create(banner=banner, image=f, order=HeroImage.objects.filter(banner=banner).count()+idx)
        messages.success(request, f'{len(files)}개 이미지가 업로드되었습니다.')
        return redirect('manage_hero_banner')
    # 이미지 삭제
    if request.method == 'POST' and 'delete_image' in request.POST:
        img_id = request.POST.get('delete_image')
        HeroImage.objects.filter(id=img_id).delete()
        messages.success(request, '이미지가 삭제되었습니다.')
        return redirect('manage_hero_banner')
    # 순서 변경
    if request.method == 'POST' and 'move_image' in request.POST:
        img_id = int(request.POST.get('move_image'))
        direction = request.POST.get('direction')
        img = HeroImage.objects.get(id=img_id)
        images = list(HeroImage.objects.filter(banner=banner).order_by('order'))
        idx = images.index(img)
        if direction == 'up' and idx > 0:
            images[idx].order, images[idx-1].order = images[idx-1].order, images[idx].order
            images[idx].save(); images[idx-1].save()
        elif direction == 'down' and idx < len(images)-1:
            images[idx].order, images[idx+1].order = images[idx+1].order, images[idx].order
            images[idx].save(); images[idx+1].save()
        return redirect('manage_hero_banner')
    images = HeroImage.objects.filter(banner=banner).order_by('order') if banner else []
    return render(request, 'manage/hero_banner.html', {
        'form': form,
        'banner': banner,
        'images': images,
    })

@login_required(login_url='/manage/login/')
def manage_project_overview(request):
    from django.forms import modelform_factory, modelformset_factory
    
    # 프로젝트 개요 섹션 가져오기
    overview_section = SectionContent.objects.filter(section='overview').first()
    
    # 프로젝트 개요 폼
    SectionContentForm = modelform_factory(SectionContent, fields=[
        'title', 'subtitle'
    ])
    
    # 카드 폼셋
    SectionCardFormSet = modelformset_factory(
        SectionCard, 
        fields=['title', 'subtitle', 'icon'],
        extra=0,
        can_delete=True
    )
    
    # 이미지 폼셋 (카드별로 분리)
    SectionImageFormSet = modelformset_factory(
        SectionImage,
        fields=['image', 'title', 'description'],
        extra=1,
        can_delete=True
    )
    
    if request.method == 'POST':
        if 'save_section' in request.POST:
            form = SectionContentForm(request.POST, instance=overview_section)
            if form.is_valid():
                form.save()
                messages.success(request, '프로젝트 개요 섹션이 저장되었습니다.')
                return redirect('manage_project_overview')
        elif 'save_cards' in request.POST:
            formset = SectionCardFormSet(request.POST, prefix='cards')
            if formset.is_valid():
                instances = formset.save(commit=False)
                for instance in instances:
                    instance.section_content = overview_section
                    instance.save()
                for obj in formset.deleted_objects:
                    obj.delete()
                messages.success(request, '카드 정보가 저장되었습니다.')
                return redirect('manage_project_overview')
        elif 'save_images' in request.POST:
            card_id = request.POST.get('card_id')
            card = SectionCard.objects.get(id=card_id) if card_id else None
            
            if card:
                # 새 이미지 추가
                new_image = request.FILES.get('new_image')
                new_image_title = request.POST.get('new_image_title')
                new_image_description = request.POST.get('new_image_description')
                
                if new_image and new_image_title:
                    # 가장 높은 order 값 찾기
                    max_order = SectionImage.objects.filter(card=card).aggregate(
                        models.Max('order')
                    )['order__max'] or 0
                    
                    SectionImage.objects.create(
                        section_content=overview_section,
                        card=card,
                        title=new_image_title,
                        description=new_image_description,
                        image=new_image,
                        order=max_order + 1
                    )
                
                # 기존 이미지 삭제
                for key, value in request.POST.items():
                    if key.startswith('delete_image_') and value == 'on':
                        image_id = key.replace('delete_image_', '')
                        try:
                            image = SectionImage.objects.get(id=image_id, card=card)
                            image.delete()
                        except SectionImage.DoesNotExist:
                            pass
                
                messages.success(request, f'카드 "{card.title}"의 이미지가 저장되었습니다.')
            
            return redirect('manage_project_overview')
    else:
        form = SectionContentForm(instance=overview_section)
        formset = SectionCardFormSet(
            queryset=SectionCard.objects.filter(section_content=overview_section).order_by('order'),
            prefix='cards'
        )
    
    # 카드별 이미지 데이터 준비
    cards = SectionCard.objects.filter(section_content=overview_section).order_by('order')
    card_images = {}
    for card in cards:
        card_images[card.id] = SectionImage.objects.filter(card=card).order_by('order')
    
    return render(request, 'manage/project_overview.html', {
        'form': form,
        'formset': formset,
        'section': overview_section,
        'cards': cards,
        'card_images': card_images,
    })

@login_required(login_url='/manage/login/')
def manage_location_environment(request):
    from django.forms import modelform_factory, modelformset_factory
    
    # 입지환경 섹션 가져오기 (features 섹션 사용)
    features_section = SectionContent.objects.filter(section='features').first()
    
    # 입지환경 폼
    SectionContentForm = modelform_factory(SectionContent, fields=[
        'title', 'subtitle'
    ])
    
    # 카드 폼셋
    SectionCardFormSet = modelformset_factory(
        SectionCard, 
        fields=['title', 'subtitle', 'icon'],
        extra=0,
        can_delete=True
    )
    
    # 이미지 폼셋 (카드별로 분리)
    SectionImageFormSet = modelformset_factory(
        SectionImage,
        fields=['image', 'title', 'description'],
        extra=1,
        can_delete=True
    )
    
    if request.method == 'POST':
        if 'save_section' in request.POST:
            form = SectionContentForm(request.POST, instance=features_section)
            if form.is_valid():
                form.save()
                messages.success(request, '입지환경 섹션이 저장되었습니다.')
                return redirect('manage_location_environment')
        elif 'save_cards' in request.POST:
            formset = SectionCardFormSet(request.POST, prefix='cards')
            if formset.is_valid():
                instances = formset.save(commit=False)
                for instance in instances:
                    instance.section_content = features_section
                    instance.save()
                for obj in formset.deleted_objects:
                    obj.delete()
                messages.success(request, '카드 정보가 저장되었습니다.')
                return redirect('manage_location_environment')
        elif 'save_images' in request.POST:
            card_id = request.POST.get('card_id')
            card = SectionCard.objects.get(id=card_id) if card_id else None
            
            if card:
                # 새 이미지 추가
                new_image = request.FILES.get('new_image')
                new_image_title = request.POST.get('new_image_title')
                new_image_description = request.POST.get('new_image_description')
                
                if new_image and new_image_title:
                    # 가장 높은 order 값 찾기
                    max_order = SectionImage.objects.filter(card=card).aggregate(
                        models.Max('order')
                    )['order__max'] or 0
                    
                    SectionImage.objects.create(
                        section_content=features_section,
                        card=card,
                        title=new_image_title,
                        description=new_image_description,
                        image=new_image,
                        order=max_order + 1
                    )
                
                # 기존 이미지 삭제
                for key, value in request.POST.items():
                    if key.startswith('delete_image_') and value == 'on':
                        image_id = key.replace('delete_image_', '')
                        try:
                            image = SectionImage.objects.get(id=image_id, card=card)
                            image.delete()
                        except SectionImage.DoesNotExist:
                            pass
                
                messages.success(request, f'카드 "{card.title}"의 이미지가 저장되었습니다.')
            
            return redirect('manage_location_environment')
    else:
        form = SectionContentForm(instance=features_section)
        formset = SectionCardFormSet(
            queryset=SectionCard.objects.filter(section_content=features_section).order_by('order'),
            prefix='cards'
        )
    
    # 카드별 이미지 데이터 준비
    cards = SectionCard.objects.filter(section_content=features_section).order_by('order')
    card_images = {}
    for card in cards:
        card_images[card.id] = SectionImage.objects.filter(card=card).order_by('order')
    
    return render(request, 'manage/location_environment.html', {
        'form': form,
        'formset': formset,
        'section': features_section,
        'cards': cards,
        'card_images': card_images,
    })

@login_required(login_url='/manage/login/')
def manage_site_plan(request):
    from django.forms import modelform_factory, modelformset_factory
    
    # 단지배치도 섹션 가져오기
    site_plan_section = SectionContent.objects.filter(section='site_plan').first()
    
    # 단지배치도 폼
    SectionContentForm = modelform_factory(SectionContent, fields=[
        'title', 'subtitle'
    ])
    
    # 카드 폼셋
    SectionCardFormSet = modelformset_factory(
        SectionCard, 
        fields=['title', 'subtitle', 'icon'],
        extra=0,
        can_delete=True
    )
    
    # 이미지 폼셋 (카드별로 분리)
    SectionImageFormSet = modelformset_factory(
        SectionImage,
        fields=['image', 'title', 'description'],
        extra=1,
        can_delete=True
    )
    
    if request.method == 'POST':
        if 'save_section' in request.POST:
            form = SectionContentForm(request.POST, instance=site_plan_section)
            if form.is_valid():
                form.save()
                messages.success(request, '단지배치도 섹션이 저장되었습니다.')
                return redirect('manage_site_plan')
        elif 'save_cards' in request.POST:
            formset = SectionCardFormSet(request.POST, prefix='cards')
            if formset.is_valid():
                instances = formset.save(commit=False)
                for instance in instances:
                    instance.section_content = site_plan_section
                    instance.save()
                for obj in formset.deleted_objects:
                    obj.delete()
                messages.success(request, '카드 정보가 저장되었습니다.')
                return redirect('manage_site_plan')
        elif 'save_image_view_images' in request.POST:
            # 이미지보기 버튼 이미지 관리
            new_image = request.FILES.get('new_image')
            new_image_title = request.POST.get('new_image_title')
            new_image_description = request.POST.get('new_image_description')
            
            if new_image and new_image_title:
                # 가장 높은 order 값 찾기
                max_order = SectionImage.objects.filter(section_content=site_plan_section, card__isnull=True).aggregate(
                    models.Max('order')
                )['order__max'] or 0
                
                SectionImage.objects.create(
                    section_content=site_plan_section,
                    card=None,  # 이미지보기 버튼용 이미지는 card=None
                    title=new_image_title,
                    description=new_image_description,
                    image=new_image,
                    order=max_order + 1
                )
            
            # 기존 이미지 삭제
            for key, value in request.POST.items():
                if key.startswith('delete_image_') and value == 'on':
                    image_id = key.replace('delete_image_', '')
                    try:
                        image = SectionImage.objects.get(id=image_id, section_content=site_plan_section, card__isnull=True)
                        image.delete()
                    except SectionImage.DoesNotExist:
                        pass
            
            messages.success(request, '이미지보기 버튼 이미지가 저장되었습니다.')
            return redirect('manage_site_plan')
    else:
        form = SectionContentForm(instance=site_plan_section)
        formset = SectionCardFormSet(
            queryset=SectionCard.objects.filter(section_content=site_plan_section).order_by('order'),
            prefix='cards'
        )
    
    # 카드별 이미지 데이터 준비
    cards = SectionCard.objects.filter(section_content=site_plan_section).order_by('order')
    card_images = {}
    for card in cards:
        card_images[card.id] = SectionImage.objects.filter(card=card).order_by('order')
    
    # 이미지보기 버튼용 이미지 (card=None인 이미지들)
    site_plan_images = SectionImage.objects.filter(section_content=site_plan_section, card__isnull=True).order_by('order')
    
    return render(request, 'manage/site_plan.html', {
        'form': form,
        'formset': formset,
        'section': site_plan_section,
        'cards': cards,
        'card_images': card_images,
        'site_plan_images': site_plan_images,
    })

@login_required(login_url='/manage/login/')
def manage_sales_info(request):
    from django.forms import modelform_factory, modelformset_factory
    
    # 분양정보 섹션 가져오기
    sales_section = SectionContent.objects.filter(section='sales').first()
    
    # 분양정보 섹션 폼
    SectionContentForm = modelform_factory(SectionContent, fields=[
        'title', 'subtitle'
    ])
    
    # 분양정보 폼셋
    SalesInfoFormSet = modelformset_factory(
        SalesInfo, 
        fields=['type', 'price_per_pyeong', 'area', 'households', 'floors', 'parking'],
        extra=0,
        can_delete=True
    )
    
    # 분양정보 이미지 폼셋
    SalesImageFormSet = modelformset_factory(
        SalesImage,
        fields=['title', 'image', 'order'],
        extra=1,
        can_delete=True
    )
    
    if request.method == 'POST':
        if 'save_section' in request.POST:
            form = SectionContentForm(request.POST, instance=sales_section)
            if form.is_valid():
                form.save()
                messages.success(request, '분양정보 섹션이 저장되었습니다.')
                return redirect('manage_sales_info')
        elif 'save_sales' in request.POST:
            formset = SalesInfoFormSet(request.POST, prefix='sales')
            if formset.is_valid():
                instances = formset.save(commit=False)
                for instance in instances:
                    instance.save()
                for obj in formset.deleted_objects:
                    obj.delete()
                messages.success(request, '분양정보가 저장되었습니다.')
                return redirect('manage_sales_info')
        elif 'save_images' in request.POST:
            sales_type = request.POST.get('sales_type')
            sales_info = SalesInfo.objects.filter(type=sales_type).first()
            
            if sales_info:
                # 새 이미지 추가
                new_image = request.FILES.get('new_image')
                new_image_title = request.POST.get('new_image_title')
                new_image_order = request.POST.get('new_image_order', 1)
                
                if new_image and new_image_title:
                    SalesImage.objects.create(
                        sales_info=sales_info,
                        title=new_image_title,
                        image=new_image,
                        order=new_image_order
                    )
                
                # 기존 이미지 삭제
                for key, value in request.POST.items():
                    if key.startswith('delete_image_') and value == 'on':
                        image_id = key.replace('delete_image_', '')
                        try:
                            image = SalesImage.objects.get(id=image_id, sales_info=sales_info)
                            image.delete()
                        except SalesImage.DoesNotExist:
                            pass
                
                messages.success(request, f'{sales_info.get_type_display()} 평면도 이미지가 저장되었습니다.')
            
            return redirect('manage_sales_info')
    else:
        form = SectionContentForm(instance=sales_section)
        formset = SalesInfoFormSet(
            queryset=SalesInfo.objects.all().order_by('type'),
            prefix='sales'
        )
    
    # 분양정보별 이미지 데이터 준비
    sales_infos = SalesInfo.objects.all().order_by('type')
    sales_images = {}
    for sales_info in sales_infos:
        sales_images[sales_info.type] = SalesImage.objects.filter(sales_info=sales_info).order_by('order')
    
    return render(request, 'manage/sales_info.html', {
        'form': form,
        'formset': formset,
        'section': sales_section,
        'sales_infos': sales_infos,
        'sales_images': sales_images,
    })

@login_required(login_url='/manage/login/')
def manage_gallery(request):
    from django.forms import modelform_factory, modelformset_factory
    from django.forms import inlineformset_factory
    
    # 3개 카드 고정
    cards = GalleryCard.objects.order_by('order')
    CardFormSet = modelformset_factory(GalleryCard, fields=['title', 'subtitle'], extra=0, can_delete=False)
    
    # 카드별 이미지 폼셋
    GalleryImageFormSet = inlineformset_factory(
        GalleryCard, GalleryImage,
        fields=['title', 'image', 'order'],
        extra=1, can_delete=True
    )
    
    if request.method == 'POST':
        if 'save_cards' in request.POST:
            card_formset = CardFormSet(request.POST, queryset=cards)
            if card_formset.is_valid():
                card_formset.save()
                messages.success(request, '카드 정보가 저장되었습니다.')
                return redirect('manage_gallery')
        elif request.POST.get('save_images_for'):
            card_id = request.POST.get('save_images_for')
            card = GalleryCard.objects.get(id=card_id)
            image_formset = GalleryImageFormSet(request.POST, request.FILES, instance=card, prefix=f'img_{card.id}')
            if image_formset.is_valid():
                image_formset.save()
                messages.success(request, f'[{card.title}] 이미지가 저장되었습니다.')
                return redirect('manage_gallery')
    else:
        card_formset = CardFormSet(queryset=cards)
    
    image_formsets = []
    for card in cards:
        image_formsets.append({
            'card': card,
            'formset': GalleryImageFormSet(instance=card, prefix=f'img_{card.id}')
        })
    
    return render(request, 'manage/gallery.html', {
        'card_formset': card_formset,
        'image_formsets': image_formsets,
        'cards': cards,
    })

@login_required(login_url='/manage/login/')
def manage_consult(request):
    consults = Consultation.objects.filter(is_deleted=False).order_by('-created_at')
    return render(request, 'manage/consult.html', {'consults': consults})

@login_required(login_url='/manage/login/')
def manage_consult_detail(request, consult_id):
    consult = get_object_or_404(Consultation, id=consult_id)
    if request.method == 'POST':
        if 'delete' in request.POST:
            consult.is_deleted = True
            consult.save()
            return redirect('manage_consult')
        elif 'reply' in request.POST:
            consult.reply_content = request.POST.get('reply_content', '')
            consult.is_replied = True
            consult.replied_at = timezone.now()
            consult.save()
            return redirect('manage_consult_detail', consult_id=consult.id)
    return render(request, 'manage/consult_detail.html', {'consult': consult})
