import React, { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ScrollView,
} from 'react-native';
import { apiService } from '../services/api';
import { useAuth } from '../contexts/AuthContext';

const LoginScreen: React.FC = () => {
  const [email, setEmail] = useState('test@test.com');
  const [password, setPassword] = useState('123456');
  const [loading, setLoading] = useState(false);
  const [logs, setLogs] = useState<string[]>([]);
  const { login } = useAuth(); // AuthContext에서 login 함수 가져오기

  // APK 빌드 시간 정보
  const BUILD_TIME = '2025-07-31 17:57:31'; // 이 값을 빌드할 때마다 업데이트

  // 로그 추가 함수
  const addLog = (message: string) => {
    const timestamp = new Date().toLocaleTimeString();
    const logMessage = `[${timestamp}] ${message}`;
    setLogs(prev => [...prev.slice(-9), logMessage]); // 최근 10개만 유지
    console.log(logMessage);
  };

  // 로그 초기화
  const clearLogs = () => {
    setLogs([]);
  };

  // 컴포넌트 마운트 시 빌드 정보 표시
  React.useEffect(() => {
    addLog('🚀 앱 시작됨');
    addLog(`📱 APK 빌드 시간: ${BUILD_TIME}`);
    addLog('🔧 디버그 모드 활성화');
  }, []);

  // 네트워크 연결 테스트 함수 추가
  const testNetworkConnection = async () => {
    try {
      addLog('🌐 네트워크 연결 테스트 시작...');
      const response = await fetch('http://222.100.103.227:8080/api/test');
      const data = await response.json();
      addLog('✅ 네트워크 테스트 성공: ' + JSON.stringify(data));
      Alert.alert('네트워크 테스트', `성공!\n서버: ${data.server_ip}\n클라이언트: ${data.client_ip}`);
    } catch (error: any) {
      addLog('❌ 네트워크 테스트 실패: ' + error.message);
      Alert.alert('네트워크 테스트', `실패!\n오류: ${error.message}`);
    }
  };

  // 직접 API 호출 테스트 함수 추가
  const testDirectApiCall = async () => {
    try {
      addLog('🧪 직접 API 호출 테스트 시작...');
      
      // fetch를 사용한 직접 호출
      const response = await fetch('http://222.100.103.227:8080/api/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          email: 'test@test.com',
          password: '123456'
        })
      });
      
      addLog(`📊 응답 상태: ${response.status}`);
      addLog(`📋 응답 헤더: ${JSON.stringify(Object.fromEntries(response.headers.entries()))}`);
      
      const responseText = await response.text();
      addLog(`📄 응답 텍스트: ${responseText}`);
      
      if (response.ok) {
        const data = JSON.parse(responseText);
        addLog('✅ 직접 API 호출 성공: ' + JSON.stringify(data));
        Alert.alert('직접 API 테스트', '성공!\n응답을 로그에서 확인하세요.');
      } else {
        addLog('❌ 직접 API 호출 실패: ' + response.status);
        Alert.alert('직접 API 테스트', `실패!\n상태: ${response.status}`);
      }
    } catch (error: any) {
      addLog('❌ 직접 API 호출 에러: ' + error.message);
      addLog('❌ 에러 상세: ' + JSON.stringify(error));
      Alert.alert('직접 API 테스트', `에러!\n${error.message}`);
    }
  };

  // axios를 사용한 직접 호출 테스트
  const testAxiosCall = async () => {
    try {
      addLog('🚀 Axios 직접 호출 테스트 시작...');
      
      const axios = require('axios');
      const response = await axios.post('http://222.100.103.227:8080/api/login', {
        email: 'test@test.com',
        password: '123456'
      }, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        timeout: 10000
      });
      
      addLog('✅ Axios 호출 성공: ' + JSON.stringify(response.data));
      Alert.alert('Axios 테스트', '성공!\n응답을 로그에서 확인하세요.');
    } catch (error: any) {
      addLog('❌ Axios 호출 실패: ' + error.message);
      if (error.response) {
        addLog('❌ 응답 에러: ' + JSON.stringify(error.response.data));
      }
      Alert.alert('Axios 테스트', `실패!\n${error.message}`);
    }
  };

  const handleLogin = async () => {
    if (!email || !password) {
      Alert.alert('오류', '이메일과 비밀번호를 입력해주세요.');
      return;
    }

    setLoading(true);
    try {
      addLog('🔐 로그인 시도 중...');
      addLog(`📧 이메일: ${email}`);
      addLog(`🔑 비밀번호: ${password}`);
      addLog('🔍 apiService.login 함수 호출 시작...');
      
      const response = await apiService.login({ email, password });
      addLog('✅ 로그인 성공: ' + JSON.stringify(response));
      
      // 로그인 성공 후 메인 화면으로 이동
      addLog('🔐 CrosswordPuzzleMobileApp AuthContext 로그인 시도 시작...');
      try {
        const loginSuccess = await login(email, password);
        addLog('🔍 CrosswordPuzzleMobileApp AuthContext 로그인 결과: ' + loginSuccess);
        if (loginSuccess) {
          addLog('✅ CrosswordPuzzleMobileApp AuthContext 로그인 성공');
        } else {
          addLog('❌ CrosswordPuzzleMobileApp AuthContext 로그인 실패');
        }
      } catch (authError: any) {
        addLog('❌ CrosswordPuzzleMobileApp AuthContext 로그인 에러: ' + authError.message);
        addLog('❌ CrosswordPuzzleMobileApp AuthContext 에러 상세: ' + JSON.stringify(authError));
      }
    } catch (error: any) {
      addLog('❌ 로그인 실패: ' + error.message);
      addLog('❌ 에러 상세: ' + JSON.stringify(error));
      Alert.alert('로그인 실패', error.message || '로그인에 실패했습니다.');
    } finally {
      setLoading(false);
    }
  };

  const handleTestLogin = async () => {
    setLoading(true);
    try {
      addLog('🧪 테스트 로그인 시도...');
      addLog('🔍 apiService.login 함수 호출 시작...');
      
      const response = await apiService.login({ 
        email: 'test@test.com', 
        password: '123456' 
      });
      addLog('✅ 테스트 로그인 성공: ' + JSON.stringify(response));
      
      // 로그인 성공 후 메인 화면으로 이동
      addLog('🔐 CrosswordPuzzleMobileApp AuthContext 테스트 로그인 시도 시작...');
      try {
        const loginSuccess = await login('test@test.com', '123456');
        addLog('🔍 CrosswordPuzzleMobileApp AuthContext 테스트 로그인 결과: ' + loginSuccess);
        if (loginSuccess) {
          addLog('✅ CrosswordPuzzleMobileApp AuthContext 테스트 로그인 성공');
        } else {
          addLog('❌ CrosswordPuzzleMobileApp AuthContext 테스트 로그인 실패');
        }
      } catch (authError: any) {
        addLog('❌ CrosswordPuzzleMobileApp AuthContext 테스트 로그인 에러: ' + authError.message);
        addLog('❌ CrosswordPuzzleMobileApp AuthContext 테스트 에러 상세: ' + JSON.stringify(authError));
      }
    } catch (error: any) {
      addLog('❌ 테스트 로그인 실패: ' + error.message);
      addLog('❌ 에러 상세: ' + JSON.stringify(error));
      Alert.alert('테스트 로그인 실패', error.message || '테스트 로그인에 실패했습니다.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <View style={styles.content}>
        <Text style={styles.title}>Crossword Puzzle</Text>
        <Text style={styles.subtitle}>로그인</Text>

        <View style={styles.inputContainer}>
          <TextInput
            style={styles.input}
            placeholder="이메일"
            value={email}
            onChangeText={setEmail}
            keyboardType="email-address"
            autoCapitalize="none"
          />
          <TextInput
            style={styles.input}
            placeholder="비밀번호"
            value={password}
            onChangeText={setPassword}
            secureTextEntry
          />
        </View>

        <View style={styles.buttonContainer}>
          <TouchableOpacity
            style={[styles.button, styles.loginButton]}
            onPress={handleLogin}
            disabled={loading}
          >
            <Text style={styles.buttonText}>
              {loading ? '로그인 중...' : '로그인'}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.button, styles.testButton]}
            onPress={handleTestLogin}
            disabled={loading}
          >
            <Text style={styles.buttonText}>테스트 로그인</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.button, styles.networkButton]}
            onPress={testNetworkConnection}
            disabled={loading}
          >
            <Text style={styles.buttonText}>네트워크 테스트</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.button, styles.clearButton]}
            onPress={clearLogs}
          >
            <Text style={styles.buttonText}>로그 초기화</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.button, styles.directApiButton]}
            onPress={testDirectApiCall}
          >
            <Text style={styles.buttonText}>직접 API 테스트</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.button, styles.axiosButton]}
            onPress={testAxiosCall}
          >
            <Text style={styles.buttonText}>Axios 테스트</Text>
          </TouchableOpacity>
        </View>

        {/* 로그 표시 영역 */}
        <View style={styles.logContainer}>
          <Text style={styles.logTitle}>📋 API 호출 로그:</Text>
          {logs.map((log, index) => (
            <Text key={index} style={styles.logText}>
              {log}
            </Text>
          ))}
        </View>

        <TouchableOpacity
          style={styles.signupLink}
          onPress={() => {
            addLog('📝 회원가입 버튼 클릭됨 (현재 비활성화)');
            Alert.alert('회원가입', '회원가입 기능은 현재 개발 중입니다.');
          }}
        >
          <Text style={styles.signupText}>계정이 없으신가요? 회원가입</Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flexGrow: 1,
    backgroundColor: '#f5f5f5',
  },
  content: {
    flex: 1,
    padding: 20,
    justifyContent: 'center',
  },
  title: {
    fontSize: 28,
    fontWeight: 'bold',
    textAlign: 'center',
    marginBottom: 10,
    color: '#333',
  },
  subtitle: {
    fontSize: 18,
    textAlign: 'center',
    marginBottom: 30,
    color: '#666',
  },
  inputContainer: {
    marginBottom: 20,
  },
  input: {
    backgroundColor: '#fff',
    paddingHorizontal: 15,
    paddingVertical: 12,
    borderRadius: 8,
    marginBottom: 10,
    fontSize: 16,
    borderWidth: 1,
    borderColor: '#ddd',
  },
  buttonContainer: {
    marginBottom: 20,
  },
  button: {
    paddingVertical: 12,
    paddingHorizontal: 20,
    borderRadius: 8,
    marginBottom: 10,
    alignItems: 'center',
  },
  loginButton: {
    backgroundColor: '#007AFF',
  },
  testButton: {
    backgroundColor: '#34C759',
  },
  networkButton: {
    backgroundColor: '#FF9500',
  },
  clearButton: {
    backgroundColor: '#FF3B30',
  },
  directApiButton: {
    backgroundColor: '#8E44AD',
  },
  axiosButton: {
    backgroundColor: '#E67E22',
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  logContainer: {
    backgroundColor: '#f8f9fa',
    padding: 10,
    borderRadius: 8,
    marginBottom: 20,
    maxHeight: 200,
  },
  logTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    marginBottom: 5,
    color: '#333',
  },
  logText: {
    fontSize: 11,
    color: '#666',
    marginBottom: 2,
    fontFamily: 'monospace',
  },
  signupLink: {
    alignItems: 'center',
  },
  signupText: {
    color: '#007AFF',
    fontSize: 16,
  },
});

export default LoginScreen; 