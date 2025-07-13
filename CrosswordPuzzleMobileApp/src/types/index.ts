// 사용자 관련 타입
export interface User {
  id: number;
  name: string;
  email: string;
  created_at: string;
  updated_at: string;
}

// 인증 관련 타입
export interface AuthState {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  error: string | null;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface RegisterRequest {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

// 퍼즐 관련 타입
export interface PuzzleLevel {
  id: number;
  level: number;
  level_name: string;
  word_count: number;
  word_difficulty: number;
  hint_difficulty: string;
  intersection_count: number;
  time_limit: number;
}

export interface PuzzleWord {
  id: number;
  word: string;
  length: number;
  category: string;
  difficulty: number;
  is_active: boolean;
  hints: PuzzleHint[];
}

export interface PuzzleHint {
  id: number;
  word_id: number;
  hint_text: string;
  hint_type: string;
  is_primary: boolean;
  difficulty: string;
}

export interface CrosswordCell {
  row: number;
  col: number;
  value: string;
  isBlack: boolean;
  number?: number;
}

export interface CrosswordClue {
  id: number;
  number: number;
  direction: 'across' | 'down';
  clue: string;
  answer: string;
  word: string;
  row: number;
  col: number;
  length: number;
  positions?: Array<{
    x: number;
    y: number;
  }>;
}

export interface CrosswordPuzzle {
  id: number;
  level_id: number;
  grid: string[][];
  clues: CrosswordClue[];
  words: PuzzleWord[];
  hints: PuzzleHint[];
  stats: {
    word_count: number;
    intersection_count: number;
    grid_size: number;
  };
}

// 게임 진행 관련 타입
export interface GameState {
  currentLevel: number;
  score: number;
  timeRemaining: number;
  isGameActive: boolean;
  puzzle: CrosswordPuzzle | null;
  userProgress: UserProgress;
  selectedCell: { row: number; col: number } | null;
  currentInput: string;
}

export interface UserProgress {
  levelId: number;
  completedWords: string[];
  usedHints: number;
  startTime: number;
  endTime?: number;
}

// 푸시 알림 관련 타입
export interface PushSettings {
  daily_reminder: boolean;
  level_complete: boolean;
  achievement: boolean;
  streak_reminder: boolean;
}

export interface PushToken {
  id: number;
  user_id: number;
  token: string;
  platform: 'ios' | 'android';
  created_at: string;
  updated_at: string;
}

export interface PushNotification {
  id: number;
  user_id: number;
  title: string;
  body: string;
  data?: any;
  sent_at?: string;
  created_at: string;
}

// API 응답 타입
export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  message?: string;
  errors?: any;
}

export interface LoginResponse {
  user: User;
  welcome_message?: string;
  redirect_url?: string;
  remember_email?: string;
  authorization: {
    token: string;
    type: string;
  };
}

export interface RegisterResponse {
  token: string;
  user: User;
}

// 네비게이션 타입
export type RootStackParamList = {
  Login: undefined;
  Register: undefined;
  Main: undefined;
  Game: { levelId: number };
  LevelSelect: undefined;
  Settings: undefined;
  PushSettings: undefined;
  Profile: undefined;
};

export type MainTabParamList = {
  Home: undefined;
  Game: undefined;
  Settings: undefined;
  Profile: undefined;
}; 