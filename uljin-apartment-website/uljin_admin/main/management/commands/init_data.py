from django.core.management.base import BaseCommand
from django.utils import timezone
from main.models import *

class Command(BaseCommand):
    help = '울진 아파트 홈페이지 초기 데이터 생성'

    def handle(self, *args, **options):
        self.stdout.write('초기 데이터 생성을 시작합니다...')

        # 히어로 배너 생성
        hero_banner, created = HeroBanner.objects.get_or_create(
            defaults={
                'title': 'PROJ-U',
                'subtitle': '프리미엄 아파트',
                'description': '경상북도 울진군 울진읍 고성리 12-45번지 일원\n총 3개동 138세대 | 지하2층~지상20층',
                'primary_button_text': '분양정보 보기',
                'primary_button_link': '#sales',
                'secondary_button_text': '상담 신청',
                'secondary_button_link': '#contact',
            }
        )
        if created:
            self.stdout.write('✓ 히어로 배너 생성 완료')

        # 프로젝트 개요 섹션 생성
        overview_section, created = SectionContent.objects.get_or_create(
            section='overview',
            defaults={
                'title': '프로젝트 개요',
                'subtitle': '울진의 새로운 랜드마크가 될 프리미엄 아파트',
            }
        )
        if created:
            self.stdout.write('프로젝트 개요 섹션 생성 완료')
        
        # 프로젝트 개요 카드들 생성
        overview_cards_data = [
            {
                'title': '총 138세대',
                'subtitle': '3개동으로 구성된 대단지\n지하2층~지상20층 규모',
                'icon': '🏢',
                'order': 1
            },
            {
                'title': '84A/84B 타입',
                'subtitle': '84A: 83.86㎡ (25.37평) - 73세대\n84B: 84.96㎡ (25.7평) - 65세대',
                'icon': '📐',
                'order': 2
            },
            {
                'title': '161대 주차',
                'subtitle': '법정 주차대수 146대 대비\n110.47% 확보',
                'icon': '🚗',
                'order': 3
            }
        ]
        
        for card_data in overview_cards_data:
            card, created = SectionCard.objects.get_or_create(
                section_content=overview_section,
                title=card_data['title'],
                defaults={
                    'subtitle': card_data['subtitle'],
                    'icon': card_data['icon'],
                    'order': card_data['order']
                }
            )
            if created:
                self.stdout.write(f'프로젝트 개요 카드 "{card_data["title"]}" 생성 완료')

        # 입지환경 섹션 생성
        features_section, created = SectionContent.objects.get_or_create(
            section='features',
            defaults={
                'title': '입지 환경',
                'subtitle': '울진의 미래 성장 동력과 함께하는 최적의 입지',
            }
        )
        if created:
            self.stdout.write('입지환경 섹션 생성 완료')
        
        # 입지환경 카드들
        location_cards = [
            {'title': '원자력수소 국가산업단지', 'subtitle': '대규모 국책사업으로 인한\n지역 발전 및 인구 유입 효과', 'icon': '🏭', 'order': 1},
            {'title': '동해선 울진역', 'subtitle': '2024년 개통으로\n수도권 접근성 획기적 개선', 'icon': '🚄', 'order': 2},
            {'title': '풍력발전단지', 'subtitle': '신재생에너지 허브로\n지속가능한 미래 도시', 'icon': '⚡', 'order': 3},
            {'title': '울진읍 중심가', 'subtitle': '행정, 상업, 교육시설이\n집중된 최적의 생활환경', 'icon': '🏛️', 'order': 4},
        ]
        
        for card_data in location_cards:
            SectionCard.objects.get_or_create(
                section_content=features_section,
                title=card_data['title'],
                defaults={
                    'subtitle': card_data['subtitle'],
                    'icon': card_data['icon'],
                    'order': card_data['order']
                }
            )
        
        # 단지배치도 섹션 생성
        site_plan_section, created = SectionContent.objects.get_or_create(
            section='site_plan',
            defaults={
                'title': '단지 배치도',
                'subtitle': '3개동으로 구성된 효율적인 단지 배치'
            }
        )
        
        # 단지배치도 카드들 (단지 내 시설)
        site_plan_cards = [
            {'title': '놀이터', 'subtitle': '어린이 놀이시설', 'icon': '🎮', 'order': 1},
            {'title': '주차장', 'subtitle': '161대 (110.47%)', 'icon': '🚗', 'order': 2},
            {'title': '조경', 'subtitle': '단지 내 녹지공간', 'icon': '🌳', 'order': 3},
            {'title': '상가', 'subtitle': '근린생활시설', 'icon': '🏪', 'order': 4},
        ]
        
        for card_data in site_plan_cards:
            SectionCard.objects.get_or_create(
                section_content=site_plan_section,
                title=card_data['title'],
                defaults={
                    'subtitle': card_data['subtitle'],
                    'icon': card_data['icon'],
                    'order': card_data['order']
                }
            )

        # 갤러리 섹션 생성
        gallery_section, created = SectionContent.objects.get_or_create(
            section='gallery',
            defaults={
                'title': '갤러리',
                'subtitle': '울진 아파트의 아름다운 모습을 감상하세요'
            }
        )
        if created:
            self.stdout.write('✓ 갤러리 섹션 생성 완료')

        # 갤러리 카드들
        gallery_cards = [
            {
                'title': '3개동 전체 조망',
                'subtitle': '조감도 001',
                'icon': '🏢',
                'order': 1,
            },
            {
                'title': '단지 중심부 조망',
                'subtitle': '조감도 003',
                'icon': '🏘️',
                'order': 2,
            },
            {
                'title': '울진읍 전경',
                'subtitle': '광역 조감도',
                'icon': '🌆',
                'order': 3,
            },
        ]

        for card_data in gallery_cards:
            card, created = SectionCard.objects.get_or_create(
                section_content=gallery_section,
                title=card_data['title'],
                defaults={
                    'subtitle': card_data['subtitle'],
                    'icon': card_data['icon'],
                    'order': card_data['order'],
                }
            )
        self.stdout.write('✓ 갤러리 카드 생성 완료')

        # 분양정보 섹션 생성
        sales_section, created = SectionContent.objects.get_or_create(
            section='sales',
            defaults={
                'title': '분양 정보',
                'subtitle': '합리적인 가격으로 만나는 프리미엄 라이프'
            }
        )
        
        # 분양정보 데이터
        sales_data = [
            {
                'type': '84A',
                'price_per_pyeong': '1,250만원',
                'area': '83.86㎡ (25.37평)',
                'households': '73세대',
                'floors': '지하2층~지상20층',
                'parking': '세대당 1대 이상'
            },
            {
                'type': '84B',
                'price_per_pyeong': '1,300만원',
                'area': '84.96㎡ (25.7평)',
                'households': '65세대',
                'floors': '지하2층~지상20층',
                'parking': '세대당 1대 이상'
            }
        ]
        
        for data in sales_data:
            SalesInfo.objects.get_or_create(
                type=data['type'],
                defaults={
                    'price_per_pyeong': data['price_per_pyeong'],
                    'area': data['area'],
                    'households': data['households'],
                    'floors': data['floors'],
                    'parking': data['parking']
                }
            )
        
        # 샘플 설문조사 생성
        survey, created = Survey.objects.get_or_create(
            title='울진 아파트 분양 의향 조사',
            defaults={
                'description': '울진 아파트 분양에 대한 고객님의 의견을 들려주세요.',
                'start_date': timezone.now(),
                'end_date': timezone.now() + timezone.timedelta(days=30),
                'is_active': True,
            }
        )
        if created:
            self.stdout.write('✓ 샘플 설문조사 생성 완료')

        # 샘플 팝업 생성
        popup, created = Popup.objects.get_or_create(
            title='울진 아파트 분양 안내',
            defaults={
                'content': '울진 아파트 분양이 시작되었습니다. 많은 관심 부탁드립니다.',
                'width': 400,
                'height': 300,
                'button_text': '닫기',
                'button_position': 'center',
                'start_date': timezone.now(),
                'end_date': timezone.now() + timezone.timedelta(days=7),
                'is_active': True,
            }
        )
        if created:
            self.stdout.write('✓ 샘플 팝업 생성 완료')
        
        self.stdout.write(
            self.style.SUCCESS('Successfully created initial data for all sections')
        ) 