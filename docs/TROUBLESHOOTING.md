# 문제 해결 가이드 (Troubleshooting)

## 2025-07-18: Wordle 스타일 게임 상태 유지 시스템 문제 해결

### 문제: 새로고침 후 맞춘 정답들이 `***` 별표로 표시됨
**증상**: 새로고침 시 맞춘 정답들이 `***` 별표로 표시되어 사용자가 맞춘 정답을 확인할 수 없음
**원인**: 보안상 정답을 클라이언트에 전송하지 않아서 발생
**해결방법**:
1. **서버 측 수정**: `PuzzleGameController::getTemplate()`에서 맞춘 정답 단어 정보를 안전하게 전송
2. **클라이언트 측 수정**: `restoreGameState()` 함수에서 서버에서 받은 정답 정보 사용
3. **보안 고려사항**: 맞춘 단어만 전송하여 보안 유지

**수정된 파일**:
- `app/Http/Controllers/PuzzleGameController.php`: 정답 단어 정보 전송 로직 추가
- `resources/views/puzzle/game/index.blade.php`: 정답 복원 로직 수정

**테스트 방법**:
1. 퍼즐게임에서 몇 개 단어를 맞춤
2. 브라우저 새로고침
3. 맞춘 정답들이 정상적으로 표시되는지 확인

### 문제: 새로고침 시 새로운 퍼즐 생성
**증상**: 새로고침할 때마다 새로운 퍼즐이 생성되어 사용자가 어려운 퍼즐을 피해갈 수 있음
**원인**: 게임 상태를 저장하지 않아서 발생
**해결방법**:
1. **데이터베이스 스키마 확장**: `user_puzzle_games` 테이블에 퍼즐 세션 정보 저장 컬럼 추가
2. **모델 확장**: `UserPuzzleGame` 모델에 퍼즐 세션 관리 메서드 추가
3. **컨트롤러 수정**: `PuzzleGameController`에서 게임 상태 관리 로직 추가
4. **프론트엔드 수정**: JavaScript에서 게임 상태 복원 로직 구현

**구현된 기능**:
- Wordle처럼 게임 진행 상태를 데이터베이스에 저장
- 새로고침 시 동일한 퍼즐과 진행 상태 복원
- 정답/오답 상태, 힌트 사용 여부 등 추적

---

## 0. 개발 환경 확인

### 원격 개발 환경 (222.100.103.227)
- **SSH 접속**: 원격 서버에 직접 연결하여 작업
- **포트 구성**:
  - Laravel: 80 포트
  - React 프론트엔드: 3000 포트
  - React API: 5050 포트
- **데이터베이스**: 127.0.0.1 (서버 내부 접근)

## 1. 데이터베이스 접속 문제

### 문제: psql 접속 실패
```
psql: error: "localhost" (127.0.0.1), 5432 포트로 서버 접속 할 수 없음: FATAL: password authentication failed
```

**해결책:**
1. 즉시 환경 설정 확인: `cat .env`
2. 올바른 접속 정보 사용:
   ```bash
   PGPASSWORD=tngkrrhk psql -h 127.0.0.1 -U myuser -d mydb
   ```

**주의사항:**
- `.env` 파일이 존재함에도 불구하고 `file_search`로 찾지 못할 수 있음
- 불필요한 중간 단계 건너뛰고 바로 환경 설정 확인

### 문제: 테이블이 존재하지 않음
```
ERROR: relation "table_name" does not exist
```

**해결책:**
1. 테이블 목록 확인: `\dt`
2. 스키마 확인: `\dn`
3. 마이그레이션 실행: `php artisan migrate`

## 2. 데이터베이스 스키마 변경 시 주의사항

### 변경 전 필수 절차
1. **영향도 분석**: 변경 작업이 데이터에 미치는 영향 상세 분석
2. **사용자 확인**: 변경 작업 진행 여부 및 백업 진행 여부 확인
3. **승인 후 진행**: 사용자 승인 후에만 작업 진행
4. **스키마 기록**: `/var/www/html/puzzle_db_schema.sql`에 변경사항 추가

### 데이터 조작 작업 규칙 (매우 중요!)
- **대화형 진행 필수**: INSERT, UPDATE, DELETE, TRUNCATE, DROP 작업 시 반드시 사용자와 대화형으로 진행
- **작업 전 확인 절차**:
  1. 실행할 SQL 문을 먼저 보여주기
  2. 예상 결과와 영향 범위 설명
  3. 사용자 승인 요청
  4. 승인 후에만 실행
