/**
 * Sample React Native App
 * https://github.com/facebook/react-native
 *
 * @format
 */

import React, { useState } from 'react';
import { AuthProvider, useAuth } from './src/contexts/AuthContext';
import { LoginScreen } from './src/screens/LoginScreen';
import { RegisterScreen } from './src/screens/RegisterScreen';
import { MainScreen } from './src/screens/MainScreen';
import { GameScreen } from './src/screens/GameScreen';

// 네비게이션 컴포넌트
const Navigation = () => {
  const { isAuthenticated, isLoading } = useAuth();
  const [currentScreen, setCurrentScreen] = useState<'main' | 'game'>('main');

  if (isLoading) {
    return (
      <div style={{ 
        display: 'flex', 
        justifyContent: 'center', 
        alignItems: 'center', 
        height: '100vh',
        fontSize: '18px'
      }}>
        로딩 중...
      </div>
    );
  }

  if (!isAuthenticated) {
    return <LoginScreen navigation={{ 
      navigate: (screen: string) => {
        if (screen === 'Register') {
          // 회원가입 화면으로 이동하는 로직은 현재 구현되지 않음
          // 필요시 추가 구현
        }
      }
    }} />;
  }

  // 게임 화면으로 이동하는 함수
  const handleStartGame = () => {
    setCurrentScreen('game');
  };

  // 메인 화면으로 돌아가는 함수
  const handleBackToMain = () => {
    setCurrentScreen('main');
  };

  if (currentScreen === 'game') {
    return <GameScreen navigation={{ 
      goBack: handleBackToMain,
      navigate: (screen: string) => {
        if (screen === 'Login') {
          // 로그아웃 시 메인 화면으로 돌아가고, AuthContext에서 로그아웃 처리
          handleBackToMain();
        } else if (screen === 'Main') {
          // 홈 버튼 클릭 시 메인 화면으로 이동
          handleBackToMain();
        }
      }
    }} />;
  }

  return <MainScreen navigation={{ navigate: (screen: string) => {
    if (screen === 'Game') {
      handleStartGame();
    }
  }}} />;
};

export default function App() {
  return (
    <AuthProvider>
      <Navigation />
    </AuthProvider>
  );
}
