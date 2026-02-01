<template>
  <PortalLayout>
    <PageHeader
      title="Subscriptions Manifest"
      subtitle="View all active subscriptions with complete details and filtering options"
      :breadcrumbs="[
        { label: 'Admin', to: '/admin/transport' },
        { label: 'Manifest' }
      ]"
    />

    <!-- Filters -->
    <Card class="filters-card">
      <template #header>
        <div class="card-header-row">
          <div class="header-title">
            <Filter class="header-icon" />
            <h3>Filters</h3>
          </div>
        </div>
      </template>
      <div class="filters-grid">
        <div class="form-group">
          <label>Route</label>
          <select v-model="filters.route_id">
            <option value="">All Routes</option>
            <option v-for="route in routes" :key="route.id" :value="route.id">
              {{ route.name_en }}
            </option>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select v-model="filters.status">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="waitlisted">Waitlisted</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div class="form-group">
          <label>Selected Day</label>
          <select v-model="filters.selected_day">
            <option value="">All Days</option>
            <option value="saturday">Saturday</option>
            <option value="sunday">Sunday</option>
            <option value="monday">Monday</option>
            <option value="tuesday">Tuesday</option>
            <option value="wednesday">Wednesday</option>
            <option value="thursday">Thursday</option>
          </select>
        </div>
        <div class="form-group">
          <label>Active Only</label>
          <div class="checkbox-wrapper">
            <input type="checkbox" v-model="filters.active_only" id="active-only" />
            <label for="active-only">Show only currently active</label>
          </div>
        </div>
        <div class="form-group actions">
          <Button variant="primary" @click="fetchManifest">
            <RefreshCw class="btn-icon" />
            Refresh
          </Button>
          <Button variant="success" @click="exportExcel" :disabled="subscriptions.length === 0">
            <Download class="btn-icon" />
            Export Excel
          </Button>
        </div>
      </div>
    </Card>

    <!-- Statistics Summary -->
    <div v-if="!loading && subscriptions.length > 0" class="stats-grid">
      <Card class="stat-card">
        <div class="stat-content">
          <div class="stat-icon-wrapper blue">
            <Users class="stat-icon" />
          </div>
          <div class="stat-details">
            <span class="stat-label">Total Subscribers</span>
            <span class="stat-value">{{ subscriptions.length }}</span>
          </div>
        </div>
      </Card>
      <Card class="stat-card">
        <div class="stat-content">
          <div class="stat-icon-wrapper green">
            <CheckCircle class="stat-icon" />
          </div>
          <div class="stat-details">
            <span class="stat-label">Active</span>
            <span class="stat-value">{{ activeCount }}</span>
          </div>
        </div>
      </Card>
      <Card class="stat-card">
        <div class="stat-content">
          <div class="stat-icon-wrapper orange">
            <Clock class="stat-icon" />
          </div>
          <div class="stat-details">
            <span class="stat-label">Waitlisted</span>
            <span class="stat-value">{{ waitlistedCount }}</span>
          </div>
        </div>
      </Card>
      <Card class="stat-card">
        <div class="stat-content">
          <div class="stat-icon-wrapper purple">
            <Banknote class="stat-icon" />
          </div>
          <div class="stat-details">
            <span class="stat-label">Total Revenue</span>
            <span class="stat-value">EGP {{ totalRevenue.toFixed(2) }}</span>
          </div>
        </div>
      </Card>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <SkeletonLoader height="120px" style="margin-bottom: 24px;" />
      <SkeletonLoader height="400px" />
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <AlertCircle class="error-icon" />
      <p>{{ error }}</p>
      <Button variant="primary" @click="fetchManifest">Retry</Button>
    </div>

    <!-- Subscriptions Table -->
    <Card v-else>
      <template #header>
        <div class="table-header">
          <div class="header-title">
            <FileText class="header-icon" />
            <h3>Subscriptions List</h3>
          </div>
          <span class="count-badge">{{ subscriptions.length }} records</span>
        </div>
      </template>
      
      <EmptyState
        v-if="subscriptions.length === 0"
        icon="inbox"
        title="No Subscriptions Found"
        message="No subscriptions match the selected filters."
      />

      <div v-else class="table-wrapper">
        <table class="manifest-table">
          <thead>
            <tr>
              <th class="col-num">#</th>
              <th class="col-student">Student</th>
              <th class="col-route">Route</th>
              <th class="col-plan">Plan</th>
              <th class="col-days-count">Days/Week</th>
              <th class="col-days">Selected Days</th>
              <th class="col-period">Period</th>
              <th class="col-amount">Amount</th>
              <th class="col-status">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(sub, index) in paginatedSubscriptions" :key="sub.id">
              <td class="col-num">{{ (currentPage - 1) * perPage + index + 1 }}</td>
              <td class="col-student">
                <div class="student-cell">
                  <User class="cell-icon" />
                  <div class="student-info">
                    <strong>{{ sub.student.name }}</strong>
                    <span class="email">{{ sub.student.email }}</span>
                  </div>
                </div>
              </td>
              <td class="col-route">
                <div class="route-cell">
                  <MapPin class="cell-icon" />
                  <span>{{ sub.route.name_en }}</span>
                </div>
              </td>
              <td class="col-plan">
                <Badge v-if="sub.plan" :variant="sub.plan.plan_type === 'monthly' ? 'info' : 'warning'">
                  {{ sub.plan.name_en }}
                </Badge>
                <Badge v-else variant="secondary">Legacy ({{ sub.plan_type }})</Badge>
              </td>
              <td class="col-days-count text-center">
                <Badge variant="primary">
                  {{ sub.plan?.allowed_days_per_week || sub.selected_days?.length || 'N/A' }}
                </Badge>
              </td>
              <td class="col-days">
                <div class="days-badges">
                  <span 
                    v-for="day in (sub.selected_days || [])" 
                    :key="day"
                    class="day-badge"
                  >
                    {{ formatDay(day) }}
                  </span>
                  <span v-if="!sub.selected_days || sub.selected_days.length === 0" class="text-muted">
                    N/A
                  </span>
                </div>
              </td>
              <td class="col-period">
                <div class="period-cell">
                  <Calendar class="cell-icon" />
                  <div class="period-info">
                    <span class="date">{{ formatDate(sub.starts_at) }}</span>
                    <ArrowRight class="arrow-icon" />
                    <span class="date">{{ formatDate(sub.ends_at) }}</span>
                  </div>
                </div>
              </td>
              <td class="col-amount">
                <div class="amount-cell">
                  <strong>EGP {{ parseFloat(sub.amount_paid_expected).toFixed(2) }}</strong>
                </div>
              </td>
              <td class="col-status">
                <Badge :variant="getStatusVariant(sub.status)">
                  <component :is="getStatusIcon(sub.status)" class="badge-icon" />
                  {{ sub.status }}
                </Badge>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="pagination">
        <Button 
          variant="text" 
          @click="currentPage--" 
          :disabled="currentPage === 1"
        >
          <ChevronLeft class="btn-icon" />
          Previous
        </Button>
        <span class="page-info">Page {{ currentPage }} of {{ totalPages }}</span>
        <Button 
          variant="text" 
          @click="currentPage++" 
          :disabled="currentPage === totalPages"
        >
          Next
          <ChevronRight class="btn-icon" />
        </Button>
      </div>
    </Card>
  </PortalLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, markRaw } from 'vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Button from '@/components/ui/Button.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue';
