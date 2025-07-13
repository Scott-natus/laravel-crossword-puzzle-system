import React, { useMemo } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Dimensions } from 'react-native';

interface WordPosition {
  id: number; // 퍼즐에 표시되는 배지 번호 (puzzle_grid_templates.word_positions의 id)
  word_id: number; // 실제 단어 ID (pz_words.id) - 정답/힌트 조회용 키값
  hint: string;
  start_x: number;
  start_y: number;
  end_x: number;
  end_y: number;
  direction: string; // 'horizontal' | 'vertical'
}

interface CrosswordGridProps {
  gridPattern: number[][];
  wordPositions: WordPosition[];
  onWordClick: (word: WordPosition) => void;
  onCellClick: (x: number, y: number) => void;
  answeredWords: Set<number>;
  wordAnswers: Map<number, string>;
  showAllAnswers?: boolean; // 추가
}

const { width: screenWidth, height: screenHeight } = Dimensions.get('window');

const CrosswordGrid: React.FC<CrosswordGridProps> = ({
  gridPattern,
  wordPositions,
  onWordClick,
  onCellClick,
  answeredWords,
  wordAnswers,
  showAllAnswers = false,
}) => {
  // 화면 크기 가져오기
  const isLandscape = screenWidth > screenHeight;

  // 그리드 크기 계산 - 모바일 최적화
  const gridSize = gridPattern.length; // 5x5, 6x6, 7x7 등
  
  // 모바일 화면에 맞는 그리드 크기 계산
  const maxGridWidth = Math.min(screenWidth * 0.9, 400); // 최대 400px, 화면의 90%
  const minGridWidth = Math.min(screenWidth * 0.85, 350); // 최소 350px, 화면의 85%
  
  // 그리드 크기에 따른 너비 결정
  const gridWidth = gridSize >= 7 ? maxGridWidth : minGridWidth;
  const cellSize = Math.floor(gridWidth / gridSize);
  
  // 모바일 터치에 최적화된 크기 조정
  const badgeSize = Math.max(16, Math.floor(cellSize * 0.4)); // 셀 크기의 40%, 최소 16px
  const badgeFontSize = Math.max(10, Math.floor(badgeSize * 0.6));
  const answerFontSize = Math.max(16, Math.floor(cellSize * 0.45)); // 더 큰 폰트
  const badgeOffset = Math.floor(cellSize * 0.08); // 셀 크기의 8%

  // 검은칸 여부
  const isBlackCell = (cell: number) => String(cell) === '2';

  // 모든 검은칸에 대해 가장 작은 배지 번호(id)를 미리 저장
  const cellToWordId = useMemo(() => {
    return gridPattern.map((row, y) =>
      row.map((cell, x) => {
        if (isBlackCell(cell)) {
          // 해당 칸에 소속된 단어들의 배지 번호(id) 수집
          const wordIds = wordPositions
            .filter(wp =>
              (wp.direction === 'horizontal' && y === wp.start_y && x >= wp.start_x && x <= wp.end_x) ||
              (wp.direction === 'vertical' && x === wp.start_x && y >= wp.start_y && y <= wp.end_y)
            )
            .map(wp => wp.id); // 배지 번호(id) 사용
          return wordIds.length > 0 ? Math.min(...wordIds) : null;
        }
        return null;
      })
    );
  }, [gridPattern, wordPositions]);

  // 해당 좌표에서 시작하는 단어들(가로/세로)
  const getWordsAtStart = (x: number, y: number) => {
    return wordPositions.filter(
      (wp: WordPosition) => wp.start_x === x && wp.start_y === y
    );
  };

  // 해당 좌표에서 정답을 표시할 문자를 가져오는 함수
  const getAnswerChar = (x: number, y: number) => {
    const wordsAtPosition = wordPositions.filter(wp => {
      if (wp.direction === 'horizontal') {
        return y === wp.start_y && x >= wp.start_x && x <= wp.end_x;
      } else {
        return x === wp.start_x && y >= wp.start_y && y <= wp.end_y;
      }
    });

    for (const word of wordsAtPosition) {
      // showAllAnswers가 true면 모든 단어의 정답 표시
      if (showAllAnswers || answeredWords.has(word.word_id)) {
        const answer = wordAnswers.get(word.word_id);
        if (answer) {
          let charIndex;
          if (word.direction === 'horizontal') {
            charIndex = x - word.start_x;
          } else {
            charIndex = y - word.start_y;
          }
          if (charIndex >= 0 && charIndex < answer.length) {
            return answer[charIndex];
          }
        }
      }
    }
    return null;
  };

  // 동적 스타일 생성 - 모바일 최적화
  const dynamicStyles = useMemo(() => ({
    cell: {
      width: cellSize,
      height: cellSize,
      borderWidth: 1.5, // 더 두꺼운 테두리
      borderColor: '#ccc',
      alignItems: 'center' as const,
      justifyContent: 'center' as const,
      position: 'relative' as const,
    },
    badge: {
      position: 'absolute' as const,
      borderRadius: Math.floor(badgeSize * 0.5), // 더 둥근 모서리
      width: badgeSize,
      height: badgeSize,
      alignItems: 'center' as const,
      justifyContent: 'center' as const,
      backgroundColor: '#ff6b6b',
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 2 },
      shadowOpacity: 0.2,
      shadowRadius: 3,
      elevation: 3,
    },
    horizontalBadge: {
      top: badgeOffset,
      left: badgeOffset,
      backgroundColor: '#ff6b6b',
    },
    verticalBadge: {
      bottom: badgeOffset,
      right: badgeOffset,
      backgroundColor: '#4ecdc4',
    },
    badgeText: {
      color: '#fff',
      fontWeight: 'bold' as const,
      fontSize: badgeFontSize,
    },
    answerChar: {
      position: 'absolute' as const,
      fontSize: answerFontSize,
      fontWeight: 'bold' as const,
      color: '#fff',
      zIndex: 1,
      textShadowColor: 'rgba(0, 0, 0, 0.5)',
      textShadowOffset: { width: 1, height: 1 },
      textShadowRadius: 2,
    },
  }), [cellSize, badgeSize, badgeFontSize, answerFontSize, badgeOffset]);

  // 디버깅을 위한 로그 추가
  console.log('CrosswordGrid 렌더링:', {
    answeredWords: Array.from(answeredWords),
    wordAnswers: Array.from(wordAnswers.entries()),
    wordPositionsCount: wordPositions.length,
    gridSize,
    cellSize,
    screenWidth,
    isLandscape,
    gridWidth
  });

  return (
    <View style={styles.gridWrapper}>
      {gridPattern.map((row: number[], y: number) => (
        <View key={y} style={styles.row}>
          {row.map((cell: number, x: number) => {
            const isBlack = isBlackCell(cell);
            const wordsAtStart = isBlack ? getWordsAtStart(x, y) : [];
            const wordId = isBlack ? cellToWordId[y][x] : null;
            const answerChar = isBlack ? getAnswerChar(x, y) : null;
            
            return (
              <TouchableOpacity
                key={x}
                style={[
                  dynamicStyles.cell, 
                  isBlack ? styles.blackCell : styles.whiteCell,
                  // 터치 피드백을 위한 스타일
                  { minHeight: 44, minWidth: 44 } // 최소 터치 영역 보장
                ]}
                activeOpacity={0.6} // 더 명확한 터치 피드백
                onPress={() => {
                  if (isBlack && wordId !== null && onWordClick) {
                    const word = wordPositions.find(wp => wp.id === wordId);
                    if (word) onWordClick(word);
                  } else if (onCellClick) {
                    onCellClick(x, y);
                  }
                }}
                disabled={!isBlack || wordId === null}
              >
                {/* 시작점에만 배지 번호(id) 표시 */}
                {isBlack && wordsAtStart.length > 0 && (
                  <>
                    {wordsAtStart.length === 2 ? (
                      // 교차점: 두 개의 배지 번호 표시
                      <View style={styles.intersectionContainer}>
                        {[0, 1].map(idx => {
                          const sorted = [...wordsAtStart].sort((a, b) => a.id - b.id);
                          const word = sorted[idx];
                          const badgeStyle = idx === 0 ? dynamicStyles.horizontalBadge : dynamicStyles.verticalBadge;
                          return (
                            <View key={idx} style={[dynamicStyles.badge, badgeStyle]}>
                              <Text style={dynamicStyles.badgeText}>{word.id}</Text>
                            </View>
                          );
                        })}
                      </View>
                    ) : (
                      // 단일 단어: 하나의 배지 번호 표시
                      <View style={[dynamicStyles.badge, dynamicStyles.horizontalBadge]}>
                        <Text style={dynamicStyles.badgeText}>{wordsAtStart[0].id}</Text>
                      </View>
                    )}
                  </>
                )}

                {/* 정답 문자 표시 */}
                {isBlack && answerChar && (
                  <Text style={dynamicStyles.answerChar}>
                    {answerChar}
                  </Text>
                )}
              </TouchableOpacity>
            );
          })}
        </View>
      ))}
    </View>
  );
};

const styles = StyleSheet.create({
  gridWrapper: {
    alignItems: 'center',
    padding: 16,
    backgroundColor: 'white',
    borderRadius: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  row: {
    flexDirection: 'row',
  },
  blackCell: {
    backgroundColor: '#2c3e50', // 더 진한 검은색
  },
  whiteCell: {
    backgroundColor: '#fff',
  },
  intersectionContainer: {
    position: 'relative',
    width: '100%',
    height: '100%',
  },
});

export default CrosswordGrid; 