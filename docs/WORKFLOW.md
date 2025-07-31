# 프로젝트U (프로젝트 번호 4번) - 관리 시스템 작업/운영/구조 정리

## 프로젝트 개요
- 명칭: **프로젝트U**
- 프로젝트 번호: 4번
- 목적: 아파트 분양/홍보/상담/설문/갤러리 등 통합 관리 시스템
- 주요 기능: 관리자 페이지(분양정보, 단지배치도, 입지환경, 프로젝트 개요, 갤러리, 온라인상담, 설문, 팝업 등) + 프론트엔드

## 서버 및 폴더 구조
- 운영 서버: 222.100.103.227 (Ubuntu Linux)
- 작업 경로: `/var/www/html/uljin-apartment-website/`
- 관리자 소스: `uljin_admin/main/`
- 관리자 템플릿: `uljin_admin/main/templates/manage/`
- 주요 문서: `/var/www/html/docs/WORKFLOW.md` (이 파일)
- 백업: `/var/www/html/backups/source_backup_YYYYMMDD_HHMMSS.tar.gz`

## 주요 기술스택
- Python 3.12, Django 5.x
- PostgreSQL
- HTML5, CSS3, JS (jQuery 일부)
- Bootstrap 기반 반응형
- Git, systemd, Nginx

## 관리자 기능 구조
- **프로젝트 개요**: 섹션(타이틀/서브타이틀), 카드(제목/서브제목/아이콘), 카드별 이미지
- **입지환경**: 섹션(타이틀/서브타이틀), 카드(제목/서브제목/아이콘), 카드별 이미지
- **단지배치도**: 섹션(타이틀/서브타이틀), 카드(제목/서브제목/아이콘), [이미지보기] 버튼용 이미지(card=None)만 관리
- **분양정보**: 섹션(타이틀/서브타이틀), 타입별 정보(가격/면적/세대수/층수/주차 등), 타입별 평면도 이미지
- **갤러리**: 카드(타이틀/서브타이틀), 카드별 이미지
- **온라인상담**: 리스트, 상세, 답변, 삭제(소프트딜리트)
- **설문조사/팝업**: 설문/팝업 관리 기능(확장 예정)
- **좌측 메뉴**: `_sidebar.html`로 모든 관리자 메뉴 통일, 동적 active 처리

## 작업/백업/운영 규칙
- 모든 소스/DB 변경 전 **백업 필수** (backups/)
- git 커밋/푸시 필수, 커밋 메시지에 작업 내역 명확히 기록
- DB 조작(INSERT/UPDATE/DELETE 등)은 반드시 대화형 승인
- 스키마 변경 시 puzzle_db_schema.sql, docs/DATABASE_SCHEMA.md에 기록
- 운영 서버는 systemd 등 백그라운드 서비스만 사용(포그라운드 실행 금지)
- 주요 변경/문제해결은 docs/ 폴더에 기록

## 참고 URL/명령어
- 관리자: http://222.100.103.227:8000/manage/
- 프론트: http://222.100.103.227:8000/
- DB 백업: `pg_dump -h 127.0.0.1 -U myuser mydb > backup.sql`
- 소스 백업: `tar czf backups/source_backup_$(date +%Y%m%d_%H%M%S).tar.gz ...`
- git 커밋/푸시: `git add . && git commit -m "메시지" && git push`

## 다음 작업시 참고
- 이 문서( /var/www/html/docs/WORKFLOW.md )를 항상 최신화
- 관리자 템플릿/구조/운영 규칙/백업 정책 등 반드시 준수
- 신규 기능/수정/문제해결 시 반드시 이 문서에 기록 후 커밋/푸시

---

# Laravel Crossword Puzzle Management System - Workflow

## 2025-07-31 작업 내역 (React Native 모바일 앱 localStorage 에러 해결)

### 문제 상황
- React Native 모바일 앱에서 `Property 'localStorage' doesn't exist` 에러 발생
- 모바일 환경에서 웹 브라우저 API인 localStorage를 사용하려고 해서 발생한 문제
- 로그인 후 게임 페이지로 전환되지 않는 인증 문제

### 해결 과정

#### 1. localStorage 사용 위치 파악
- `CrosswordPuzzleMobileApp/src/services/api.ts`: API 서비스에서 토큰 저장/로드
- `CrosswordPuzzleMobileApp/src/contexts/AuthContext.tsx`: 인증 컨텍스트에서 토큰 관리
- 두 파일 모두 localStorage를 직접 사용하여 모바일 환경에서 에러 발생

#### 2. 환경별 스토리지 처리 로직 구현
- **React Native 환경**: AsyncStorage 사용
- **웹 환경**: localStorage 사용 (fallback)
- **AsyncStorage 로드 실패 시**: 메모리 스토리지 사용

#### 3. 수정된 파일들

**CrosswordPuzzleMobileApp/src/services/api.ts**
```typescript
const getStorage = () => {
  try {
    const AsyncStorage = require('@react-native-async-storage/async-storage').default;
    console.log('✅ CrosswordPuzzleMobileApp AsyncStorage 로드 성공');
    return AsyncStorage;
  } catch (error) {
    console.log('❌ CrosswordPuzzleMobileApp AsyncStorage 로드 실패, 메모리 스토리지 사용');
    const memoryStorage: any = {};
    return {
      getItem: (key: string) => Promise.resolve(memoryStorage[key] || null),
      setItem: (key: string, value: string) => Promise.resolve(memoryStorage[key] = value),
      removeItem: (key: string) => Promise.resolve(delete memoryStorage[key]),
    };
  }
};
```

