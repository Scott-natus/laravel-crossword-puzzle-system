import { CrosswordClue } from '../types';
import WebAsyncStorage from './WebAsyncStorage';

const API_BASE_URL = 'http://222.100.103.227/api';

interface Puzzle {
  id: number;
  grid: string[][];
  clues: CrosswordClue[];
}

interface AnswerResult {
  correct: boolean;
  message?: string;
}

class ApiService {
  private async request<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
    const url = `${API_BASE_URL}${endpoint}`;
    
    const defaultOptions: RequestInit = {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      ...options,
    };

    try {
      const response = await fetch(url, defaultOptions);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      return await response.json();
    } catch (error) {
      console.error('API request failed:', error);
      throw error;
    }
  }

  async getRandomPuzzle(): Promise<Puzzle | null> {
    try {
      return await this.request<Puzzle>('/puzzles/random');
    } catch (error) {
      console.error('Failed to get random puzzle:', error);
      return null;
    }
  }

  async getHint(puzzleId: number, row: number, col: number): Promise<string | null> {
    try {
      const response = await this.request<{ hint: string }>(`/puzzles/${puzzleId}/hint`, {
        method: 'POST',
        body: JSON.stringify({ row, col }),
      });
      return response.hint;
    } catch (error) {
      console.error('Failed to get hint:', error);
      return null;
    }
  }

  async submitAnswer(puzzleId: number, row: number, col: number, answer: string): Promise<AnswerResult> {
    try {
      return await this.request<AnswerResult>(`/puzzles/${puzzleId}/submit`, {
        method: 'POST',
        body: JSON.stringify({ row, col, answer }),
      });
    } catch (error) {
      console.error('Failed to submit answer:', error);
      return { correct: false, message: '제출에 실패했습니다.' };
    }
  }

  async checkAnswer(puzzleId: number, row: number, col: number, answer: string): Promise<AnswerResult> {
    try {
      return await this.request<AnswerResult>(`/puzzles/${puzzleId}/check`, {
        method: 'POST',
        body: JSON.stringify({ row, col, answer }),
      });
    } catch (error) {
      console.error('Failed to check answer:', error);
      return { correct: false, message: '확인에 실패했습니다.' };
    }
  }

  async login(email: string, password: string): Promise<{ token: string; user: any } | null> {
    try {
      return await this.request<{ token: string; user: any }>('/auth/login', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
      });
    } catch (error) {
      console.error('Login failed:', error);
      return null;
    }
  }

  async register(email: string, password: string, name: string): Promise<{ token: string; user: any } | null> {
    try {
      return await this.request<{ token: string; user: any }>('/auth/register', {
        method: 'POST',
        body: JSON.stringify({ email, password, name }),
      });
    } catch (error) {
      console.error('Registration failed:', error);
      return null;
    }
  }

  async registerPushToken(token: string): Promise<boolean> {
    try {
      await this.request('/push/register', {
        method: 'POST',
        body: JSON.stringify({ token }),
      });
      return true;
    } catch (error) {
      console.error('Failed to register push token:', error);
      return false;
    }
  }

  async getPuzzleByLevel(levelId: number): Promise<Puzzle | null> {
    try {
      const token = await WebAsyncStorage.getItem('auth_token');
      return await this.request<Puzzle>(`/crossword/puzzle/${levelId}`, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
        },
      });
    } catch (error) {
      console.error('Failed to get puzzle by level:', error);
      return null;
    }
  }
}

export const apiService = new ApiService(); 