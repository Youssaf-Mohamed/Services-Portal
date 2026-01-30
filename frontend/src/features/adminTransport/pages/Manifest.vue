<template>
  <PortalLayout>
    <PageHeader
      title="Passenger Manifest"
      subtitle="View and export passenger lists for specific routes and schedules"
      :breadcrumbs="[
        { label: 'Admin', to: '/admin/transport' },
        { label: 'Manifest' }
      ]"
    />

    <!-- Filters -->
    <Card class="filters-card">
      <div class="filters-grid">
        <div class="form-group">
          <label>Route *</label>
          <select v-model="filters.route_id" @change="clearManifest">
            <option value="">Select Route</option>
            <option v-for="route in routes" :key="route.id" :value="route.id">
              {{ route.name_en }}
            </option>
          </select>
        </div>
        <div class="form-group">
          <label>Day *</label>
          <select v-model="filters.day_of_week" @change="clearManifest">
            <option v-for="(day, index) in dayNames" :key="index" :value="index">
              {{ day }}
            </option>
          </select>
        </div>
        <div class="form-group">
          <label>Time *</label>
          <input type="time" v-model="filters.time" @change="clearManifest" />
        </div>
        <div class="form-group actions">
          <Button variant="primary" @click="fetchManifest" :disabled="!isValid">
            Load Manifest
          </Button>
          <Button 
            variant="secondary" 
            @click="exportCsv" 
            :disabled="!manifest || manifest.length === 0"
          >
            📥 Export CSV
          </Button>
        </div>
      </div>
    </Card>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <SkeletonLoader height="100px" style="margin-bottom: 24px;" />
      <SkeletonLoader height="300px" />
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
    </div>

    <!-- Manifest Content -->
    <div v-else-if="slotInfo" class="manifest-content">
      <!-- Summary -->
      <Card class="summary-card">
        <div class="summary-grid">
          <div class="summary-item">
            <span class="label">Route</span>
            <span class="value">{{ slotInfo.route_name }}</span>
          </div>
          <div class="summary-item">
            <span class="label">Day</span>
            <span class="value">{{ getDayName(slotInfo.day_of_week) }}</span>
          </div>
          <div class="summary-item">
            <span class="label">Time</span>
            <span class="value">{{ slotInfo.time }}</span>
          </div>
          <div class="summary-item">
            <span class="label">Direction</span>
            <span class="value">{{ formatDirection(slotInfo.direction) }}</span>
          </div>
          <div class="summary-item highlight">
            <span class="label">Total Passengers</span>
            <span class="value">{{ totalPassengers }} / {{ slotInfo.capacity }}</span>
          </div>
        </div>
      </Card>

      <!-- Passenger List -->
      <Card>
        <template #header>
          <h3>Passenger List</h3>
        </template>
        
        <EmptyState
          v-if="manifest.length === 0"
          icon="👥"
          title="No Passengers"
          message="There are no active subscriptions with seat reservations for this slot."
        />

        <table v-else class="manifest-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Student Name</th>
              <th>Email</th>
              <th>Student ID</th>
              <th>Plan</th>
              <th>Valid Until</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(passenger, index) in manifest" :key="passenger.subscription_id">
              <td>{{ index + 1 }}</td>
              <td>{{ passenger.student_name }}</td>
              <td>{{ passenger.student_email }}</td>
              <td>{{ passenger.student_id || 'N/A' }}</td>
              <td>
                <Badge :variant="passenger.plan_type === 'monthly' ? 'info' : 'secondary'">
                  {{ passenger.plan_type }}
                </Badge>
              </td>
              <td>{{ passenger.end_date }}</td>
            </tr>
          </tbody>
        </table>
      </Card>
    </div>

    <!-- Initial State -->
    <EmptyState
      v-else
      icon="📊"
      title="Select Manifest Criteria"
      message="Choose a route, day, and time to view the passenger manifest."
    />
  </PortalLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Button from '@/components/ui/Button.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue';
import { adminTransportApi } from '../api/adminTransport.api';

const loading = ref(false);
const error = ref(null);
const routes = ref([]);
const slotInfo = ref(null);
const manifest = ref([]);
const totalPassengers = ref(0);

const filters = reactive({
  route_id: '',
  day_of_week: 0,
  time: '08:00',
});

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const isValid = computed(() => {
  return filters.route_id && filters.time;
});

const fetchRoutes = async () => {
  try {
    const response = await adminTransportApi.getRoutes();
    if (response.data.success) {
      routes.value = response.data.data.routes;
    }
  } catch (err) {
    console.error('Failed to fetch routes:', err);
  }
};

const fetchManifest = async () => {
  if (!isValid.value) return;

  try {
    loading.value = true;
    error.value = null;

    const response = await adminTransportApi.getManifest({
      route_id: filters.route_id,
      day_of_week: filters.day_of_week,
      time: filters.time,
    });

    if (response.data.success) {
      slotInfo.value = response.data.data.slot;
      manifest.value = response.data.data.manifest;
      totalPassengers.value = response.data.data.total_passengers;
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load manifest';
    slotInfo.value = null;
    manifest.value = [];
  } finally {
    loading.value = false;
  }
};

const clearManifest = () => {
  slotInfo.value = null;
  manifest.value = [];
  error.value = null;
};

const exportCsv = () => {
  const url = adminTransportApi.getManifestExportUrl({
    route_id: filters.route_id,
    day_of_week: filters.day_of_week,
    time: filters.time,
  });
  window.open(url, '_blank');
};

const getDayName = (day) => dayNames[day] || 'Unknown';

const formatDirection = (dir) => {
  return dir === 'round_trip' ? 'Round Trip' : 'One Way';
};

onMounted(fetchRoutes);
</script>

<style scoped>
.filters-card {
  margin-bottom: var(--spacing-xl);
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
  font-size: 12px;
  font-weight: 600;
  color: var(--color-textMuted);
  text-transform: uppercase;
}

.form-group select,
.form-group input {
  padding: 10px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 14px;
}

.form-group select:focus,
.form-group input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: var(--shadow-focusRing);
}

.form-group.actions {
  display: flex;
  flex-direction: row;
  gap: var(--spacing-sm);
  align-items: flex-end;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-3xl);
  color: var(--color-textMuted);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: var(--spacing-md);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-state {
  text-align: center;
  padding: var(--spacing-3xl);
  color: var(--color-danger);
}

.manifest-content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xl);
}

.summary-card {
  background: var(--color-successBg);
  border-color: var(--color-success);
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: var(--spacing-lg);
}

.summary-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.summary-item .label {
  font-size: 12px;
  color: var(--color-textMuted);
  text-transform: uppercase;
}

.summary-item .value {
  font-size: 16px;
  font-weight: 600;
  color: var(--color-textMain);
}

.summary-item.highlight .value {
  color: var(--color-primary);
  font-size: 20px;
}

.manifest-table {
  width: 100%;
  border-collapse: collapse;
}

.manifest-table th,
.manifest-table td {
  padding: var(--spacing-md) var(--spacing-lg);
  text-align: left;
  border-bottom: 1px solid var(--color-border);
}

.manifest-table th {
  background: #f9fafb;
  font-size: 12px;
  font-weight: 600;
  color: var(--color-textMuted);
  text-transform: uppercase;
}

.manifest-table tbody tr:hover {
  background: #f9fafb;
}
</style>