**CrosswordPuzzleMobileApp/src/contexts/AuthContext.tsx**
```typescript
const getStorage = () => {
  try {
    const AsyncStorage = require('@react-native-async-storage/async-storage').default;
    console.log('✅ CrosswordPuzzleMobileApp AuthContext AsyncStorage 로드 성공');
    return AsyncStorage;
  } catch (error) {
    console.log('❌ CrosswordPuzzleMobileApp AuthContext AsyncStorage 로드 실패, 메모리 스토리지 사용');
    const memoryStorage: any = {};
    return {
      getItem: (key: string) => Promise.resolve(memoryStorage[key] || null),
      setItem: (key: string, value: string) => Promise.resolve(memoryStorage[key] = value),
      removeItem: (key: string) => Promise.resolve(delete memoryStorage[key]),
    };
  }
};
```

#### 4. 디버깅 로그 추가
- 각 파일에 `CrosswordPuzzleMobileApp` 접두사를 사용한 로그 추가
- AsyncStorage 로드 성공/실패 로그
- API 요청/응답 로그
- 인증 상태 변경 로그

#### 5. 빌드 및 테스트
- Android APK 빌드: `./build-android.sh`
- 빌드 시간: 2025-07-31 15:30:00
- 테스트 계정: test@test.com / 123456

### 예상 결과
- localStorage 에러 해결
- 모바일 환경에서 AsyncStorage 정상 동작
- 로그인 후 게임 페이지로 정상 전환
- 인증 토큰 저장/로드 정상 동작

### 다음 단계
1. 새 APK 설치 및 테스트
2. 로그인 플로우 확인
3. 인증 상태 유지 확인
4. 게임 페이지 전환 확인

---

## 2025-07-18 작업 내역 (Wordle 스타일 게임 상태 유지 시스템 - 정답 표시 문제 해결)

### 문제 상황
- 새로고침 후 맞춘 정답들이 `***` 별표로 표시됨
- 사용자가 맞춘 정답은 보여야 하는데 보안상 정답을 클라이언트에 전송하지 않아서 발생한 문제

### 해결 과정

#### 1. 서버 측 수정 (PuzzleGameController.php)
- `getTemplate()` 메서드에서 맞춘 정답 단어 정보를 안전하게 전송하는 로직 추가
- `answered_words_with_answers` 필드 추가: 맞춘 단어의 정답만 서버에서 전송
- 보안 고려사항: 맞춘 단어만 전송하여 보안 유지

#### 2. 클라이언트 측 수정 (index.blade.php)
- `restoreGameState()` 함수 수정: 서버에서 받은 정답 정보 사용
- `answeredWordsWithAnswers` 파라미터 추가하여 정답 복원 로직 개선
- 정답 정보가 없으면 별표로 표시하는 fallback 로직 유지

#### 3. 구현된 기능
- 새로고침 시 맞춘 정답들이 정상적으로 표시됨
- 보안 유지: 맞춘 단어만 전송, 미완성 단어는 전송하지 않음
- 사용자 경험 개선: Wordle처럼 게임 진행 상태 완전 복원

### 수정된 파일
- `app/Http/Controllers/PuzzleGameController.php`: 정답 단어 정보 전송 로직 추가
- `resources/views/puzzle/game/index.blade.php`: 정답 복원 로직 수정

### 테스트 방법
1. 퍼즐게임에서 몇 개 단어를 맞춤
2. 브라우저 새로고침
3. 맞춘 정답들이 정상적으로 표시되는지 확인

---

## 2025-07-18 작업 내역 (Wordle 스타일 게임 상태 유지 시스템 구축)

### 문제 상황
- 새로고침할 때마다 새로운 퍼즐이 생성되어 사용자가 어려운 퍼즐을 피해갈 수 있는 문제
- Wordle처럼 새로고침해도 게임 진행 상태가 유지되어야 함

### 해결 과정

#### 1. 데이터베이스 스키마 확장
- `user_puzzle_games` 테이블에 퍼즐 세션 정보 저장 컬럼 추가
- `current_puzzle_data`: 현재 퍼즐의 템플릿, 단어, 그리드 정보
- `current_game_state`: 현재 게임 상태 (정답/오답, 힌트 사용 등)
- `current_puzzle_started_at`: 현재 퍼즐 시작 시간
- `has_active_puzzle`: 활성 퍼즐 존재 여부

#### 2. UserPuzzleGame 모델 확장
- 퍼즐 세션 관리용 메서드 추가:
  - `startNewPuzzle()`: 새 퍼즐 시작
  - `endCurrentPuzzle()`: 현재 퍼즐 종료
  - `updateGameState()`: 게임 상태 업데이트
  - `hasActivePuzzle()`: 활성 퍼즐 존재 확인
  - `getCurrentPuzzleData()`: 현재 퍼즐 데이터 조회
  - `getCurrentGameState()`: 현재 게임 상태 조회

#### 3. PuzzleGameController 수정
- `getTemplate()` 메서드: 활성 퍼즐 세션이 있으면 기존 퍼즐과 게임 상태 복원
- `checkAnswer()` 메서드: 게임 상태 업데이트 로직 추가
- `completeLevel()` 메서드: 퍼즐 세션 종료 로직 추가

#### 4. 프론트엔드 JavaScript 수정
- `loadTemplate()` 함수: 서버에서 받은 게임 상태 복원
- `restoreGameState()` 함수: 정답 상태 복원
- `restoreAnsweredCells()` 함수: 그리드에 정답 상태 표시
- `updateGridWithAnswer()` 함수: 게임 상태 업데이트 로그 추가

#### 5. 구현된 기능
- Wordle처럼 게임 진행 상태를 데이터베이스에 저장
- 새로고침 시 동일한 퍼즐과 진행 상태 복원
- 정답/오답 상태, 힌트 사용 여부 등 추적
- 정답 단어는 보안상 서버에만 저장하고 클라이언트에는 표시용으로만 복원

### 수정된 파일
- `database/migrations/2025_07_18_102239_add_current_puzzle_session_to_user_puzzle_games_table.php`: 마이그레이션 파일
- `app/Models/UserPuzzleGame.php`: 퍼즐 세션 관리 메서드 추가
- `app/Http/Controllers/PuzzleGameController.php`: 게임 상태 관리 로직 추가
- `resources/views/puzzle/game/index.blade.php`: 프론트엔드 게임 상태 복원 로직 추가

