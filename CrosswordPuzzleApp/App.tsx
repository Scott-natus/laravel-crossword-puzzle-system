/**
 * Sample React Native App
 * https://github.com/facebook/react-native
 *
 * @format
 */

import React, { useState } from 'react';
import { View, Text, StyleSheet, ActivityIndicator } from 'react-native';
import { GameScreen } from './src/screens/GameScreen';
import { LoginScreen } from './src/screens/LoginScreen';
import { RegisterScreen } from './src/screens/RegisterScreen';
import { MainScreen } from './src/screens/MainScreen';
import { AuthProvider, useAuth } from './src/contexts/AuthContext';

// 네비게이션 타입 정의
type Screen = 'main' | 'game' | 'login' | 'register';

const AppContent: React.FC = () => {
  const { isAuthenticated, isLoading } = useAuth();
  const [currentScreen, setCurrentScreen] = useState<Screen>('main');

  console.log('AppContent: isLoading =', isLoading, 'isAuthenticated =', isAuthenticated);

  if (isLoading) {
    console.log('AppContent: Showing loading screen');
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#007AFF" />
        <Text style={styles.loadingText}>로딩 중...</Text>
      </View>
    );
  }

  if (!isAuthenticated) {
    console.log('AppContent: Showing login screen');
    return <LoginScreen navigation={{ navigate: (screen: Screen) => setCurrentScreen(screen) }} />;
  }

  // 인증된 사용자의 화면 전환
  const navigation = {
    navigate: (screen: Screen) => setCurrentScreen(screen),
    goBack: () => setCurrentScreen('main')
  };

  console.log('AppContent: Showing screen =', currentScreen);

  switch (currentScreen) {
    case 'game':
      return <GameScreen navigation={navigation} route={{}} />;
    case 'login':
      return <LoginScreen navigation={navigation} />;
    case 'register':
      return <RegisterScreen navigation={navigation} />;
    case 'main':
    default:
      return <MainScreen navigation={navigation} />;
  }
};

function App(): React.JSX.Element {
  return (
    <AuthProvider>
      <View style={styles.container}>
        <AppContent />
      </View>
    </AuthProvider>
  );
}
const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#f5f5f5',
  },
  loadingText: {
    marginTop: 16,
    fontSize: 16,
    color: '#666',
  },
});

export default App;

