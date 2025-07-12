import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, Alert, TouchableOpacity } from 'react-native';
import CrosswordPuzzle from '../components/CrosswordPuzzle';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';

interface WordPosition {
  id: number;
  word_id: number;
  word: string;
  hint: string;
  start_x: number;
  start_y: number;
  end_x: number;
  end_y: number;
  direction: number;
}

interface PuzzleData {
  grid: string[][];
  wordPositions: WordPosition[];
}

interface NavigationProps {
  navigate: (screen: string) => void;
  goBack?: () => void;
}

interface GameScreenProps {
  navigation: NavigationProps;
}

const GameScreen: React.FC<GameScreenProps> = ({ navigation }) => {
  const { user, logout } = useAuth();
  const [puzzleData, setPuzzleData] = useState<PuzzleData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [level, setLevel] = useState(1);

  useEffect(() => {
    loadPuzzle();
  }, [level]);

  const loadPuzzle = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await api.get(`/puzzles/random?level=${level}`);
      
      if (response.data.success) {
        setPuzzleData(response.data.data);
      } else {
        setError('퍼즐을 불러오는데 실패했습니다.');
      }
    } catch (err: any) {
      console.error('Puzzle load error:', err);
      setError(err.response?.data?.message || '퍼즐을 불러오는데 실패했습니다.');
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = () => {
    Alert.alert(
      '로그아웃',
      '정말 로그아웃하시겠습니까?',
      [
        { text: '취소', style: 'cancel' },
        { text: '로그아웃', onPress: logout, style: 'destructive' }
      ]
    );
  };

  const handleBackToMain = () => {
    navigation.navigate('main');
  };

  const handleLevelChange = (newLevel: number) => {
    setLevel(newLevel);
  };

  const handlePuzzleComplete = () => {
    Alert.alert(
      '축하합니다!',
      '퍼즐을 완성했습니다!',
      [
        { text: '다음 레벨', onPress: () => handleLevelChange(level + 1) },
        { text: '다시 하기', onPress: loadPuzzle }
      ]
    );
  };

  if (loading) {
    return (
      <View style={styles.container}>
        <View style={styles.header}>
          <TouchableOpacity onPress={handleBackToMain} style={styles.backButton}>
            <Text style={styles.backButtonText}>← 뒤로</Text>
          </TouchableOpacity>
          <Text style={styles.headerText}>크로스워드 퍼즐</Text>
          <TouchableOpacity onPress={handleLogout} style={styles.logoutButton}>
            <Text style={styles.logoutText}>로그아웃</Text>
          </TouchableOpacity>
        </View>
        <View style={styles.loadingContainer}>
          <Text style={styles.loadingText}>퍼즐을 불러오는 중...</Text>
        </View>
      </View>
    );
  }

  if (error) {
    return (
      <View style={styles.container}>
        <View style={styles.header}>
          <TouchableOpacity onPress={handleBackToMain} style={styles.backButton}>
            <Text style={styles.backButtonText}>← 뒤로</Text>
          </TouchableOpacity>
          <Text style={styles.headerText}>크로스워드 퍼즐</Text>
          <TouchableOpacity onPress={handleLogout} style={styles.logoutButton}>
            <Text style={styles.logoutText}>로그아웃</Text>
          </TouchableOpacity>
        </View>
        <View style={styles.errorContainer}>
          <Text style={styles.errorText}>{error}</Text>
          <TouchableOpacity onPress={loadPuzzle} style={styles.retryButton}>
            <Text style={styles.retryText}>다시 시도</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  if (!puzzleData) {
    return (
      <View style={styles.container}>
        <View style={styles.header}>
          <TouchableOpacity onPress={handleBackToMain} style={styles.backButton}>
            <Text style={styles.backButtonText}>← 뒤로</Text>
          </TouchableOpacity>
          <Text style={styles.headerText}>크로스워드 퍼즐</Text>
          <TouchableOpacity onPress={handleLogout} style={styles.logoutButton}>
            <Text style={styles.logoutText}>로그아웃</Text>
          </TouchableOpacity>
        </View>
        <View style={styles.errorContainer}>
          <Text style={styles.errorText}>퍼즐 데이터가 없습니다.</Text>
          <TouchableOpacity onPress={loadPuzzle} style={styles.retryButton}>
            <Text style={styles.retryText}>새로고침</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={handleBackToMain} style={styles.backButton}>
          <Text style={styles.backButtonText}>← 뒤로</Text>
        </TouchableOpacity>
        <View style={styles.headerControls}>
          <Text style={styles.levelText}>레벨 {level}</Text>
          <TouchableOpacity onPress={handleLogout} style={styles.logoutButton}>
            <Text style={styles.logoutText}>로그아웃</Text>
          </TouchableOpacity>
        </View>
      </View>
      
      <View style={styles.gameContainer}>
        <CrosswordPuzzle
          puzzleData={puzzleData}
          onComplete={handlePuzzleComplete}
        />
      </View>
      
      <View style={styles.controls}>
        <TouchableOpacity onPress={loadPuzzle} style={styles.controlButton}>
          <Text style={styles.controlButtonText}>새 퍼즐</Text>
        </TouchableOpacity>
        <TouchableOpacity 
          onPress={() => handleLevelChange(Math.max(1, level - 1))} 
          style={styles.controlButton}
        >
          <Text style={styles.controlButtonText}>이전 레벨</Text>
        </TouchableOpacity>
        <TouchableOpacity 
          onPress={() => handleLevelChange(level + 1)} 
          style={styles.controlButton}
        >
          <Text style={styles.controlButtonText}>다음 레벨</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 20,
    backgroundColor: '#007bff',
    paddingTop: 40,
  },
  headerText: {
    fontSize: 20,
    fontWeight: 'bold',
    color: 'white',
  },
  headerControls: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 15,
  },
  levelText: {
    fontSize: 16,
    color: 'white',
    fontWeight: 'bold',
  },
  backButton: {
    paddingHorizontal: 15,
    paddingVertical: 8,
    backgroundColor: 'rgba(255,255,255,0.2)',
    borderRadius: 6,
  },
  backButtonText: {
    color: 'white',
    fontSize: 14,
    fontWeight: 'bold',
  },
  logoutButton: {
    paddingHorizontal: 15,
    paddingVertical: 8,
    backgroundColor: 'rgba(255,255,255,0.2)',
    borderRadius: 6,
  },
  logoutText: {
    color: 'white',
    fontSize: 14,
    fontWeight: 'bold',
  },
  gameContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 10,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    fontSize: 18,
    color: '#666',
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  errorText: {
    fontSize: 16,
    color: '#dc3545',
    textAlign: 'center',
    marginBottom: 20,
  },
  retryButton: {
    paddingHorizontal: 20,
    paddingVertical: 10,
    backgroundColor: '#007bff',
    borderRadius: 6,
  },
  retryText: {
    color: 'white',
    fontSize: 16,
    fontWeight: 'bold',
  },
  controls: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    padding: 15,
    backgroundColor: 'white',
    borderTopWidth: 1,
    borderTopColor: '#dee2e6',
  },
  controlButton: {
    paddingHorizontal: 15,
    paddingVertical: 10,
    backgroundColor: '#6c757d',
    borderRadius: 6,
  },
  controlButtonText: {
    color: 'white',
    fontSize: 14,
    fontWeight: 'bold',
  },
});

export default GameScreen; 