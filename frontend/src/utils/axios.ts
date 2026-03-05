import axios from 'axios';
import type { InternalAxiosRequestConfig } from 'axios';

const instance = axios.create({
  baseURL: 'http://127.0.0.1:8000/api', // URL dari php artisan serve tadi
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: false,
});

// Interceptor untuk masukin token otomatis
instance.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default instance;