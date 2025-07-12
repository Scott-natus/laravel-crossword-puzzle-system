import React from 'react';
import { View, Text, StyleSheet } from 'react-native';

interface WordPosition {
  id: number; // 퍼즐에 표시되는 번호
  word_id: number; // 정/오답 판정용 키
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
}

const CrosswordGrid: React.FC<CrosswordGridProps> = ({ grid, wordPositions }) => {
  // 검은칸 여부
  const isBlackCell = (cell: number) => String(cell) === '2';

  // 해당 좌표에서 시작하는 단어들(가로/세로)
  const getWordsAtStart = (x: number, y: number) => {
    return wordPositions.filter(
      (wp: WordPosition) => wp.start_x === x && wp.start_y === y
    );
  };

  return (
    <View style={styles.gridWrapper}>
      {grid.map((row: number[], y: number) => (
        <View key={y} style={styles.row}>
          {row.map((cell: number, x: number) => {
            const isBlack = isBlackCell(cell);
            const wordsAtStart = isBlack ? getWordsAtStart(x, y) : [];
            return (
              <View
                key={x}
                style={[styles.cell, isBlack ? styles.blackCell : styles.whiteCell]}
              >
                {/* 시작점에만 번호(배지) 표시 */}
                {isBlack && wordsAtStart.length > 0 && (
                  <>
                    {wordsAtStart.length === 2 ? (
                      // 교차점: 두 개의 번호 표시
                      <View style={styles.intersectionContainer}>
                        {/* 작은 번호(더 낮은 id)를 왼쪽 위, 큰 번호를 오른쪽 아래 */}
                        <View style={[styles.badge, styles.horizontalBadge]}>
                          <Text style={styles.badgeText}>
                            {Math.min(wordsAtStart[0].id, wordsAtStart[1].id)}
                          </Text>
                        </View>
                        <View style={[styles.badge, styles.verticalBadge]}>
                          <Text style={styles.badgeText}>
                            {Math.max(wordsAtStart[0].id, wordsAtStart[1].id)}
                          </Text>
                        </View>
                      </View>
                    ) : (
                      // 단일 단어: 하나의 번호만 표시
                      <View style={styles.badge}>
                        <Text style={styles.badgeText}>{wordsAtStart[0].id}</Text>
                      </View>
                    )}
                  </>
                )}
              </View>
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
});

export default CrosswordGrid; 