- **단계별 진행**: 복잡한 작업은 단계별로 나누어 진행
- **실행 후 확인**: 작업 완료 후 결과 확인 및 보고

### 문제: 스키마 변경 후 서비스 오류
**원인:** 데이터베이스 스키마와 코드 불일치

**해결책:**
1. 백업에서 복원
2. 단계별 변경 진행
3. 각 단계마다 테스트

## 3. 500 에러 (서버 내부 오류)

### 문제: 힌트 생성 시 500 에러
```
Column 'difficulty' doesn't exist in table 'pz_hints'
```

**원인:** 데이터베이스 스키마와 모델/컨트롤러 불일치

**해결책:**
1. 테이블 구조 확인: `\d puzzle_hints`
2. 누락된 컬럼 추가:
   ```sql
   ALTER TABLE puzzle_hints ADD COLUMN difficulty VARCHAR(20) NOT NULL DEFAULT 'medium';
   ```
3. 컬럼명 통일 (content → hint_text)

### 문제: 컬럼명 불일치
```
Column 'content' doesn't exist in table 'puzzle_hints'
```

**해결책:**
- 모든 코드에서 `hint_text` 사용 (모델, 컨트롤러, 뷰)
- 데이터베이스 스키마와 일치하도록 수정

## 4. UI/UX 문제

### 문제: 드롭다운 메뉴 작동 안함
**원인:** Bootstrap JavaScript 중복 로드

**해결책:**
1. `resources/views/layouts/app.blade.php`에서 중복 스크립트 제거
2. Vite로 로드되는 Bootstrap만 사용

### 문제: 페이지네이션 오류
**원인:** 클라이언트사이드 필터링과 서버사이드 페이지네이션 충돌

**해결책:**
1. 모든 필터링/검색을 서버사이드로 구현
2. Laravel 기본 `links()` 대신 Bootstrap 커스텀 페이지네이션 사용

### 문제: 정렬 기능 작동 안함
**원인:** 클라이언트사이드 정렬 구현

**해결책:**
1. 컨트롤러에서 GET 파라미터 처리
2. 서버사이드 정렬 구현

### 문제: 게시판별 글쓰기 버튼이 항상 같은 게시판으로 이동
**원인:** AppServiceProvider에서 전역 변수 공유 시 컨텍스트 문제

**해결책:**
1. AppServiceProvider에서 전역 `$boardType` 변수 제거
2. 레이아웃에서 현재 URL의 `boardType` 파라미터를 직접 읽어 링크 생성
3. 각 게시판에서 올바른 글쓰기 페이지로 이동하는지 확인

**구현 예시:**
```php
// layouts/app.blade.php
@php
    $currentBoardType = request()->route('boardType');
@endphp
<a href="{{ route('board.create', ['boardType' => $currentBoardType]) }}" class="btn btn-primary">글쓰기</a>
```

### 문제: 그리드 템플릿 저장 시 사용자가 선택한 번호가 무시됨
**원인:** PuzzleGridTemplateService의 saveTemplate()에서 sortWordPositionsWithPriority() 함수가 사용자 선택 번호를 재할당

**해결책:**
1. `app/Services/PuzzleGridTemplateService.php`의 `saveTemplate()` 메서드에서 `sortWordPositionsWithPriority()` 호출 제거
2. 사용자가 입력한 `word_positions`를 그대로 저장
3. 불필요한 `sortWordPositionsWithPriority()` 메서드 삭제

**수정된 코드:**
```php
public function saveTemplate($template)
{
    try {
        // 사용자가 입력한 번호를 그대로 유지 (정렬하지 않음)
        $wordPositions = $template['word_positions'];
        
        $insertData = [
            // ... 기타 필드들
            'word_positions' => json_encode($wordPositions),
            // ... 나머지 필드들
        ];
        
        $id = DB::table('puzzle_grid_templates')->insertGetId($insertData);
        return $id;
    } catch (\Exception $e) {
        throw $e;
    }
}
```

### 문제: 템플릿 수정 시 번호 정보가 변경됨
**원인:** 수정 모드에서도 자동 넘버링이 적용됨

**해결책:**
1. 수정 모드에서는 기존 번호 정보 유지
2. 사용자가 변경한 번호만 업데이트
3. 자동 넘버링 비활성화

### 문제: 단어 추출 시 단어 순서가 뒤섞여 표시됨
**원인:** 백엔드에서 word_positions를 정렬하지 않고 처리

