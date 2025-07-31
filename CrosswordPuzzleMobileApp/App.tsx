/**
 * Sample React Native App
 * https://github.com/facebook/react-native
 *
 * @format
 */

import React from 'react';
import { AuthProvider, useAuth } from './src/contexts/AuthContext';
import LoginScreen from './src/screens/LoginScreen';
import GameScreen from './src/screens/GameScreen';

const MainApp = () => {
  const { isAuthenticated, isLoading, user } = useAuth();

  console.log('🔍 MainApp: isAuthenticated =', isAuthenticated);
  console.log('🔍 MainApp: isLoading =', isLoading);
  console.log('🔍 MainApp: user =', user);

  if (isLoading) {
    console.log('⏳ MainApp: 로딩 중...');
    return null; // 로딩 중에는 빈 화면
  }

  console.log('🎯 MainApp: 화면 전환 결정 - isAuthenticated =', isAuthenticated);
  return isAuthenticated ? <GameScreen /> : <LoginScreen />;
};

const App = () => {
  return (
    <AuthProvider>
      <MainApp />
    </AuthProvider>
  );
};

export default App;
