import React, { useMemo } from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';

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
  onWordClick?: (word: WordPosition) => void;
  onCellClick?: (x: number, y: number) => void;
  answeredWords?: Set<number>; // 정답이 입력된 단어들의 word_id 집합
  wordAnswers?: Map<number, string>; // word_id별 정답 단어 매핑
}

const CrosswordGrid: React.FC<CrosswordGridProps> = ({ 
  grid, 
  wordPositions, 
  onWordClick, 
  answeredWords = new Set(),
  wordAnswers = new Map()
}) => {
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
      if (answeredWords.has(word.word_id)) {
        const answer = wordAnswers.get(word.word_id);
        if (answer) {
          // 해당 위치의 문자 인덱스 계산
          let charIndex;
          if (word.direction === 'horizontal') {
            charIndex = x - word.start_x;
          } else {
            charIndex = y - word.start_y;
          }
          const char = answer.charAt(charIndex);
          return char;
        }
      }
    }
    return null;
  };

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
                style={[styles.cell, isBlack ? styles.blackCell : styles.whiteCell]}
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
                          const badgeStyle = idx === 0 ? styles.horizontalBadge : styles.verticalBadge;
                          return (
                            <TouchableOpacity
                              key={word.id}
                              style={[styles.badge, badgeStyle]}
                              onPress={() => onWordClick && onWordClick(word)}
                              activeOpacity={0.7}
                            >
                              <Text style={styles.badgeText}>{word.id}</Text>
                            </TouchableOpacity>
                          );
                        })}
                      </View>
                    ) : (
                      // 단일 단어: 하나의 배지 번호만 표시
                      <TouchableOpacity
                        style={styles.badge}
                        onPress={() => onWordClick && onWordClick(wordsAtStart[0])}
                        activeOpacity={0.7}
                      >
                        <Text style={styles.badgeText}>{wordsAtStart[0].id}</Text>
                      </TouchableOpacity>
                    )}
                  </>
                )}
                
                {/* 정답 문자 표시 */}
                {isBlack && getAnswerChar(x, y) && (
                  <Text style={styles.answerChar}>{getAnswerChar(x, y)}</Text>
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