### 테스트 방법
1. 퍼즐게임에서 몇 개 단어를 맞춤
2. 브라우저 새로고침
3. 동일한 퍼즐과 진행 상태가 복원되는지 확인
4. 정답/오답 상태가 정상적으로 표시되는지 확인

---

## 2025-07-11 작업 내역 (React Native 웹앱 빌드 및 서비스 설정)

### 문제 상황
- React Native 웹앱이 개발 서버 모드로 실행 중
- 정식 빌드 및 정적 파일 서비스가 아닌 상태
- UI 변경사항이 브라우저에 반영되지 않는 문제

### 해결 과정

#### 1. 빌드 시스템 확인
- `package.json`에서 `build-web` 스크립트 확인
- `npm run build-web` 명령으로 프로덕션 빌드 실행
- `dist/` 폴더에 정적 파일 생성 확인

#### 2. Systemd 서비스 설정
- `/etc/systemd/system/crossword-puzzle-app.service` 생성
- Python HTTP 서버로 3001 포트에서 정적 파일 서비스
- 서비스 활성화 및 시작

#### 3. 현재 상태
- ✅ **빌드 완료**: `dist/` 폴더에 `index.html`, `bundle.js` 생성
- ✅ **서비스 생성**: systemd 서비스 파일 생성 완료
- ⚠️ **서비스 시작 실패**: Python HTTP 서버 시작 시 오류 발생
- ⚠️ **권한 문제**: www-data 사용자 권한 설정 필요

### 다음 작업 예정
1. **서비스 권한 문제 해결**: www-data 사용자 권한 설정
2. **서비스 재시작**: 정상적인 정적 파일 서비스 확인
3. **브라우저 캐시 클리어**: 하드 리프레시로 새 빌드 반영 확인
4. **UI 기능 테스트**: 로그인, 게임 화면, 퍼즐 기능 정상 작동 확인

### 중요 파일 위치
- **빌드 파일**: `/var/www/html/CrosswordPuzzleApp/dist/`
- **서비스 설정**: `/etc/systemd/system/crossword-puzzle-app.service`
- **소스 코드**: `/var/www/html/CrosswordPuzzleApp/src/`

### 디버깅 명령어
- **서비스 상태 확인**: `sudo systemctl status crossword-puzzle-app`
- **서비스 로그 확인**: `sudo journalctl -u crossword-puzzle-app -f`
- **빌드 재실행**: `cd CrosswordPuzzleApp && npm run build-web`
- **수동 서비스 테스트**: `cd CrosswordPuzzleApp/dist && python3 -m http.server 3001`

---

## 2025-07-10 작업 내역 (React Native 웹앱 인증 시스템 구축)

### 문제 상황
- React Native 웹앱에서 퍼즐게임 진입 시 인증 없이 바로 게임 화면 표시
- Laravel API 호출 시 404 Not Found 에러 발생
- 기존 라라벨 웹서비스(쿠키&세션)와 React 앱(토큰 기반)의 인증 구조 차이

### 해결 과정

#### 1. 인증 구조 분석
- **라라벨 기존 웹 로그인**: 쿠키 + 세션 기반 (Auth::routes())
- **React용 API 로그인**: Sanctum 토큰 기반 (AuthController)
- **퍼즐게임 API**: 인증 토큰이 있어야 접근 가능

#### 2. API 서버 분리
- **기존 라라벨 웹서비스**: http://222.100.103.227/ (포트 80)
- **React Native 웹앱**: http://222.100.103.227:3001/ (포트 3001)
- **API 서버**: http://222.100.103.227:8080/api (포트 8080, 새로 분리)

#### 3. 인증 시스템 구현
- **AuthContext 생성**: 인증 상태 전역 관리
- **App.tsx 수정**: 인증 상태에 따른 화면 분기
- **LoginScreen/RegisterScreen**: AuthContext 사용하도록 수정
- **GameScreen**: 인증 체크 추가, 로그아웃 기능 구현

#### 4. 스토리지 호환성 해결
- **웹 환경**: localStorage 사용
- **모바일 환경**: AsyncStorage 사용
- **통합 스토리지 인터페이스**: 환경에 따라 자동 선택

#### 5. 서버 설정
- **API 서버 실행**: `cd public && php -S 0.0.0.0:8080`
- **방화벽 설정**: `sudo ufw allow 8080/tcp`
- **포트포워딩**: 8080 포트 외부 접근 허용 필요

#### 6. 테스트 계정 생성
- **이메일**: test@test.com
- **비밀번호**: 123456
- **API 테스트**: 회원가입/로그인 정상 작동 확인

### 현재 상태
- ✅ **API 서버**: 8080 포트에서 정상 실행
- ✅ **방화벽**: 8080 포트 허용 완료
- ✅ **인증 API**: 회원가입/로그인/토큰 발급 정상 작동
- ✅ **React Native 웹앱**: 인증 상태에 따른 화면 분기 구현
- ⚠️ **포트포워딩**: 8080 포트 외부 접근 설정 필요
- ⚠️ **인증 화면 전환**: 브라우저에서 아직 로그인 화면으로 전환 안 됨

### 다음 작업 예정
1. **포트포워딩 설정**: 8080 포트 외부 접근 허용
2. **인증 화면 전환 디버깅**: AuthContext 로그 확인 및 수정
3. **퍼즐게임 API 연동**: 인증된 사용자의 퍼즐 데이터 로드
4. **실제 로그인 테스트**: 브라우저에서 전체 인증 흐름 확인

