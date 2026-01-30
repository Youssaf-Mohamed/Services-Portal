<template>
  <div class="admin-page">
    <div class="header">
      <h1>Admin Dashboard</h1>
      <button @click="handleLogout" class="logout-btn">Logout</button>
    </div>

    <div class="content">
      <div class="user-info">
        <h2>Welcome, {{ authStore.user?.name }}!</h2>
        <div class="info-row">
          <span class="label">Email:</span>
          <span class="value">{{ authStore.user?.email }}</span>
        </div>
        <div class="info-row">
          <span class="label">Role:</span>
          <span class="value role-badge">{{ authStore.user?.role }}</span>
        </div>
      </div>

      <!-- Transport Management Section -->
      <div class="transport-section">
        <h3>Transport Management</h3>
        <div class="transport-links">
          <router-link to="/admin/transport" class="transport-card">
            <span class="card-icon">📊</span>
            <span class="card-title">Dashboard</span>
            <span class="card-desc">View KPIs & overview</span>
          </router-link>
          <router-link to="/admin/transport/requests" class="transport-card">
            <span class="card-icon">📝</span>
            <span class="card-title">Requests</span>
            <span class="card-desc">Review & moderate</span>
          </router-link>
          <router-link to="/admin/transport/routes" class="transport-card">
            <span class="card-icon">🗺️</span>
            <span class="card-title">Routes</span>
            <span class="card-desc">Manage bus routes</span>
          </router-link>
          <router-link to="/admin/transport/slots" class="transport-card">
            <span class="card-icon">📅</span>
            <span class="card-title">Slots</span>
            <span class="card-desc">Schedule & capacity</span>
          </router-link>
          <router-link to="/admin/transport/manifest" class="transport-card">
            <span class="card-icon">👥</span>
            <span class="card-title">Manifest</span>
            <span class="card-desc">Passenger lists</span>
          </router-link>
        </div>
      </div>

      <div class="test-api">
        <h3>API Tests</h3>
        <div class="button-group">
          <button @click="testAdminPing" :disabled="testing">
            {{ testing ? 'Testing...' : 'Test Admin API' }}
          </button>
          <button @click="testStorageCheck" :disabled="testing">
            {{ testing ? 'Testing...' : 'Test Storage Check' }}
          </button>
        </div>
        <div v-if="apiResult" class="api-result">
          <h4>Response:</h4>
          <pre>{{ apiResult }}</pre>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import axios from '../../utils/axios';

const router = useRouter();
const authStore = useAuthStore();
const apiResult = ref('');
const testing = ref(false);

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};

const testAdminPing = async () => {
  testing.value = true;
  try {
    const response = await axios.get('/api/admin/ping');
    apiResult.value = JSON.stringify(response.data, null, 2);
  } catch (error) {
    apiResult.value = JSON.stringify(error.response?.data || { error: error.message }, null, 2);
  } finally {
    testing.value = false;
  }
};

const testStorageCheck = async () => {
  testing.value = true;
  try {
    const response = await axios.get('/api/admin/storage-check');
    apiResult.value = JSON.stringify(response.data, null, 2);
  } catch (error) {
    apiResult.value = JSON.stringify(error.response?.data || { error: error.message }, null, 2);
  } finally {
    testing.value = false;
  }
};
</script>

<style scoped>
.admin-page {
  min-height: 100vh;
  background: var(--color-background);
  padding: 2rem;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

h1 {
  color: white;
  margin: 0;
  font-size: 2rem;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.logout-btn {
  padding: 0.625rem 1.5rem;
  background: white;
  color: #f5576c;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.logout-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.content {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.user-info {
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 2px solid #e2e8f0;
}

.user-info h2 {
  color: #2d3748;
  margin-bottom: 1.5rem;
  font-size: 1.5rem;
}

.info-row {
  display: flex;
  margin: 0.75rem 0;
  align-items: center;
}

.label {
  font-weight: 600;
  color: #4a5568;
  min-width: 80px;
}

.value {
  color: #2d3748;
}

.role-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: var(--color-danger);
  color: white;
  border-radius: var(--radius-xl);
  font-size: 0.875rem;
  font-weight: 600;
}

.test-api h3 {
  margin-bottom: 1rem;
  color: #2d3748;
  font-size: 1.25rem;
}

.button-group {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.test-api button {
  padding: 0.625rem 1.25rem;
  background: var(--color-danger);
  color: white;
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  font-weight: 600;
  transition: all var(--transition-fast);
}

.test-api button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
}

.test-api button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.api-result {
  margin-top: 1.5rem;
  padding: 1.5rem;
  background: #f7fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.api-result h4 {
  margin: 0 0 0.75rem 0;
  color: #4a5568;
  font-size: 0.95rem;
}

.api-result pre {
  margin: 0;
  font-size: 0.875rem;
  color: #2d3748;
  overflow-x: auto;
  font-family: 'Courier New', monospace;
}

/* Transport Management Section */
.transport-section {
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 2px solid #e2e8f0;
}

.transport-section h3 {
  margin-bottom: 1rem;
  color: #2d3748;
  font-size: 1.25rem;
}

.transport-links {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 1rem;
}

.transport-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1.25rem 1rem;
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  text-decoration: none;
  transition: all 0.2s ease;
}

.transport-card:hover {
  border-color: var(--color-primary);
  background: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.card-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.card-title {
  font-weight: 600;
  color: #2d3748;
  font-size: 1rem;
  margin-bottom: 0.25rem;
}

.card-desc {
  font-size: 0.75rem;
  color: #718096;
  text-align: center;
}
</style>
