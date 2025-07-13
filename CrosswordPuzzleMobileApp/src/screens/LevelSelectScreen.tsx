import React from 'react';
import { View, Text, StyleSheet } from 'react-native';

export const LevelSelectScreen: React.FC = () => {
  return (
    <View style={styles.container}>
      <Text style={styles.text}>레벨 선택 화면 (개발 중)</Text>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#f5f5f5',
  },
  text: {
    fontSize: 18,
    color: '#333',
  },
}); 