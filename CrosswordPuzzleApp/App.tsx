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
    return <LoginScreen />;
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
    return <GameScreen navigation={{ goBack: handleBackToMain }} />;
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