import { adminTransportApi } from '../api/adminTransport.api';
import { useToast } from '@/composables/useToast';
import {
  Filter,
  RefreshCw,
  Download,
  Users,
  CheckCircle,
  Clock,
  Banknote,
  AlertCircle,
  FileText,
  User,
  MapPin,
  Calendar,
  ArrowRight,
  ChevronLeft,
  ChevronRight,
  XCircle,
} from 'lucide-vue-next';

const toast = useToast();

const loading = ref(false);
const error = ref(null);
const routes = ref([]);
const subscriptions = ref([]);
const currentPage = ref(1);
const perPage = ref(15);

const filters = reactive({
  route_id: '',
  status: '',
  selected_day: '',
  active_only: false,
});

// Computed
const activeCount = computed(() => subscriptions.value.filter(s => s.status === 'active').length);
const waitlistedCount = computed(() => subscriptions.value.filter(s => s.status === 'waitlisted').length);
const totalRevenue = computed(() => subscriptions.value.reduce((sum, s) => sum + parseFloat(s.amount_paid_expected || 0), 0));

const totalPages = computed(() => Math.ceil(subscriptions.value.length / perPage.value));
const paginatedSubscriptions = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  const end = start + perPage.value;
  return subscriptions.value.slice(start, end);
});

// Methods
const fetchRoutes = async () => {
  try {
    const response = await adminTransportApi.getRoutes();
    if (response.data.success) {
      routes.value = response.data.data?.routes || response.data.data || [];
    }
  } catch (err) {
    console.error('Failed to fetch routes:', err);
  }
};

