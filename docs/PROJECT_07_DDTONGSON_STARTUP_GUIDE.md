# 프로젝트 7번: 로또 비당첨확률 번호 제공 서비스 "똥손" 개발 시작 가이드

## 🚀 프로젝트 개요
- **프로젝트명**: 똥손 (로또 비당첨확률 번호 제공 서비스)
- **프로젝트 번호**: #7
- **개발 환경**: 222.100.103.227 (기존 서버 활용)
- **기술 스택**: Laravel + React Native + PostgreSQL + Redis

## 📋 1단계: 프로젝트 초기 설정 (1-2일)

### 1.1 프로젝트 디렉토리 생성
```bash
# 서버 접속
ssh root@222.100.103.227

# 프로젝트 디렉토리 생성
cd /var/www/html
mkdir project07-ddongsun
cd project07-ddongsun

# Laravel 프로젝트 생성
composer create-project laravel/laravel backend
cd backend
```

### 1.2 데이터베이스 설정
```bash
# PostgreSQL 데이터베이스 생성
sudo -u postgres psql
CREATE DATABASE ddongsun_db;
CREATE USER ddongsun_user WITH PASSWORD 'ddongsun_password';
GRANT ALL PRIVILEGES ON DATABASE ddongsun_db TO ddongsun_user;
\q

# Laravel .env 설정
cp .env.example .env
# .env 파일 수정
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ddongsun_db
DB_USERNAME=ddongsun_user
DB_PASSWORD=ddongsun_password
```

### 1.3 React Native 프로젝트 생성
```bash
# 프로젝트 루트로 이동
cd /var/www/html/project07-ddongsun

# React Native 프로젝트 생성
npx react-native init DdongsunApp --template react-native-template-typescript
cd DdongsunApp

# 웹 지원 추가
npm install @react-native-community/cli-platform-web
```

## 📋 2단계: 기본 인증 시스템 구축 (2-3일)

### 2.1 Laravel Sanctum 설정
```bash
cd /var/www/html/project07-ddongsun/backend

# Sanctum 설치
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate

# 사용자 모델 수정
# app/Models/User.php에 Sanctum trait 추가
```

### 2.2 인증 API 구현
```bash
# 인증 컨트롤러 생성
php artisan make:controller AuthController

# API 라우트 설정
# routes/api.php에 인증 라우트 추가
```

### 2.3 React Native 인증 화면
```bash
cd /var/www/html/project07-ddongsun/DdongsunApp

# 필요한 패키지 설치
npm install @react-navigation/native @react-navigation/stack
npm install react-native-screens react-native-safe-area-context
npm install @react-native-async-storage/async-storage
```

## 📋 3단계: 데이터베이스 스키마 구축 (2-3일)

### 3.1 핵심 테이블 마이그레이션 생성
```bash
cd /var/www/html/project07-ddongsun/backend

# 마이그레이션 생성
php artisan make:migration create_lotto_tickets_table
php artisan make:migration create_lotto_results_table
php artisan make:migration create_ddongsun_rankings_table
php artisan make:migration create_number_statistics_table
php artisan make:migration add_ddongsun_fields_to_users_table
```

### 3.2 테이블 구조 정의
각 마이그레이션 파일에서 다음 구조 정의:

#### users 테이블 확장
```php
// add_ddongsun_fields_to_users_table
$table->integer('total_ddongsun_power')->default(0);
$table->string('current_level')->default('브론즈');
$table->string('profile_image')->nullable();
```

#### lotto_tickets 테이블
```php
$table->id();
$table->foreignId('user_id')->constrained()->onDelete('cascade');
$table->string('image_path');
$table->json('numbers'); // 선택된 번호들
$table->integer('ddongsun_power'); // 해당 용지의 똥손력
$table->date('upload_date');
$table->timestamps();
```

#### lotto_results 테이블
```php
$table->id();
$table->integer('round_number'); // 회차
$table->json('winning_numbers'); // 당첨 번호들
$table->date('draw_date');
$table->timestamps();
```

### 3.3 기본 시드 데이터 생성
```bash
# 시더 생성
php artisan make:seeder LottoResultsSeeder
php artisan make:seeder TestUsersSeeder

# 시드 실행
php artisan db:seed --class=LottoResultsSeeder
php artisan db:seed --class=TestUsersSeeder
```

## 📋 4단계: 로또 용지 업로드 시스템 (3-4일)

### 4.1 이미지 업로드 API
```bash
# 컨트롤러 생성
php artisan make:controller LottoTicketController

# 파일 업로드 미들웨어 설정
# config/filesystems.php에서 스토리지 설정
```

### 4.2 OCR 서비스 연동
```bash
# Google Cloud Vision API 패키지 설치
composer require google/cloud-vision

# OCR 서비스 클래스 생성
php artisan make:service OcrService
```

### 4.3 React Native 업로드 화면
```bash
cd /var/www/html/project07-ddongsun/DdongsunApp

# 이미지 관련 패키지 설치
npm install react-native-image-picker
npm install react-native-camera
```

## 📋 5단계: 똥손력 계산 시스템 (2-3일)

### 5.1 계산 알고리즘 구현
```bash
# 서비스 클래스 생성
php artisan make:service DdongsunPowerCalculator

# 계산 로직 구현:
# 1. 당첨 번호와의 거리 계산
# 2. 통계적 확률 반영
# 3. 복합 점수 계산
```

