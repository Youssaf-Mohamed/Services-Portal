import axios from 'axios';

const axiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

/**
 * Request interceptor to attach XSRF-TOKEN from cookie.
 * Laravel sets the XSRF-TOKEN as a URL-encoded cookie.
 * We need to decode it and attach as X-XSRF-TOKEN header.
 */
axiosInstance.interceptors.request.use((config) => {
  // Get all cookies
  const cookies = document.cookie.split(';');
  
  // Find XSRF-TOKEN cookie
  for (const cookie of cookies) {
    const [name, value] = cookie.trim().split('=');
    if (name === 'XSRF-TOKEN' && value) {
      // Decode the URI-encoded token and set header
      config.headers['X-XSRF-TOKEN'] = decodeURIComponent(value);
      break;
    }
  }
  
  return config;
});

/**
 * Get CSRF cookie from Laravel Sanctum before making authenticated requests.
 */
export const getCsrfCookie = async () => {
  await axiosInstance.get('/sanctum/csrf-cookie');
};

export default axiosInstance;
