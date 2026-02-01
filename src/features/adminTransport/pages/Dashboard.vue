<template>
  <PortalLayout>
    <PageHeader
      title="Transport Dashboard"
      subtitle="Overview of transport system performance and activity"
      :breadcrumbs="[
        { label: 'Admin', to: '/admin/transport' },
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

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <div class="error-content">
        <p>{{ error }}</p>
        <Button variant="primary" @click="fetchDashboard">Retry Dashboard</Button>
      </div>
    </div>

    <!-- Dashboard Content -->
    <div v-else class="dashboard-content">
      
      <!-- Section 1: Key Performance Indicators -->
      <section class="kpi-section">
        <div class="kpi-grid">
          <KpiCard
            title="Total Revenue"
            :value="formatCurrency(stats.financials?.total_revenue || 0)"
            :icon="Banknote"
            variant="primary"
            :trend="stats.financials?.potential_revenue > 0 ? `+${formatCurrency(stats.financials.potential_revenue)} pending` : ''"
            trendType="positive"
          />

          <KpiCard
            title="Active Subscriptions"
            :value="stats.active_subscriptions"
            :icon="Users"
            variant="info"
            subtext="Students enrolled"
            to="/admin/transport/requests?status=approved"
            linkText="View Details"
          />

          <KpiCard
            title="Pending Requests"
            :value="stats.pending_requests"
            :icon="FileClock"
            variant="warning"
            subtext="Requires action"
            to="/admin/transport/requests?status=pending"
            linkText="Review Requests"
          />

          <KpiCard
            title="Active Routes"
            :value="stats.active_routes"
            :icon="Route"
            variant="success"
            subtext="Operational routes"
            to="/admin/transport/routes"
            linkText="Manage Routes"
          />
        </div>
      </section>

      <!-- Section 2: Analytics & Charts -->
      <section class="analytics-section">
        <div class="chart-card main-chart">
          <div class="card-header">
            <h3>Transport Requests Overview</h3>
            <div class="card-actions">
              <!-- Placeholder for date filter if needed later -->
            </div>
          </div>
          <div class="card-body">
            <Bar v-if="requestChartData" :data="requestChartData" :options="barChartOptions" />
          </div>
        </div>

        <div class="chart-card side-chart">
          <div class="card-header">
            <h3>Subscription Status</h3>
          </div>
          <div class="card-body doughnut-container">
            <Doughnut v-if="statusChartData" :data="statusChartData" :options="doughnutOptions" />
             <div class="chart-legend-custom">
               <div class="legend-item">
                 <span class="dot active"></span>
                 <span>Active ({{stats.active_subscriptions}})</span>
               </div>
               <div class="legend-item">
                 <span class="dot pending"></span>
                 <span>Pending ({{stats.pending_requests}})</span>
               </div>
                <div class="legend-item">
                 <span class="dot waitlisted"></span>
                 <span>Waitlisted ({{stats.waitlisted_subscriptions}})</span>
               </div>
             </div>
          </div>
        </div>
      </section>

      <!-- Section 3: Quick Management -->
      <section class="management-section">
        <h3 class="section-title">Quick Actions</h3>
        <div class="actions-grid">
          <ActionCard
            title="Manage Requests"
            description="Approve or reject applications"
            :icon="FileText"
            to="/admin/transport/requests"
          />

          <ActionCard
            title="Schedule Slots"
            description="Manage timings and capacity"
            :icon="Calendar"
            to="/admin/transport/slots"
          />

          <ActionCard
            title="View Reports"
            description="Export system data"
            :icon="FileSpreadsheet"
            to="/admin/transport/reports"
          />

          <ActionCard
            title="Passenger Manifest"
            description="Daily rider lists"
            :icon="Users"
            to="/admin/transport/manifest"
          />
        </div>
      </section>

    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Button from '@/components/ui/Button.vue';
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue';
import { KpiCard, ActionCard } from '@/components/admin'; // Shared Components
import { adminTransportApi } from '../api/adminTransport.api';
import { 
  Banknote, 
  Users, 
  FileClock, 
  Route, 
  FileText,
  Calendar,
  FileSpreadsheet
} from 'lucide-vue-next';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js';
import { Bar, Doughnut } from 'vue-chartjs';

// Register ChartJS components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
);

const loading = ref(true);
const error = ref(null);
const stats = ref({
  pending_requests: 0,
  active_subscriptions: 0,
  waitlisted_subscriptions: 0,
  active_routes: 0,
  active_slots: 0,
  financials: { total_revenue: 0, potential_revenue: 0 }
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

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '70%',
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: function(context) {
          return ` ${context.label}: ${context.raw}`;
        }
      }
    }
  }
};

const requestChartData = computed(() => {
  return {
    labels: ['Pending', 'Active', 'Waitlisted', 'Rejected'],
    datasets: [{
      label: 'Requests',
      backgroundColor: ['#F59E0B', '#10B981', '#6B7280', '#EF4444'],
      borderRadius: 4,
      data: [
        stats.value.pending_requests, 
        stats.value.active_subscriptions, 
        stats.value.waitlisted_subscriptions,
        5 // Mock rejected count
      ]
    }]
  };
});

const statusChartData = computed(() => {
  return {
    labels: ['Active', 'Pending', 'Waitlisted'],
    datasets: [{
      backgroundColor: ['#10B981', '#F59E0B', '#6B7280'],
      borderWidth: 0,
      data: [
        stats.value.active_subscriptions, 
        stats.value.pending_requests, 
        stats.value.waitlisted_subscriptions
      ]
    }]
  };
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
    console.error(err);
    error.value = err.response?.data?.message || 'Failed to load transport data';
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
/* Layout & Grid */
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
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-lg);
}

.card-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: var(--color-textMain);
}

.card-body {
  position: relative;
  height: 300px;
  width: 100%;
}

.doughnut-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 240px; 
}

.chart-legend-custom {
  margin-top: var(--spacing-lg);
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--color-textMain);
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.dot.active { background-color: #10B981; }
.dot.pending { background-color: #F59E0B; }
.dot.waitlisted { background-color: #6B7280; }

/* Management Section */
.management-section {
  margin-top: var(--spacing-md);
}

.section-title {
  font-size: 18px;
  font-weight: 600;
  color: var(--color-textMain);
  margin-bottom: var(--spacing-lg);
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: var(--spacing-md);
}

.loading-grid {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.error-state {
  display: flex;
  justify-content: center;
  padding: var(--spacing-3xl);
}

.error-content {
  text-align: center;
  color: var(--color-danger);
}
</style>
