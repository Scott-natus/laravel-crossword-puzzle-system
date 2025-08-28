import axios, { AxiosInstance, AxiosResponse } from 'axios';

// React Native 환경에 맞는 스토리지 설정
const getStorage = () => {
  // React Native 환경에서는 AsyncStorage 사용
  try {
    const AsyncStorage = require('@react-native-async-storage/async-storage').default;
    console.log('✅ CrosswordPuzzleMobileApp AsyncStorage 로드 성공');
    return AsyncStorage;
  } catch (error) {
    console.log('❌ CrosswordPuzzleMobileApp AsyncStorage 로드 실패, 메모리 스토리지 사용');
    // AsyncStorage 로드 실패 시 메모리 스토리지 사용
    const memoryStorage: any = {};
    return {
      getItem: (key: string) => Promise.resolve(memoryStorage[key] || null),
      setItem: (key: string, value: string) => Promise.resolve(memoryStorage[key] = value),
      removeItem: (key: string) => Promise.resolve(delete memoryStorage[key]),
    };
  }
};

const storage = getStorage();
import { 
  LoginRequest, 
  RegisterRequest, 
  User, 
  CrosswordPuzzle, 
  PuzzleLevel,
  ApiResponse,
  PushSettings,
  PushToken,
  LoginResponse
} from '../types';

// API URL 설정 - 모바일에서 접근 가능한 URL로 변경
const API_BASE_URL = 'http://222.100.103.227:8080/api';

class ApiService {
  private api: AxiosInstance;

  constructor() {
    this.api = axios.create({
      baseURL: API_BASE_URL,
      timeout: 30000, // 타임아웃 증가
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });

    // 요청 인터셉터 - 토큰 추가 및 디버깅
    this.api.interceptors.request.use(
      async (config) => {
        console.log('🌐 CrosswordPuzzleMobileApp API 요청:', config.method?.toUpperCase(), config.url);
        console.log('📡 CrosswordPuzzleMobileApp 요청 데이터:', config.data);
        console.log('🔗 CrosswordPuzzleMobileApp 요청 헤더:', config.headers);
        
        const token = await storage.getItem('auth_token');
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
          console.log('🔑 CrosswordPuzzleMobileApp 토큰 추가됨');
        }
        return config;
      },
      (error) => {
        console.error('❌ CrosswordPuzzleMobileApp 요청 인터셉터 오류:', error);
        return Promise.reject(error);
      }
    );