### 중요 파일 위치
- **React Native 웹앱**: /var/www/html/CrosswordPuzzleApp/
- **AuthContext**: CrosswordPuzzleApp/src/contexts/AuthContext.tsx
- **API 서비스**: CrosswordPuzzleApp/src/services/api.ts
- **App.tsx**: CrosswordPuzzleApp/App.tsx
- **API 서버**: public/api.php (새로 생성)

### 디버깅 명령어
- **API 서버 상태 확인**: `curl -X POST http://127.0.0.1:8080/api/login -H "Content-Type: application/json" -d '{"email":"test@test.com","password":"123456"}'`
- **React 앱 재시작**: `cd CrosswordPuzzleApp && npm run web`
- **포트 충돌 해결**: `pkill -f "webpack-dev-server" && pkill -f "react-native start"`

---

## 2025-07-09 작업 내역
- 단어 난이도 보정 스케줄러 개선 및 임시테이블 동기화 로직 개선
- Gemini API 프롬프트 구조 단순화 및 모델 버전별(1.5/2.5 flash) 실험
- 난이도 분포 집계 및 신규/재측정 방식 차이 분석
- Gemini API 쿼터 초과(429) 이슈 및 대응
- docs/WORKFLOW.md, docs/DATABASE_SCHEMA.md, docs/TROUBLESHOOTING.md에 상세 내역 기록 및 커밋/푸시 완료

---

## 2025-07-01 작업 내역
- 교차점 개수 validation을 '최소값' 기준으로 변경 (프론트엔드, 백엔드 모두)
- 조건 확인 div 안내 문구를 '최소 N개 필요'로 수정
- 삭제 기능 JS 이벤트 연결을 DOMContentLoaded 이후로 이동하여 DOM 로딩 타이밍 문제 해결
- Blade 템플릿 중복 @endpush 태그 제거
- git 커밋 및 원격 저장소 푸시, 병합 충돌 해결

---

## 2025-06-30 작업 내역
- 서버 OS 재설치 후 크론탭(`*/10 * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1`) 재등록
- storage 및 storage/logs 디렉토리, 로그 파일 권한/소유자(www-data)로 변경
- 배치 스케줄러 정상 동작 및 로그 권한 이슈 대응
- 단어 추출 로직에서 난이도 조건(단어 난이도, 힌트 난이도) 제거하여 더 다양한 단어 추출 가능
- 단어 추출 실패 시 확정된 단어를 초기화하고 최대 5회까지 재시도하는 로직 구현
- 크론 배치 작업 권한 문제 해결: natus-server를 www-data 그룹에 추가하여 로그 파일 쓰기 권한 확보

---

## 2025-01-27: React Native 웹앱 퍼즐 그리드 렌더링 문제 해결

### 문제 상황
- React Native 웹앱에서 퍼즐 그리드와 번호(배지)가 전혀 표시되지 않음
- API에서 데이터는 정상적으로 받아오지만 화면에 렌더링되지 않는 현상
- 사용자가 웹 서비스(Blade/Laravel)와 동일한 구조로 검은칸(2)에만 번호 표시 요청

### 원인 분석
- React Native for Web 환경에서는 HTML 태그(`<div>`, `<span>`)가 렌더링되지 않음
- 반드시 RN 컴포넌트(`<View>`, `<Text>`)를 사용해야 함
- CrosswordGrid.tsx에서 HTML 태그를 사용하여 렌더링 실패

### 해결 과정
1. **전체 소스 백업**: 작업 전 안전을 위해 백업 진행
2. **CrosswordGrid.tsx 수정**: 
   - HTML 태그를 RN 컴포넌트로 완전 교체
   - `<div>` → `<View>`, `<span>` → `<Text>` 변경
   - 검은칸(2)에만 번호 표시하는 로직 구현
   - RN 스타일 적용 (flexbox, 스타일 객체 등)
3. **빌드 및 서비스 재시작**: 수정사항 반영
4. **테스트**: 브라우저에서 새로고침 후 정상 표시 확인

### 결과
- ✅ 퍼즐 그리드 정상 표시
- ✅ 검은칸(2)에만 번호(배지) 표시
- ✅ 웹 서비스와 동일한 구조 구현 완료
- ✅ React Native for Web 환경 호환성 확보

### 기술적 교훈
- React Native for Web에서는 반드시 RN 컴포넌트 사용
- HTML 태그는 웹 환경에서도 렌더링되지 않음
- 스타일링도 RN 방식으로 적용해야 함

### 관련 파일
- `CrosswordPuzzleApp/src/components/CrosswordGrid.tsx`: 메인 수정 파일
- 백업 파일: `backups/CrosswordPuzzleApp_20250127_143000.tar.gz`

---

## 2025-07-01 작업 내역
- 교차점 개수 validation을 '최소값' 기준으로 변경 (프론트엔드, 백엔드 모두)
- 조건 확인 div 안내 문구를 '최소 N개 필요'로 수정
- 삭제 기능 JS 이벤트 연결을 DOMContentLoaded 이후로 이동하여 DOM 로딩 타이밍 문제 해결
- Blade 템플릿 중복 @endpush 태그 제거
- git 커밋 및 원격 저장소 푸시, 병합 충돌 해결

---

## 2025-06-30 작업 내역
- 서버 OS 재설치 후 크론탭(`*/10 * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1`) 재등록
- storage 및 storage/logs 디렉토리, 로그 파일 권한/소유자(www-data)로 변경
- 배치 스케줄러 정상 동작 및 로그 권한 이슈 대응
- 단어 추출 로직에서 난이도 조건(단어 난이도, 힌트 난이도) 제거하여 더 다양한 단어 추출 가능
- 단어 추출 실패 시 확정된 단어를 초기화하고 최대 5회까지 재시도하는 로직 구현
- 크론 배치 작업 권한 문제 해결: natus-server를 www-data 그룹에 추가하여 로그 파일 쓰기 권한 확보

---

