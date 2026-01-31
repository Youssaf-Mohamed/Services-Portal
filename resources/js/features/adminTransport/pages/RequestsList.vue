<template>
  <PortalLayout>
    <PageHeader
      title="Subscription Requests"
      subtitle="Review and process transport subscription requests"
      :breadcrumbs="[
        { label: 'Admin', to: '/admin/transport' },
        { label: 'Requests' }
      ]"
    />

    <!-- Filters -->
    <div class="filters-section">
      <div class="filters-row">
        <div class="filter-group">
          <label>Status</label>
          <select v-model="filters.status" @change="fetchRequests">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Route</label>
          <select v-model="filters.route_id" @change="fetchRequests">
            <option value="">All Routes</option>
            <option v-for="route in routes" :key="route.id" :value="route.id">
              {{ route.name_en }}
            </option>
          </select>
        </div>
        <div class="filter-group">
          <label>Search</label>
          <input 
            type="text" 
            v-model="filters.search" 
            placeholder="Name or email..."
          />
        </div>
        <Button variant="secondary" size="sm" @click="resetFilters">
          Reset
        </Button>
      </div>
    </div>

    <!-- Bulk Actions Toolbar -->
    <div v-show="selectedIds.length > 0" class="bulk-actions-toolbar">
        <div class="selection-info">
            <span class="count">{{ selectedIds.length }}</span>
            <span>requests selected</span>
        </div>
        <div class="actions">
            <Button size="sm" variant="success" @click="handleBulkApprove" :disabled="processingBulk">
                Approve Selected
            </Button>
            <div style="width: 8px; display: inline-block;"></div>
            <Button size="sm" variant="danger" @click="handleBulkReject" :disabled="processingBulk">
                Reject Selected
            </Button>
        </div>
    </div>

    <!-- Data Table -->
    <DataTable
      :columns="columns"
      :data="requests"
      :loading="loading"
      :error="error"
      :pagination="pagination"
      selectable
      :selected-ids="selectedIds"
      @update:selectedIds="selectedIds = $event"
      @page-change="goToPage"
      @retry="fetchRequests"
      :disable-row-selection="(row) => row.status !== 'pending'"
    >
      <!-- Custom Slot: Student Info -->
      <template #cell-user="{ row }">
        <div class="student-info">
          <span class="student-name">{{ row.user.name }}</span>
          <span class="student-email">{{ row.user.email }}</span>
        </div>
      </template>

      <!-- Custom Slot: Route -->
      <template #cell-route="{ value }">
        {{ value?.name_en || 'N/A' }}
      </template>

      <!-- Custom Slot: Status -->
      <template #cell-status="{ value }">
        <StatusBadge :status="value" />
      </template>
      
      <!-- Custom Slot: Plan Type with regular Badge -->
      <template #cell-plan_type="{ value }">
         <Badge :variant="value === 'monthly' ? 'info' : 'secondary'">
            {{ value }}
         </Badge>
      </template>

       <!-- Custom Slot: Actions -->
      <template #cell-actions="{ row }">
        <router-link 
          :to="`/admin/transport/requests/${row.id}`"
          class="action-link"
        >
          View Details
        </router-link>
      </template>
    </DataTable>

  </PortalLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { PageHeader, Button, Badge, DataTable, StatusBadge } from '@/components/ui';
import { adminTransportApi } from '../api/adminTransport.api';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const loading = ref(true);
const processingBulk = ref(false);
const error = ref(null);
const requests = ref([]);
const routes = ref([]);
const selectedIds = ref([]);

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
});

const columns = [
  { key: 'id', label: 'ID', width: '60px' },
  { key: 'user', label: 'Student' },
  { key: 'route', label: 'Route' },
  { key: 'plan_type', label: 'Plan' },
  { key: 'status', label: 'Status' },
  { key: 'created_at', label: 'Date', format: 'date' },
  { key: 'actions', label: 'Actions', align: 'right' }
];