### 5.2 실시간 업데이트 로직
```bash
# 이벤트 및 리스너 생성
php artisan make:event LottoTicketUploaded
php artisan make:listener CalculateDdongsunPower
```

## 📋 6단계: 랭킹 시스템 (2-3일)

### 6.1 Redis 설정
```bash
# Redis 설치 (이미 설치되어 있을 수 있음)
sudo apt update
sudo apt install redis-server

# Laravel Redis 패키지 설치
composer require predis/predis
```

### 6.2 랭킹 계산 로직
```bash
# 랭킹 서비스 생성
php artisan make:service RankingService

# 스케줄러 생성
php artisan make:command CalculateWeeklyRankings
```

## 📋 7단계: 똥손 픽 시스템 (2-3일)

### 7.1 통계 계산 로직
```bash
# 통계 서비스 생성
php artisan make:service NumberStatisticsService

# API 엔드포인트 구현
php artisan make:controller DdongsunPickController
```

### 7.2 시각화 컴포넌트
```bash
cd /var/www/html/project07-ddongsun/DdongsunApp

# 차트 라이브러리 설치
npm install react-native-chart-kit
npm install react-native-svg
```

## 📋 8단계: 관리자 시스템 (2-3일)

### 8.1 관리자 대시보드
```bash
# 관리자 컨트롤러 생성
php artisan make:controller AdminController

# 관리자 미들웨어 생성
php artisan make:middleware AdminMiddleware
```

### 8.2 데이터 관리 기능
```bash
# 당첨 번호 관리 API
php artisan make:controller LottoResultController

# 사용자 관리 API
php artisan make:controller AdminUserController
```

## 📋 9단계: 테스트 및 최적화 (2-3일)

### 9.1 테스트 작성
```bash
# 테스트 생성
php artisan make:test LottoTicketTest
php artisan make:test DdongsunPowerTest
php artisan make:test RankingTest
```

### 9.2 성능 최적화
```bash
# 데이터베이스 인덱스 추가
# 캐싱 전략 구현
# API 응답 최적화
```

## 📋 10단계: 배포 및 모니터링 (1-2일)

### 10.1 배포 설정
```bash
# Nginx 설정
# SSL 인증서 설정
# 환경 변수 설정
```

### 10.2 모니터링 설정
```bash
# 로그 설정
# 에러 모니터링
# 성능 모니터링
```

## 🎯 개발 우선순위 체크리스트

### 🔥 1주차 목표 (MVP)
- [ ] 프로젝트 초기 설정 완료
- [ ] 기본 인증 시스템 구축
- [ ] 데이터베이스 스키마 구축
- [ ] 로또 용지 업로드 기본 기능

### ⚡ 2주차 목표 (핵심 기능)
- [ ] OCR 연동 완료
- [ ] 똥손력 계산 시스템
- [ ] 기본 프론트엔드 구현
- [ ] 랭킹 시스템 기반 구축

### 🎯 3주차 목표 (완성도)
- [ ] 똥손 픽 시스템 완성
- [ ] 관리자 시스템 구축
- [ ] 테스트 및 최적화
- [ ] 배포 및 모니터링

## 🛠️ 개발 환경 설정 명령어

### 서버 접속 및 기본 설정
```bash
# 서버 접속
ssh root@222.100.103.227

# 프로젝트 디렉토리로 이동
cd /var/www/html/project07-ddongsun

# 권한 설정
sudo chown -R www-data:www-data /var/www/html/project07-ddongsun
sudo chmod -R 755 /var/www/html/project07-ddongsun
```

### 개발 서버 실행
```bash
# Laravel 개발 서버
cd backend
php artisan serve --host=0.0.0.0 --port=8080

# React Native 개발 서버
cd ../DdongsunApp
npm run web
```

## 📊 진행 상황 추적

### 일일 체크리스트
- [ ] 오늘의 목표 설정
- [ ] 코드 커밋 및 푸시
- [ ] 테스트 실행
- [ ] 문서 업데이트
- [ ] 다음 날 계획 수립

### 주간 리뷰
- [ ] 목표 달성도 확인
- [ ] 문제점 및 해결방안
- [ ] 다음 주 계획 조정
- [ ] 팀 회의 (필요시)

## 🚨 주의사항

### 보안
- API 키는 환경 변수로 관리
- 사용자 데이터 암호화
- 파일 업로드 보안 검증

### 성능
- 데이터베이스 쿼리 최적화
- 이미지 압축 및 최적화
- 캐싱 전략 구현

### 확장성
- 마이크로서비스 아키텍처 고려
- 데이터베이스 파티셔닝 계획
- 로드 밸런싱 준비

## 📞 지원 및 문의

### 기술적 문제
- Laravel 공식 문서 참조
- React Native 공식 문서 참조
- Stack Overflow 검색

### 프로젝트 관리
- GitHub Issues 활용
- 프로젝트 문서 업데이트
- 정기적인 코드 리뷰

---

**프로젝트 7번 "똥손" 개발을 시작합니다! 🚀**

각 단계별로 체계적으로 진행하여 3주 내에 MVP를 완성하고, 사용자 피드백을 받아 지속적으로 개선해 나가겠습니다.