## 2025-01-27 작업 내역
- 단어 난이도 업데이트 방식 개선: 힌트 생성과 단어 난이도 업데이트 완전 분리
- 임시 테이블(tmp_pz_word_difficulty) 생성하여 기존 단어 ID 복사 및 update_yn 관리
- 힌트 생성 API에서 단어 난이도 업데이트 코드 제거
- 단어 생성 API는 단어만 생성하고, 난이도는 별도 Gemini API 호출로 평가
- 단어 난이도 업데이트 API 별도 구현: update_yn='n'인 단어 50개씩 가져와 Gemini API에 일괄 평가
- UpdateWordDifficultyScheduler artisan 명령어 생성 및 Laravel 스케줄러에 10분마다 실행 등록
- GeminiService.php의 난이도 평가 프롬프트를 십자낱말 퀴즈 관점에서 1~5 숫자로 평가하도록 수정
- 테스트 모드와 실제 업데이트 모두 정상 작동 확인

---

## 2025-07-18 작업 내역 (퍼즐 단어 정리 및 비활성화 스케줄러 구축)

### 문제 상황
- `pz_words` 테이블에 "단어,숫자" 형태로 저장된 데이터들이 존재
- 영문이나 숫자가 포함된 단어들이 활성 상태로 남아있음
- 매일 정기적으로 단어 데이터를 정리하고 비활성화할 필요

### 해결 과정

#### 1. Artisan 명령어 생성
- `CleanupPuzzleWords` 명령어 생성: `php artisan make:command CleanupPuzzleWords`
- 두 가지 기능 구현:
  1. 쉼표와 숫자가 포함된 단어 정리
  2. 영문이나 숫자가 포함된 단어 비활성화

#### 2. 명령어 기능 구현
- `cleanupCommaNumberWords()`: 쉼표와 숫자 제거, length 업데이트
- `deactivateEnglishNumberWords()`: 영문/숫자 포함 단어 비활성화
- 로그 기록 및 에러 처리 구현

#### 3. 스케줄러 등록
- `app/Console/Kernel.php`에 매일 새벽 1시 실행 스케줄 등록
- `storage/logs/word-cleanup.log`에 로그 기록
- `withoutOverlapping()` 설정으로 중복 실행 방지

#### 4. 테스트 결과
- 쉼표 포함 단어: 0개 (이미 정리 완료)
- 영문/숫자 포함 단어: 22개 비활성화
- 비활성화된 단어 예시: "PR전략", "의료AI", "MZ세대", "NFT아트", "UI/UX", "3D프린터" 등

### 구현된 기능
- ✅ 매일 새벽 1시 자동 실행
- ✅ 쉼표와 숫자 제거하여 순수 단어만 남김
- ✅ 영문이나 숫자가 포함된 단어 자동 비활성화
- ✅ 로그 기록 및 에러 처리
- ✅ 중복 실행 방지

### 수정된 파일
- `app/Console/Commands/CleanupPuzzleWords.php`: 단어 정리 명령어
- `app/Console/Kernel.php`: 스케줄러 등록

### 테스트 방법
```bash
# 수동 실행
php artisan puzzle:cleanup-words

# 로그 확인
tail -f storage/logs/word-cleanup.log
cat storage/logs/laravel.log | grep "cleanup"
```

---

## 주요 작업 원칙

### 1. 데이터베이스 변경 시 필수 절차
- **변경 전**: 데이터에 미치는 영향 분석 및 설명
- **사용자 확인**: 변경 작업 진행 여부 및 백업 진행 여부 확인
- **스키마 기록**: /var/www/html/puzzle_db_schema.sql에 변경사항 추가

### 2. 데이터베이스 조작 작업 규칙 (매우 중요!)
- **대화형 진행 필수**: INSERT, UPDATE, DELETE, TRUNCATE, DROP 작업 시 반드시 사용자와 대화형으로 진행
- **작업 전 확인**: 실행할 SQL 문과 예상 결과를 먼저 보여주고 승인 요청
- **단계별 진행**: 복잡한 작업은 단계별로 나누어 진행
- **실행 후 확인**: 작업 완료 후 결과 확인 및 보고

### 3. 라이브 서버 주의사항 (222.100.103.227 전용)
- 데이터베이스 변경 시 항상 백업 후 진행
- Laravel 파일 수정 시 서비스 영향도 분석
- React 기능 추가/수정 시 Laravel 서비스 영향 고려

### 4. 환경 설정 확인
- 문제 발생 시 즉시 `cat .env`로 환경 설정 확인
- 불필요한 중간 단계 건너뛰고 직접적인 방법 사용

### 5. 문서화 원칙
- 모든 주요 변경사항은 docs/ 폴더에 기록
- 변경 후 실제 동작 확인

### 6. 로그 시스템 관리
- 로그 설정은 config/logging.php에서 관리
- 모든 로그 채널이 NullHandler로 설정되지 않도록 주의
- 디버깅 로그는 \Log::info() 사용하여 기록
- 로그 레벨은 env('LOG_LEVEL', 'debug')로 설정

### 7. 게시판 시스템 관리
- 각 게시판별 글쓰기 버튼은 현재 URL의 boardType 파라미터를 직접 읽어 생성
- AppServiceProvider에서 전역 변수 공유 시 주의 (boardType 등)

---

## 2025-01-27 작업 내역 (DataTable 체크박스 상태 유지 문제 해결)

### 문제 상황
- DataTable 무한 스크롤링으로 인해 체크박스 상태가 사라지는 문제
- 스크롤할 때마다 새로운 데이터를 서버에서 가져와서 DOM이 재생성됨
- 일괄변경을 위해 체크한 항목들이 스크롤 후 사라지는 문제

### 해결 과정

#### 1. 전역 변수로 체크박스 상태 관리
- `selectedCheckboxes` Set 객체로 선택된 체크박스 ID 관리
- 페이지 변경 시에도 선택 상태 유지
- DOM 재생성과 무관하게 상태 보존

