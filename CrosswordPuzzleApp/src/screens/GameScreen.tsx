import React, { useState, useEffect, useCallback, useMemo, useRef } from 'react';
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
  hint_id?: number; // 기본 힌트 ID
  start_x: number;
  start_y: number;
  end_x: number;
  end_y: number;
  direction: string; // 'horizontal' | 'vertical'
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
  // answer 상태값 제거
  const [answeredWords, setAnsweredWords] = useState<Set<number>>(new Set());
  const [wrongAnswers, setWrongAnswers] = useState<Map<number, number>>(new Map());
  const [hintsShown, setHintsShown] = useState<Set<number>>(new Set());
  const [additionalHints, setAdditionalHints] = useState<Map<number, string[]>>(new Map());
  const [gameComplete, setGameComplete] = useState(false);
  const [currentLevel, setCurrentLevel] = useState(1);
  const [showHint, setShowHint] = useState(false);
  const [wordPositions, setWordPositions] = useState<WordPosition[]>([]);
  const [wordAnswers, setWordAnswers] = useState<Map<number, string>>(new Map()); // word_id별 정답 단어 저장
  const [answerStatus, setAnswerStatus] = useState<{ type: 'correct' | 'wrong' | null; message: string }>({ type: null, message: '' });
  // 입력값 ref 선언
  const answerInputRef = useRef("");

  useEffect(() => {
    loadPuzzle();
  }, []);

  // useEffect(() => {
  //   console.log('wordPositions:', wordPositions);
  //   if (puzzleData?.template?.grid_pattern) {
  //     console.log('grid:', puzzleData.template.grid_pattern);
  //   }
  // }, [wordPositions, puzzleData]);

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
      console.log('퍼즐 데이터:', data); // 전체 데이터 로그
      if (data.success && data.data) {
        setPuzzleData(data.data);
        setCurrentLevel(data.data.level?.level || 1);
        // template.words를 wordPositions로 변환
        if (data.data.template.words) {
          // data.data.template.words.forEach((w: any) => {
          //   // w.word_id: pz_words.id (정답/힌트 조회용 키값)
          //   // w.position.id: puzzle_grid_templates.word_positions의 id (배지 번호)
          //   console.log('word_id:', w.word_id, 'id:', w.position.id, 'hint:', w.hint, 'hint_id:', w.hint_id);
          // });
          setWordPositions(
            data.data.template.words.map((w: any) => ({
              id: w.position.id, // 배지 번호 (퍼즐판에 표시되는 1, 2, 3...)
              word_id: w.word_id, // 실제 단어 ID (pz_words.id) - 정답/힌트 조회용
              hint: w.hint,
              hint_id: w.hint_id, // 기본 힌트 ID
              start_x: w.position.start_x,
              start_y: w.position.start_y,
              end_x: w.position.end_x,
              end_y: w.position.end_y,
              direction:
                w.position.direction === 0 ||
                w.position.direction === 'horizontal' ||
                w.position.direction === 'H'
                  ? 'horizontal'
                  : 'vertical',
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

  // 기존 handleWordSelect를 onWordClick으로 이름 변경
  const onWordClick = (word: WordPosition) => {
    setSelectedWord(word);
    setShowHint(false);
    answerInputRef.current = "";
  };

  // 빈 검은칸 클릭 시 해당 칸에 소속된 모든 단어(가로/세로) 중 더 작은 id(배지 번호) 단어의 힌트 표시
  const onCellClick = (x: number, y: number) => {
    // 해당 칸에 소속된 모든 단어(가로/세로) 찾기
    const words = wordPositions.filter(wp => {
      if (wp.direction === 'horizontal') {
        return y === wp.start_y && x >= wp.start_x && x <= wp.end_x;
      } else {
        return x === wp.start_x && y >= wp.start_y && y <= wp.end_y;
      }
    });
    if (words.length > 0) {
      // 여러 개면 더 작은 id(배지 번호) 단어 선택
      const selected = words.reduce((min, curr) => (curr.id < min.id ? curr : min), words[0]);
      setSelectedWord(selected);
      setShowHint(false);
      answerInputRef.current = "";
    }
  };

  const showAnswerStatus = (type: 'correct' | 'wrong', message: string) => {
    setAnswerStatus({ type, message });
    setTimeout(() => {
      setAnswerStatus({ type: null, message: '' });
    }, 3000);
  };

  const handleAnswerSubmit = async () => {
    const inputValue = answerInputRef.current;
    if (!selectedWord || !inputValue.trim()) {
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
          answer: inputValue.trim(),
        }),
      });
      const result = await response.json();
      if (result.is_correct) {
        setAnsweredWords(prev => new Set([...prev, selectedWord.word_id]));
        setWordAnswers(prev => new Map(prev).set(selectedWord.word_id, inputValue.trim()));
        setWrongAnswers(prev => {
          const newMap = new Map(prev);
          newMap.delete(selectedWord.word_id);
          return newMap;
        });
        showAnswerStatus('correct', '정답입니다!');
        answerInputRef.current = "";
        if (answeredWords.size + 1 >= wordPositions.length) {
          handleGameComplete();
        }
      } else {
        const currentWrongCount = wrongAnswers.get(selectedWord.word_id) || 0;
        const newWrongCount = currentWrongCount + 1;
        setWrongAnswers(prev => new Map(prev).set(selectedWord.word_id, newWrongCount));
        if (newWrongCount >= 4) {
          Alert.alert(
            '경고',
            '현재 오답이 4회입니다. 5회 오답시 레벨을 재시작합니다.',
            [
              { text: '재도전', onPress: () => { answerInputRef.current = ""; } },
              { text: '힌트보기', onPress: () => handleShowHint() },
            ]
          );
        } else {
          showAnswerStatus('wrong', '오답입니다.');
          answerInputRef.current = "";
        }
      }
    } catch (error) {
      console.error('답안 제출 오류:', error);
      Alert.alert('오류', '답안을 제출하는데 실패했습니다.');
    }
  };

  const handleShowHint = async () => {
    if (!selectedWord) return;

    // console.log('힌트보기 클릭:', {
    //   word_id: selectedWord.word_id,
    //   hint_id: selectedWord.hint_id,
    //   current_hint: selectedWord.hint
    // });

    try {
      // 기본 힌트 외 추가 힌트 조회 (기존 힌트 제외)
      const params = new URLSearchParams({
        word_id: selectedWord.word_id.toString()
      });
      
      // hint_id가 있으면 base_hint_id로 추가
      if (selectedWord.hint_id) {
        params.append('base_hint_id', selectedWord.hint_id.toString());
      }
      
      const response = await fetch(`http://222.100.103.227:8080/api/puzzle/hints?${params.toString()}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        },
      });

      // console.log('힌트 API 응답 상태:', response.status);
      const result = await response.json();
      // console.log('힌트 API 응답:', result);

      if (result.success) {
        // word_id를 기준으로 힌트 표시 상태 추적
        setHintsShown(prev => new Set([...prev, selectedWord.word_id]));
        setAdditionalHints(prev => new Map(prev).set(selectedWord.word_id, result.hints));
        
        console.log('추가 힌트 API 응답 성공:', {
          word_id: selectedWord.word_id,
          result: result,
          hints: result.hints,
          hintsCount: result.hints?.length || 0,
          message: result.message
        });
      } else {
        console.log('힌트 API 실패:', result);
      }
    } catch (error) {
      console.error('힌트 로드 오류:', error);
      Alert.alert('오류', '힌트를 불러오는데 실패했습니다.');
    }
  };

  const handleShowAnswer = async () => {
    if (!selectedWord) return;

    try {
      // word_id를 사용하여 정답 조회 (pz_words.id)
      const response = await fetch(`http://222.100.103.227:8080/api/puzzle/show-answer?word_id=${selectedWord.word_id}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        },
      });

      const result = await response.json();
      if (result.success) {
        // 정답을 입력칸에 자동 입력
        answerInputRef.current = result.answer;
        Alert.alert('정답', '정답이 입력칸에 입력되었습니다.');
      }
    } catch (error) {
      console.error('정답 보기 오류:', error);
      Alert.alert('오류', '정답을 불러오는데 실패했습니다.');
    }
  };

  const handleShowWrongCount = () => {
    if (!selectedWord) return;
    
    const wrongCount = wrongAnswers.get(selectedWord.word_id) || 0;
    
    if (wrongCount >= 5) {
      Alert.alert(
        '오답 초과',
        '오답의 회수가 초과했습니다. 레벨을 다시 시작합니다.',
        [
          { text: '재도전', onPress: () => handleRestartLevel() },
        ]
      );
    } else if (wrongCount >= 4) {
      Alert.alert(
        '경고',
        '현재 오답이 4회입니다. 5회 오답시 레벨을 재시작합니다.',
        [
          { text: '확인', onPress: () => {} },
        ]
      );
    } else {
      Alert.alert(
        '오답 정보',
        `현재 오답: ${wrongCount}회`,
        [
          { text: '확인', onPress: () => {} },
        ]
      );
    }
  };

  const handleRestartLevel = async () => {
    try {
      const response = await fetch('http://222.100.103.227:8080/api/puzzle/restart-level', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        },
      });

      const result = await response.json();
      if (result.success) {
        setAnsweredWords(new Set());
        setWrongAnswers(new Map());
        setHintsShown(new Set());
        setAdditionalHints(new Map());
        answerInputRef.current = "";
        setSelectedWord(null);
        setShowHint(false);
        loadPuzzle();
        Alert.alert('재시작', '레벨이 재시작되었습니다.');
      }
    } catch (error) {
      console.error('레벨 재시작 오류:', error);
      Alert.alert('오류', '레벨 재시작에 실패했습니다.');
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

  // 추가 힌트 뷰 useMemo로 미리 계산 (return문 바깥으로 이동)
  const additionalHintView = useMemo(() => {
    if (!selectedWord) return null;
    const hasShownHint = hintsShown.has(selectedWord.word_id);
    const hasAdditionalHints = additionalHints.has(selectedWord.word_id);
    const hints = additionalHints.get(selectedWord.word_id);
    if (hasShownHint && hasAdditionalHints) {
      return (
        <View style={styles.additionalHintsContainer}>
          <Text style={styles.additionalHintsTitle}>추가 힌트:</Text>
          {hints?.map((hint, index) => (
            <Text key={index} style={styles.additionalHintText}>
              • {hint}
            </Text>
          ))}
        </View>
      );
    }
    return null;
  }, [selectedWord, hintsShown, additionalHints]);

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
            onWordClick={onWordClick}
            onCellClick={onCellClick}
            answeredWords={answeredWords}
            wordAnswers={wordAnswers}
          />
        </View>
        {/* 선택된 단어 정보 및 입력 */}
        {selectedWord && (
          <View style={styles.inputSection}>
            {/* selectedWord.id: 퍼즐판에 표시되는 배지 번호 (puzzle_grid_templates.word_positions의 id) */}
            <Text style={styles.selectedWordText}>선택된 단어 번호: {selectedWord.id}</Text>
            
            {/* 정답/오답 상태 메시지 */}
            {answerStatus.type && (
              <Text style={[
                styles.answerStatusText,
                answerStatus.type === 'correct' ? styles.correctStatus : styles.wrongStatus
              ]}>
                {answerStatus.message}
              </Text>
            )}
            
            <Text style={styles.hintText}>힌트: {selectedWord.hint}</Text>
            
            {/* 추가 힌트 표시 - word_id(pz_words.id)를 사용하여 조회 */}
            {additionalHintView}
            
            <View style={styles.inputRow}>
              <TextInput
                style={styles.answerInput}
                placeholder="답을 입력하세요"
                autoCapitalize="none"
                autoCorrect={false}
                onChangeText={text => { answerInputRef.current = text; }}
                value={undefined} // 상태값 미사용, 입력값은 ref에만 저장
              />
              <TouchableOpacity style={styles.submitButton} onPress={handleAnswerSubmit}>
                <Text style={styles.buttonText}>입력</Text>
              </TouchableOpacity>
            </View>

            <View style={styles.bottomButtonRow}>
              <TouchableOpacity style={styles.hintButton} onPress={handleShowHint}>
                <Text style={styles.buttonText}>힌트보기</Text>
              </TouchableOpacity>
              
              <TouchableOpacity style={styles.wrongButton} onPress={handleShowWrongCount}>
                <Text style={styles.buttonText}>
                  오답 ({wrongAnswers.get(selectedWord.word_id) || 0}회)
                </Text>
              </TouchableOpacity>
              
              {user?.is_admin && (
                <TouchableOpacity style={styles.answerButton} onPress={handleShowAnswer}>
                  <Text style={styles.buttonText}>정답보기</Text>
                </TouchableOpacity>
              )}
            </View>
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
    flex: 1,
    marginRight: 10,
    backgroundColor: '#fff',
  },
  buttonRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 15,
  },
  bottomButtonRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 10,
  },
  submitButton: {
    backgroundColor: '#007AFF',
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 8,
    minWidth: 80,
  },
  hintButton: {
    backgroundColor: '#FF9500',
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 8,
    flex: 1,
    marginHorizontal: 5,
  },
  wrongButton: {
    backgroundColor: '#FF6B6B',
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
  inputRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 15,
  },
  answerStatusText: {
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 10,
    textAlign: 'center',
  },
  correctStatus: {
    color: '#4CAF50', // 정답 상태 메시지 색상
  },
  wrongStatus: {
    color: '#FF3B30', // 오답 상태 메시지 색상
  },
});

export default GameScreen; 