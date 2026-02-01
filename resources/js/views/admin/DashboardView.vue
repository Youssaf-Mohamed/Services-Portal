<template>
  <div class="admin-page">
    <div class="header-container">
      <PageHeader
        title="Admin Dashboard"
        subtitle="System overview and quick links"
      />
      <button @click="handleLogout" class="logout-btn">
        <LogOut class="btn-icon" /> Logout
      </button>
    </div>

    <div class="content">
      <div class="user-info">
        <h2>Welcome, {{ authStore.user?.name }}!</h2>
        <div class="info-grid">
          <div class="info-item">
            <Mail class="info-icon" />
            <span class="value">{{ authStore.user?.email }}</span>
          </div>
          <div class="info-item">
            <Shield class="info-icon" />
            <span class="value role-badge">{{ authStore.user?.role }}</span>
          </div>
        </div>
      </div>

      <!-- Transport Management Section -->
      <div class="section-container">
        <div class="section-header">
          <Bus class="section-icon" />
          <h3>Transport Management</h3>
        </div>
        <div class="dashboard-grid">
          <router-link to="/admin/transport" class="dashboard-card">
            <div class="card-icon-wrapper primary">
              <BarChart3 class="card-icon" />
            </div>
            <div class="card-content">
              <span class="card-title">Overview</span>
              <span class="card-desc">View KPIs & statistics</span>
            </div>
          </router-link>

          <router-link to="/admin/transport/requests" class="dashboard-card">
            <div class="card-icon-wrapper warning">
              <FileText class="card-icon" />
            </div>
            <div class="card-content">
              <span class="card-title">Requests</span>
              <span class="card-desc">Review & moderate</span>
            </div>
          </router-link>

          <router-link to="/admin/transport/routes" class="dashboard-card">
            <div class="card-icon-wrapper info">
              <Map class="card-icon" />
            </div>
            <div class="card-content">
              <span class="card-title">Routes</span>
              <span class="card-desc">Manage bus lines</span>
            </div>
          </router-link>

          <router-link to="/admin/transport/slots" class="dashboard-card">
            <div class="card-icon-wrapper success">
              <Calendar class="card-icon" />
            </div>
            <div class="card-content">
              <span class="card-title">Slots</span>
              <span class="card-desc">Schedule & capacity</span>
            </div>
          </router-link>

          <router-link to="/admin/transport/manifest" class="dashboard-card">
            <div class="card-icon-wrapper purple">
              <Users class="card-icon" />
            </div>
            <div class="card-content">
              <span class="card-title">Manifest</span>
              <span class="card-desc">Passenger lists</span>
            </div>
          </router-link>
        </div>
      </div>
      
      <!-- ID Card Management Section -->
      <div class="section-container">
        <div class="section-header">
          <CreditCard class="section-icon" />
          <h3>ID Card Services</h3>
        </div>
        <div class="dashboard-grid">
          <router-link to="/admin/id-card" class="dashboard-card">
            <div class="card-icon-wrapper primary">
              <LayoutDashboard class="card-icon" />
            </div>
            <div class="card-content">
              <span class="card-title">Overview</span>
              <span class="card-desc">Service stats & KPIs</span>
            </div>
          </router-link>

          <router-link to="/admin/id-card/requests" class="dashboard-card">
            <div class="card-icon-wrapper warning">
              <ClipboardList class="card-icon" />
            </div>
            <div class="card-content">
              <span class="card-title">Requests</span>
              <span class="card-desc">Process applications</span>
            </div>
          </router-link>

          <router-link to="/admin/id-card/settings" class="dashboard-card">
            <div class="card-icon-wrapper secondary">
              <Settings class="card-icon" />
            </div>
            <div class="card-content">
              <span class="card-title">Settings</span>
              <span class="card-desc">Configure services</span>
            </div>
          </router-link>
        </div>
      </div>

      <!-- Testing Tools (Collapsed or moved to bottom) -->
      <div class="test-api">
        <h3>System Diagnostics</h3>
        <div class="button-group">
          <button @click="testAdminPing" :disabled="testing" class="btn btn-outline">
            <Activity class="btn-icon" />
            {{ testing ? '...' : 'Ping API' }}
          </button>
          <button @click="testStorageCheck" :disabled="testing" class="btn btn-outline">
            <HardDrive class="btn-icon" />
            {{ testing ? '...' : 'Check Storage' }}
          </button>
        </div>
        <div v-if="apiResult" class="api-result">
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
import { PageHeader } from '@/components/ui';
import { 
  LogOut, Mail, Shield, Bus, CreditCard, 
  BarChart3, FileText, Map, Calendar, Users,
  LayoutDashboard, ClipboardList, Settings,
  Activity, HardDrive
} from 'lucide-vue-next';

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
  padding: var(--spacing-xl);
}