**해결책:**
1. `app/Http/Controllers/GridTemplateController.php`의 `extractWords` 메서드에서 word_positions를 id 순서대로 정렬
2. 정렬 로직 활성화:
   ```php
   usort($wordPositions, function($a, $b) {
       return $a['id'] - $b['id'];
   });
   ```

### 문제: 템플릿 수정 시 사용자가 선택한 번호가 저장되지 않음
**원인:** 수정 모드에서 word_positions의 id 값을 업데이트하지 않음

**해결책:**
1. `resources/views/puzzle/grid-templates/create.blade.php`의 폼 제출 로직에서 수정 모드일 때 사용자가 선택한 번호로 word_positions의 id 값 업데이트
2. 번호 매핑 생성 후 word_positions의 id 값 변경:
   ```javascript
   if (isEditMode && wordNumbering.length > 0) {
       const numberMapping = {};
       wordNumbering.forEach(item => {
           numberMapping[item.word_id] = item.order;
       });
       
       wordPositions.forEach(word => {
           if (numberMapping[word.id]) {
               word.id = numberMapping[word.id];
           }
       });
   ```

## 5. 단어 난이도 업데이트 시스템 문제

### 문제: 단어 난이도 업데이트가 힌트 생성과 함께 실행되어 성능 저하
**원인:** 힌트 생성 API에서 단어 난이도도 함께 업데이트하여 API 응답 시간 증가

**해결책:**
1. 힌트 생성과 단어 난이도 업데이트 완전 분리
2. 임시 테이블(tmp_pz_word_difficulty) 생성하여 업데이트 관리
3. UpdateWordDifficultyScheduler artisan 명령어로 별도 처리

### 문제: 단어 난이도 업데이트 스케줄러가 실행되지 않음
**원인:** Laravel 스케줄러 등록 누락 또는 크론탭 설정 문제

**해결책:**
1. `app/Console/Kernel.php`에 스케줄러 등록 확인:
   ```php
   $schedule->command('puzzle:update-word-difficulty')->everyTenMinutes();
   ```
2. 크론탭 설정 확인:
   ```bash
   */10 * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
   ```
3. 수동 실행으로 테스트:
   ```bash
   php artisan puzzle:update-word-difficulty
   ```

### 문제: Gemini API에서 난이도 평가 응답이 예상 형식과 다름
**원인:** 프롬프트가 명확하지 않거나 응답 파싱 로직 오류

**해결책:**
1. GeminiService.php의 난이도 평가 프롬프트 확인
2. 응답 형식이 `[단어,난이도]` 형태인지 확인
3. 정규식 파싱 로직 점검:
   ```php
   preg_match_all('/\[([^,]+),(\d+)\]/', $response, $matches);
   ```

### 문제: 임시 테이블에 데이터가 없음
**원인:** 기존 단어 데이터가 임시 테이블에 복사되지 않음

**해결책:**
1. 임시 테이블 생성 확인:
   ```sql
   CREATE TABLE tmp_pz_word_difficulty (
       id BIGSERIAL PRIMARY KEY,
       word_id BIGINT NOT NULL,
       word VARCHAR(255) NOT NULL,
       update_yn CHAR(1) DEFAULT 'n',
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
   ```
2. 기존 단어 데이터 복사:
   ```sql
   INSERT INTO tmp_pz_word_difficulty (word_id, word)
   SELECT id, word FROM puzzle_words WHERE is_active = true;
   ```

### 문제: 특정 템플릿에서 번호가 잘못 저장됨
**원인:** 일부 템플릿이 자동 생성된 번호로 저장되어 있음

**해결책:**
1. 문제가 있는 템플릿 확인: `SELECT id, template_name, word_positions FROM puzzle_grid_templates WHERE template_name LIKE '%템플릿 #14%' OR template_name LIKE '%템플릿 #15%';`
2. 문제 템플릿 비활성화: `UPDATE puzzle_grid_templates SET is_active = false WHERE template_name LIKE '%템플릿 #14%' OR template_name LIKE '%템플릿 #15%';`
3. 새로운 템플릿 생성 또는 기존 템플릿 수정

## 5. 로그 시스템 문제

### 문제: 로그가 생성되지 않음
**원인:** config/logging.php에서 모든 로그 채널이 NullHandler로 설정

**해결책:**
1. config/logging.php 설정 확인
2. 모든 로그 채널이 NullHandler로 설정되지 않았는지 확인
3. 올바른 로그 설정으로 수정:
   ```php
   'single' => [
       'driver' => 'single',
       'path' => storage_path('logs/laravel.log'),
       'level' => env('LOG_LEVEL', 'debug'),
   ],
   ```
