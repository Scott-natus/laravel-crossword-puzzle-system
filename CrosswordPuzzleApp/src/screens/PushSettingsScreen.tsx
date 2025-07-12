import React from 'react';
import { View, Text, StyleSheet } from 'react-native';

export const PushSettingsScreen: React.FC = () => {
  return (
    <View style={styles.container}>
      <Text style={styles.text}>푸시 설정 화면 (개발 중)</Text>
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