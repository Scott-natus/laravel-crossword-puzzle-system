# API 변경사항 문서

## 2025-07-04: 단어 보충 API 수정

### 변경 내용
- **API 목적**: 십자낱말 퍼즐을 만들기 위한 단어 제안
- **단어 조건**: 명사, 고유명사, 신조어로 구성된 2음절~5음절 단어
- **추천 개수**: 100개 정도
- **응답 형식**: [카테고리,단어] 형태로 한 줄에 하나씩

### 수정된 파일들

#### 1. app/Console/Commands/GenerateWordsScheduler.php
- `buildPrompt()` 메서드 수정
  - 십자낱말 퍼즐용 프롬프트로 변경
  - 다양한 카테고리와 단어 쌍으로 추천
  - 예시: [동물,강아지], [음식,김치찌개], [직업,의사], [스포츠,축구공]

- `getSyllableCondition()` 메서드 수정
  - 2음절에서 5음절 사이의 단어 조건으로 통일

- `processAndSaveWords()` 메서드 수정
  - 단어 길이 검증을 2~5음절로 변경
  - 카테고리별 중복 체크에서 전체 중복 체크로 변경

- `checkDuplicates()` 메서드 수정
  - 카테고리별 체크에서 전체 체크로 변경

#### 2. app/Services/GeminiService.php
- `extractWordsFromResponse()` 메서드 수정
  - 단어 길이 검증을 2~5음절로 변경

### 테스트 결과
- 테스트 모드에서 정상 작동 확인
- 실제 단어 생성에서 20개 중 9개 신규 단어 추가 성공
- 다양한 카테고리(음식, 스포츠, 직업, 날씨, 과일, 교통, 영화, 유행어, 식물)에서 단어 생성

### 사용법
```bash
# 테스트 모드 (실제 저장하지 않음)
php artisan puzzle:generate-words-scheduler --limit=10 --dry-run

# 실제 단어 생성
php artisan puzzle:generate-words-scheduler --limit=100

# 특정 카테고리만 생성
php artisan puzzle:generate-words-scheduler --limit=50 --category=음식
```

### 생성된 단어 예시
- [음식,피자] (2음절)
- [스포츠,배구공] (3음절)
- [직업,프로그래머] (5음절)
- [영화,겨울왕국] (4음절)
- [식물,장미꽃] (3음절) 