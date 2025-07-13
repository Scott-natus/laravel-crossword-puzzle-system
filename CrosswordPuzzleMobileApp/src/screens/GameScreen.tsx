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
  findNodeHandle,
  Image,
  Modal,
  Dimensions,
  SafeAreaView,
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

const { width: screenWidth, height: screenHeight } = Dimensions.get('window');

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
  // 입력값 상태 추가 (정답보기 기능을 위해)
  const [answerInput, setAnswerInput] = useState("");
  
  // 새로운 상태 추가
  const [showModal, setShowModal] = useState(false);
  const [modalType, setModalType] = useState<'restart' | 'complete' | null>(null);
  const [modalMessage, setModalMessage] = useState("");
  const [showAllAnswers, setShowAllAnswers] = useState(false);
  const [levelWrongCount, setLevelWrongCount] = useState(0); // 레벨당 누적 오답 횟수
  const [showLogoutModal, setShowLogoutModal] = useState(false);
  const [puzzleError, setPuzzleError] = useState<string | null>(null); // 퍼즐 로드 에러 상태 추가

  // wordAnswers 상태 변경 감지를 위한 useEffect 추가
  useEffect(() => {
    console.log('wordAnswers 상태 변경:', Array.from(wordAnswers.entries()));
  }, [wordAnswers]);

  // answeredWords 상태 변경 감지를 위한 useEffect 추가
  useEffect(() => {
    console.log('answeredWords 상태 변경:', Array.from(answeredWords));
  }, [answeredWords]);

  useEffect(() => {
    loadPuzzle();
  }, []);

  // 레벨당 누적 오답 횟수 계산
  useEffect(() => {
    const totalWrongCount = Array.from(wrongAnswers.values()).reduce((sum, count) => sum + count, 0);
    setLevelWrongCount(totalWrongCount);
  }, [wrongAnswers]);

  const loadPuzzle = async () => {
    try {
      setLoading(true);
      setPuzzleError(null); // 에러 상태 초기화
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
        if (data.data.template.words && data.data.template.words.length > 0) {
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
          // 단어 추출 실패 시 에러 상태 설정
          setPuzzleError('단어 추출에 실패했습니다. 다시 시도해주세요.');
          console.error('단어 추출 실패: template.words가 없거나 비어있음');
        }
      } else {
        throw new Error('퍼즐 데이터 형식이 올바르지 않습니다.');
      }
    } catch (error) {
      console.error('퍼즐 로드 오류:', error);
      setPuzzleError('퍼즐을 불러오는데 실패했습니다.');
    } finally {
      setLoading(false);
    }
  };

  // 기존 handleWordSelect를 onWordClick으로 이름 변경
  const onWordClick = (word: WordPosition) => {
    setSelectedWord(word);
    setShowHint(false);
    setAnswerInput(""); // ref 대신 상태값 사용
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
      onWordClick(selected);
    }
  };

  const showAnswerStatus = (type: 'correct' | 'wrong', message: string) => {
    setAnswerStatus({ type, message });
    setTimeout(() => {
      setAnswerStatus({ type: null, message: '' });
    }, 2000);
  };

  const handleAnswerSubmit = async () => {
    if (!selectedWord || !answerInput.trim()) return;

    try {
      const response = await fetch('http://222.100.103.227:8080/api/puzzle/submit-answer', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          word_id: selectedWord.word_id,
          answer: answerInput.trim(),
        }),
      });

      const data = await response.json();
      console.log('정답 제출 응답:', data);

      if (data.success) {
        // 정답인 경우
        setAnsweredWords(prev => new Set([...prev, selectedWord.word_id]));
        setWordAnswers(prev => new Map(prev.set(selectedWord.word_id, answerInput.trim())));
        showAnswerStatus('correct', '정답입니다!');
        setAnswerInput("");
        
        // 모든 단어를 맞췄는지 확인
        const allAnswered = wordPositions.every(wp => 
          answeredWords.has(wp.word_id) || wp.word_id === selectedWord.word_id
        );
        
        if (allAnswered) {
          setGameComplete(true);
          setModalType('complete');
          setModalMessage('축하합니다! 모든 단어를 맞추셨습니다!');
          setShowModal(true);
        }
      } else {
        // 오답인 경우
        const currentWrongCount = wrongAnswers.get(selectedWord.word_id) || 0;
        setWrongAnswers(prev => new Map(prev.set(selectedWord.word_id, currentWrongCount + 1)));
        showAnswerStatus('wrong', '틀렸습니다. 다시 시도해주세요.');
        setAnswerInput("");
      }
    } catch (error) {
      console.error('정답 제출 오류:', error);
      Alert.alert('오류', '정답을 제출하는 중 오류가 발생했습니다.');
    }
  };

  const getWrongCountMessage = () => {
    if (!selectedWord) return '';
    const wrongCount = wrongAnswers.get(selectedWord.word_id) || 0;
    if (wrongCount >= 3) {
      return '오답 3회 초과로 정답을 확인할 수 있습니다.';
    }
    return `오답 ${wrongCount}/3회`;
  };

  const handleShowHint = async () => {
    if (!selectedWord) return;

    try {
      const params = new URLSearchParams({
        word_id: selectedWord.word_id.toString(),
      });
      
      if (selectedWord.hint_id) {
        params.append('base_hint_id', selectedWord.hint_id.toString());
      }

      const response = await fetch(`http://222.100.103.227:8080/api/puzzle/hints?${params}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        },
      });

      const data = await response.json();
      console.log('힌트 응답:', data);

      if (data.success && data.data.hints && data.data.hints.length > 0) {
        const newHints = data.data.hints.map((hint: any) => hint.hint_text);
        setAdditionalHints(prev => new Map(prev.set(selectedWord.word_id, newHints)));
        setHintsShown(prev => new Set([...prev, selectedWord.word_id]));
        setShowHint(true);
      } else {
        Alert.alert('힌트', '추가 힌트가 없습니다.');
      }
    } catch (error) {
      console.error('힌트 로드 오류:', error);
      Alert.alert('오류', '힌트를 불러오는 중 오류가 발생했습니다.');
    }
  };

  const handleShowAnswer = async () => {
    if (!selectedWord) return;

    try {
      const response = await fetch(`http://222.100.103.227:8080/api/puzzle/show-answer?word_id=${selectedWord.word_id}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        },
      });

      const data = await response.json();
      console.log('정답 보기 응답:', data);

      if (data.success && data.data.answer) {
        setWordAnswers(prev => new Map(prev.set(selectedWord.word_id, data.data.answer)));
        setAnsweredWords(prev => new Set([...prev, selectedWord.word_id]));
        Alert.alert('정답', `정답은 "${data.data.answer}"입니다.`);
      } else {
        Alert.alert('오류', '정답을 불러올 수 없습니다.');
      }
    } catch (error) {
      console.error('정답 보기 오류:', error);
      Alert.alert('오류', '정답을 확인하는 중 오류가 발생했습니다.');
    }
  };

  const handleShowAllAnswersOnWrongCount = async () => {
    if (!selectedWord) return;

    try {
      const response = await fetch(`http://222.100.103.227:8080/api/puzzle/show-answer-wrong-count?word_id=${selectedWord.word_id}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        },
      });

      const data = await response.json();
      console.log('오답 초과 정답 보기 응답:', data);

      if (data.success && data.data.answer) {
        setWordAnswers(prev => new Map(prev.set(selectedWord.word_id, data.data.answer)));
        setAnsweredWords(prev => new Set([...prev, selectedWord.word_id]));
        Alert.alert('정답', `정답은 "${data.data.answer}"입니다.`);
      } else {
        Alert.alert('오류', '정답을 불러올 수 없습니다.');
      }
    } catch (error) {
      console.error('오답 초과 정답 보기 오류:', error);
      Alert.alert('오류', '정답을 확인하는 중 오류가 발생했습니다.');
    }
  };

  const handleShowWrongCount = () => {
    if (!selectedWord) return;
    
    const wrongCount = wrongAnswers.get(selectedWord.word_id) || 0;
    if (wrongCount >= 3) {
      Alert.alert(
        '오답 3회 초과',
        '오답 3회를 초과하셨습니다. 정답을 확인하시겠습니까?',
        [
          { text: '취소', style: 'cancel' },
          { text: '정답 보기', onPress: handleShowAllAnswersOnWrongCount },
        ]
      );
    } else {
      Alert.alert('오답 횟수', `현재 오답 ${wrongCount}/3회입니다.`);
    }
  };

  const handleRestartLevel = async () => {
    Alert.alert(
      '레벨 재시작',
      '현재 레벨을 다시 시작하시겠습니까?',
      [
        { text: '취소', style: 'cancel' },
        {
          text: '재시작',
          onPress: async () => {
            setAnsweredWords(new Set());
            setWrongAnswers(new Map());
            setHintsShown(new Set());
            setAdditionalHints(new Map());
            setWordAnswers(new Map());
            setGameComplete(false);
            setShowHint(false);
            setAnswerInput("");
            setSelectedWord(null);
            setAnswerStatus({ type: null, message: '' });
            await loadPuzzle();
          },
        },
      ]
    );
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

      const data = await response.json();
      console.log('레벨 완료 응답:', data);

      if (data.success) {
        Alert.alert('축하합니다!', '레벨을 완료했습니다!');
        // 다음 레벨로 이동하거나 메인 화면으로 이동
        if (navigation) {
          navigation.navigate('Main');
        }
      } else {
        Alert.alert('오류', '레벨 완료 처리 중 오류가 발생했습니다.');
      }
    } catch (error) {
      console.error('레벨 완료 오류:', error);
      Alert.alert('오류', '레벨 완료 처리 중 오류가 발생했습니다.');
    }
  };

  const handleNextLevel = async () => {
    setCurrentLevel(prev => prev + 1);
    setAnsweredWords(new Set());
    setWrongAnswers(new Map());
    setHintsShown(new Set());
    setAdditionalHints(new Map());
    setWordAnswers(new Map());
    setGameComplete(false);
    setShowHint(false);
    setAnswerInput("");
    setSelectedWord(null);
    setAnswerStatus({ type: null, message: '' });
    await loadPuzzle();
  };

  const handleLogout = () => {
    setShowLogoutModal(true);
  };

  const handleConfirmLogout = async () => {
    await logout();
    setShowLogoutModal(false);
    if (navigation) {
      navigation.navigate('Login');
    }
  };

  const handleCancelLogout = () => {
    setShowLogoutModal(false);
  };

  if (loading) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#007AFF" />
          <Text style={styles.loadingText}>퍼즐을 불러오는 중...</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (puzzleError) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <View style={styles.errorContainer}>
          <Text style={styles.errorText}>{puzzleError}</Text>
          <TouchableOpacity style={styles.retryButton} onPress={loadPuzzle}>
            <Text style={styles.retryButtonText}>다시 시도</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.container}>
        {/* 헤더 */}
        <View style={styles.header}>
          <View style={styles.headerLeft}>
            <Text style={styles.title}>레벨 {currentLevel}</Text>
            <Text style={styles.progressText}>
              완성: {answeredWords.size}/{wordPositions.length}
            </Text>
          </View>
          <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
            <Text style={styles.logoutButtonText}>로그아웃</Text>
          </TouchableOpacity>
        </View>

        {/* 퍼즐 그리드 */}
        <View style={styles.gridContainer}>
          {puzzleData && (
            <CrosswordGrid
              gridPattern={puzzleData.template.grid_pattern}
              wordPositions={wordPositions}
              answeredWords={answeredWords}
              wordAnswers={wordAnswers}
              onWordClick={onWordClick}
              onCellClick={onCellClick}
            />
          )}
        </View>

        {/* 선택된 단어 정보 및 입력 */}
        {selectedWord && (
          <View style={styles.wordInfo}>
            <View style={styles.wordHeader}>
              <Text style={styles.wordNumber}>단어 {selectedWord.id}</Text>
              <Text style={styles.wordDirection}>
                {selectedWord.direction === 'horizontal' ? '가로' : '세로'}
              </Text>
            </View>
            
            <Text style={styles.hint}>{selectedWord.hint}</Text>
            
            {answerStatus.type && (
              <View style={[
                styles.answerStatus,
                answerStatus.type === 'correct' ? styles.correctStatus : styles.wrongStatus
              ]}>
                <Text style={styles.answerStatusText}>{answerStatus.message}</Text>
              </View>
            )}

            <Text style={styles.wrongCount}>{getWrongCountMessage()}</Text>

            <TextInput
              style={styles.answerInput}
              value={answerInput}
              onChangeText={setAnswerInput}
              placeholder="정답을 입력하세요"
              autoCapitalize="none"
              autoCorrect={false}
              returnKeyType="done"
              onSubmitEditing={handleAnswerSubmit}
            />

            <View style={styles.buttonGrid}>
              <TouchableOpacity style={styles.submitButton} onPress={handleAnswerSubmit}>
                <Text style={styles.submitButtonText}>정답 제출</Text>
              </TouchableOpacity>
              
              <TouchableOpacity style={styles.hintButton} onPress={handleShowHint}>
                <Text style={styles.hintButtonText}>힌트 보기</Text>
              </TouchableOpacity>
              
              <TouchableOpacity style={styles.answerButton} onPress={handleShowAnswer}>
                <Text style={styles.answerButtonText}>정답 보기</Text>
              </TouchableOpacity>
              
              <TouchableOpacity style={styles.wrongCountButton} onPress={handleShowWrongCount}>
                <Text style={styles.wrongCountButtonText}>오답 횟수</Text>
              </TouchableOpacity>
            </View>

            {showHint && additionalHints.get(selectedWord.word_id) && (
              <View style={styles.additionalHints}>
                <Text style={styles.additionalHintsTitle}>추가 힌트:</Text>
                {additionalHints.get(selectedWord.word_id)?.map((hint, index) => (
                  <Text key={index} style={styles.additionalHint}>• {hint}</Text>
                ))}
              </View>
            )}
          </View>
        )}

        {/* 하단 버튼 */}
        <View style={styles.footer}>
          <TouchableOpacity style={styles.restartButton} onPress={handleRestartLevel}>
            <Text style={styles.restartButtonText}>레벨 재시작</Text>
          </TouchableOpacity>
        </View>

        {/* 모달 */}
        <Modal visible={showModal} transparent animationType="fade">
          <View style={styles.modalOverlay}>
            <View style={styles.modalContent}>
              <Text style={styles.modalTitle}>
                {modalType === 'complete' ? '레벨 완료!' : '재시작'}
              </Text>
              <Text style={styles.modalMessage}>{modalMessage}</Text>
              <View style={styles.modalButtons}>
                {modalType === 'complete' && (
                  <>
                    <TouchableOpacity style={styles.modalButton} onPress={handleGameComplete}>
                      <Text style={styles.modalButtonText}>레벨 완료</Text>
                    </TouchableOpacity>
                    <TouchableOpacity style={styles.modalButton} onPress={handleNextLevel}>
                      <Text style={styles.modalButtonText}>다음 레벨</Text>
                    </TouchableOpacity>
                  </>
                )}
                <TouchableOpacity 
                  style={[styles.modalButton, styles.modalButtonCancel]} 
                  onPress={() => setShowModal(false)}
                >
                  <Text style={styles.modalButtonText}>닫기</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </Modal>

        {/* 로그아웃 모달 */}
        <Modal visible={showLogoutModal} transparent animationType="fade">
          <View style={styles.modalOverlay}>
            <View style={styles.modalContent}>
              <Text style={styles.modalTitle}>로그아웃</Text>
              <Text style={styles.modalMessage}>정말 로그아웃하시겠습니까?</Text>
              <View style={styles.modalButtons}>
                <TouchableOpacity style={styles.modalButton} onPress={handleConfirmLogout}>
                  <Text style={styles.modalButtonText}>로그아웃</Text>
                </TouchableOpacity>
                <TouchableOpacity 
                  style={[styles.modalButton, styles.modalButtonCancel]} 
                  onPress={handleCancelLogout}
                >
                  <Text style={styles.modalButtonText}>취소</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </Modal>
      </View>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#f8f9fa',
  },
  container: {
    flex: 1,
    backgroundColor: '#f8f9fa',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
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
    padding: 20,
  },
  errorText: {
    fontSize: 16,
    color: '#ff0000',
    textAlign: 'center',
    marginBottom: 20,
  },
  retryButton: {
    backgroundColor: '#007AFF',
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 8,
    minHeight: 44,
  },
  retryButtonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: 'bold',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    backgroundColor: 'white',
    borderBottomWidth: 1,
    borderBottomColor: '#e0e0e0',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  headerLeft: {
    flex: 1,
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#333',
  },
  progressText: {
    fontSize: 14,
    color: '#666',
    marginTop: 4,
  },
  logoutButton: {
    backgroundColor: '#ff3b30',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 8,
    minHeight: 44,
  },
  logoutButtonText: {
    color: 'white',
    fontSize: 14,
    fontWeight: 'bold',
  },
  gridContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 10,
  },
  wordInfo: {
    backgroundColor: 'white',
    margin: 16,
    padding: 20,
    borderRadius: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  wordHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  wordNumber: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
  },
  wordDirection: {
    fontSize: 14,
    color: '#666',
    backgroundColor: '#f0f0f0',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
  },
  hint: {
    fontSize: 16,
    color: '#333',
    marginBottom: 15,
    lineHeight: 22,
  },
  answerStatus: {
    padding: 12,
    borderRadius: 8,
    marginBottom: 15,
  },
  correctStatus: {
    backgroundColor: '#d4edda',
    borderColor: '#c3e6cb',
    borderWidth: 1,
  },
  wrongStatus: {
    backgroundColor: '#f8d7da',
    borderColor: '#f5c6cb',
    borderWidth: 1,
  },
  answerStatusText: {
    fontSize: 16,
    fontWeight: 'bold',
    textAlign: 'center',
  },
  wrongCount: {
    fontSize: 14,
    color: '#ff9800',
    marginBottom: 15,
    fontWeight: 'bold',
  },
  answerInput: {
    borderWidth: 2,
    borderColor: '#ddd',
    borderRadius: 12,
    padding: 16,
    fontSize: 18,
    marginBottom: 20,
    backgroundColor: 'white',
    minHeight: 50,
  },
  buttonGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    marginBottom: 15,
  },
  submitButton: {
    backgroundColor: '#007AFF',
    paddingHorizontal: 16,
    paddingVertical: 14,
    borderRadius: 12,
    flex: 1,
    marginRight: 8,
    minHeight: 50,
  },
  submitButtonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: 'bold',
    textAlign: 'center',
  },
  hintButton: {
    backgroundColor: '#ff9800',
    paddingHorizontal: 16,
    paddingVertical: 14,
    borderRadius: 12,
    flex: 1,
    marginHorizontal: 4,
    minHeight: 50,
  },
  hintButtonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: 'bold',
    textAlign: 'center',
  },
  answerButton: {
    backgroundColor: '#4CAF50',
    paddingHorizontal: 16,
    paddingVertical: 14,
    borderRadius: 12,
    flex: 1,
    marginHorizontal: 4,
    minHeight: 50,
  },
  answerButtonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: 'bold',
    textAlign: 'center',
  },
  wrongCountButton: {
    backgroundColor: '#9c27b0',
    paddingHorizontal: 16,
    paddingVertical: 14,
    borderRadius: 12,
    flex: 1,
    marginLeft: 8,
    minHeight: 50,
  },
  wrongCountButtonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: 'bold',
    textAlign: 'center',
  },
  additionalHints: {
    marginTop: 15,
    padding: 15,
    backgroundColor: '#f0f8ff',
    borderRadius: 8,
  },
  additionalHintsTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 10,
  },
  additionalHint: {
    fontSize: 14,
    color: '#666',
    marginBottom: 8,
    lineHeight: 20,
  },
  footer: {
    padding: 16,
    backgroundColor: 'white',
    borderTopWidth: 1,
    borderTopColor: '#e0e0e0',
  },
  restartButton: {
    backgroundColor: '#ff5722',
    paddingVertical: 16,
    borderRadius: 12,
    minHeight: 50,
  },
  restartButtonText: {
    color: 'white',
    fontSize: 18,
    fontWeight: 'bold',
    textAlign: 'center',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalContent: {
    backgroundColor: 'white',
    borderRadius: 16,
    padding: 24,
    margin: 20,
    minWidth: 300,
    maxWidth: screenWidth * 0.9,
  },
  modalTitle: {
    fontSize: 22,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 15,
    textAlign: 'center',
  },
  modalMessage: {
    fontSize: 16,
    color: '#666',
    marginBottom: 20,
    textAlign: 'center',
    lineHeight: 22,
  },
  modalButtons: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    flexWrap: 'wrap',
  },
  modalButton: {
    backgroundColor: '#007AFF',
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 8,
    flex: 1,
    marginHorizontal: 5,
    minHeight: 44,
  },
  modalButtonCancel: {
    backgroundColor: '#666',
  },
  modalButtonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: 'bold',
    textAlign: 'center',
  },
});

export default GameScreen;