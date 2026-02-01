<template>
  <PortalLayout>
    <PageHeader
      title="ID Card Dashboard"
      subtitle="Overview of ID card service requests and activity"
      :breadcrumbs="[
        { label: 'Admin', to: '/admin/id-card' },
        { label: 'Dashboard' }
      ]"
    />

    <!-- Loading State -->
    <div v-if="loading" class="loading-grid">
      <div v-for="i in 4" :key="i" class="kpi-skeleton">
        <SkeletonLoader height="160px" width="100%" border-radius="var(--radius-lg)" />
      </div>
      <div class="chart-skeleton">
         <SkeletonLoader height="300px" width="100%" border-radius="var(--radius-lg)" />
      </div>
    </div>

    <!-- Dashboard Content -->
    <div v-else class="dashboard-content">

      <!-- Section 1: KPI Cards -->
      <section class="kpi-section">
        <div class="kpi-grid">
          <KpiCard
            title="Pending Requests"
            :value="stats.pending"
            :icon="FileClock"
            variant="warning"
            subtext="Requires review"
            to="/admin/id-card/requests?status=pending"
            linkText="View Queue"
          />

          <KpiCard
            title="Approved"
            :value="stats.approved"
            :icon="CheckCircle"
            variant="info"
            subtext="In production"
            to="/admin/id-card/requests?status=approved"
            linkText="View Approved"
          />

          <KpiCard
            title="Ready for Pickup"
            :value="stats.ready_for_pickup"
            :icon="Package"
            variant="success"
            subtext="Student notification sent"
            to="/admin/id-card/requests?status=ready_for_pickup"
            linkText="View Ready"
          />

          <KpiCard
            title="Total Revenue"
            :value="formatCurrency(stats.total_revenue || 0)"
            :icon="Banknote"
            variant="primary"
            subtext="Generated this month"
          />
        </div>
      </section>

      <!-- Section 2: Alerts & Charts -->
      <section class="analytics-section">
        <div class="chart-card main-chart">
           <div class="card-header">
            <h3>Request Status Overview</h3>
          </div>
          <div class="card-body">
            <Bar v-if="requestChartData" :data="requestChartData" :options="barChartOptions" />
          </div>
        </div>

        <!-- Alerts Column -->
         <div class="side-column">
             <!-- Payment Alerts -->
             <div v-if="stats.pending_payment_verification > 0 || stats.flagged_payments > 0" class="alerts-container">
                 <div v-if="stats.pending_payment_verification > 0" class="alert-card warning">
                    <CreditCard class="alert-icon" />
                    <div class="alert-content">
                        <span class="alert-count">{{ stats.pending_payment_verification }}</span>
                        <span class="alert-text">Payments pending verification</span>
                    </div>
                    <router-link to="/admin/id-card/requests?payment_status=pending" class="alert-link">Review</router-link>
                 </div>

                 <div v-if="stats.flagged_payments > 0" class="alert-card danger">
                    <AlertTriangle class="alert-icon" />
                    <div class="alert-content">
                        <span class="alert-count">{{ stats.flagged_payments }}</span>
                        <span class="alert-text">Flagged payments</span>
                    </div>
                    <router-link to="/admin/id-card/requests?payment_status=flagged" class="alert-link">Resolve</router-link>
                 </div>
             </div>

             <!-- Quick Actions (Moved here for balance) -->
             <div class="quick-actions-list">
                 <h3>Quick Actions</h3>
                 <ActionCard
                    title="All Requests"
                    description="View full request history"
                    :icon="ClipboardList"
                    to="/admin/id-card/requests"
                  />
                  <ActionCard
                    title="Settings"
                    description="Configure fees & payments"
                    :icon="Settings"
                    to="/admin/id-card/settings"
                  />
             </div>
         </div>
      </section>

    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue';
import { KpiCard, ActionCard } from '@/components/admin';
import { adminIdCardApi } from '../api/adminIdCard.api';
import { 
  FileClock, 
  CheckCircle, 
  Package, 
  Banknote, 
  CreditCard, 
  AlertTriangle,
  ClipboardList,
  Settings
} from 'lucide-vue-next';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend
);

const loading = ref(true);
const stats = ref({
  pending: 0,
  approved: 0,
  ready_for_pickup: 0,
  delivered_today: 0,
  total_this_month: 0,
  pending_payment_verification: 0,
  flagged_payments: 0,
  total_revenue: 0 // Will need backend update or mock
});

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#1f2937',
      padding: 12,
      cornerRadius: 8,
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: { color: '#f3f4f6' },
      ticks: { font: { size: 11 } }
    },
    x: {
      grid: { display: false },
      ticks: { font: { size: 11 } }
    }
  }
};

const requestChartData = computed(() => {
  return {
    labels: ['Pending', 'Approved', 'Ready', 'Delivered Today'],
    datasets: [{
      label: 'Requests',
      backgroundColor: ['#F59E0B', '#3B82F6', '#10B981', '#6B7280'],
      borderRadius: 4,
      data: [
        stats.value.pending, 
        stats.value.approved, 
        stats.value.ready_for_pickup,
        stats.value.delivered_today
      ]
    }]
  };
});

const fetchDashboard = async () => {
  try {
    loading.value = true;
    const response = await adminIdCardApi.getDashboard();
    stats.value = { ...stats.value, ...response.data };
    // Backend now provides total_revenue based on verified payments

  } catch (error) {
    console.error('Failed to load dashboard:', error);
  } finally {
    loading.value = false;
  }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-EG', { style: 'currency', currency: 'EGP', maximumFractionDigits: 0 }).format(value);
};

onMounted(fetchDashboard);
</script>

<style scoped>
/* Layout */
.dashboard-content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2xl);
  padding-bottom: var(--spacing-3xl);
  max-width: 1200px;
  margin: 0 auto;
}

/* KPI Section */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: var(--spacing-lg);
}

/* Analytics Section */
.analytics-section {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: var(--spacing-lg);
}

@media (max-width: 1024px) {
  .analytics-section {
    grid-template-columns: 1fr;
  }
}

.chart-card {
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-xl);
  box-shadow: var(--shadow-sm);
  height: 400px;
  display: flex;
  flex-direction: column;
}

.card-header {
  margin-bottom: var(--spacing-lg);
}

.card-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: var(--color-textMain);
  margin: 0;
}

.card-body {
  flex: 1;
  position: relative;
  width: 100%;
}

/* Side Column (Alerts + Actions) */
.side-column {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xl);
}

/* Alerts */
.alerts-container {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.alert-card {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
    border-radius: var(--radius-md);
    border: 1px solid transparent;
}

.alert-card.warning {
    background-color: #fffbeb;
    border-color: #fcd34d;
    color: #92400e;
}

.alert-card.danger {
    background-color: #fef2f2;
    border-color: #fca5a5;
    color: #b91c1c;
}

.alert-icon {
    width: 24px;
    height: 24px;
}

.alert-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.alert-count {
    font-weight: 700;
    font-size: 18px;
    line-height: 1;
}

.alert-text {
    font-size: 12px;
    opacity: 0.9;
}

.alert-link {
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    color: inherit;
    border-bottom: 1px dashed currentColor;
}

/* Quick Actions List */
.quick-actions-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.quick-actions-list h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--color-textMain);
    margin: 0;
}

/* Loading */
.loading-grid {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}
</style>
