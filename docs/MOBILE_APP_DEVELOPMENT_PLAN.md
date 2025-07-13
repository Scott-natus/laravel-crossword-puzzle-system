# 모바일 앱 개발 작업계획

## 프로젝트 구성

### 1. 라라벨 웹서비스 (1번째 프로젝트)
- **포트**: 80
- **URL**: http://222.100.103.227/
- **기능**: 게시판, 관리자 페이지, 퍼즐 관리 시스템
- **상태**: 기존 유지 (변경 없음)
- **위치**: `/var/www/html/` (라라벨 루트)

### 2. React Native 웹앱 (2번째 프로젝트)
- **포트**: 3001
- **URL**: http://222.100.103.227:3001/
- **기능**: 퍼즐 게임 플레이 (웹 브라우저용)
- **상태**: 기존 유지 (변경 없음)
- **위치**: `/var/www/html/CrosswordPuzzleApp/`

### 3. React Native 모바일 앱 (3번째 프로젝트) - 새로 개발
- **플랫폼**: Android/iOS
- **기능**: 퍼즐 게임 플레이 (모바일 앱)
- **API**: 8080포트 API 서버 연동
- **위치**: `/var/www/html/CrosswordPuzzleMobileApp/` (새로 생성)

## 개발 단계별 계획

### Phase 1: 프로젝트 초기 설정 (1일)

#### 1.1 모바일 앱 프로젝트 생성
```bash
cd /var/www/html
npx react-native init CrosswordPuzzleMobileApp
```

#### 1.2 필수 패키지 설치
```bash
cd CrosswordPuzzleMobileApp
npm install @react-navigation/native @react-navigation/stack
npm install @react-native-async-storage/async-storage
npm install axios
npm install react-native-screens react-native-safe-area-context
npm install react-native-gesture-handler
```

#### 1.3 프로젝트 구조 설정
```
CrosswordPuzzleMobileApp/
├── src/
│   ├── components/          # UI 컴포넌트
│   ├── screens/            # 화면 컴포넌트
│   ├── services/           # API 서비스
│   ├── contexts/           # 상태 관리
│   ├── types/              # TypeScript 타입
│   └── utils/              # 유틸리티 함수
├── android/                # Android 설정
├── ios/                    # iOS 설정
└── package.json
```

### Phase 2: 핵심 로직 이전 및 모바일 최적화 (2-3일)

#### 2.1 기존 웹앱 코드 복사
```bash
# 핵심 컴포넌트 복사
cp -r ../CrosswordPuzzleApp/src/components src/
cp -r ../CrosswordPuzzleApp/src/screens src/
cp -r ../CrosswordPuzzleApp/src/services src/
cp -r ../CrosswordPuzzleApp/src/contexts src/
cp -r ../CrosswordPuzzleApp/src/types src/
```

#### 2.2 모바일 전용 수정사항

##### A. 스토리지 시스템
```typescript
// AsyncStorage만 사용 (웹 분기 처리 제거)
import AsyncStorage from '@react-native-async-storage/async-storage';

// WebAsyncStorage.ts 파일 제거
// localStorage 관련 코드 제거
```

##### B. 네비게이션 시스템
```typescript
// React Navigation 사용 (웹 라우팅 제거)
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';

const Stack = createStackNavigator();

const App = () => {
  return (
    <NavigationContainer>
      <Stack.Navigator>
        <Stack.Screen name="Login" component={LoginScreen} />
        <Stack.Screen name="Main" component={MainScreen} />
        <Stack.Screen name="Game" component={GameScreen} />
      </Stack.Navigator>
    </NavigationContainer>
  );
};
```

##### C. API 서비스 수정
```typescript
// 모바일 전용 API 설정
const API_BASE_URL = 'http://222.100.103.227:8080/api';

// AsyncStorage 사용
this.api.interceptors.request.use(
  async (config) => {
    const token = await AsyncStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  }
);
```

### Phase 3: 모바일 UI/UX 최적화 (2-3일)

#### 3.1 터치 인터페이스 구현
- 터치 이벤트 처리
- 제스처 인식
- 스와이프 동작

#### 3.2 모바일 키보드 처리
- 키보드 표시/숨김 처리
- 입력 필드 포커스 관리
- 화면 스크롤 조정

#### 3.3 반응형 UI 구현
- 다양한 화면 크기 대응
- 세로/가로 모드 처리
- 안전 영역(Safe Area) 처리

#### 3.4 모바일 전용 컴포넌트
```typescript
// 터치 최적화된 버튼
import { TouchableOpacity } from 'react-native';

// 모바일 키보드 대응
import { KeyboardAvoidingView } from 'react-native';

// 안전 영역 처리
import { SafeAreaView } from 'react-native-safe-area-context';
```

