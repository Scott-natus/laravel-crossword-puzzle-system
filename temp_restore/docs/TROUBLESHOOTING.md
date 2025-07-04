# 문제 해결 가이드 (Troubleshooting)

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