import { defineStore } from 'pinia';
import axios, { getCsrfCookie } from '../utils/axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.user,
    userRole: (state) => state.user?.role || null,
    // Returns a function that checks if user has a specific role
    hasRole: (state) => (role) => state.user?.role === role,
  },

  actions: {
    async fetchMe() {
      try {
        this.loading = true;
        this.error = null;
        const response = await axios.get('/api/auth/me');
        if (response.data.success) {
          this.user = response.data.data;
        }
      } catch (error) {
        this.user = null;
        // Don't set error for 401, just clear user
        if (error.response?.status !== 401) {
          this.error = error.response?.data?.message || 'Failed to fetch user';
        }
      } finally {
        this.loading = false;
      }
    },

    async login(email, password) {
      try {
        this.loading = true;
        this.error = null;
        
        // Get CSRF cookie first
        await getCsrfCookie();
        
        // Perform login
        const response = await axios.post('/api/auth/login', { email, password });
        
        if (response.data.success) {
          this.user = response.data.data;
          return true;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Login failed';
        this.user = null;
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async logout() {
      try {
        this.loading = true;
        this.error = null;
        await axios.post('/api/auth/logout');
      } catch (error) {
        this.error = error.response?.data?.message || 'Logout failed';
      } finally {
        this.user = null;
        this.loading = false;
      }
    },
  },
});
