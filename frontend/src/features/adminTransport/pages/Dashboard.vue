<template>
  <PortalLayout>
    <PageHeader
      title="Transport Management"
      subtitle="Overview of transport subscription system"
      :breadcrumbs="[
        { label: 'Admin', to: '/admin/transport' },
        { label: 'Dashboard' }
      ]"
    />

    <!-- Loading State -->
    <div v-if="loading" class="loading-grid">
      <div v-for="i in 5" :key="i" class="kpi-skeleton">
        <SkeletonLoader height="32px" width="32px" border-radius="var(--radius-full)" />
        <SkeletonLoader height="40px" width="60px" />
        <SkeletonLoader height="16px" width="100px" />
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
      <Button variant="primary" @click="fetchDashboard">Retry</Button>
    </div>

    <!-- Dashboard Content -->
    <div v-else class="dashboard-content">
      <!-- KPI Cards -->
      <div class="kpi-grid">
        <div class="kpi-card pending">
          <div class="kpi-icon">📋</div>
          <div class="kpi-info">
            <span class="kpi-value">{{ stats.pending_requests }}</span>
            <span class="kpi-label">Pending Requests</span>
          </div>
          <router-link to="/admin/transport/requests?status=pending" class="kpi-link">
            View All →
          </router-link>
        </div>

        <div class="kpi-card active">
          <div class="kpi-icon">✅</div>
          <div class="kpi-info">
            <span class="kpi-value">{{ stats.active_subscriptions }}</span>
            <span class="kpi-label">Active Subscriptions</span>
          </div>
          <router-link to="/admin/transport/requests?status=approved" class="kpi-link">
            View All →
          </router-link>
        </div>

        <div class="kpi-card waitlisted">
          <div class="kpi-icon">⏳</div>
          <div class="kpi-info">
            <span class="kpi-value">{{ stats.waitlisted_subscriptions }}</span>
            <span class="kpi-label">Waitlisted</span>
          </div>
        </div>

        <div class="kpi-card routes">
          <div class="kpi-icon">🚌</div>
          <div class="kpi-info">
            <span class="kpi-value">{{ stats.active_routes }}</span>
            <span class="kpi-label">Active Routes</span>
          </div>
          <router-link to="/admin/transport/routes" class="kpi-link">
            Manage →
          </router-link>
        </div>

        <div class="kpi-card slots">
          <div class="kpi-icon">🕐</div>
          <div class="kpi-info">
            <span class="kpi-value">{{ stats.active_slots }}</span>
            <span class="kpi-label">Active Slots</span>
          </div>
          <router-link to="/admin/transport/slots" class="kpi-link">
            Manage →
          </router-link>
        </div>

        <div class="kpi-card revenue">
          <div class="kpi-icon">💰</div>
          <div class="kpi-info">
            <span class="kpi-value">{{ formatCurrency(stats.financials?.total_revenue || 0) }}</span>
            <span class="kpi-label">Total Revenue</span>
          </div>
        </div>

        <div class="kpi-card potential">
          <div class="kpi-icon">📈</div>
          <div class="kpi-info">
            <span class="kpi-value">{{ formatCurrency(stats.financials?.potential_revenue || 0) }}</span>
            <span class="kpi-label">Potential Revenue (Pending)</span>
          </div>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="quick-links">
        <h2>Quick Actions</h2>
        <div class="links-grid">
          <router-link to="/admin/transport/requests" class="quick-link-card">
            <span class="link-icon">📝</span>
            <span class="link-text">Review Requests</span>
          </router-link>
          <router-link to="/admin/transport/routes" class="quick-link-card">
            <span class="link-icon">🗺️</span>
            <span class="link-text">Manage Routes</span>
          </router-link>
          <router-link to="/admin/transport/slots" class="quick-link-card">
            <span class="link-icon">📅</span>
            <span class="link-text">Manage Slots</span>
          </router-link>
          <router-link to="/admin/transport/stops" class="quick-link-card">
            <span class="link-icon">🚏</span>
            <span class="link-text">Manage Stops</span>
          </router-link>
          <router-link to="/admin/transport/manifest" class="quick-link-card">
            <span class="link-icon">📊</span>
            <span class="link-text">Export Manifest</span>
          </router-link>
        </div>
      </div>
    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Button from '@/components/ui/Button.vue';
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue';
import { adminTransportApi } from '../api/adminTransport.api';