const fetchManifest = async () => {
  try {
    loading.value = true;
    error.value = null;

    const params = {};
    if (filters.route_id) params.route_id = filters.route_id;
    if (filters.status) params.status = filters.status;
    if (filters.selected_day) params.selected_day = filters.selected_day;
    if (filters.active_only === true) params.active_only = 1;

    const response = await adminTransportApi.getManifest(params);

    if (response.data.success) {
      subscriptions.value = response.data.data?.subscriptions || [];
      currentPage.value = 1;
    }
  } catch (err) {
    console.error('Failed to load manifest:', err);
    error.value = err.response?.data?.message || 'Failed to load subscriptions';
    subscriptions.value = [];
  } finally {
    loading.value = false;
  }
};

const exportExcel = () => {
  if (subscriptions.value.length === 0) {
    toast.warning('No data to export');
    return;
  }

  // Generate CSV content
  const headers = ['#', 'Student Name', 'Email', 'Route', 'Plan', 'Days/Week', 'Selected Days', 'Start Date', 'End Date', 'Amount (EGP)', 'Status'];
  
  const rows = subscriptions.value.map((sub, index) => [
    index + 1,
    sub.student.name,
    sub.student.email,
    sub.route.name_en,
    sub.plan?.name_en || `Legacy (${sub.plan_type})`,
    sub.plan?.allowed_days_per_week || sub.selected_days?.length || 'N/A',
    (sub.selected_days || []).join(', ') || 'N/A',
    formatDate(sub.starts_at),
    formatDate(sub.ends_at),
    parseFloat(sub.amount_paid_expected).toFixed(2),
    sub.status
  ]);

  let csvContent = headers.join(',') + '\n';
  rows.forEach(row => {
    const escapedRow = row.map(field => {
      const str = String(field);
      if (str.includes(',') || str.includes('"') || str.includes('\n')) {
        return `"${str.replace(/"/g, '""')}"`;
      }
      return str;
    });
    csvContent += escapedRow.join(',') + '\n';
  });

  // Create and download file
  const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `transport_subscriptions_${new Date().toISOString().split('T')[0]}.csv`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);

  toast.success('Subscriptions exported successfully!');
};

