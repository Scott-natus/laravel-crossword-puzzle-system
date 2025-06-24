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

## 5. API 관련 문제

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

## 6. 포트 및 서비스 충돌 문제

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

## 7. 성능 문제

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

## 8. 백업 및 복구 문제

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

## 9. 개발 환경 문제

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
php artisan view:clear
```

## 10. 일반적인 디버깅 방법

### 로그 확인
```bash
tail -f storage/logs/laravel.log
```

### 데이터베이스 쿼리 확인
```php
// app/Providers/AppServiceProvider.php에 추가
DB::listen(function($query) {
    Log::info($query->sql, $query->bindings);
});
```

### 환경 설정 확인
```bash
php artisan tinker --execute="echo config('database.default');"
```

### 포트 사용 현황 확인
```bash
netstat -tulpn | grep :80
netstat -tulpn | grep :3000
netstat -tulpn | grep :5050
```

## 11. 예방 조치

1. **정기 백업**: 자동 백업 스크립트 설정
2. **코드 리뷰**: 변경사항 검토
3. **테스트**: 변경 후 기능 테스트
4. **문서화**: 모든 변경사항 기록
5. **영향도 분석**: 데이터베이스 변경 전 필수
6. **사용자 확인**: 모든 중요 변경사항 승인 후 진행
7. **대화형 진행**: 데이터베이스 조작 작업 시 반드시 사용자와 대화형으로 진행

## 1. React/Laravel API 연동 500 에러
- storage, session, cache 권한 문제 → chown/chmod로 권한 변경
- 캐시/세션 드라이버 array로 변경

## 2. CORS 오류
- public/index.php의 직접 CORS 헤더 제거
- config/cors.php에서 allowed_origins만 관리

## 3. 419 CSRF 에러
- VerifyCsrfToken 미들웨어 except에 puzzle/hint-generator/* 추가

## 4. 퍼즐/힌트/레벨 데이터 문제
- puzzle_levels 테이블 백업 후 신규 구조로 일괄 업데이트
- 대량 작업은 artisan 명령어/스케줄러로 처리

## 5. 자동화/스케줄러
- 크론탭에 schedule:run 등록, Kernel.php에서 스케줄러 관리
- 로그는 storage/logs/hint-scheduler.log 확인

## 6. 백업/복구
- DB/소스 전체 백업은 backups/ 폴더에 저장
- 복구 시 pg_restore, tar 명령 사용 