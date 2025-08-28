import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Alert,
  ScrollView,
  Dimensions,
} from 'react-native';
import { useAuth } from '../contexts/AuthContext';
import { apiService } from '../services/api';
import CrosswordGrid from '../components/CrosswordGrid';

const { width: screenWidth } = Dimensions.get('window');

const GameScreen: React.FC<{ navigation: any }> = ({ navigation }) => {
  const { user, logout } = useAuth();
  const [puzzleData, setPuzzleData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [answeredWords, setAnsweredWords] = useState<Set<number>>(new Set());
  const [wordAnswers, setWordAnswers] = useState<Map<number, string>>(new Map());

  useEffect(() => {
    loadPuzzle();
  }, []);

  const loadPuzzle = async () => {
    try {
      setLoading(true);
      console.log('🎮 퍼즐 로딩 중...');
      const response = await apiService.getPuzzleTemplate();
      
      if (response.success) {
        setPuzzleData(response.data);
        console.log('✅ 퍼즐 로딩 완료');
      } else {
        Alert.alert('오류', '퍼즐을 불러올 수 없습니다.');
      }
    } catch (error: any) {
      console.error('❌ 퍼즐 로딩 실패:', error);
      Alert.alert('오류', error.message || '퍼즐 로딩에 실패했습니다.');
    } finally {
      setLoading(false);
    }
  };

  const handleWordClick = (word: any) => {
    Alert.alert('단어 정보', `단어: ${word.hint}\n방향: ${word.direction}`);
  };

  const handleCellClick = (x: number, y: number) => {
    console.log(`셀 클릭: (${x}, ${y})`);
  };

  const handleLogout = async () => {
    try {
      await logout();
      Alert.alert('로그아웃', '로그아웃되었습니다.');
    } catch (error) {
      Alert.alert('오류', '로그아웃 중 오류가 발생했습니다.');
    }
  };

  if (loading) {
    return (
      <View style={styles.container}>
        <Text style={styles.loadingText}>퍼즐 로딩 중...</Text>
      </View>
    );
  }

  if (!puzzleData) {
    return (
      <View style={styles.container}>
        <Text style={styles.errorText}>퍼즐을 불러올 수 없습니다.</Text>
        <TouchableOpacity style={styles.button} onPress={loadPuzzle}>
          <Text style={styles.buttonText}>다시 시도</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <ScrollView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>Crossword Puzzle</Text>
        <Text style={styles.subtitle}>환영합니다, {user?.name}님!</Text>
      </View>

      <View style={styles.gameContainer}>
        <CrosswordGrid
          gridPattern={puzzleData.grid_pattern}
          wordPositions={puzzleData.word_positions}
          onWordClick={handleWordClick}
          onCellClick={handleCellClick}
          answeredWords={answeredWords}
          wordAnswers={wordAnswers}
        />
      </View>

      <View style={styles.controls}>
        <TouchableOpacity style={styles.button} onPress={loadPuzzle}>
          <Text style={styles.buttonText}>새 퍼즐</Text>
        </TouchableOpacity>
        
        <TouchableOpacity style={[styles.button, styles.logoutButton]} onPress={handleLogout}>
          <Text style={styles.buttonText}>로그아웃</Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  header: {
    padding: 20,
    alignItems: 'center',
    backgroundColor: 'white',
    borderBottomWidth: 1,
    borderBottomColor: '#eee',
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 5,
  },
  subtitle: {
    fontSize: 16,
    color: '#666',
  },
  gameContainer: {
    padding: 20,
    alignItems: 'center',
  },
  controls: {
    padding: 20,
    flexDirection: 'row',
    justifyContent: 'space-around',
  },
  button: {
    backgroundColor: '#007AFF',
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 8,
    minWidth: 100,
    alignItems: 'center',
  },
  logoutButton: {
    backgroundColor: '#FF3B30',
  },
  buttonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: '600',
  },
  loadingText: {
    fontSize: 18,
    color: '#666',
    textAlign: 'center',
    marginTop: 100,
  },
  errorText: {
    fontSize: 16,
    color: '#FF3B30',
    textAlign: 'center',
    marginTop: 100,
  },
});

export default GameScreen;