### Phase 4: 퍼즐 게임 모바일 최적화 (2-3일)

#### 4.1 퍼즐 그리드 모바일 최적화
- 터치 기반 셀 선택
- 모바일 화면에 맞는 그리드 크기
- 줌/팬 기능 (필요시)

#### 4.2 입력 시스템 최적화
- 모바일 키보드 최적화
- 자동 완성 기능
- 입력 힌트 표시

#### 4.3 게임 상태 관리
- 모바일 앱 생명주기 처리
- 백그라운드/포그라운드 전환
- 게임 진행 상태 저장

### Phase 5: 테스트 및 디버깅 (1-2일)

#### 5.1 Android 테스트
```bash
npx react-native run-android
```

#### 5.2 iOS 테스트 (Mac 필요)
```bash
npx react-native run-ios
```

#### 5.3 기능 테스트
- 로그인/회원가입
- 퍼즐 게임 플레이
- API 연동
- 오답 초과 처리

### Phase 6: 앱 스토어 배포 준비 (1-2일)

#### 6.1 앱 아이콘 및 스플래시 스크린
- 앱 아이콘 생성
- 스플래시 스크린 설정
- 앱 이름 및 버전 설정

#### 6.2 Android 배포 준비
```bash
# APK 빌드
cd android && ./gradlew assembleRelease

# 앱 서명
keytool -genkey -v -keystore my-release-key.keystore -alias my-key-alias -keyalg RSA -keysize 2048 -validity 10000
```

#### 6.3 iOS 배포 준비 (Mac 필요)
- Xcode에서 Archive 생성
- App Store Connect 설정

## 기술 스택

### 프론트엔드
- **React Native**: 0.72+ (최신 안정 버전)
- **TypeScript**: 타입 안정성
- **React Navigation**: 네비게이션
- **AsyncStorage**: 로컬 스토리지

### 백엔드 연동
- **API 서버**: 8080포트 (기존 유지)
- **인증**: Sanctum 토큰 기반
- **통신**: Axios

### 개발 도구
- **Android Studio**: Android 개발
- **Xcode**: iOS 개발 (Mac 필요)
- **Metro**: 번들러
- **Flipper**: 디버깅

## 예상 일정

| Phase | 작업 | 예상 기간 | 누적 기간 |
|-------|------|-----------|-----------|
| 1 | 프로젝트 초기 설정 | 1일 | 1일 |
| 2 | 핵심 로직 이전 | 2-3일 | 3-4일 |
| 3 | 모바일 UI/UX 최적화 | 2-3일 | 5-7일 |
| 4 | 퍼즐 게임 최적화 | 2-3일 | 7-10일 |
| 5 | 테스트 및 디버깅 | 1-2일 | 8-12일 |
| 6 | 앱 스토어 배포 준비 | 1-2일 | 9-14일 |

**총 예상 기간**: 9-14일

## 리스크 및 대응 방안

### 리스크 1: 기존 웹앱 영향
- **대응**: 완전히 별도 프로젝트로 분리
- **확인**: 기존 웹앱 정상 작동 확인

### 리스크 2: API 호환성
- **대응**: 기존 8080포트 API 서버 그대로 사용
- **확인**: 모바일에서 API 호출 테스트

### 리스크 3: 모바일 성능
- **대응**: React Native 최적화 기법 적용
- **확인**: 실제 디바이스에서 성능 테스트

## 성공 기준

### 기능적 기준
- ✅ 로그인/회원가입 정상 작동
- ✅ 퍼즐 게임 플레이 가능
- ✅ 오답 초과 처리 정상
- ✅ API 연동 정상

### 성능적 기준
- ✅ 앱 시작 시간 < 3초
- ✅ 퍼즐 로딩 시간 < 2초
- ✅ 메모리 사용량 적정
- ✅ 배터리 소모 최적화

### 사용자 경험 기준
- ✅ 직관적인 터치 인터페이스
- ✅ 부드러운 애니메이션
- ✅ 모바일 키보드 최적화
- ✅ 다양한 화면 크기 대응

## 다음 단계

1. **Phase 1 시작**: 모바일 앱 프로젝트 생성
2. **개발 환경 확인**: Android Studio, Node.js 버전
3. **기존 웹앱 백업**: 현재 상태 보존
4. **단계별 진행**: 각 Phase 완료 후 다음 단계 진행

---

**작성일**: 2025-07-13  
**작성자**: AI Assistant  
**프로젝트**: 크로스워드 퍼즐 모바일 앱 개발 