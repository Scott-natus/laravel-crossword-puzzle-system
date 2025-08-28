#!/bin/bash

echo "🚀 CrosswordPuzzleMobileApp Android 빌드 시작..."

# 현재 시간을 가져와서 BUILD_TIME 변수로 설정
BUILD_TIME=$(date '+%Y-%m-%d %H:%M:%S')
echo "📅 빌드 시간: $BUILD_TIME"

# 프로젝트 디렉토리로 이동
cd /var/www/html/CrosswordPuzzleMobileApp

# LoginScreen.tsx 파일에서 BUILD_TIME 업데이트
echo "🔧 빌드 시간 업데이트 중..."
sed -i "s/BUILD_TIME = '.*'/BUILD_TIME = '$BUILD_TIME'/" src/screens/LoginScreen.tsx

# 의존성 설치
echo "📦 의존성 설치 중..."
npm install

# Android 빌드 디렉토리로 이동
cd android

# 기존 빌드 정리
echo "🧹 기존 빌드 정리 중..."
./gradlew clean

# Release APK 빌드
echo "🔨 Release APK 빌드 중..."
./gradlew assembleRelease

# 빌드 결과 확인
if [ -f "app/build/outputs/apk/release/app-release.apk" ]; then
    echo "✅ APK 빌드 성공!"
    echo "📱 APK 파일 위치: $(pwd)/app/build/outputs/apk/release/app-release.apk"
    echo "📏 파일 크기: $(du -h app/build/outputs/apk/release/app-release.apk | cut -f1)"
    echo "🕐 빌드 완료 시간: $BUILD_TIME"
else
    echo "❌ APK 빌드 실패!"
    exit 1
fi 