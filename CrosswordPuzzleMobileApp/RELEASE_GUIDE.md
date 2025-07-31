# 📱 CrosswordPuzzleMobileApp 배포 가이드

## 🚀 Android 앱 배포

### 1. APK 빌드
```bash
# 빌드 스크립트 실행
cd /var/www/html/CrosswordPuzzleMobileApp
./build-android.sh
```

### 2. 앱 서명 (Google Play Store 배포용)
```bash
# 키스토어 생성
keytool -genkey -v -keystore crossword-puzzle-key.keystore -alias crossword-puzzle-key -keyalg RSA -keysize 2048 -validity 10000

# 서명된 APK 생성
jarsigner -verbose -sigalg SHA1withRSA -digestalg SHA1 -keystore crossword-puzzle-key.keystore app-release-unsigned.apk crossword-puzzle-key

# APK 최적화
zipalign -v 4 app-release-unsigned.apk CrosswordPuzzle-v1.0.apk
```

### 3. Google Play Console 업로드
1. Google Play Console 접속
2. 새 앱 생성
3. APK 파일 업로드
4. 앱 정보 입력 (제목, 설명, 스크린샷 등)
5. 개인정보처리방침 URL 추가
6. 출시 트랙 선택 (내부 테스트/비공개/공개)

## 🍎 iOS 앱 배포 (Mac 환경 필요)

### 1. Xcode에서 빌드
```bash
cd /var/www/html/CrosswordPuzzleMobileApp/ios
pod install
open CrosswordPuzzleMobileApp.xcworkspace
```

### 2. App Store Connect 업로드
1. Xcode에서 Archive 생성
2. Organizer에서 Distribute App 선택
3. App Store Connect 업로드
4. App Store Connect에서 앱 정보 설정

## 📋 배포 체크리스트

### 앱 설정
- [ ] 앱 아이콘 설정
- [ ] 스플래시 스크린 설정
- [ ] 앱 이름 및 설명
- [ ] 버전 번호 설정
- [ ] 권한 설정 확인

### 기능 테스트
- [ ] 로그인/회원가입
- [ ] 퍼즐게임 플레이
- [ ] 힌트 시스템
- [ ] 레벨 진행
- [ ] 오프라인 모드

### 성능 최적화
- [ ] 앱 크기 최적화
- [ ] 로딩 시간 개선
- [ ] 메모리 사용량 최적화
- [ ] 배터리 사용량 최적화

## 🔧 문제 해결

### 빌드 오류
```bash
# 캐시 정리
cd android && ./gradlew clean
cd .. && npx react-native start --reset-cache
```

### 서명 오류
```bash
# 키스토어 확인
keytool -list -v -keystore crossword-puzzle-key.keystore
```

## 📞 지원 정보
- 개발자: Your Name
- 이메일: your.email@example.com
- 버전: 1.0.0
- 최소 SDK: Android 21, iOS 12.0 