#### 2. 서버 사이드에서 클라이언트 사이드로 변경
- `serverSide: false` 설정으로 클라이언트 사이드 처리로 변경
- 모든 데이터를 한 번에 로드하여 스크롤링 시 추가 요청 없음
- 체크박스 상태가 안정적으로 유지됨

#### 3. DataTable 설정 개선
- `stateSave: true` 설정으로 테이블 상태 저장 기능 활성화
- `xhr` 이벤트 핸들러 추가로 스크롤링 시 체크박스 상태 복원
- 체크박스 이벤트 관리 및 전체 선택/해제 기능 개선

#### 4. 서버 사이드 컨트롤러 수정
- `getData()` 메서드를 클라이언트 사이드에 맞게 수정
- 페이징 로직 제거하고 모든 데이터를 한 번에 반환
- 난이도 필터는 서버에서 처리

### 구현된 기능
- ✅ 무한 스크롤 시에도 체크박스 상태 유지
- ✅ 페이지 변경 시 선택된 항목 보존
- ✅ 전체 선택/해제 기능 정상 작동
- ✅ 일괄변경 시 선택된 항목 정확히 처리
- ✅ 선택된 개수 실시간 업데이트

### 수정된 파일
- `resources/views/puzzle/words/index.blade.php`: DataTable 설정 및 체크박스 상태 관리 로직 개선
- `app/Http/Controllers/PzWordController.php`: 클라이언트 사이드 처리로 변경

### 테스트 방법
1. 단어 관리 화면에서 여러 항목 체크
2. 스크롤하여 페이지 변경
3. 체크된 항목들이 그대로 유지되는지 확인
4. 일괄변경 기능 정상 작동 확인
- 레이아웃에서 동적 링크 생성 시 현재 컨텍스트 확인

