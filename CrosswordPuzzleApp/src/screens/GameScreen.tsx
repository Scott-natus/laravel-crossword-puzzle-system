import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  TextInput,
  Alert,
  ActivityIndicator,
  ScrollView,
} from 'react-native';
import { useAuth } from '../contexts/AuthContext';
import CrosswordGrid from '../components/CrosswordGrid';

interface GameScreenProps {
  navigation?: any;
}

interface WordPosition {
  id: number;
  word_id: number;
  hint: string;
  start_x: number;
  start_y: number;
  end_x: number;
  end_y: number;
  direction: number; // 0: horizontal, 1: vertical
}

interface PuzzleData {
  template: {
    id: number;
    template_name: string;
    grid_pattern: number[][];
    grid_width: number;
    grid_height: number;
    words: Array<{
      word_id: number;
      position: {
        id: number;
        start_x: number;
        start_y: number;
        end_x: number;
        end_y: number;
        direction: number;
      };
      hint: string;
    }>;
  };
  level: {
    id: number;
    level: number;
    level_name: string;
  };
  game: {
    id: number;
    current_level: number;
  };
}

export const GameScreen: React.FC<GameScreenProps> = ({ navigation }) => {
  const { user, logout } = useAuth();
  const [puzzleData, setPuzzleData] = useState<PuzzleData | null>(null);
  const [loading, setLoading] = useState(true);
  const [selectedWord, setSelectedWord] = useState<WordPosition | null>(null);
  const [answer, setAnswer] = useState('');
  const [answeredWords, setAnsweredWords] = useState<Set<number>>(new Set());
  const [wrongAnswers, setWrongAnswers] = useState<Map<number, number>>(new Map());
  const [hintsShown, setHintsShown] = useState<Set<number>>(new Set());
  const [additionalHints, setAdditionalHints] = useState<Map<number, string[]>>(new Map());
  const [gameComplete, setGameComplete] = useState(false);
  const [currentLevel, setCurrentLevel] = useState(1);
  const [showHint, setShowHint] = useState(false);
  const [wordPositions, setWordPositions] = useState<WordPosition[]>([]);

  useEffect(() => {
    loadPuzzle();
  }, []);

  useEffect(() => {
    console.log('wordPositions:', wordPositions);
    if (puzzleData?.template?.grid_pattern) {
      console.log('grid:', puzzleData.template.grid_pattern);
    }
  }, [wordPositions, puzzleData]);

  const loadPuzzle = async () => {
    try {
      setLoading(true);
      const response = await fetch('http://222.100.103.227:8080/api/puzzle/template', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error('퍼즐을 불러올 수 없습니다.');
      }

      const data = await response.json();
      console.log('퍼즐 데이터:', data);
      
      if (data.success && data.data) {
        setPuzzleData(data.data);
        setCurrentLevel(data.data.level?.level || 1);
        // template.words를 wordPositions로 변환
        if (data.data.template.words) {
          setWordPositions(
            data.data.template.words.map((w: any) => ({
              id: w.position.id,
              word_id: w.word_id,
              hint: w.hint,
              start_x: w.position.start_x,
              start_y: w.position.start_y,
              end_x: w.position.end_x,
              end_y: w.position.end_y,
              direction: w.position.direction
            }))
          );
        } else {
          setWordPositions([]);
        }
      } else {
        throw new Error('퍼즐 데이터 형식이 올바르지 않습니다.');
      }
    } catch (error) {
      console.error('퍼즐 로드 오류:', error);
      Alert.alert('오류', '퍼즐을 불러오는데 실패했습니다.');
    } finally {
      setLoading(false);
    }
  };

  const handleWordSelect = (word: WordPosition) => {
    setSelectedWord(word);
    setAnswer('');
    setShowHint(false);
  };

  const handleCellClick = (x: number, y: number) => {
    // 단어가 선택되지 않은 상태에서 셀 클릭 시 힌트 표시
    if (!selectedWord) {
      // 해당 위치의 단어 찾기
      const wordAtPosition = wordPositions?.find(word => {
        if (word.direction === 0) { // 가로
          return y === word.start_y && x >= word.start_x && x <= word.end_x;
        } else { // 세로
          return x === word.start_x && y >= word.start_y && y <= word.end_y;
        }
      });

      if (wordAtPosition) {
        setSelectedWord(wordAtPosition);
        setShowHint(true);
      } else {
        Alert.alert('안내', '이 위치에는 단어가 없습니다.');
      }
    }
  };

  const handleAnswerSubmit = async () => {
    if (!selectedWord || !answer.trim()) {
      Alert.alert('오류', '답을 입력해주세요.');
      return;
    }

    try {
      const response = await fetch('http://222.100.103.227:8080/api/puzzle/submit-answer', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          word_id: selectedWord.word_id,
          answer: answer.trim(),
        }),
      });

      const result = await response.json();

      if (result.correct) {
        // 정답
        setAnsweredWords(prev => new Set([...prev, selectedWord.word_id]));
        setWrongAnswers(prev => {
          const newMap = new Map(prev);
          newMap.delete(selectedWord.word_id);
          return newMap;
        });
        Alert.alert('정답!', '축하합니다!');
        setAnswer('');
        setSelectedWord(null);
        setShowHint(false);

        // 모든 단어를 맞췄는지 확인
        if (answeredWords.size + 1 >= wordPositions.length) {
          handleGameComplete();
        }
      } else {
        // 오답
        const currentWrongCount = wrongAnswers.get(selectedWord.word_id) || 0;
        const newWrongCount = currentWrongCount + 1;
        setWrongAnswers(prev => new Map(prev).set(selectedWord.word_id, newWrongCount));

        if (newWrongCount >= 4) {
          Alert.alert(
            '경고',
            '현재 오답이 4회입니다. 5회 오답시 레벨을 재시작합니다.',
            [
              { text: '재도전', onPress: () => setAnswer('') },
              { text: '힌트보기', onPress: () => handleShowHint() },
            ]
          );
        } else {
          Alert.alert('오답', `틀렸습니다. (${newWrongCount}/5)`);
          setAnswer('');
        }
      }
    } catch (error) {
      console.error('답안 제출 오류:', error);
      Alert.alert('오류', '답안을 제출하는데 실패했습니다.');
    }
  };

  const handleShowHint = async () => {
    if (!selectedWord) return;

    try {
      const response = await fetch(`http://222.100.103.227:8080/api/puzzle/hints?word_id=${selectedWord.word_id}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        },
      });

      const result = await response.json();
      if (result.success) {
        setHintsShown(prev => new Set([...prev, selectedWord.word_id]));
        setAdditionalHints(prev => new Map(prev).set(selectedWord.word_id, result.hints));
        setShowHint(true);
      }
    } catch (error) {
      console.error('힌트 로드 오류:', error);
      Alert.alert('오류', '힌트를 불러오는데 실패했습니다.');
    }
  };

  const handleShowAnswer = async () => {
    if (!selectedWord) return;

    try {
      const response = await fetch(`http://222.100.103.227/puzzle-game/show-answer?word_id=${selectedWord.word_id}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        },
      });

      const result = await response.json();
      if (result.success) {
        Alert.alert('정답', result.answer);
      }
    } catch (error) {
      console.error('정답 보기 오류:', error);
      Alert.alert('오류', '정답을 불러오는데 실패했습니다.');
    }
  };

  const handleGameComplete = async () => {
    try {
      const response = await fetch('http://222.100.103.227:8080/api/puzzle/complete-level', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        },
      });

      const result = await response.json();
      if (result.success) {
        setGameComplete(true);
        Alert.alert(
          '축하합니다!',
          '레벨을 완성했습니다! 다음 레벨로 진행합니다.',
          [
            {
              text: '다음 레벨',
              onPress: () => {
                setGameComplete(false);
                setAnsweredWords(new Set());
                setWrongAnswers(new Map());
                setHintsShown(new Set());
                setAdditionalHints(new Map());
                loadPuzzle();
              },
            },
          ]
        );
      }
    } catch (error) {
      console.error('레벨 완료 처리 오류:', error);
    }
  };

  const handleLogout = () => {
    logout();
    if (navigation) {
      navigation.navigate('Login');
    }
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#007AFF" />
        <Text style={styles.loadingText}>퍼즐을 불러오는 중...</Text>
      </View>
    );
  }

  if (!puzzleData) {
    return (
      <View style={styles.errorContainer}>
        <Text style={styles.errorText}>퍼즐을 불러올 수 없습니다.</Text>
        <TouchableOpacity style={styles.retryButton} onPress={loadPuzzle}>
          <Text style={styles.retryButtonText}>다시 시도</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* 상단 헤더 */}
      <View style={styles.header}>
        <Text style={styles.title}>Korean Cross Word</Text>
        <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
          <Text style={styles.logoutButtonText}>로그아웃</Text>
        </TouchableOpacity>
      </View>

      {/* 레벨 정보 */}
      <View style={styles.levelHeader}>
        <Text style={styles.levelText}>레벨 {currentLevel}</Text>
        <Text style={styles.statsText}>
          완성: {answeredWords.size}/{wordPositions.length || 0}
        </Text>
      </View>

      <ScrollView style={styles.content}>
        {/* 퍼즐 그리드 */}
        <View style={styles.gridContainer}>
          <CrosswordGrid
            grid={puzzleData.template?.grid_pattern || []}
            wordPositions={wordPositions}
            answeredWords={answeredWords}
            wrongAnswers={wrongAnswers}
            onWordSelect={handleWordSelect}
            onCellSelect={handleCellClick}
          />
        </View>

        {/* 선택된 단어 정보 및 입력 */}
        {selectedWord && (
          <View style={styles.inputSection}>
            <Text style={styles.selectedWordText}>선택된 단어 ID: {selectedWord.word_id}</Text>
            <Text style={styles.hintText}>힌트: {selectedWord.hint}</Text>
            
            {/* 추가 힌트 표시 */}
            {showHint && hintsShown.has(selectedWord.word_id) && additionalHints.has(selectedWord.word_id) && (
              <View style={styles.additionalHintsContainer}>
                <Text style={styles.additionalHintsTitle}>추가 힌트:</Text>
                {additionalHints.get(selectedWord.word_id)?.map((hint, index) => (
                  <Text key={index} style={styles.additionalHintText}>
                    • {hint}
                  </Text>
                ))}
              </View>
            )}
            
            <TextInput
              style={styles.answerInput}
              value={answer}
              onChangeText={setAnswer}
              placeholder="답을 입력하세요"
              autoCapitalize="none"
              autoCorrect={false}
            />

            <View style={styles.buttonRow}>
              <TouchableOpacity style={styles.submitButton} onPress={handleAnswerSubmit}>
                <Text style={styles.buttonText}>입력</Text>
              </TouchableOpacity>
              
              <TouchableOpacity style={styles.hintButton} onPress={handleShowHint}>
                <Text style={styles.buttonText}>힌트보기</Text>
              </TouchableOpacity>
              
              {user?.is_admin && (
                <TouchableOpacity style={styles.answerButton} onPress={handleShowAnswer}>
                  <Text style={styles.buttonText}>정답보기</Text>
                </TouchableOpacity>
              )}
            </View>

            {/* 오답 횟수 표시 */}
            {wrongAnswers.has(selectedWord.word_id) && (
              <Text style={styles.wrongCountText}>
                오답: {wrongAnswers.get(selectedWord.word_id)}/5
              </Text>
            )}
          </View>
        )}

        {/* 게임 안내 */}
        {!selectedWord && (
          <View style={styles.instructionContainer}>
            <Text style={styles.instructionText}>
              번호를 클릭하여 단어를 선택하거나, 빈 칸을 클릭하여 힌트를 확인하세요.
            </Text>
          </View>
        )}
      </ScrollView>
    </View>
  );
};

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
    marginTop: 10,
    fontSize: 16,
    color: '#666',
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#f5f5f5',
  },
  errorText: {
    fontSize: 16,
    color: '#666',
    marginBottom: 20,
  },
  retryButton: {
    backgroundColor: '#007AFF',
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 8,
  },
  retryButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 15,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#e0e0e0',
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#333',
  },
  logoutButton: {
    backgroundColor: '#FF3B30',
    paddingHorizontal: 15,
    paddingVertical: 8,
    borderRadius: 6,
  },
  logoutButtonText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '600',
  },
  levelHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 10,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#e0e0e0',
  },
  levelText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
  },
  statsText: {
    fontSize: 14,
    color: '#666',
  },
  content: {
    flex: 1,
  },
  gridContainer: {
    padding: 20,
    alignItems: 'center',
  },
  inputSection: {
    padding: 20,
    backgroundColor: '#fff',
    margin: 20,
    borderRadius: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  selectedWordText: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 10,
  },
  hintText: {
    fontSize: 16,
    color: '#333',
    marginBottom: 15,
    fontWeight: '600',
  },
  answerInput: {
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
    marginBottom: 15,
    backgroundColor: '#fff',
  },
  buttonRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 15,
  },
  submitButton: {
    backgroundColor: '#007AFF',
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 8,
    flex: 1,
    marginRight: 5,
  },
  hintButton: {
    backgroundColor: '#FF9500',
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 8,
    flex: 1,
    marginHorizontal: 5,
  },
  answerButton: {
    backgroundColor: '#FF3B30',
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 8,
    flex: 1,
    marginLeft: 5,
  },
  buttonText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '600',
    textAlign: 'center',
  },
  additionalHintsContainer: {
    marginTop: 15,
    padding: 15,
    backgroundColor: '#f8f9fa',
    borderRadius: 8,
  },
  additionalHintsTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
    marginBottom: 10,
  },
  additionalHintText: {
    fontSize: 14,
    color: '#666',
    marginBottom: 5,
  },
  wrongCountText: {
    fontSize: 14,
    color: '#FF3B30',
    fontWeight: '600',
    textAlign: 'center',
    marginTop: 10,
  },
  instructionContainer: {
    padding: 20,
    backgroundColor: '#fff',
    margin: 20,
    borderRadius: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  instructionText: {
    fontSize: 16,
    color: '#666',
    textAlign: 'center',
    lineHeight: 24,
  },
});

export default GameScreen; 