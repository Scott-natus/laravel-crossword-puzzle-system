import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { apiService } from '../services/api';

// 웹 환경에서 localStorage 사용, 모바일에서는 AsyncStorage 사용
const getStorage = () => {
  if (typeof (globalThis as any).window !== 'undefined') {
    return {
      getItem: (key: string) => Promise.resolve(localStorage.getItem(key)),
      setItem: (key: string, value: string) => Promise.resolve(localStorage.setItem(key, value)),
      removeItem: (key: string) => Promise.resolve(localStorage.removeItem(key)),
    };
  } else {
    // 웹 환경에서는 AsyncStorage를 직접 사용하지 않음
    return {
      getItem: (key: string) => Promise.resolve(localStorage.getItem(key)),
      setItem: (key: string, value: string) => Promise.resolve(localStorage.setItem(key, value)),
      removeItem: (key: string) => Promise.resolve(localStorage.removeItem(key)),
    };
  }
};

const storage = getStorage();

interface User {
  id: number;
  name: string;
  email: string;
  is_admin?: boolean;
}

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  isAdmin: boolean;
  login: (email: string, password: string) => Promise<boolean>;
  register: (name: string, email: string, password: string, password_confirmation: string) => Promise<boolean>;
  logout: () => Promise<void>;
  checkAuthStatus: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

interface AuthProviderProps {
  children: ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  const isAuthenticated = !!user;
  const isAdmin = user?.is_admin || false;

  const checkAuthStatus = async () => {
    try {
      const token = await storage.getItem('auth_token');
      console.log('Checking auth status, token:', token);
      if (token) {
        const response = await apiService.me();
        if (response.success && response.data) {
          setUser(response.data);
          console.log('User authenticated:', response.data);
        } else {
          // 토큰이 유효하지 않으면 삭제
          await storage.removeItem('auth_token');
          await storage.removeItem('user');
          setUser(null);
          console.log('Token invalid, cleared storage');
        }
      } else {
        setUser(null);
        console.log('No token found');
      }
    } catch (error) {
      console.error('Auth status check error:', error);
      // 에러 발생 시 토큰 삭제
      await storage.removeItem('auth_token');
      await storage.removeItem('user');
      setUser(null);
    } finally {
      setIsLoading(false);
    }
  };

  const login = async (email: string, password: string): Promise<boolean> => {
    try {
      console.log('Attempting login with:', email);
      const response = await apiService.login({ email, password });
      console.log('Login response:', response);
      
      if ((response.success || (response as any).status === 'success')) {
        // Laravel 기존 로그인과 동일한 비즈니스 로직 처리
        const responseData = response as any;
        console.log('Saving token to storage:', responseData.authorization.token);
        console.log('Saving user to storage:', responseData.user);
        
        try {
          await storage.setItem('auth_token', responseData.authorization.token);
          console.log('Token saved successfully');
        } catch (error) {
          console.error('Failed to save token:', error);
        }
        
        try {
          await storage.setItem('user', JSON.stringify(responseData.user));
          console.log('User saved successfully');
        } catch (error) {
          console.error('Failed to save user:', error);
        }
        
        // 환영 메시지 저장
        if (responseData.welcome_message) {
          await storage.setItem('welcome_message', responseData.welcome_message);
        }
        
        // 리다이렉션 URL 저장
        if (responseData.redirect_url) {
          await storage.setItem('redirect_url', responseData.redirect_url);
        }
        
        // 로그인 정보 기억하기 처리
        if (responseData.remember_email) {
          await storage.setItem('remember_email', responseData.remember_email);
        }
        
        // 사용자 상태 업데이트
        setUser(responseData.user);
        console.log('Login successful, user set:', responseData.user);
        console.log('Welcome message:', responseData.welcome_message);
        console.log('Redirect URL:', responseData.redirect_url);
        return true;
      } else {
        console.log('Login failed - response not successful:', response);
        return false;
      }
    } catch (error) {
      console.error('Login error:', error);
      return false;
    }
  };

  const register = async (name: string, email: string, password: string, password_confirmation: string): Promise<boolean> => {
    try {
      console.log('Attempting register with:', email);
      const response = await apiService.register({ name, email, password, password_confirmation });
      if (response.success && response.data) {
        await storage.setItem('auth_token', response.data.authorization.token);
        await storage.setItem('user', JSON.stringify(response.data.user));
        setUser(response.data.user);
        console.log('Register successful:', response.data.user);
        return true;
      }
      console.log('Register failed:', response);
      return false;
    } catch (error) {
      console.error('Register error:', error);
      return false;
    }
  };

  const logout = async () => {
    try {
      // 로컬 스토리지에서 토큰과 사용자 정보 삭제
      await storage.removeItem('auth_token');
      await storage.removeItem('user');
      setUser(null);
      console.log('Logout completed');
      
      // 서버 로그아웃 요청은 제거 (토큰이 이미 삭제되어 401 에러 발생)
      // 클라이언트 측에서만 토큰 삭제로 충분
    } catch (error) {
      console.error('Logout error:', error);
      // 에러가 발생해도 로컬 상태는 정리
      await storage.removeItem('auth_token');
      await storage.removeItem('user');
      setUser(null);
    }
  };

  useEffect(() => {
    checkAuthStatus();
  }, []);

  const value: AuthContextType = {
    user,
    isLoading,
    isAuthenticated,
    isAdmin,
    login,
    register,
    logout,
    checkAuthStatus,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}; 