const formatDay = (day) => {
  const dayMap = {
    saturday: 'Sat',
    sunday: 'Sun',
    monday: 'Mon',
    tuesday: 'Tue',
    wednesday: 'Wed',
    thursday: 'Thu',
  };
  return dayMap[day] || day;
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const getStatusVariant = (status) => {
  const variants = {
    active: 'success',
    waitlisted: 'warning',
    cancelled: 'danger',
  };
  return variants[status] || 'secondary';
};

const getStatusIcon = (status) => {
  const icons = {
    active: markRaw(CheckCircle),
    waitlisted: markRaw(Clock),
    cancelled: markRaw(XCircle),
  };
  return icons[status] || markRaw(AlertCircle);
};

// Watch filters
watch(filters, () => {
  fetchManifest();
});

// Lifecycle
onMounted(() => {
  fetchRoutes();
  fetchManifest();
});
</script>

<style scoped>
.filters-card {
  margin-bottom: var(--spacing-xl);
}

.card-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-title {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.header-title h3 {
  margin: 0;
  font-size: var(--font-lg);
  font-weight: var(--fw-semibold);
  color: var(--color-textStrong);
}

.header-icon {
  width: 20px;
  height: 20px;
  color: var(--color-primary);
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: var(--spacing-lg);
  align-items: flex-end;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.form-group label {
  font-size: var(--font-xs);
  font-weight: var(--fw-semibold);
  color: var(--color-textMuted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-group select {
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--font-sm);
  background: var(--color-surface);
  color: var(--color-textMain);
  transition: all var(--transition-fast);
}

.form-group select:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(var(--color-primary-rgb), 0.1);
}

.checkbox-wrapper {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) 0;
}

.checkbox-wrapper input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: var(--color-primary);
}

.checkbox-wrapper label {
  font-size: var(--font-sm);
  font-weight: normal;
  text-transform: none;
  color: var(--color-textMain);
  cursor: pointer;
}

.form-group.actions {
  display: flex;
  flex-direction: row;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}

.btn-icon {
  width: 16px;
  height: 16px;
  margin-right: 6px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: var(--spacing-lg);
  margin-bottom: var(--spacing-xl);
}

.stat-card {
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.stat-content {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  padding: var(--spacing-md);
}

.stat-icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: var(--radius-md);
}

.stat-icon-wrapper.blue {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.stat-icon-wrapper.green {
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
}

.stat-icon-wrapper.orange {
  background: rgba(249, 115, 22, 0.1);
  color: #f97316;
}

.stat-icon-wrapper.purple {
  background: rgba(139, 92, 246, 0.1);
  color: #8b5cf6;
}

.stat-icon {
  width: 24px;
  height: 24px;
}

.stat-details {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.stat-label {
  font-size: var(--font-xs);
  color: var(--color-textMuted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  font-size: var(--font-xl);
  font-weight: var(--fw-bold);
  color: var(--color-textStrong);
}

.loading-state,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-3xl);
  gap: var(--spacing-lg);
}

.error-icon {
  width: 48px;
  height: 48px;
  color: var(--color-danger);
}

.error-state p {
  color: var(--color-danger);
  font-size: var(--font-md);
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.count-badge {
  background: var(--color-primaryBg);
  color: var(--color-primary);
  padding: var(--spacing-xs) var(--spacing-md);
  border-radius: var(--radius-full);
  font-size: var(--font-sm);
  font-weight: var(--fw-semibold);
}

.table-wrapper {
  overflow-x: auto;
}

.manifest-table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--font-sm);
}

.manifest-table th,
.manifest-table td {
  padding: var(--spacing-md);
  text-align: left;
  border-bottom: 1px solid var(--color-borderLight);
}

.manifest-table th {
  background: var(--color-background);
  font-size: var(--font-xs);
  font-weight: var(--fw-bold);
  color: var(--color-textMuted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  position: sticky;
  top: 0;
  white-space: nowrap;
}

.manifest-table tbody tr {
  transition: background var(--transition-fast);
}

.manifest-table tbody tr:hover {
  background: var(--color-surfaceHighlight);
}

.col-num {
  width: 50px;
  text-align: center;
}

.student-cell {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.student-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.student-info .email {
  font-size: var(--font-xs);
  color: var(--color-textMuted);
}

.route-cell {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
}

.cell-icon {
  width: 16px;
  height: 16px;
  color: var(--color-textMuted);
  flex-shrink: 0;
}

.days-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.day-badge {
  display: inline-block;
  padding: 2px 8px;
  background: var(--color-primaryBg);
  color: var(--color-primary);
  border-radius: var(--radius-sm);
  font-size: var(--font-xs);
  font-weight: var(--fw-medium);
}

.period-cell {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.period-info {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  font-size: var(--font-xs);
}

.period-info .date {
  white-space: nowrap;
  background: var(--color-background);
  padding: 2px 6px;
  border-radius: var(--radius-sm);
}

.arrow-icon {
  width: 12px;
  height: 12px;
  color: var(--color-textMuted);
}

.amount-cell {
  color: var(--color-success);
  font-weight: var(--fw-semibold);
}

.badge-icon {
  width: 12px;
  height: 12px;
  margin-right: 4px;
}

.text-center {
  text-align: center;
}

.text-muted {
  color: var(--color-textMuted);
  font-style: italic;
  font-size: var(--font-xs);
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: var(--spacing-md);
  padding: var(--spacing-lg);
  border-top: 1px solid var(--color-borderLight);
}

.page-info {
  font-size: var(--font-sm);
  color: var(--color-textMuted);
}

@media (max-width: 1200px) {
  .table-wrapper {
    overflow-x: scroll;
  }
  
  .manifest-table {
    min-width: 1000px;
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .form-group.actions {
    flex-direction: column;
  }
}
</style>
