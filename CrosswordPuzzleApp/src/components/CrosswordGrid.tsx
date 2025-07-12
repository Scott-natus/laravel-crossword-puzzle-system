import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';

interface WordPosition {
  id: number;
  word_id: number;
  hint: string;
  start_x: number;
  start_y: number;
  end_x: number;
  end_y: number;
  direction: number;
}

interface CrosswordGridProps {
  grid: number[][];
  wordPositions: WordPosition[];
}

const CrosswordGrid: React.FC<CrosswordGridProps> = ({ grid, wordPositions }) => {
  // 검은칸 여부
  const isBlackCell = (cell: number) => String(cell) === '2';

  // 해당 좌표에 wordPosition이 있는지
  const getWordPositionAt = (x: number, y: number) => {
    return wordPositions.find(
      (wp: WordPosition) => wp.start_x === x && wp.start_y === y
    );
  };

  return (
    <View style={styles.gridWrapper}>
      {grid.map((row: number[], y: number) => (
        <View key={y} style={styles.row}>
          {row.map((cell: number, x: number) => {
            const isBlack = isBlackCell(cell);
            const wordPos = isBlack ? getWordPositionAt(x, y) : null;
            return (
              <View
                key={x}
                style={[styles.cell, isBlack ? styles.blackCell : styles.whiteCell]}
              >
                {isBlack && wordPos && (
                  <View style={styles.badge}>
                    <Text style={styles.badgeText}>{wordPos.id}</Text>
                  </View>
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
  badge: {
    position: 'absolute',
    top: 2,
    left: 2,
    backgroundColor: '#ff6b6b',
    borderRadius: 8,
    width: 18,
    height: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeText: {
    color: '#fff',
    fontWeight: 'bold',
    fontSize: 12,
  },
});

export default CrosswordGrid; 