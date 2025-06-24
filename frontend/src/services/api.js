import axios from 'axios';

export const api = axios.create({
  baseURL: 'http://222.100.103.227/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// 요청 인터셉터
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

// 응답 인터셉터
api.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
); 