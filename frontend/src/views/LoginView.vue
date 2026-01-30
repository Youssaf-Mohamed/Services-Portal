<template>
  <div class="login-container">
    <div class="login-card">
      <h1>Services Portal Login</h1>
      
      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label for="email">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            placeholder="Enter your email"
            :disabled="loading"
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            placeholder="Enter your password"
            :disabled="loading"
          />
        </div>

        <div v-if="error" class="error-message">
          {{ error }}
        </div>

        <button type="submit" :disabled="loading">
          {{ loading ? 'Logging in...' : 'Login' }}
        </button>
      </form>

      <div class="test-credentials">
        <p><strong>Test Credentials:</strong></p>
        <p>Admin: admin@test.com / Password123!</p>
        <p>Student: student@test.com / Password123!</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

const handleLogin = async () => {
  try {
    loading.value = true;
    error.value = '';
    
    await authStore.login(email.value, password.value);
    
    // Redirect based on role
    const role = authStore.userRole;
    if (role === 'admin') {
      router.push('/admin');
    } else if (role === 'student') {
      router.push('/student/transport');
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Login failed. Please try again.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: var(--color-background);
  padding: 1rem;
}

.login-card {
  background: white;
  padding: 2.5rem;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  width: 100%;
  max-width: 420px;
}

h1 {
  margin-bottom: 2rem;
  color: #2d3748;
  text-align: center;
  font-size: 1.75rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  color: #4a5568;
  font-weight: 600;
  font-size: 0.95rem;
}

input {
  width: 100%;
  padding: 0.875rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  box-sizing: border-box;
  transition: border-color 0.2s, box-shadow 0.2s;
}

input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

input:disabled {
  background: #f7fafc;
  cursor: not-allowed;
}

button {
  width: 100%;
  padding: 0.875rem;
  background: var(--color-secondary);
  color: white;
  border: none;
  border-radius: var(--radius-md);
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  margin-top: 1.5rem;
  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.error-message {
  padding: 0.875rem;
  background: #fff5f5;
  color: #c53030;
  border-radius: 8px;
  margin-top: 1rem;
  border-left: 4px solid #fc8181;
  font-size: 0.9rem;
}

.test-credentials {
  margin-top: 2rem;
  padding: 1.25rem;
  background: #f7fafc;
  border-radius: 8px;
  font-size: 0.875rem;
  border: 1px solid #e2e8f0;
}

.test-credentials p {
  margin: 0.4rem 0;
  color: #4a5568;
}

.test-credentials p:first-child {
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 0.75rem;
}
</style>
