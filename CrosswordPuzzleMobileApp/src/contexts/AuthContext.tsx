import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { apiService } from '../services/api';

// React Native 환경에 맞는 스토리지 설정
const getStorage = () => {
  // React Native 환경에서는 AsyncStorage 사용
  try {
    const AsyncStorage = require('@react-native-async-storage/async-storage').default;
    console.log('✅ CrosswordPuzzleMobileApp AuthContext AsyncStorage 로드 성공');
    return AsyncStorage;
  } catch (error) {
    console.log('❌ CrosswordPuzzleMobileApp AuthContext AsyncStorage 로드 실패, 메모리 스토리지 사용');
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
  
  console.log('🔍 CrosswordPuzzleMobileApp AuthContext: user =', user);
  console.log('🔍 CrosswordPuzzleMobileApp AuthContext: isAuthenticated =', isAuthenticated);
  console.log('🚨 CrosswordPuzzleMobileApp AuthProvider 컴포넌트가 렌더링됨!');

  const checkAuthStatus = async () => {
    try {
      console.log('🔍 AuthContext: 인증 상태 확인 시작');
      const token = await storage.getItem('auth_token');
      console.log('🔑 AuthContext: 저장된 토큰 확인:', token ? '있음' : '없음');
      if (token) {
        console.log('✅ AuthContext: 토큰 발견, 사용자 정보 확인 중...');
        const response = await apiService.me();
        console.log('📄 AuthContext: me() 응답:', response);
        if (response.success && response.data) {
          console.log('✅ AuthContext: 사용자 인증 성공, 상태 업데이트');
          setUser(response.data);
          console.log('👤 AuthContext: 사용자 상태 설정 완료:', response.data);
        } else {
          console.log('❌ AuthContext: 토큰이 유효하지 않음, 스토리지 정리');
          // 토큰이 유효하지 않으면 삭제
          await storage.removeItem('auth_token');
          await storage.removeItem('user');
          setUser(null);
          console.log('🧹 AuthContext: 스토리지 정리 완료');
        }
      } else {
        console.log('❌ AuthContext: 토큰 없음, 사용자 상태 초기화');
        setUser(null);
      }
    } catch (error) {
      console.error('❌ AuthContext: 인증 상태 확인 에러:', error);
      // 에러 발생 시 토큰 삭제
      await storage.removeItem('auth_token');
      await storage.removeItem('user');
      setUser(null);
      console.log('🧹 AuthContext: 에러로 인한 스토리지 정리 완료');
    } finally {
      console.log('🏁 AuthContext: 인증 상태 확인 완료, 로딩 상태 해제');
      setIsLoading(false);
    }
  };

  const login = async (email: string, password: string): Promise<boolean> => {
    console.log('🚨 CrosswordPuzzleMobileApp AuthContext login 함수 호출됨!');
    console.log('📧 CrosswordPuzzleMobileApp 이메일:', email);
    console.log('🔑 CrosswordPuzzleMobileApp 비밀번호:', password);
    
    try {
      console.log('🔐 CrosswordPuzzleMobileApp AuthContext: 로그인 시도 시작');
      const response = await apiService.login({ email, password });
      console.log('📄 CrosswordPuzzleMobileApp AuthContext: 로그인 응답 받음:', response);
      console.log('🔍 CrosswordPuzzleMobileApp AuthContext: 응답 타입:', typeof response);
      console.log('🔍 CrosswordPuzzleMobileApp AuthContext: 응답 키들:', Object.keys(response || {}));
      
      // API 응답 구조에 맞게 수정
      let responseData: any;
      console.log('🔍 CrosswordPuzzleMobileApp AuthContext: 응답 구조 분석 시작');
      
      if (response && typeof response === 'object') {
        console.log('🔍 CrosswordPuzzleMobileApp AuthContext: response는 객체입니다');
        
        // response 자체가 데이터인 경우 (API 응답 구조)
        if ((response as any).status === 'success' && (response as any).authorization && (response as any).user) {
          responseData = response;
          console.log('✅ CrosswordPuzzleMobileApp AuthContext: response 자체가 데이터인 경우');
        }
        // response.data에 데이터가 있는 경우
        else if ((response as any).data && (response as any).data.status === 'success' && (response as any).data.authorization && (response as any).data.user) {
          responseData = (response as any).data;
          console.log('✅ CrosswordPuzzleMobileApp AuthContext: response.data에 데이터가 있는 경우');
        }
        // 기타 경우들
        else if ((response as any).authorization && (response as any).user) {
          responseData = response;
          console.log('✅ CrosswordPuzzleMobileApp AuthContext: authorization과 user가 있는 경우');
        }
        else if ((response as any).data && (response as any).data.authorization && (response as any).data.user) {
          responseData = (response as any).data;
          console.log('✅ CrosswordPuzzleMobileApp AuthContext: data.authorization과 data.user가 있는 경우');
        }
        else {
          console.log('❌ CrosswordPuzzleMobileApp AuthContext: 어떤 조건도 만족하지 않음');
          console.log('❌ CrosswordPuzzleMobileApp AuthContext: response.status:', (response as any).status);
          console.log('❌ CrosswordPuzzleMobileApp AuthContext: response.authorization:', (response as any).authorization);
          console.log('❌ CrosswordPuzzleMobileApp AuthContext: response.user:', (response as any).user);
        }
      } else {
        console.log('❌ CrosswordPuzzleMobileApp AuthContext: response가 객체가 아님');
      }
      
      if (responseData && responseData.authorization && responseData.user) {
        console.log('✅ CrosswordPuzzleMobileApp AuthContext: 응답 구조 확인 성공');
        console.log('💾 CrosswordPuzzleMobileApp AuthContext: 토큰 저장 시작:', responseData.authorization.token);
        console.log('👤 CrosswordPuzzleMobileApp AuthContext: 사용자 정보 저장 시작:', responseData.user);
        
        try {
          await storage.setItem('auth_token', responseData.authorization.token);
          console.log('✅ AuthContext: 토큰 저장 성공');
        } catch (error) {
          console.error('❌ AuthContext: 토큰 저장 실패:', error);
        }
        
        try {
          await storage.setItem('user', JSON.stringify(responseData.user));
          console.log('✅ AuthContext: 사용자 정보 저장 성공');
        } catch (error) {
          console.error('❌ AuthContext: 사용자 정보 저장 실패:', error);
        }
        
        // 환영 메시지 저장
        if (responseData.welcome_message) {
          await storage.setItem('welcome_message', responseData.welcome_message);
          console.log('✅ AuthContext: 환영 메시지 저장 성공');
        }
        
        // 리다이렉션 URL 저장
        if (responseData.redirect_url) {
          await storage.setItem('redirect_url', responseData.redirect_url);
          console.log('✅ AuthContext: 리다이렉션 URL 저장 성공');
        }
        
        // 로그인 정보 기억하기 처리
        if (responseData.remember_email) {
          await storage.setItem('remember_email', responseData.remember_email);
          console.log('✅ AuthContext: 이메일 기억하기 저장 성공');
        }
        
        // 사용자 상태 업데이트
        console.log('🔄 AuthContext: 사용자 상태 업데이트 시작');
        setUser(responseData.user);
        console.log('✅ AuthContext: 사용자 상태 업데이트 완료');
        console.log('👋 AuthContext: 환영 메시지:', responseData.welcome_message);
        console.log('🔗 AuthContext: 리다이렉션 URL:', responseData.redirect_url);
        console.log('✅ AuthContext: 로그인 완료 - true 반환');
        return true;
      } else {
        console.log('❌ CrosswordPuzzleMobileApp AuthContext: 응답 구조 확인 실패');
        console.log('❌ CrosswordPuzzleMobileApp AuthContext: response:', response);
        console.log('❌ CrosswordPuzzleMobileApp AuthContext: responseData:', responseData);
        return false;
      }
    } catch (error) {
      console.error('❌ CrosswordPuzzleMobileApp AuthContext: 로그인 에러:', error);
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