4. 설정 캐시 클리어: `php artisan config:clear`
5. 실시간 로그 확인: `tail -f storage/logs/laravel.log`

### 문제: 로그 레벨 설정 오류
**원인:** .env 파일에서 LOG_CHANNEL 설정 문제

**해결책:**
1. .env 파일에서 LOG_CHANNEL 확인
2. 기본값은 'stack' 또는 'single' 사용
3. 설정 변경 후 config:clear 실행

### 문제: 디버깅 로그가 보이지 않음
**원인:** 로그 설정 또는 코드 문제

**해결책:**
1. 컨트롤러에서 로그 코드 확인:
   ```php
   \Log::info('Board index method called:', [
       'requested_slug' => $boardTypeSlug,
       'found_boardType_id' => $boardType->id
   ]);
   ```
2. 로그 파일 권한 확인: `chmod 755 storage/logs/`
3. 로그 파일 존재 확인: `ls -la storage/logs/`

## 6. API 관련 문제

### 문제: Gemini API 호출 실패
**원인:** 개별 호출로 인한 비효율성

**해결책:**
1. 일괄 처리로 변경
2. 정규식으로 응답 파싱
3. 에러 처리 및 재시도 로직 추가

### 문제: API 응답 파싱 오류
**원인:** 응답 형식 불일치

**해결책:**
1. 정규식 패턴 확인
2. 응답 형식 표준화
3. 디버깅 로그 추가

## 7. 포트 및 서비스 충돌 문제

### 문제: 포트 충돌
**원인:** Laravel과 React 서비스 간 포트 충돌

**해결책:**
1. 포트 사용 현황 확인: `netstat -tulpn | grep :80`
2. 서비스별 포트 확인:
   - Laravel: 80 포트
   - React 프론트엔드: 3000 포트
   - React API: 5050 포트

### 문제: React 기능이 Laravel에 영향
**원인:** API 엔드포인트 변경 시 Laravel 측 영향

**해결책:**
1. Laravel 서비스 영향도 분석
2. API 호환성 확인
3. 단계별 배포

## 8. 성능 문제

### 문제: 페이지 로딩 느림
**원인:** N+1 쿼리 문제

**해결책:**
1. Eloquent 관계에서 `with()` 사용
2. 쿼리 최적화
3. 인덱스 추가

### 문제: 메모리 사용량 높음
**원인:** 대량 데이터 처리

**해결책:**
1. 청크 단위 처리
2. 메모리 제한 설정
3. 배치 처리 구현

## 9. 백업 및 복구 문제

### 문제: 백업 파일이 너무 큼
**해결책:**
```bash
# 특정 테이블만 백업
pg_dump -h 127.0.0.1 -U myuser -t puzzle_levels mydb > puzzle_levels_backup.sql

# 압축 백업
pg_dump -h 127.0.0.1 -U myuser mydb | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz
```

### 문제: 백업 복원 실패
**해결책:**
1. 데이터베이스 연결 확인
2. 권한 확인
3. 충돌하는 데이터 확인

## 10. 개발 환경 문제

### 문제: 파일 권한 오류
**해결책:**
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### 문제: 캐시 문제
**해결책:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 문제: 설정 변경이 반영되지 않음
**원인:** 설정 캐시 문제

**해결책:**
1. 설정 캐시 클리어: `php artisan config:clear`
2. 애플리케이션 캐시 클리어: `php artisan cache:clear`
3. 뷰 캐시 클리어: `php artisan view:clear`

## 11. 게시판 시스템 문제

### 문제: 게시판 타입별 데이터 불일치
**원인:** board_type_id 연결 문제

**해결책:**
1. board_types 테이블 데이터 확인:
   ```sql
   SELECT * FROM board_types;
   ```
2. boards 테이블의 board_type_id 확인:
   ```sql
   SELECT b.id, b.title, bt.name as board_type 
   FROM boards b 
   JOIN board_types bt ON b.board_type_id = bt.id;
   ```

### 문제: 게시판 URL 라우팅 오류
**원인:** 라우트 파라미터 문제

**해결책:**
1. routes/web.php에서 게시판 라우트 확인
2. 컨트롤러에서 boardType 파라미터 처리 확인
3. 뷰에서 올바른 URL 생성 확인

## 12. 일반적인 디버깅 방법