.header-container {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: var(--spacing-xl);
}



.logout-btn {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-lg);
  background: var(--color-surface);
  color: var(--color-danger);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  font-weight: var(--fw-medium);
  cursor: pointer;
  transition: all var(--transition-normal);
}

.logout-btn:hover {
  background: var(--color-dangerBg);
  border-color: var(--color-danger);
}

.btn-icon {
  width: 18px;
  height: 18px;
}

.content {
  background: var(--color-surface);
  padding: var(--spacing-2xl);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
}

.user-info {
  margin-bottom: var(--spacing-2xl);
  padding-bottom: var(--spacing-xl);
  border-bottom: 1px solid var(--color-border);
}

.user-info h2 {
  color: var(--color-textMain);
  margin-bottom: var(--spacing-lg);
  font-size: var(--font-xl);
}

.info-grid {
  display: flex;
  gap: var(--spacing-2xl);
  flex-wrap: wrap;
}

.info-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  color: var(--color-textSecondary);
}

.info-icon {
  width: 20px;
  height: 20px;
  color: var(--color-primary);
}

.role-badge {
  padding: 2px 10px;
  background: var(--color-primaryBg);
  color: var(--color-primary);
  border-radius: var(--radius-full);
  font-size: var(--font-xs);
  font-weight: var(--fw-bold);
  text-transform: uppercase;
}

/* Sections */
.section-container {
  margin-bottom: var(--spacing-2xl);
}

.section-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-lg);
}

.section-icon {
  width: 24px;
  height: 24px;
  color: var(--color-primary);
}

.section-header h3 {
  margin: 0;
  color: var(--color-textMain);
  font-size: var(--font-lg);
  font-weight: var(--fw-semibold);
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: var(--spacing-lg);
}

.dashboard-card {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  padding: var(--spacing-lg);
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  text-decoration: none;
  transition: all var(--transition-normal);
}

.dashboard-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: var(--color-primary);
}

.card-icon-wrapper {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* Icon Colors */
.card-icon-wrapper.primary { background: var(--color-primaryBg); color: var(--color-primary); }
.card-icon-wrapper.warning { background: var(--color-warningBg); color: var(--color-warningText); }
.card-icon-wrapper.info { background: var(--color-infoBg); color: var(--color-infoText); }
.card-icon-wrapper.success { background: var(--color-successBg); color: var(--color-successText); }
.card-icon-wrapper.secondary { background: var(--color-neturalBg); color: var(--color-neutralText); }
.card-icon-wrapper.purple { background: #f3e8ff; color: #9333ea; }

.card-icon {
  width: 24px;
  height: 24px;
}

.card-content {
  display: flex;
  flex-direction: column;
}

.card-title {
  font-weight: var(--fw-semibold);
  color: var(--color-textMain);
  font-size: var(--font-base);
}

.card-desc {
  font-size: var(--font-xs);
  color: var(--color-textMuted);
  margin-top: 2px;
}

/* Test API Section */
.test-api {
  margin-top: var(--spacing-2xl);
  padding-top: var(--spacing-xl);
  border-top: 1px solid var(--color-border);
}

.test-api h3 {
  color: var(--color-textSecondary);
  font-size: var(--font-base);
  margin-bottom: var(--spacing-md);
}

.button-group {
  display: flex;
  gap: var(--spacing-md);
}

.btn {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-lg);
  border-radius: var(--radius-md);
  font-size: var(--font-sm);
  font-weight: var(--fw-medium);
  cursor: pointer;
  transition: all var(--transition-normal);
}

.btn-outline {
  background: transparent;
  border: 1px solid var(--color-border);
  color: var(--color-textSecondary);
}

.btn-outline:hover:not(:disabled) {
  background: var(--color-surfaceHighlight);
  color: var(--color-primary);
  border-color: var(--color-primary);
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.api-result {
  margin-top: var(--spacing-lg);
  padding: var(--spacing-md);
  background: var(--color-background);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  font-family: monospace;
  font-size: var(--font-xs);
  color: var(--color-textMuted);
  max-height: 200px;
  overflow-y: auto;
}
</style>
