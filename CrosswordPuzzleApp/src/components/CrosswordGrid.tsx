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
  grid: number[][];
  wordPositions: WordPosition[];
  onWordClick: (word: WordPosition) => void;
  onCellClick: (x: number, y: number) => void;
  answeredWords: Set<number>;
  wordAnswers: Map<number, string>;
  showAllAnswers?: boolean; // 추가
}

const CrosswordGrid: React.FC<CrosswordGridProps> = ({
  grid,
  wordPositions,
  onWordClick,
  onCellClick,
  answeredWords,
  wordAnswers,
  showAllAnswers = false,
}) => {
  // 화면 크기 가져오기
  const screenWidth = Dimensions.get('window').width;
  const screenHeight = Dimensions.get('window').height;
  const isLandscape = screenWidth > screenHeight;

  // 그리드 크기 계산
  const gridSize = grid.length; // 5x5, 6x6, 7x7 등
  const maxGridWidth = isLandscape ? 400 : screenWidth * 0.9; // 가로형일 때 400px, 세로형일 때 90%
  const minGridWidth = isLandscape ? 320 : screenWidth * 0.8; // 가로형일 때 320px, 세로형일 때 80%
  
  // 그리드 크기에 따른 너비 결정
  const gridWidth = gridSize >= 7 ? maxGridWidth : minGridWidth;
  const cellSize = Math.floor(gridWidth / gridSize);
  
  // 배지 크기와 폰트 크기 조정
  const badgeSize = Math.max(12, Math.floor(cellSize * 0.45)); // 셀 크기의 45%
  const badgeFontSize = Math.max(8, Math.floor(badgeSize * 0.6));
  const answerFontSize = Math.max(14, Math.floor(cellSize * 0.5));
  const badgeOffset = Math.floor(cellSize * 0.1); // 셀 크기의 10%

  // 검은칸 여부
  const isBlackCell = (cell: number) => String(cell) === '2';

  // 모든 검은칸에 대해 가장 작은 배지 번호(id)를 미리 저장
  const cellToWordId = useMemo(() => {
    return grid.map((row, y) =>
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
  }, [grid, wordPositions]);

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

  // 동적 스타일 생성
  const dynamicStyles = useMemo(() => ({
    cell: {
      width: cellSize,
      height: cellSize,
      borderWidth: 1,
      borderColor: '#ccc',
      alignItems: 'center' as const,
      justifyContent: 'center' as const,
      position: 'relative' as const,
    },
    badge: {
      position: 'absolute' as const,
      borderRadius: Math.floor(badgeSize * 0.4),
      width: badgeSize,
      height: badgeSize,
      alignItems: 'center' as const,
      justifyContent: 'center' as const,
      backgroundColor: '#ff6b6b',
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
      {grid.map((row: number[], y: number) => (
        <View key={y} style={styles.row}>
          {row.map((cell: number, x: number) => {
            const isBlack = isBlackCell(cell);
            const wordsAtStart = isBlack ? getWordsAtStart(x, y) : [];
            const wordId = isBlack ? cellToWordId[y][x] : null;
            return (
              <TouchableOpacity
                key={x}
                style={[dynamicStyles.cell, isBlack ? styles.blackCell : styles.whiteCell]}
                activeOpacity={0.7}
                onPress={() => {
                  if (isBlack && wordId !== null && onWordClick) {
                    const word = wordPositions.find(wp => wp.id === wordId);
                    if (word) onWordClick(word);
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
                            <TouchableOpacity
                              key={word.id}
                              style={[dynamicStyles.badge, badgeStyle]}
                              onPress={() => onWordClick && onWordClick(word)}
                              activeOpacity={0.7}
                            >
                              <Text style={dynamicStyles.badgeText}>{word.id}</Text>
                            </TouchableOpacity>
                          );
                        })}
                      </View>
                    ) : (
                      // 단일 단어: 하나의 배지 번호만 표시
                      <TouchableOpacity
                        style={dynamicStyles.badge}
                        onPress={() => onWordClick && onWordClick(wordsAtStart[0])}
                        activeOpacity={0.7}
                      >
                        <Text style={dynamicStyles.badgeText}>{wordsAtStart[0].id}</Text>
                      </TouchableOpacity>
                    )}
                  </>
                )}
                
                {/* 정답 문자 표시 */}
                {isBlack && getAnswerChar(x, y) && (
                  <Text style={dynamicStyles.answerChar}>{getAnswerChar(x, y)}</Text>
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
    alignSelf: 'center',
    marginVertical: 20,
  },
  row: {
    flexDirection: 'row',
  },
  cell: {
    width: 40,
    height: 40,
    borderWidth: 1,
    borderColor: '#ccc',
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
  },
  blackCell: {
    backgroundColor: '#222',
  },
  whiteCell: {
    backgroundColor: '#fff',
  },
  intersectionContainer: {
    position: 'absolute',
    width: '100%',
    height: '100%',
  },
  badge: {
    position: 'absolute',
    borderRadius: 8,
    width: 18,
    height: 18,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#ff6b6b', // 기본 빨간색
  },
  horizontalBadge: {
    top: 2,
    left: 2,
    backgroundColor: '#ff6b6b', // 빨간색(작은 번호)
  },
  verticalBadge: {
    bottom: 2,
    right: 2,
    backgroundColor: '#4ecdc4', // 청록색(큰 번호)
  },
  badgeText: {
    color: '#fff',
    fontWeight: 'bold',
    fontSize: 10,
  },
  answerChar: {
    position: 'absolute',
    fontSize: 20,
    fontWeight: 'bold',
    color: '#fff',
    zIndex: 1,
  },
});

export default React.memo(CrosswordGrid); 