### 로그 확인
```bash
# 실시간 로그 확인
tail -f storage/logs/laravel.log

# 최근 로그 확인
tail -50 storage/logs/laravel.log

# 특정 키워드로 로그 검색
grep "Board index method" storage/logs/laravel.log
```

### 데이터베이스 확인
```bash
# PostgreSQL 접속
PGPASSWORD=tngkrrhk psql -h 127.0.0.1 -U myuser -d mydb

# 테이블 구조 확인
\d table_name

# 데이터 확인
SELECT * FROM table_name LIMIT 10;
```

### 환경 설정 확인
```bash
# .env 파일 확인
cat .env

# Laravel 설정 확인
php artisan tinker --execute="echo config('database.default');"
```

## 13. 최근 해결된 문제들

### 2025-06-24: 게시판별 글쓰기 버튼 문제
**문제:** 모든 게시판에서 글쓰기 버튼이 프로젝트 게시판으로 이동
**해결:** AppServiceProvider에서 전역 변수 제거, 레이아웃에서 현재 URL 파라미터 직접 읽기

### 2025-06-24: 로그 시스템 문제
**문제:** 로그가 생성되지 않음
**해결:** config/logging.php에서 NullHandler 설정을 올바른 로그 설정으로 변경

### 2025-06-22: 데이터베이스 스키마 변경
**문제:** puzzle_levels 테이블 구조 변경 필요
**해결:** 백업 후 일괄 업데이트, 스키마 문서화 

## 2025-06-30 크론탭 및 배치 스케줄러 문제 해결
- 서버 OS 재설치 후 크론탭 미설정 → 크론탭(`*/10 * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1`) 재등록
- storage/logs/laravel.log 권한 문제 → 소유자 및 권한을 www-data: 775로 변경
- 로그 파일 삭제 없이 권한만 조정, 필요시 백업 후 신규 생성
- 배치 스케줄러 정상 동작 확인 (로그 tail 및 dry-run 테스트)

## 2025-06-30 단어 추출 로직 개선 및 크론 권한 문제 해결
- 단어 추출 로직에서 난이도 조건 제거: extractIndependentWord, extractWordWithConfirmedSyllables 메서드에서 단어/힌트 난이도 필터링 제거
- 단어 추출 실패 시 재시도 로직 구현: 최대 5회까지 확정된 단어를 초기화하고 처음부터 재시도
- 크론 배치 작업 권한 문제 해결: natus-server를 www-data 그룹에 추가 (sudo usermod -a -G www-data natus-server)
- convertDifficultyToEnglish 메서드 제거 (더 이상 사용되지 않음)
- 재시도 로직에서 실패 시 "단어 ID X에서 확정된 음절들과 매칭되는 단어를 찾을 수 없습니다." 메시지 출력 

## 2025-07-01 퍼즐 게임 및 관리 시스템 문제해결 내역
- 교차점 클릭 시 배지 우선 단어 선택, 힌트 표시 오류 해결
- 정답 입력 시 배지와 정답 겹침/숨김 UI 문제 해결
- 모든 단어 정답 시 레벨 완료 모달 정상 동작 확인
- 상단 타이틀 링크 /home 미동작 이슈 수정
- 소스코드/핵심소스 백업 명령어 정리
- phpPgAdmin 완전 삭제 및 의존성 정리
- git 커밋/푸시, 소스 백업 정상 동작 확인 

### 첨부파일(이미지) 미노출 문제

- 증상: 첨부파일이 업로드는 되지만 웹에서 '이미지 없음' 또는 미리보기가 되지 않음
- 원인: `public/storage` 심볼릭 링크 미설정
- 해결: 서버에서 `php artisan storage:link` 실행 후 정상 노출되는지 확인
- 참고: storage/app/public/attachments 경로에 파일이 실제로 존재하는지, 퍼미션(권한)도 함께 점검 

- 만약 storage:link 후에도 미노출이면 public/storage, storage/app/public, attachments 폴더 및 파일 권한이 www-data(웹서버)로 되어 있는지 확인 

## 2025-07-01 삭제 기능 JS 이벤트 연결 문제
- 증상: 삭제 버튼 클릭 시 아무 반응 없음, 네트워크 요청도 발생하지 않음
- 원인: confirmDelete 버튼에 JS 이벤트가 DOMContentLoaded 전에 연결되어 실제로 연결되지 않음
- 해결: 이벤트 연결을 document.addEventListener('DOMContentLoaded', ...) 내부로 이동 

# 2025-07-07 문제 해결 내역