const loading = ref(true);
const error = ref(null);
const stats = ref({
  pending_requests: 0,
  active_subscriptions: 0,
  waitlisted_subscriptions: 0,
  active_routes: 0,
  active_slots: 0,
});

const fetchDashboard = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await adminTransportApi.getDashboard();
    if (response.data.success) {
      stats.value = response.data.data;
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load dashboard';
  } finally {
    loading.value = false;
  }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-EG', { style: 'currency', currency: 'EGP' }).format(value);
};

onMounted(fetchDashboard);
</script>

<style scoped>
.loading-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--spacing-lg);
  padding: var(--spacing-lg) 0;
}

.kpi-skeleton {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-xl);
  height: 140px;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.error-state {
  text-align: center;
  padding: var(--spacing-3xl);
  color: var(--color-danger);
}

.dashboard-content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2xl);
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--spacing-lg);
}

.kpi-card {
  background: var(--color-surface);
  border-radius: var(--radius-lg);
  padding: var(--spacing-xl);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  position: relative;
  transition: box-shadow 0.2s ease;
}

.kpi-card:hover {
  box-shadow: var(--shadow-md);
}

.kpi-icon {
  font-size: 32px;
}

.kpi-info {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.kpi-value {
  font-size: 32px;
  font-weight: var(--fw-bold);
  color: var(--color-textMain);
}

.kpi-label {
  font-size: var(--font-base);
  color: var(--color-textMuted);
}

.kpi-link {
  font-size: var(--font-sm);
  color: var(--color-primary);
  text-decoration: none;
  font-weight: var(--fw-medium);
}

.kpi-link:hover {
  text-decoration: underline;
}

/* KPI Colors */
.kpi-card.pending {
  border-left: 4px solid var(--color-warning);
}
.kpi-card.pending .kpi-icon { color: var(--color-warning); }

.kpi-card.active {
  border-left: 4px solid var(--color-success);
}
.kpi-card.active .kpi-icon { color: var(--color-success); }

.kpi-card.waitlisted {
  border-left: 4px solid var(--color-neutral); /* Changed to neutral as gray */
}
.kpi-card.waitlisted .kpi-icon { color: var(--color-neutral); }

.kpi-card.routes {
  border-left: 4px solid var(--color-primary);
}
.kpi-card.routes .kpi-icon { color: var(--color-primary); }

.kpi-card.slots {
  border-left: 4px solid var(--color-info);
}
.kpi-card.slots .kpi-icon { color: var(--color-info); }

.kpi-card.revenue {
  border-left: 4px solid #10B981;
  background: #ECFDF5;
}
.kpi-card.revenue .kpi-icon { color: #10B981; }

.kpi-card.potential {
  border-left: 4px solid #F59E0B;
  background: #FFFBEB;
}
.kpi-card.potential .kpi-icon { color: #F59E0B; }

.quick-links h2 {
  font-size: 20px;
  font-weight: 600;
  color: var(--color-textMain);
  margin-bottom: var(--spacing-lg);
}

.links-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: var(--spacing-md);
}

.quick-link-card {
  background: white;
  border-radius: var(--radius-md);
  padding: var(--spacing-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  text-decoration: none;
  color: var(--color-textMain);
  transition: all 0.2s ease;
}

.quick-link-card:hover {
  background: var(--color-neutral);
  border-color: var(--color-primary);
}

.link-icon {
  font-size: 24px;
}

.link-text {
  font-size: 14px;
  font-weight: 500;
}
</style>