    // 응답 인터셉터 - 에러 처리 및 디버깅
    this.api.interceptors.response.use(
      (response) => {
        console.log('✅ CrosswordPuzzleMobileApp API 응답 성공:', response.status, response.config.url);
        return response;
      },
      async (error) => {
        console.error('❌ CrosswordPuzzleMobileApp API 응답 오류:', error.response?.status, error.response?.data);
        console.error('📡 CrosswordPuzzleMobileApp 오류 URL:', error.config?.url);
        console.error('🔍 CrosswordPuzzleMobileApp 오류 상세:', error.message);
        console.error('🔍 CrosswordPuzzleMobileApp 오류 코드:', error.code);
        
        if (error.response?.status === 401) {
          // 토큰이 만료된 경우 로그아웃
          await storage.removeItem('auth_token');
          await storage.removeItem('user');
          console.log('🔑 CrosswordPuzzleMobileApp 토큰 만료로 로그아웃 처리');
        }
        return Promise.reject(error);
      }
    );
  }

  // 인증 관련 API
  async login(credentials: LoginRequest): Promise<ApiResponse<LoginResponse>> {
    try {
      console.log('🔐 CrosswordPuzzleMobileApp 로그인 시도:', credentials.email);
      console.log('📡 CrosswordPuzzleMobileApp 요청 URL:', this.api.defaults.baseURL + '/login');
      console.log('📦 CrosswordPuzzleMobileApp 요청 데이터:', JSON.stringify(credentials));
      console.log('🌐 CrosswordPuzzleMobileApp API 인스턴스:', this.api.defaults.baseURL);
      
      console.log('🚀 CrosswordPuzzleMobileApp API 호출 시작...');
      console.log('🌐 CrosswordPuzzleMobileApp 요청 URL:', this.api.defaults.baseURL + '/login');
      console.log('⏱️ CrosswordPuzzleMobileApp 타임아웃 설정:', this.api.defaults.timeout);
      
      const response: AxiosResponse = await this.api.post('/login', credentials);
      
      console.log('✅ CrosswordPuzzleMobileApp 서버 응답 받음');
      console.log('📊 CrosswordPuzzleMobileApp 응답 상태:', response.status);
      console.log('📋 CrosswordPuzzleMobileApp 응답 헤더:', response.headers);
      console.log('📄 CrosswordPuzzleMobileApp 응답 데이터:', JSON.stringify(response.data));
      
      // 응답 데이터 구조 확인
      console.log('🔍 CrosswordPuzzleMobileApp 응답 데이터 구조 분석 시작');
      console.log('🔍 CrosswordPuzzleMobileApp response.data 존재 여부:', !!response.data);
      console.log('🔍 CrosswordPuzzleMobileApp response.data.status:', response.data?.status);
      
      if (response.data && response.data.status === 'success') {
        console.log('✅ CrosswordPuzzleMobileApp 로그인 성공 - 응답 파싱 완료');
        console.log('✅ CrosswordPuzzleMobileApp 반환할 데이터:', JSON.stringify(response.data));
        return response.data;
      } else {
        console.error('❌ CrosswordPuzzleMobileApp 응답 데이터 구조 문제:', response.data);
        console.error('❌ CrosswordPuzzleMobileApp response.data 타입:', typeof response.data);
        console.error('❌ CrosswordPuzzleMobileApp response.data 키들:', Object.keys(response.data || {}));
        throw new Error('🚨 2025-07-31 수정된 코드가 적용됨! 서버 응답 형식이 올바르지 않습니다.');
      }
    } catch (error: any) {
      console.error('❌ CrosswordPuzzleMobileApp 로그인 실패:', error.message);
      console.error('🔍 CrosswordPuzzleMobileApp 에러 타입:', typeof error);
      console.error('📊 CrosswordPuzzleMobileApp 에러 객체:', error);
      console.error('🔍 CrosswordPuzzleMobileApp 에러 스택:', error.stack);
      console.error('🔍 CrosswordPuzzleMobileApp 에러 코드:', error.code);
      console.error('🔍 CrosswordPuzzleMobileApp 에러 이름:', error.name);
      throw this.handleError(error);
    }
  }

  async register(userData: RegisterRequest): Promise<ApiResponse<{ user: User; authorization: { token: string; type: string } }>> {
    try {
      const response: AxiosResponse = await this.api.post('/register', userData);
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async logout(): Promise<ApiResponse> {
    try {
      const response: AxiosResponse = await this.api.post('/logout');
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async me(): Promise<ApiResponse<User>> {
    try {
      const response: AxiosResponse = await this.api.get('/me');
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  // React 앱용 퍼즐게임 API (새로운 API)
  async getPuzzleTemplate(): Promise<ApiResponse<any>> {
    try {
      const response: AxiosResponse = await this.api.get('/puzzle/template');
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async checkPuzzleAnswer(data: { word_id: number; answer: string }): Promise<ApiResponse<any>> {
    try {
      const response: AxiosResponse = await this.api.post('/puzzle/submit-answer', data);
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async getPuzzleHints(data: { word_id: number; current_hint_id?: number | null; base_hint_id?: number | null }): Promise<ApiResponse<any>> {
    try {
      const params = new URLSearchParams();
      params.append('word_id', data.word_id.toString());
      if (data.current_hint_id) {
        params.append('current_hint_id', data.current_hint_id.toString());
      }
      if (data.base_hint_id) {
        params.append('base_hint_id', data.base_hint_id.toString());
      }
      
      const response: AxiosResponse = await this.api.get(`/puzzle/hints?${params.toString()}`);
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async generatePuzzle(levelId: number): Promise<ApiResponse<any>> {
    try {
      const response: AxiosResponse = await this.api.post('/puzzle/generate', { level_id: levelId });
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async checkCompletion(sessionToken: string): Promise<ApiResponse<any>> {
    try {
      const response: AxiosResponse = await this.api.post('/puzzle/check-completion', { session_token: sessionToken });
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async saveGameState(sessionToken: string, gameState: any): Promise<ApiResponse> {
    try {
      const response: AxiosResponse = await this.api.post('/puzzle/save-game-state', { 
        session_token: sessionToken, 
        game_state: gameState 
      });
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async submitResult(data: {
    session_token: string;
    score: number;
    accuracy: number;
    hints_used: number;
    time_used: number;
  }): Promise<ApiResponse> {
    try {
      const response: AxiosResponse = await this.api.post('/puzzle/submit-result', data);
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async completeLevel(): Promise<ApiResponse> {
    try {
      const response: AxiosResponse = await this.api.post('/puzzle/complete-level');
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  // 기존 퍼즐 게임 관련 API (호환성 유지)
  async getPuzzle(levelId: number): Promise<ApiResponse<CrosswordPuzzle>> {
    try {
      const response: AxiosResponse = await this.api.get(`/crossword/puzzle/${levelId}`);
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  // PC 웹과 동일한 퍼즐게임 API (기존)
  async getPuzzleTemplateOld(): Promise<ApiResponse<any>> {
    try {
      const response: AxiosResponse = await this.api.get('/puzzle-game/template');
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async checkPuzzleAnswerOld(data: { word_id: number; answer: string }): Promise<ApiResponse<any>> {
    try {
      const response: AxiosResponse = await this.api.post('/puzzle-game/check-answer', data);
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async getPuzzleHintsOld(wordId: number): Promise<ApiResponse<any>> {
    try {
      const response: AxiosResponse = await this.api.get(`/puzzle-game/hints?word_id=${wordId}`);
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async completePuzzleLevel(levelId: number): Promise<ApiResponse<any>> {
    try {
      const response: AxiosResponse = await this.api.post('/puzzle-game/complete-level', { level_id: levelId });
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async submitAnswer(data: { levelId: number; word: string; answer: string }): Promise<ApiResponse> {
    try {
      const response: AxiosResponse = await this.api.post('/crossword/submit-answer', data);
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async getTemplate(): Promise<ApiResponse<any>> {
    try {
      const response: AxiosResponse = await this.api.get('/puzzle-game/template');
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async getHints(wordId: number): Promise<ApiResponse<any[]>> {
    try {
      const response: AxiosResponse = await this.api.get(`/puzzle-game/hints?word_id=${wordId}`);
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async completeLevelOld(levelId: number): Promise<ApiResponse> {
    try {
      const response: AxiosResponse = await this.api.post('/puzzle-game/complete-level', { level_id: levelId });
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async getLevels(): Promise<ApiResponse<PuzzleLevel[]>> {
    try {
      const response: AxiosResponse = await this.api.get('/puzzle-game/levels');
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async generateHints(words: string[]): Promise<ApiResponse<Record<string, string[]>>> {
    try {
      const response: AxiosResponse = await this.api.post('/puzzle-game/generate-hints', { words });
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  // 푸시 알림 관련 API
  async registerPushToken(token: string, platform: 'ios' | 'android'): Promise<ApiResponse> {
    try {
      const response: AxiosResponse = await this.api.post('/push/register', { 
        token, 
        platform 
      });
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async unregisterPushToken(token: string): Promise<ApiResponse> {
    try {
      const response: AxiosResponse = await this.api.post('/push/unregister', { token });
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async getPushSettings(): Promise<ApiResponse<PushSettings>> {
    try {
      const response: AxiosResponse = await this.api.get('/push/settings');
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async updatePushSettings(settings: PushSettings): Promise<ApiResponse> {
    try {
      const response: AxiosResponse = await this.api.put('/push/settings', settings);
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  // 사용자 통계 API
  async getUserStats(): Promise<ApiResponse<{
    current_level: number;
    total_games: number;
    total_score: number;
    last_played: string | null;
    total_play_time: number;
    average_accuracy: number;
    total_hints_used: number;
  }>> {
    try {
      const response: AxiosResponse = await this.api.get('/user/stats');
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async getRecentGames(): Promise<ApiResponse<{
    id: number;
    level: number;
    score: number;
    completed_at: string;
    created_at: string;
  }[]>> {
    try {
      const response: AxiosResponse = await this.api.get('/user/recent-games');
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  async getRecentGameSessions(): Promise<ApiResponse<{
    id: number;
    word_id: number;
    word: string;
    session_started_at: string;
    session_ended_at: string | null;
    total_play_time: number;
    accuracy_rate: number;
    total_correct_answers: number;
    total_wrong_answers: number;
    hints_used_count: number;
    is_completed: boolean;
    created_at: string;
  }[]>> {
    try {
      const response: AxiosResponse = await this.api.get('/user/recent-sessions');
      return response.data;
    } catch (error: any) {
      throw this.handleError(error);
    }
  }

  private handleError(error: any): Error {
    console.error('🔍 CrosswordPuzzleMobileApp 에러 상세 분석:');
    console.error('  - CrosswordPuzzleMobileApp error.response:', error.response);
    console.error('  - CrosswordPuzzleMobileApp error.request:', error.request);
    console.error('  - CrosswordPuzzleMobileApp error.message:', error.message);
    console.error('  - CrosswordPuzzleMobileApp error.code:', error.code);
    console.error('  - CrosswordPuzzleMobileApp error.config:', error.config);
    console.error('  - CrosswordPuzzleMobileApp error.name:', error.name);
    console.error('  - CrosswordPuzzleMobileApp error.stack:', error.stack);
    
    if (error.response) {
      // 서버 응답이 있는 경우
      console.log('✅ CrosswordPuzzleMobileApp 서버 응답 받음:', error.response.status, error.response.data);
      const message = error.response.data?.message || error.response.data?.error || '서버 오류가 발생했습니다.';
      return new Error(message);
    } else if (error.request) {
      // 요청은 보냈지만 응답을 받지 못한 경우
      console.log('❌ CrosswordPuzzleMobileApp 요청은 보냈지만 응답 없음');
      console.log('❌ CrosswordPuzzleMobileApp error.request:', error.request);
      return new Error('🚨 2025-07-31 수정된 코드! 네트워크 연결을 확인해주세요. (요청은 보냈지만 응답 없음)');
    } else {
      // 요청 자체에 문제가 있는 경우
      console.log('❌ CrosswordPuzzleMobileApp 요청 자체에 문제 있음');
      console.log('❌ CrosswordPuzzleMobileApp error.message:', error.message);
      console.log('❌ CrosswordPuzzleMobileApp error.code:', error.code);
      return new Error(`🚨 2025-07-31 수정된 코드! 요청을 처리할 수 없습니다. (${error.message || error.code || '알 수 없는 오류'})`);
    }
  }
}

export const apiService = new ApiService(); 