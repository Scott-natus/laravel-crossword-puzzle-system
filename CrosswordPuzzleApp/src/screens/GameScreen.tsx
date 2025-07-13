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

  // useEffect(() => {
  //   console.log('wordPositions:', wordPositions);
  //   if (puzzleData?.template?.grid_pattern) {
  //     console.log('grid:', puzzleData.template.grid_pattern);
  //   }
  // }, [wordPositions, puzzleData]);

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
      setSelectedWord(selected);
      setShowHint(false);
      setAnswerInput(""); // ref 대신 상태값 사용
    }
  };

  const showAnswerStatus = (type: 'correct' | 'wrong', message: string) => {
    setAnswerStatus({ type, message });
    
    // 3초 후 메시지 숨기기 (정답/오답 상태 메시지만)
    setTimeout(() => {
      setAnswerStatus({ type: null, message: '' });
    }, 3000);
  };

  const handleAnswerSubmit = async () => {
    if (!selectedWord || !answerInput.trim()) {
      Alert.alert('알림', '답을 입력해주세요.');
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
          answer: answerInput.trim(),
        }),
      });

      const result = await response.json();
      
      if (result.is_correct) {
        const newAnswer = answerInput.trim();
        
        // wordAnswers를 먼저 업데이트
        setWordAnswers(prev => {
          const newMap = new Map(prev);
          newMap.set(selectedWord.word_id, newAnswer);
          console.log('정답 제출 성공 - wordAnswers 업데이트:', {
            word_id: selectedWord.word_id,
            answer: newAnswer,
            prevWordAnswers: Array.from(prev.entries()),
            updatedWordAnswers: Array.from(newMap.entries())
          });
          return newMap;
        });
        
        // 그 다음 answeredWords 업데이트
        setAnsweredWords(prev => {
          const newSet = new Set([...prev, selectedWord.word_id]);
          console.log('정답 제출 성공 - answeredWords 업데이트:', {
            word_id: selectedWord.word_id,
            prevAnsweredWords: Array.from(prev),
            updatedAnsweredWords: Array.from(newSet)
          });
          
          // 모든 단어를 맞췄는지 확인 (업데이트된 후 체크)
          if (newSet.size >= wordPositions.length) {
            console.log('레벨 완료! 모든 단어를 맞췄습니다:', {
              answeredWordsCount: newSet.size,
              totalWordsCount: wordPositions.length
            });
            // 비동기로 handleGameComplete 호출
            setTimeout(() => handleGameComplete(), 100);
          }
          
          return newSet;
        });
        
        showAnswerStatus('correct', result.message);
        setAnswerInput(""); // 정답 시 입력칸 초기화
        
        // 기존 레벨 완료 체크 로직 제거 (위에서 처리)
      } else {
        const currentWrongCount = wrongAnswers.get(selectedWord.word_id) || 0;
        const newWrongCount = currentWrongCount + 1;
        setWrongAnswers(prev => new Map(prev).set(selectedWord.word_id, newWrongCount));
        
        // A 영역: 항상 "오답입니다 (누적오답: n회)" 표시
        const totalWrongCount = Array.from(wrongAnswers.values()).reduce((sum, count) => sum + count, 0) + 1;
        showAnswerStatus('wrong', `오답입니다 (누적오답: ${totalWrongCount}회)`);
        
        // 레벨당 누적 오답 횟수 확인
        if (totalWrongCount >= 5) {
          // 5회 초과 시 모든 정답 표시하고 모달 표시
          // 모든 단어의 정답을 서버에서 받아와 wordAnswers를 채움
          (async () => {
            const allAnswers = new Map(wordAnswers);
            await Promise.all(wordPositions.map(async (wp) => {
              if (!allAnswers.has(wp.word_id)) {
                try {
                  const res = await fetch(`http://222.100.103.227:8080/api/puzzle/show-answer-wrong-count?word_id=${wp.word_id}`, {
                    headers: {
                      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                    },
                  });
                  const result = await res.json();
                  if (result.success && result.answer) {
                    allAnswers.set(wp.word_id, result.answer);
                  }
                } catch (e) {
                  // 무시
                }
              }
            }));
            setWordAnswers(allAnswers);
            setShowAllAnswers(true);
          })();
          // setModalType('restart'); // 모달 대신 그리드 하단에 표시
          // setModalMessage('오답횟수가 초과했습니다. 레벨을 다시 시작합니다.');
          // setShowModal(true);
        }
      }
    } catch (error) {
      console.error('답안 제출 오류:', error);
      Alert.alert('오류', '답안을 제출하는데 실패했습니다.');
    }
  };

  // 오답 횟수별 메시지 반환
  const getWrongCountMessage = () => {
    if (levelWrongCount >= 5) {
      return '오답횟수가 초과했습니다. 레벨을 다시 시작합니다.';
    } else if (levelWrongCount >= 4) {
      return '현재 오답이 4회 입니다. 5회 오답시 레벨을 재시작합니다.';
    } else {
      return `(레벨당) 누적 오답: ${levelWrongCount}회`;
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
        // 정답을 입력칸에 자동 입력 (상태값 사용)
        setAnswerInput(result.answer);
        Alert.alert('정답', '정답이 입력칸에 입력되었습니다.');
      }
    } catch (error) {
      console.error('정답 보기 오류:', error);
      Alert.alert('오류', '정답을 불러오는데 실패했습니다.');
    }
  };

  // 오답 초과 시 모든 정답을 보여주는 함수 (관리자 권한과 관계없이)
  const handleShowAllAnswersOnWrongCount = async () => {
    try {
      // 모든 단어의 정답을 가져오기
      const allAnswers = new Map<number, string>();
      
      for (const word of wordPositions) {
        try {
          const response = await fetch(`http://222.100.103.227:8080/api/puzzle/show-answer-wrong-count?word_id=${word.word_id}`, {
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            },
          });

          const result = await response.json();
          if (result.success) {
            allAnswers.set(word.word_id, result.answer);
          }
        } catch (error) {
          console.error(`정답 조회 오류 (word_id: ${word.word_id}):`, error);
        }
      }

      // 모든 정답을 wordAnswers에 설정
      setWordAnswers(allAnswers);
      setShowAllAnswers(true);
      Alert.alert('정답 표시', '오답 초과로 인해 모든 정답이 표시됩니다.');
    } catch (error) {
      console.error('전체 정답 보기 오류:', error);
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

  // 레벨 재시작(재도전) - API 호출 대신 퍼즐 새로고침
  const handleRestartLevel = async () => {
    // 오답 초과 상태일 때는 정답을 모두 보여주고 재시작
    if (levelWrongCount >= 5) {
      await handleShowAllAnswersOnWrongCount();
    }
    
    setAnsweredWords(new Set());
    setWrongAnswers(new Map());
    setHintsShown(new Set());
    setAdditionalHints(new Map());
    setAnswerInput("");
    setSelectedWord(null);
    setShowHint(false);
    setShowAllAnswers(false);
    // setShowModal(false); // 모달 대신 그리드 하단에 표시
    // setModalType(null);
    // setModalMessage("");
    setLevelWrongCount(0);
    await loadPuzzle(); // 퍼즐 새로고침
  };

  const handleGameComplete = async () => {
    console.log('handleGameComplete 호출됨');
    try {
      const response = await fetch('http://222.100.103.227:8080/api/puzzle/complete-level', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
        },
      });

      const result = await response.json();
      console.log('레벨 완료 API 응답:', result);
      
      if (result.success) {
        console.log('레벨 완료 성공 - gameComplete 상태를 true로 설정');
        setGameComplete(true);
      } else {
        console.log('레벨 완료 API 실패했지만 gameComplete 상태를 true로 설정');
        setGameComplete(true); // API 실패해도 UI는 표시
      }
    } catch (error) {
      console.error('레벨 완료 처리 오류:', error);
      console.log('API 오류 발생했지만 gameComplete 상태를 true로 설정');
      setGameComplete(true); // API 오류가 발생해도 UI는 표시
    }
  };

  const handleNextLevel = async () => {
    setGameComplete(false);
    setAnsweredWords(new Set());
    setWrongAnswers(new Map());
    setHintsShown(new Set());
    setAdditionalHints(new Map());
    // setShowModal(false); // 모달 대신 그리드 하단에 표시
    // setModalType(null);
    // setModalMessage("");
    await loadPuzzle();
  };

  const handleLogout = () => {
    setShowLogoutModal(true);
  };

  const handleConfirmLogout = async () => {
    setShowLogoutModal(false);
    await logout();
    if (navigation) {
      navigation.navigate('Login');
    }
  };

  const handleCancelLogout = () => {
    setShowLogoutModal(false);
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

  const gridContainerRef = useRef<View | null>(null);
  const [gridBottom, setGridBottom] = useState(0);

  useEffect(() => {
    if (gridContainerRef.current) {
      const handle = findNodeHandle(gridContainerRef.current);
      if (handle && typeof handle === 'number') {
        // @ts-ignore
        gridContainerRef.current.measure?.((x: number, y: number, width: number, height: number, pageX: number, pageY: number) => {
          setGridBottom(pageY + height);
        });
      }
    }
  }, [loading, puzzleData, showAllAnswers]);

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#007AFF" />
        <Text style={styles.loadingText}>퍼즐을 불러오는 중...</Text>
      </View>
    );
  }

  if (!puzzleData || puzzleError) {
    return (
      <View style={styles.errorContainer}>
        <Text style={styles.errorText}>
          {puzzleError || '퍼즐을 불러올 수 없습니다.'}
        </Text>
        <TouchableOpacity style={styles.retryButton} onPress={loadPuzzle}>
          <Text style={styles.retryButtonText}>다시 시도</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <>
      {/* 오답 초과(5회) 시 오버레이+안내박스: 최상단에서 같은 부모로 렌더링 */}
      {levelWrongCount >= 5 && (
        <>
          {/* 오버레이: 전체 덮기, zIndex: 100 */}
          <View
            style={{
              position: 'absolute',
              top: 0, left: 0, right: 0, bottom: 0,
              backgroundColor: 'rgba(0,0,0,0.3)',
              zIndex: 100,
            }}
            pointerEvents="box-none"
          />
          {/* 안내박스: 오버레이 위에, zIndex: 200 */}
          <View
            style={{
              position: 'absolute',
              left: 0, right: 0,
              top: '50%',
              transform: [{ translateY: 180 }], // 중앙에서 300px 아래로 (기존 -120에서 +300 = 180)
              zIndex: 200,
              justifyContent: 'center',
              alignItems: 'center',
              pointerEvents: 'auto',
            }}
          >
            <View style={styles.bottomModalBox}>
              <Text style={styles.wrongCountTitle}>오답 횟수 초과</Text>
              <Text style={styles.wrongCountMessage}>오답횟수가 초과했습니다. 레벨을 다시 시작합니다.</Text>
              <TouchableOpacity style={styles.wrongCountButton} onPress={handleRestartLevel}>
                <Text style={styles.wrongCountButtonText}>재도전</Text>
              </TouchableOpacity>
            </View>
          </View>
        </>
      )}
      {/* 기존 화면 전체 */}
      <View style={styles.container}>
        {/* 상단 헤더 */}
        <View style={styles.header}>
          <Text style={styles.title}>Korean Cross Word</Text>
          <View style={styles.headerButtons}>
            <TouchableOpacity style={styles.homeButton} onPress={() => {
              if (navigation && typeof navigation.navigate === 'function') {
                navigation.navigate('Main');
              } else if (navigation && navigation.replace) {
                navigation.replace('Main');
              } else {
                // fallback: navigation이 없거나 navigate가 함수가 아니면 아무 동작 안 함
                console.warn('navigation 객체가 올바르지 않음');
              }
            }}>
              <Image source={require('../../assets/images/home.png')} style={styles.homeIcon} resizeMode="contain" />
            </TouchableOpacity>
            <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
              <Text style={styles.logoutButtonText}>로그아웃</Text>
            </TouchableOpacity>
          </View>
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
          <View style={styles.gridContainer} ref={gridContainerRef}>
            <CrosswordGrid
              key={`grid-${Array.from(wordAnswers.keys()).join('-')}`}
              grid={puzzleData.template?.grid_pattern || []}
              wordPositions={wordPositions}
              onWordClick={onWordClick}
              onCellClick={onCellClick}
              answeredWords={answeredWords}
              wordAnswers={wordAnswers}
              showAllAnswers={showAllAnswers}
            />
          </View>

          {/* 선택된 단어 정보 + 입력/버튼 영역 */}
          <View style={styles.wordInfoContainer}>
            {/* 퀴즈 번호/힌트/추가힌트는 selectedWord 있을 때만 */}
            {selectedWord ? (
              <>
                <Text style={styles.wordInfoTitle}>
                  {selectedWord.id}번 {selectedWord.direction === 'horizontal' ? '가로' : '세로'}
                </Text>
                <Text style={styles.wordInfoHint}>{selectedWord.hint}</Text>
                {/* 추가 힌트 표시 */}
                {additionalHintView}
              </>
            ) : (
              <>
                <Text style={styles.wordInfoTitle}></Text>
                <Text style={styles.wordInfoHint}></Text>
              </>
            )}

            {/* 입력 필드: 항상 노출 */}
            <View style={styles.inputContainer}>
              <TextInput
                style={styles.answerInput}
                value={answerInput}
                onChangeText={setAnswerInput}
                placeholder="답을 입력하세요"
                autoCapitalize="none"
                autoCorrect={false}
                editable={!!selectedWord}
              />
              <TouchableOpacity style={styles.submitButton} onPress={handleAnswerSubmit} disabled={!selectedWord}>
                <Text style={styles.submitButtonText}>제출</Text>
              </TouchableOpacity>
            </View>

            {/* 버튼들: 항상 노출, selectedWord 없으면 비활성화, 관리자만 정답보기 버튼 표시 */}
            <View style={styles.buttonContainer}>
              <TouchableOpacity style={styles.hintButton} onPress={handleShowHint} disabled={!selectedWord}>
                <Text style={styles.hintButtonText}>힌트 보기</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.wrongCountButton} onPress={handleShowWrongCount} disabled={!selectedWord}>
                <Text style={styles.wrongCountButtonText}>
                  오답 횟수{selectedWord ? `(${wrongAnswers.get(selectedWord.word_id) || 0}회)` : ''}
                </Text>
              </TouchableOpacity>
              {user?.is_admin ? (
                <TouchableOpacity style={styles.answerButton} onPress={handleShowAnswer} disabled={!selectedWord}>
                  <Text style={styles.answerButtonText}>정답 보기</Text>
                </TouchableOpacity>
              ) : null}
            </View>

            {/* 정답/오답 상태 메시지 (A) */}
            {answerStatus.type && (
              <View style={[
                styles.statusMessage,
                answerStatus.type === 'correct' ? styles.correctMessage : styles.wrongMessage
              ]}>
                <Text style={styles.statusMessageText}>{answerStatus.message}</Text>
              </View>
            )}
          </View>
        </ScrollView>

        {/* B영역: (레벨당) 누적 오답: n회, 입력/버튼 아래, 화면 하단에만 노출 */}
        {(levelWrongCount === 4 || levelWrongCount === 5) && (
          <View style={[styles.statusMessage, styles.wrongMessage, {margin: 16, marginTop: 0}]}> 
            <Text style={styles.statusMessageText}>{getWrongCountMessage()}</Text>
          </View>
        )}

        {/* 레벨 완료 시 축하 메시지 및 다음 레벨 이동 모달 */}
        {gameComplete && (
          <View style={styles.completeOverlay}>
            <View style={styles.completeModal}>
              <Text style={styles.completeTitle}>🎉 축하합니다!</Text>
              <Text style={styles.completeMessage}>레벨 {currentLevel}을 완료했습니다!</Text>
              <TouchableOpacity style={styles.completeButton} onPress={handleNextLevel}>
                <Text style={styles.completeButtonText}>다음 레벨</Text>
              </TouchableOpacity>
            </View>
          </View>
        )}
      </View>

      {/* 로그아웃 확인 모달 */}
      <Modal
        visible={showLogoutModal}
        transparent={true}
        animationType="fade"
        onRequestClose={handleCancelLogout}
      >
        <View style={styles.logoutModalOverlay}>
          <View style={styles.logoutModalContent}>
            <Text style={styles.logoutModalTitle}>로그아웃</Text>
            <Text style={styles.logoutModalMessage}>로그아웃 하시겠습니까?</Text>
            <View style={styles.logoutModalButtons}>
              <TouchableOpacity style={styles.logoutModalCancelButton} onPress={handleCancelLogout}>
                <Text style={styles.logoutModalCancelButtonText}>취소</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.logoutModalConfirmButton} onPress={handleConfirmLogout}>
                <Text style={styles.logoutModalConfirmButtonText}>확인</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </>
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
    padding: 16,
    backgroundColor: '#007AFF',
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
    color: 'white',
  },
  headerButtons: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  homeButton: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    borderRadius: 8,
    marginRight: 8,
  },
  homeButtonText: {
    fontSize: 20,
  },
  homeIcon: {
    width: 20, // 2px 더 작게
    height: 20, // 2px 더 작게
  },
  logoutButton: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    borderRadius: 8,
  },
  logoutButtonText: {
    color: 'white',
    fontWeight: 'bold',
  },
  levelHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    backgroundColor: 'white',
    borderBottomWidth: 1,
    borderBottomColor: '#e0e0e0',
  },
  levelText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
  },
  statsText: {
    fontSize: 16,
    color: '#666',
  },
  content: {
    flex: 1,
  },
  gridContainer: {
    alignItems: 'center',
    padding: 16,
  },
  wordInfoContainer: {
    backgroundColor: 'white',
    margin: 16,
    padding: 16,
    borderRadius: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  wordInfoTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 8,
  },
  wordInfoHint: {
    fontSize: 16,
    color: '#666',
    marginBottom: 16,
    lineHeight: 22,
  },
  additionalHintsContainer: {
    marginTop: 12,
    padding: 12,
    backgroundColor: '#f8f9fa',
    borderRadius: 8,
  },
  additionalHintsTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#495057',
    marginBottom: 8,
  },
  additionalHintText: {
    fontSize: 14,
    color: '#6c757d',
    marginBottom: 4,
  },
  inputContainer: {
    flexDirection: 'row',
    marginBottom: 16,
  },
  answerInput: {
    flex: 1,
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
    marginRight: 8,
  },
  submitButton: {
    backgroundColor: '#007AFF',
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 8,
    justifyContent: 'center',
  },
  submitButtonText: {
    color: 'white',
    fontWeight: 'bold',
    fontSize: 16,
  },
  buttonContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginBottom: 16,
    gap: 8,
  },
  hintButton: {
    backgroundColor: '#28a745',
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 8,
    alignItems: 'center',
  },
  hintButtonText: {
    color: 'white',
    fontWeight: 'bold',
  },
  answerButton: {
    backgroundColor: '#ffc107',
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 8,
    alignItems: 'center',
  },
  answerButtonText: {
    color: '#212529',
    fontWeight: 'bold',
  },
  wrongCountButton: {
    backgroundColor: '#dc3545',
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 8,
    alignItems: 'center',
  },
  wrongCountButtonText: {
    color: 'white',
    fontWeight: 'bold',
  },
  statusMessage: {
    padding: 12,
    borderRadius: 8,
    alignItems: 'center',
  },
  correctMessage: {
    backgroundColor: '#d4edda',
    borderColor: '#c3e6cb',
    borderWidth: 1,
  },
  wrongMessage: {
    backgroundColor: '#f8d7da',
    borderColor: '#f5c6cb',
    borderWidth: 1,
  },
  statusMessageText: {
    fontSize: 16,
    fontWeight: 'bold',
  },
  wrongCountContainer: {
    backgroundColor: 'white',
    margin: 16,
    padding: 12,
    borderRadius: 8,
    alignItems: 'center',
  },
  wrongCountText: {
    fontSize: 14,
    color: '#666',
  },
  logoutModalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  logoutModalContent: {
    backgroundColor: 'white',
    borderRadius: 12,
    padding: 24,
    margin: 20,
    minWidth: 280,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.25,
    shadowRadius: 4,
    elevation: 5,
  },
  logoutModalTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
    textAlign: 'center',
    marginBottom: 12,
  },
  logoutModalMessage: {
    fontSize: 16,
    color: '#666',
    textAlign: 'center',
    marginBottom: 24,
  },
  logoutModalButtons: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  logoutModalCancelButton: {
    flex: 1,
    backgroundColor: '#f0f0f0',
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 8,
    marginRight: 8,
  },
  logoutModalCancelButtonText: {
    color: '#666',
    fontSize: 16,
    fontWeight: '600',
    textAlign: 'center',
  },
  logoutModalConfirmButton: {
    flex: 1,
    backgroundColor: '#ff3b30',
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 8,
    marginLeft: 8,
  },
  logoutModalConfirmButtonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: '600',
    textAlign: 'center',
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
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#f5f5f5',
  },
  errorText: {
    fontSize: 16,
    color: '#666',
    marginBottom: 16,
  },
  retryButton: {
    backgroundColor: '#007AFF',
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 8,
  },
  retryButtonText: {
    color: 'white',
    fontWeight: 'bold',
  },
  // answerRevealContainer: 오답 초과 안내문구+버튼 스타일 추가
  answerRevealContainer: {
    backgroundColor: 'white',
    marginHorizontal: 16,
    marginTop: 16,
    marginBottom: 0,
    padding: 20,
    borderRadius: 12,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.08,
    shadowRadius: 4,
    elevation: 2,
  },
  answerRevealMessage: {
    fontSize: 16,
    color: '#333',
    marginBottom: 16,
    textAlign: 'center',
    fontWeight: 'bold',
  },
  answerRevealButton: {
    backgroundColor: '#007AFF',
    paddingHorizontal: 32,
    paddingVertical: 12,
    borderRadius: 8,
    alignItems: 'center',
  },
  answerRevealButtonText: {
    color: 'white',
    fontWeight: 'bold',
    fontSize: 16,
  },
  bottomModalBox: {
    backgroundColor: 'white',
    borderTopLeftRadius: 16,
    borderTopRightRadius: 16,
    borderBottomLeftRadius: 16,
    borderBottomRightRadius: 16,
    alignItems: 'center',
    padding: 5,
    boxShadow: '0 -2px 8px rgba(0,0,0,0.08)',
  },
  compactModalBox: {
    backgroundColor: 'white',
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
    paddingVertical: 20,
    minWidth: 260,
    maxWidth: '80%',
    // 높이 최소화: minHeight 제거, margin/padding 최소화
    elevation: 5,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 8,
  },
  wrongCountOverlay: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 100,
  },
  wrongCountModal: {
    backgroundColor: 'white',
    borderRadius: 16,
    padding: 24,
    alignItems: 'center',
    width: '80%',
    elevation: 5,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 8,
  },
  wrongCountTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 12,
  },
  wrongCountMessage: {
    fontSize: 16,
    color: '#666',
    textAlign: 'center',
    marginBottom: 24,
  },
  wrongCountButton: {
    backgroundColor: '#007AFF',
    paddingVertical: 12,
    paddingHorizontal: 32,
    borderRadius: 8,
  },
  wrongCountButtonText: {
    color: 'white',
    fontWeight: 'bold',
    fontSize: 16,
  },
  completeOverlay: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 100,
  },
  completeModal: {
    backgroundColor: 'white',
    borderRadius: 16,
    padding: 24,
    alignItems: 'center',
    width: '80%',
    elevation: 5,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 8,
  },
  completeTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#007AFF',
    marginBottom: 12,
  },
  completeMessage: {
    fontSize: 18,
    color: '#333',
    textAlign: 'center',
    marginBottom: 24,
  },
  completeButton: {
    backgroundColor: '#007AFF',
    paddingVertical: 12,
    paddingHorizontal: 32,
    borderRadius: 8,
  },
  completeButtonText: {
    color: 'white',
    fontWeight: 'bold',
    fontSize: 16,
  },
});

export default GameScreen;