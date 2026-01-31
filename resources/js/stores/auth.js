import { defineStore } from 'pinia';
import axios from '../utils/axios';

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
        const token = localStorage.getItem('token');
        if (!token) {
          return;
        }

        this.loading = true;
        this.error = null;
        const response = await axios.get('/api/auth/me');
        if (response.data.success) {
          this.user = response.data.data;
        }
      } catch (error) {
        this.user = null;
        // If 401, clear token as well
        if (error.response?.status === 401) {
          localStorage.removeItem('token');
        }
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

        // Perform login
        const response = await axios.post('/api/auth/login', { email, password });

        if (response.data.success) {
          const { access_token, user } = response.data.data;

          // Store token
          localStorage.setItem('token', access_token);

          // Store user
          this.user = user;
          return true;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Login failed';
        this.user = null;
        localStorage.removeItem('token');
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
        localStorage.removeItem('token');
        this.loading = false;
      }
    },
  },
});