const handleBulkApprove = async () => {
    if (!confirm(`Approve ${selectedIds.value.length} requests?`)) return;
    
    processingBulk.value = true;
    try {
        const response = await adminTransportApi.bulkApprove(selectedIds.value);
        if (response.data.success) {
            toast.success(response.data.data.message);
            selectedIds.value = [];
            fetchRequests();
        }
    } catch (err) {
        toast.error(err.response?.data?.message || 'Bulk approve failed');
    } finally {
        processingBulk.value = false;
    }
};

const handleBulkReject = async () => {
    const reason = prompt('Enter rejection reason for selected requests:');
    if (!reason) return;

    processingBulk.value = true;
    try {
        const response = await adminTransportApi.bulkReject(selectedIds.value, reason);
        if (response.data.success) {
            toast.success(response.data.data.message);
            selectedIds.value = [];
            fetchRequests();
        }
    } catch (err) {
        toast.error(err.response?.data?.message || 'Bulk reject failed');
    } finally {
        processingBulk.value = false;
    }
};

const filters = reactive({
  status: '',
  route_id: '',
  search: '',
  page: 1,
});

// Debounce Utility
const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const debouncedSearch = debounce(() => {
    filters.page = 1; // Reset to first page
    fetchRequests();
}, 400);

watch(() => filters.search, (newVal) => {
    debouncedSearch();
});

// Initialize filters from query params
const initFiltersFromQuery = () => {
  if (route.query.status) filters.status = route.query.status;
  if (route.query.route_id) filters.route_id = route.query.route_id;
  if (route.query.search) filters.search = route.query.search;
  if (route.query.page) filters.page = parseInt(route.query.page);
};

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

const fetchRequests = async () => {
  try {
    loading.value = true;
    error.value = null;

    // Update URL with filters
    const query = {};
    if (filters.status) query.status = filters.status;
    if (filters.route_id) query.route_id = filters.route_id;
    if (filters.search) query.search = filters.search;
    if (filters.page > 1) query.page = filters.page;
    router.replace({ query });

    const response = await adminTransportApi.getRequests({
      status: filters.status || undefined,
      route_id: filters.route_id || undefined,
      search: filters.search || undefined,
      page: filters.page,
    });

    if (response.data.success) {
      requests.value = response.data.data.requests;
      Object.assign(pagination, response.data.data.pagination);
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load requests';
  } finally {
    loading.value = false;
  }
};

const resetFilters = () => {
  filters.status = '';
  filters.route_id = '';
  filters.search = '';
  filters.page = 1;
  fetchRequests();
};

const goToPage = (page) => {
  filters.page = page;
  fetchRequests();
};

onMounted(() => {
  initFiltersFromQuery();
  fetchRoutes();
  fetchRequests();
});
</script>

<style scoped>
.filters-section {
  background: white;
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg);
  margin-bottom: var(--spacing-xl);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
}

.filters-row {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-md);
  align-items: flex-end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.filter-group label {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-textMuted);
  text-transform: uppercase;
}

.filter-group select,
.filter-group input {
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 14px;
  min-width: 160px;
}

.filter-group select:focus,
.filter-group input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: var(--shadow-focusRing);
}

.student-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.student-name {
  font-weight: 500;
  color: var(--color-textMain);
}

.student-email {
  font-size: 12px;
  color: var(--color-textMuted);
}

.action-link {
  color: var(--color-primary);
  text-decoration: none;
  font-weight: 500;
  font-size: 14px;
}

.action-link:hover {
  text-decoration: underline;
}

@media (max-width: 768px) {
  .filters-row {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-group select,
  .filter-group input {
    width: 100%;
  }
}

.bulk-actions-toolbar {
    background: var(--color-surfaceHighlight);
    border: 1px solid var(--color-primary);
    border-radius: var(--radius-lg);
    padding: var(--spacing-md) var(--spacing-lg);
    margin-bottom: var(--spacing-lg);
    display: flex;
    align-items: center;
    justify-content: space-between;
    animation: slideDown 0.2s ease-out;
    position: sticky;
    top: 20px;
    z-index: 10;
}

.selection-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}

.selection-info .count {
    background: var(--color-primary);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