### 8. 그리드 템플릿 시스템 관리
- 그리드 템플릿 저장 시 사용자가 "단어 위치 정보"에서 선택한 번호가 그대로 저장됨
- PuzzleGridTemplateService의 saveTemplate()에서 sortWordPositionsWithPriority() 호출 제거
- 사용자 입력 번호 우선 보존, 자동 넘버링 비활성화
- 템플릿 수정 시 사용자가 선택한 번호로 word_positions의 id 값 업데이트
- 단어 추출 시 word_positions를 id 순서대로 정렬하여 표시 (백엔드에서 처리)
- 문제가 있는 템플릿(#14, #15)은 비활성화 처리

### 9. React Native 웹앱 인증 시스템 관리 (2025-07-10 추가)
- **인증 구조**: 토큰 기반 인증 (Sanctum)
- **인증 흐름**: 앱 진입 → 토큰 없으면 로그인 화면 → 로그인 성공 시 퍼즐게임 화면
- **API 서버**: 8080 포트로 분리 (기존 라라벨 웹서비스와 독립)
- **스토리지**: 웹 환경에서는 localStorage, 모바일에서는 AsyncStorage 사용
- **포트 구성**:
  - React Native 웹앱: http://222.100.103.227:3001/
  - API 서버: http://222.100.103.227:8080/api
  - 라라벨 웹서비스: http://222.100.103.227/
- **방화벽**: 8080 포트 허용 완료

### 10. React Native 웹앱 빌드 및 서비스 관리 (2025-07-11 추가)
- **빌드 시스템**: `npm run build-web` 명령으로 프로덕션 빌드
- **정적 파일**: `dist/` 폴더에 `index.html`, `bundle.js` 생성
- **서비스 관리**: systemd 서비스로 Python HTTP 서버 실행
- **포트 구성**: 3001 포트에서 정적 파일 서비스
- **권한 관리**: www-data 사용자로 서비스 실행

## 자주 사용하는 명령어
- 데이터베이스 백업: `pg_dump -h 127.0.0.1 -U myuser mydb > backup.sql`
- 테이블 구조 확인: `\d table_name`
- Laravel 설정 확인: `php artisan tinker --execute="echo config('database.default');"`
- 환경 설정 확인: `cat .env`
- 로그 확인: `tail -f storage/logs/laravel.log`
- 설정 캐시 클리어: `php artisan config:clear`
- React Native 웹앱 실행: `cd CrosswordPuzzleApp && npm run web`
- API 서버 실행: `cd public && php -S 0.0.0.0:8080`
- React 앱 빌드: `cd CrosswordPuzzleApp && npm run build-web`
- 서비스 상태 확인: `sudo systemctl status crossword-puzzle-app`

## 주의사항
- **원격 개발 환경**: SSH로 222.100.103.227에 직접 연결하여 작업
- **데이터베이스 호스트**: 127.0.0.1 (서버 내부에서 같은 서버의 DB 접근)
- **모든 설명**: 한글로 진행
- **스키마 변경**: puzzle_db_schema.sql에 반드시 기록
- **DB 조작**: INSERT/UPDATE/DELETE/TRUNCATE/DROP 시 반드시 대화형 진행 
- **로그 설정**: config/logging.php 수정 후 반드시 config:clear 실행
- **React Native 웹앱**: 인증 토큰이 없으면 반드시 로그인 화면으로 이동해야 함
- **빌드 후 서비스**: 빌드 완료 후 반드시 systemd 서비스 재시작 필요 

## 2025-07-12 오답 초과 안내박스 UI/UX 반복 개선 및 배포

- 오답 5회 초과 시 안내 모달(재도전) 위치/동작/클릭 가능성 20회 이상 반복 수정
- 안내박스가 오버레이 위에 정확히 뜨도록 구조 개선 (zIndex, 렌더링 순서, pointerEvents)
- 안내박스가 입력/버튼 박스와 정확히 겹치게 ref+absolute 방식으로 위치 조정 시도
- transform, top, px, %, calc 등 다양한 CSS/React Native Web 스타일 실험
- 오버레이와 안내박스를 같은 부모에서, 오버레이 먼저, 안내박스 나중에, zIndex로 분리하여 최종 구조 확정
- 빌드(npm run build-web) 및 서비스 재시작(systemctl restart crossword-puzzle-app) 반복
- 실제 서비스에서 위치/동작/클릭 가능성 등 실사용 피드백 반영
- 서버 소스 전체 풀백업 진행 (source_backup_YYYYMMDD_HHMMSS.tar.gz) 
## 2025-07-12 한글 음절별 단어 수집 자동화 및 장애 대응

- temp_hangul_syllables 테이블 구조 개선(초성, processed_at 컬럼 추가)
- syllable별 단어 수집 프롬프트/자동화 설계 및 CollectWordsFromSyllables artisan 커맨드 구현
- 1분마다 스케줄러 자동 실행, Gemini API 연동, 결과 임시테이블 저장
- 장애(503 등) 발생 시 롤백 처리 및 로그 기록 개선
- 운영 정책 및 DB 작업 규칙 철저 준수
- 전체 플로우/운영 정책 docs/WORKFLOW.md, docs/DATABASE_SCHEMA.md, docs/TROUBLESHOOTING.md에 반영

### 2025-07-17 작업내역
- 퍼즐게임 클리어 조건 실시간 반영 및 중복 API 호출 방지 개선
- 클리어 기록 항상 저장, 클리어 조건 충족 시 다음 레벨 이동 정상화
- 프론트엔드: 다음 레벨 버튼 중복 호출 방지, 실시간 카운트 표시
- DB/소스/문서 백업 및 git 커밋

# 백업 정책
- 데이터베이스는 **풀백업** 진행 (pg_dump 등)
- 소스코드는 **핵심 소스만** 백업 (app, resources, routes, config, public, database, docs 등) 

# 첨부파일(이미지) 미노출 문제 발생 시
- storage/app/public/attachments에 파일이 있는데 웹에서 보이지 않으면 `php artisan storage:link` 상태를 반드시 점검
- 심볼릭 링크가 없으면 웹에서 /storage/attachments/ 경로로 접근 불가
- 서버 이전/OS 재설치/퍼미션 변경 후 반드시 storage:link 재확인

# 2025-07-09 작업 내역
- 단어 난이도 보정 스케줄러 개선 및 임시테이블 동기화 로직 개선
- Gemini API 프롬프트 구조 단순화 및 모델 버전별(1.5/2.5 flash) 실험
- 난이도 분포 집계 및 신규/재측정 방식 차이 분석
- Gemini API 쿼터 초과(429) 이슈 및 대응
- docs/WORKFLOW.md, docs/DATABASE_SCHEMA.md, docs/TROUBLESHOOTING.md에 상세 내역 기록 및 커밋/푸시 완료 

# 작업 규칙(중요)
- 모든 서버와 서비스는 반드시 서버사이드(systemd 등 백그라운드)에서만 실행한다.
- 터미널에서 직접 실행(포그라운드)은 금지. 

# 2025-01-28 작업 내역 (모바일 최적화)
## 퍼즐게임 모바일 반응형 개선
### 1. 상단 배너 모바일 숨김 처리
- **파일**: `resources/views/layouts/app.blade.php`
- **변경사항**: 모바일 화면(768px 이하)에서 상단 hero-banner 숨김
- **CSS 추가**: `@media (max-width: 768px) { .hero-banner { display: none !important; } }`

### 2. 퍼즐게임 모바일 최적화
- **파일**: `resources/views/puzzle/game/index.blade.php`
- **변경사항**: 모바일 화면에서 퍼즐 셀 크기 및 UI 요소 최적화
- **적용된 최적화**:
  - 컨테이너 패딩 축소 (0 10px)
  - 카드 헤더 폰트 크기 축소 (h3: 1.5rem, h5: 1rem)
  - 배지 크기 축소 (font-size: 0.75rem, padding: 0.25rem 0.5rem)
  - 퍼즐 셀 크기 축소 (35px x 35px, 320px 이하에서는 30px x 30px)
  - 셀 번호 및 단어 번호 폰트 크기 축소
  - 버튼 및 입력 필드 크기 최적화

### 3. 반응형 디자인 적용
- **768px 이하**: 일반 모바일 화면 최적화
- **320px 이하**: 매우 작은 모바일 화면 추가 최적화
- **터치 친화적**: 터치 영역 확보 및 가독성 향상

## 적용된 개선사항
- ✅ **상단 배너 숨김**: 모바일에서 불필요한 공간 절약
- ✅ **퍼즐 셀 크기 조정**: 모바일 화면에 맞는 적절한 크기
- ✅ **폰트 크기 최적화**: 가독성 향상
- ✅ **레이아웃 개선**: 모바일에서 더 나은 사용자 경험
- ✅ **터치 친화적**: 터치 영역 확보

## 테스트 필요사항
1. **다양한 모바일 기기**: iPhone, Android 다양한 화면 크기에서 테스트
2. **가로/세로 모드**: 화면 회전 시 레이아웃 확인
3. **터치 반응성**: 퍼즐 셀 클릭 및 버튼 터치 확인
4. **가독성**: 작은 화면에서 텍스트 가독성 확인
\n2025-07-22 index.ejs 기반 상담모달/갤러리/분양정보/모든 UI/스타일 1:1 복원 및 반응형 개선\n- 상담하기(consult-modal, consult-complete-modal) 모달 구조/스타일/동작 100% 복원 (HTML, CSS, JS)\n- 갤러리 카드형 구조 및 오버레이, 모달 1:1 복원\n- 분양정보 평면도 버튼, 모달, 스타일 1:1 복원\n- 모든 input/label/안내문구/버튼/라디오/체크박스 등 폼 요소 스타일 index.ejs와 동일하게 적용\n- 모바일/반응형(600px 이하)에서 모달/폼/버튼/텍스트/스크롤 완벽 복원\n- main.js: 모든 모달/트리거/공통 함수/상담 fetch 등 index.ejs와 동일하게 동작\n- custom.css: index.ejs의 <style> 및 반응형, 폼, 모달, 버튼, 안내문구 등 1:1 복사\n- index.html: consult-modal, gallery, sales-info 등 모든 구조/클래스/텍스트 1:1 복원\n- git 커밋 및 원격 저장소 푸시 완료\n

## 2025-07-24 작업 내역 (Django /manage/ 관리자 인증 및 계정 관리 UI 구축)

### 목적
- 기존 Django admin이 아닌 별도 관리자 페이지(/manage/)에 세션 기반 인증 및 계정 관리 기능 도입

### 주요 구현 내용
- /manage/ 하위 모든 뷰에 @login_required 데코레이터 적용(비로그인 시 접근 불가)
- /manage/login/ (로그인), /manage/logout/ (로그아웃), /manage/password_change/ (비밀번호 변경) URL 및 뷰 추가
- 로그인/비밀번호 변경 폼 템플릿(manage/login.html, manage/password_change.html) 및 공통 레이아웃(manage/base.html) 생성
- 로그인 성공 시 /manage/로 리다이렉트되도록 LOGIN_REDIRECT_URL 설정
- 템플릿 경로 및 권한 문제 해결, 서버 재시작 및 정상 동작 확인
- 관리자 계정 생성/삭제 등 추가 기능은 추후 구현 예정

### 적용 파일
- uljin_admin/main/urls.py
- uljin_admin/main/views.py
- uljin_admin/main/templates/manage/login.html
- uljin_admin/main/templates/manage/password_change.html
- uljin_admin/main/templates/manage/base.html
- uljin_admin/uljin_admin/settings.py
- docs/WORKFLOW.md (본 문서)

### 테스트 방법
1. /manage/login/에서 로그인 시도 → 성공 시 /manage/로 이동
2. 비밀번호 변경, 로그아웃 등 정상 동작 확인
3. 로그인하지 않으면 /manage/ 하위 접근 불가 확인

---

## 2025-07-31 작업 내역 (React Native 모바일 앱 개발 - AuthContext 디버깅)

### 문제 상황
- React Native 모바일 앱에서 로그인 API 호출은 성공하지만 AuthContext의 login 함수가 호출되지 않는 문제
- 두 개의 AuthContext.tsx 파일이 존재하여 어떤 파일이 실제로 사용되는지 불분명
- 터미널 감지 문제로 빌드 진행이 어려운 상황

### 해결 과정

#### 1. AuthContext 파일 중복 문제 해결
- `CrosswordPuzzleMobileApp/src/contexts/AuthContext.tsx`: 모바일 앱용 AuthContext
- `/var/www/html/CrosswordPuzzleApp/src/contexts/AuthContext.tsx`: 웹앱용 AuthContext
- 각 파일에 고유한 로그 메시지 추가로 실제 사용되는 파일 식별

#### 2. 디버깅 로그 추가
- 모바일 앱 AuthContext: `🚨 AuthContext login 함수 호출됨!`
- 웹앱 AuthContext: `🔥 CrosswordPuzzleApp AuthContext login 함수 호출됨!`
- API 서비스: `📡 요청 URL:`, `🔍 apiService.login 함수 호출 시작...` 등 상세 로그

#### 3. 빌드 시스템 개선
- `build-android.sh` 스크립트에 빌드 시간 자동 업데이트 기능 추가
- `BUILD_TIME` 상수를 LoginScreen.tsx에 자동 반영
- APK 설치 후 앱에서 빌드 시간 확인 가능

#### 4. 현재 상태
- ✅ **APK 빌드 성공**: 45M 크기, 2025-07-31 13:27:48 빌드
- ✅ **API 서버 정상**: 8080 포트에서 Laravel API 서비스 중
- ✅ **CORS 설정 완료**: 모든 도메인에서 API 접근 허용
- ⚠️ **AuthContext 호출 문제**: 로그인 성공 후 AuthContext login 함수 미호출
- ⚠️ **터미널 감지 문제**: 빌드 중 터미널 상태 감지 어려움

### 수정된 파일
- `CrosswordPuzzleMobileApp/src/screens/LoginScreen.tsx`: 상세 로그 추가, AuthContext 사용
- `CrosswordPuzzleMobileApp/src/services/api.ts`: API 호출 로그 강화
- `CrosswordPuzzleMobileApp/src/contexts/AuthContext.tsx`: 고유 로그 메시지 추가
- `/var/www/html/CrosswordPuzzleApp/src/contexts/AuthContext.tsx`: 고유 로그 메시지 추가
- `CrosswordPuzzleMobileApp/build-android.sh`: 빌드 시간 자동 업데이트

### 다음 작업 예정
1. **APK 재설치 및 테스트**: 새로 빌드된 APK로 AuthContext 로그 확인
2. **AuthContext 파일 식별**: 어떤 AuthContext 파일이 실제 사용되는지 확인
3. **로그인 플로우 완성**: AuthContext login 함수 정상 호출 및 화면 전환
4. **터미널 감지 개선**: 빌드 진행 상황 실시간 모니터링

### 중요 파일 위치
- **모바일 앱**: `/var/www/html/CrosswordPuzzleMobileApp/`
- **웹앱**: `/var/www/html/CrosswordPuzzleApp/`
- **API 서버**: `public/api.php` (8080 포트)
- **빌드 스크립트**: `CrosswordPuzzleMobileApp/build-android.sh`

### 디버깅 명령어
- **APK 빌드**: `cd CrosswordPuzzleMobileApp && ./build-android.sh`
- **API 테스트**: `curl -X POST http://222.100.103.227:8080/api/login -H "Content-Type: application/json" -d '{"email":"test@test.com","password":"123456"}'`
- **서비스 상태**: `ps aux | grep php` (API 서버 확인)
- **로그 확인**: `adb logcat` (Android 디바이스 로그)

---
