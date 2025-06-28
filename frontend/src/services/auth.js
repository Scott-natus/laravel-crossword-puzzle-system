import axios from 'axios';

const API_URL = 'http://222.100.103.227/api';

// axios 인스턴스 생성
const api = axios.create({
  baseURL: API_URL,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
});

// 요청 인터셉터 추가
api.interceptors.request.use(
  (config) => {
    const user = JSON.parse(localStorage.getItem('user'));
    if (user?.authorization?.token) {
      config.headers.Authorization = `Bearer ${user.authorization.token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// 응답 인터셉터 추가
api.interceptors.response.use(
  (response) => response,
  (error) => {
    console.error('API Error:', error);
    return Promise.reject(error);
  }
);

const register = async (name, email, password) => {
  try {
    // CSRF 토큰 가져오기
    await api.get('/sanctum/csrf-cookie');
    
    const response = await api.post('/register', {
      name,
      email,
      password,
      password_confirmation: password
    });
    if (response.data.authorization?.token) {
      localStorage.setItem('user', JSON.stringify(response.data));
    }
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: '회원가입 중 오류가 발생했습니다.' };
  }
};

const login = async (email, password) => {
  try {
    // CSRF 토큰 가져오기
    await api.get('/sanctum/csrf-cookie');
    
    const response = await api.post('/login', {
      email,
      password,
    });
    if (response.data.authorization?.token) {
      localStorage.setItem('user', JSON.stringify(response.data));
    }
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: '로그인 중 오류가 발생했습니다.' };
  }
};

const logout = async () => {
  try {
    await api.post('/logout');
    localStorage.removeItem('user');
  } catch (error) {
    console.error('Logout error:', error);
  }
};

const getCurrentUser = () => {
  const user = localStorage.getItem('user');
  return user ? JSON.parse(user) : null;
};

const authService = {
  register,
  login,
  logout,
  getCurrentUser,
  api,
};

export default authService; 