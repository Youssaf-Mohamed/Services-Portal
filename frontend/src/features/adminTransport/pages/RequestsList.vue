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

    <!-- Loading State -->
    <div v-if="loading" class="table-container">
      <div style="padding: 24px;">
        <div style="margin-bottom: 20px; display: flex; gap: 20px;">
          <SkeletonLoader height="32px" width="100px" />
          <SkeletonLoader height="32px" width="100px" />
          <SkeletonLoader height="32px" width="200px" />
        </div>
        <div v-for="i in 5" :key="i" style="margin-bottom: 12px;">
           <SkeletonLoader height="48px" width="100%" />
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
      <Button variant="primary" @click="fetchRequests">Retry</Button>
    </div>

    <!-- Empty State -->
    <EmptyState
      v-else-if="requests.length === 0"
      icon="📋"
      title="No Requests Found"
      message="There are no subscription requests matching your filters."
    />

    <!-- Requests Table -->
    <div v-else class="table-container">
      <table class="requests-table">
        <thead>
          <tr>
            <th style="width: 40px">
                <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" />
            </th>
            <th>ID</th>
            <th>Student</th>
            <th>Route</th>
            <th>Plan</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="req in requests" :key="req.id">
            <td>
                <input 
                    type="checkbox" 
                    v-model="selectedIds" 
                    :value="req.id" 
                    v-if="req.status === 'pending'"
                />
            </td>
            <td>#{{ req.id }}</td>
            <td>
              <div class="student-info">
                <span class="student-name">{{ req.user.name }}</span>
                <span class="student-email">{{ req.user.email }}</span>
              </div>
            </td>
            <td>{{ req.route?.name_en || 'N/A' }}</td>
            <td>
              <Badge :variant="req.plan_type === 'monthly' ? 'info' : 'secondary'">
                {{ req.plan_type }}
              </Badge>
            </td>
            <td>
              <Badge :variant="getStatusVariant(req.status)">
                {{ req.status }}
              </Badge>
            </td>
            <td>{{ formatDate(req.created_at) }}</td>
            <td>
              <router-link 
                :to="`/admin/transport/requests/${req.id}`"
                class="action-link"
              >
                View Details
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="pagination">
        <Button 
          variant="secondary" 
          size="sm" 
          :disabled="pagination.current_page === 1"
          @click="goToPage(pagination.current_page - 1)"
        >
          Previous
        </Button>
        <span class="page-info">
          Page {{ pagination.current_page }} of {{ pagination.last_page }}
        </span>
        <Button 
          variant="secondary" 
          size="sm" 
          :disabled="pagination.current_page === pagination.last_page"
          @click="goToPage(pagination.current_page + 1)"
        >
          Next
        </Button>
      </div>
    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PortalLayout from '@/layouts/PortalLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Button from '@/components/ui/Button.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue';
import { adminTransportApi } from '../api/adminTransport.api';

const route = useRoute();
const router = useRouter();
import { useToast } from '@/composables/useToast';

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
  per_page: 15,
  total: 0,
});

const isAllSelected = computed(() => {
    return requests.value.length > 0 && selectedIds.value.length === requests.value.filter(r => r.status === 'pending').length;
});

const hasSelection = computed(() => selectedIds.value.length > 0);

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = requests.value
            .filter(r => r.status === 'pending')
            .map(r => r.id);
    }
};

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

import { watch } from 'vue';

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

const getStatusVariant = (status) => {
  switch (status) {
    case 'pending': return 'warning';
    case 'approved': return 'success';
    case 'rejected': return 'danger';
    default: return 'secondary';
  }
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
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

.table-container {
  background: white;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  overflow: hidden;
}

.requests-table {
  width: 100%;
  border-collapse: collapse;
}

.requests-table th,
.requests-table td {
  padding: var(--spacing-md) var(--spacing-lg);
  text-align: left;
  border-bottom: 1px solid var(--color-border);
}

.requests-table th {
  background: var(--color-surfaceHighlight);
  font-size: var(--font-xs);
  font-weight: var(--fw-bold);
  color: var(--color-textMuted);
  text-transform: uppercase;
}

.requests-table tbody tr:hover {
  background: var(--color-surfaceHighlight);
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

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-md);
  padding: var(--spacing-lg);
  border-top: 1px solid var(--color-border);
}

.page-info {
  font-size: 14px;
  color: var(--color-textMuted);
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

  .requests-table {
    font-size: 14px;
  }

  .requests-table th,
  .requests-table td {
    padding: var(--spacing-sm) var(--spacing-md);
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