## 1. 파일 업로드 413 에러 (Content Too Large)
### 문제
- 300MB 파일 업로드 시 413 에러 발생
- PHP 설정 제한: upload_max_filesize = 2M, post_max_size = 8M

### 해결 방법
1. **PHP 설정 변경** (/etc/php/8.2/fpm/php.ini):
   ```ini
   upload_max_filesize = 500M
   post_max_size = 500M
   memory_limit = 512M
   max_execution_time = 300
   ```

2. **Apache 설정 변경** (/etc/apache2/apache2.conf):
   ```
   LimitRequestBody 524288000
   ```

3. **Laravel Validation 변경**:
   ```php
   'max:512000' // 500MB
   ```

## 2. WebDAV Internal Server Error
### 문제
- WebDAV 접속 시 500 Internal Server Error
- "AuthType Digest configured without corresponding module" 에러

### 해결 방법
1. **Digest → Basic 인증 변경**:
   ```apache
   AuthType Basic
   AuthName "WebDAV Restricted Access"
   AuthUserFile /etc/apache2/webdav.passwd
   Require valid-user
   ```

2. **중복 설정 제거**:
   - `/etc/apache2/sites-available/000-default.conf`에서 WebDAV 설정 제거
   - SSL 설정 파일(`natus-project-ssl.conf`)에만 WebDAV 설정 유지

3. **모듈 활성화**:
   ```bash
   sudo a2enmod dav dav_fs auth_digest
   sudo systemctl restart apache2
   ```

## 3. WebDAV 인증 팝업 미출현
### 문제
- 인증 팝업창이 뜨지 않고 바로 401 Unauthorized 발생

### 해결 방법
1. **인증 헤더 명시적 설정**:
   ```apache
   Header always set WWW-Authenticate 'Basic realm="WebDAV Restricted Access"'
   ```

2. **브라우저 캐시 삭제**
3. **시크릿 모드로 테스트**

## 4. NTFS 파티션 손상
### 문제
- /dev/sdb3 마운트 시 "NTFS is inconsistent" 에러

### 해결 방법
1. **NTFS 복구**:
   ```bash
   sudo ntfsfix /dev/sdb3
   ```

2. **ext4로 포맷** (권장):
   ```bash
   sudo mkfs.ext4 /dev/sdb3
   sudo mount /dev/sdb3 /mnt/nas_storage/hdd_data
   ```

## 5. WebDAV 권한 문제
### 문제
- HDD 마운트 후 WebDAV에서 파일 읽기/쓰기 불가

### 해결 방법
1. **소유권 변경**:
   ```bash
   sudo chown -R www-data:www-data /mnt/nas_storage/hdd_data
   ```

## 주의사항
- 파일 업로드 용량 변경 시 PHP-FPM과 Apache 모두 재시작 필요
- WebDAV 설정 변경 시 인증 팝업 캐시 문제 가능성
- NTFS → ext4 포맷 시 모든 데이터 삭제됨

### 2025-07-09 단어 난이도 평가/업데이트 관련 문제해결

- Gemini API 429(쿼터 초과) 발생 시: 대기 또는 API 키 교체, 쿼터 복구 후 재시도
- Gemini 2.5 flash 모델은 단어 생성 불가(빈 응답), 1.5 flash로 원복 필요
- 프롬프트 구조 단순화 후 분포 변화, 신규/재측정 방식 차이 분석
- DB/로그/응답값 불일치 시 직접 쿼리 및 로그 확인

## 2025-07-12 오답 초과 안내박스 위치/동작 문제 및 파일/백업 이슈

- 안내박스가 중앙/하단/입력박스 위 등 요구에 따라 반복적으로 위치가 꼬임
- transform, top, px, %, calc 등 다양한 위치 지정 방식이 React Native Web에서 다르게 동작함을 경험
- 오버레이와 안내박스가 다른 부모/렌더링 순서/zIndex 꼬임으로 안내박스가 오버레이 뒤에 깔리는 문제 반복
- ref+absolute 방식으로 입력/버튼 박스와 정확히 겹치게 시도, measure/레이아웃 측정 활용
- AI가 파일을 삭제/비우는 일은 절대 없으며, 실제로는 서버 소스는 멀쩡했으나 에디터/AI 쪽에서 읽기 오류 발생
- 서버 소스 전체 풀백업 진행, git/백업/복구 방법 숙지
- 실서비스에서 반복 피드백/테스트로 최종 구조 확정### 2025-07-17
- 클리어 조건/횟수 실시간 반영 문제: 프론트엔드 중복 호출 방지로 해결

