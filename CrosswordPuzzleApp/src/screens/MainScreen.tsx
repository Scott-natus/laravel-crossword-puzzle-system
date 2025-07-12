import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
  Alert,
  ActivityIndicator,
} from 'react-native';
import { useAuth } from '../contexts/AuthContext';
import { apiService } from '../services/api';

interface MainScreenProps {
  navigation?: any;
}

interface UserStats {
  current_level: number;
  total_score: number;
  games_played: number;
  games_completed: number;
  accuracy_rate: number;
  best_streak: number;
}

interface RecentGame {
  id: number;
  level: number;
  completed_at: string;
  score: number;
  accuracy_rate: number;
}

export const MainScreen: React.FC<MainScreenProps> = ({ navigation }) => {
  const { user, logout } = useAuth();
  const [stats, setStats] = useState<UserStats | null>(null);
  const [recentGames, setRecentGames] = useState<RecentGame[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadUserData();
  }, []);

  const loadUserData = async () => {
    try {
      setLoading(true);
      
      // 사용자 통계 로드
      const statsResponse = await apiService.getUserStats();
      if (statsResponse.success && statsResponse.data) {
        setStats({
          current_level: statsResponse.data.current_level,
          total_score: statsResponse.data.total_score,
          games_played: statsResponse.data.total_games || 0,
          games_completed: 0, // API에서 제공하지 않는 경우 기본값
          accuracy_rate: statsResponse.data.average_accuracy || 0,
          best_streak: 0, // API에서 제공하지 않는 경우 기본값
        });
      }

      // 최근 게임 이력 로드
      const gamesResponse = await apiService.getRecentGames();
      if (gamesResponse.success && gamesResponse.data) {
        setRecentGames(gamesResponse.data.map((game: any) => ({
          id: game.id,
          level: game.level,
          completed_at: game.completed_at,
          score: game.score,
          accuracy_rate: 0, // API에서 제공하지 않는 경우 기본값
        })));
      }
    } catch (error) {
      console.error('사용자 데이터 로드 오류:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleStartGame = () => {
    // 게임 화면으로 이동
    if (navigation) {
      navigation.navigate('Game');
    }
  };

  const handleLogout = async () => {
    if (typeof window !== 'undefined') {
      // 웹 환경에서는 confirm 사용
      if (confirm('정말 로그아웃하시겠습니까?')) {
        await logout();
      }
    } else {
      // 모바일 환경에서는 Alert.alert 사용
      Alert.alert(
        '로그아웃',
        '정말 로그아웃하시겠습니까?',
        [
          { text: '취소', style: 'cancel' },
          {
            text: '로그아웃',
            style: 'destructive',
            onPress: async () => {
              await logout();
            },
          },
        ]
      );
    }
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#007AFF" />
        <Text style={styles.loadingText}>데이터를 불러오는 중...</Text>
      </View>
    );
  }

  return (
    <ScrollView style={styles.container}>
      {/* 헤더 */}
      <View style={styles.header}>
        <View style={styles.userInfo}>
          <Text style={styles.welcomeText}>
            안녕하세요, {user?.name}님! 👋
          </Text>
          <Text style={styles.subtitle}>크로스워드 퍼즐을 즐겨보세요</Text>
        </View>
        <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
          <Text style={styles.logoutButtonText}>로그아웃</Text>
        </TouchableOpacity>
      </View>

      {/* 통계 카드 */}
      {stats && (
        <View style={styles.statsContainer}>
          <Text style={styles.sectionTitle}>📊 게임 통계</Text>
          <View style={styles.statsGrid}>
            <View style={styles.statCard}>
              <Text style={styles.statValue}>{stats.current_level}</Text>
              <Text style={styles.statLabel}>현재 레벨</Text>
            </View>
            <View style={styles.statCard}>
              <Text style={styles.statValue}>{stats.total_score}</Text>
              <Text style={styles.statLabel}>총 점수</Text>
            </View>
            <View style={styles.statCard}>
              <Text style={styles.statValue}>{stats.games_played}</Text>
              <Text style={styles.statLabel}>플레이 횟수</Text>
            </View>
            <View style={styles.statCard}>
              <Text style={styles.statValue}>{stats.accuracy_rate}%</Text>
              <Text style={styles.statLabel}>정답률</Text>
            </View>
          </View>
        </View>
      )}

      {/* 최근 게임 이력 */}
      {recentGames.length > 0 && (
        <View style={styles.recentGamesContainer}>
          <Text style={styles.sectionTitle}>🎮 최근 게임</Text>
          {recentGames.slice(0, 5).map((game) => (
            <View key={game.id} style={styles.gameItem}>
              <View style={styles.gameInfo}>
                <Text style={styles.gameLevel}>레벨 {game.level}</Text>
                <Text style={styles.gameDate}>
                  {new Date(game.completed_at).toLocaleDateString()}
                </Text>
              </View>
              <View style={styles.gameStats}>
                <Text style={styles.gameScore}>점수: {game.score}</Text>
                <Text style={styles.gameAccuracy}>정답률: {game.accuracy_rate}%</Text>
              </View>
            </View>
          ))}
        </View>
      )}

      {/* 게임 시작 버튼 */}
      <View style={styles.startGameContainer}>
        <TouchableOpacity style={styles.startGameButton} onPress={handleStartGame}>
          <Text style={styles.startGameButtonText}>🎯 게임 시작</Text>
        </TouchableOpacity>
        <Text style={styles.startGameDescription}>
          현재 레벨 {stats?.current_level || 1}의 퍼즐을 풀어보세요!
        </Text>
      </View>
    </ScrollView>
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
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 20,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#e0e0e0',
  },
  userInfo: {
    flex: 1,
  },
  welcomeText: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 5,
  },
  subtitle: {
    fontSize: 16,
    color: '#666',
  },
  logoutButton: {
    backgroundColor: '#ff3b30',
    paddingHorizontal: 15,
    paddingVertical: 8,
    borderRadius: 6,
  },
  logoutButtonText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '600',
  },
  statsContainer: {
    padding: 20,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 15,
  },
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
  },
  statCard: {
    backgroundColor: '#fff',
    padding: 15,
    borderRadius: 10,
    width: '48%',
    marginBottom: 10,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  statValue: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#007AFF',
    marginBottom: 5,
  },
  statLabel: {
    fontSize: 14,
    color: '#666',
    textAlign: 'center',
  },
  recentGamesContainer: {
    padding: 20,
  },
  gameItem: {
    backgroundColor: '#fff',
    padding: 15,
    borderRadius: 10,
    marginBottom: 10,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  gameInfo: {
    flex: 1,
  },
  gameLevel: {
    fontSize: 16,
    fontWeight: '600',
    color: '#333',
    marginBottom: 5,
  },
  gameDate: {
    fontSize: 14,
    color: '#666',
  },
  gameStats: {
    alignItems: 'flex-end',
  },
  gameScore: {
    fontSize: 14,
    fontWeight: '600',
    color: '#007AFF',
    marginBottom: 2,
  },
  gameAccuracy: {
    fontSize: 12,
    color: '#666',
  },
  startGameContainer: {
    padding: 20,
    alignItems: 'center',
  },
  startGameButton: {
    backgroundColor: '#007AFF',
    paddingHorizontal: 40,
    paddingVertical: 15,
    borderRadius: 25,
    marginBottom: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.2,
    shadowRadius: 4,
    elevation: 4,
  },
  startGameButtonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: 'bold',
  },
  startGameDescription: {
    fontSize: 14,
    color: '#666',
    textAlign: 'center',
  },
}); 