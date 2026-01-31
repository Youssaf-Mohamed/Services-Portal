<template>
  <PortalLayout>
    <PageHeader
      title="ID Card Requests"
      subtitle="Manage and process ID card service requests"
      :breadcrumbs="[
        { label: 'Admin', to: '/admin/id-card' },
        { label: 'Requests' }
      ]"
    />

    <!-- Filters -->
    <div class="filters-bar">
      <div class="filter-group">
        <label>Status</label>
        <select v-model="filters.status" @change="fetchRequests">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="ready_for_pickup">Ready for Pickup</option>
          <option value="delivered">Delivered</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Type</label>
        <select v-model="filters.type_code" @change="fetchRequests">
          <option value="">All Types</option>
          <option value="lost">Lost Card</option>
          <option value="photo_change">Photo Change</option>
          <option value="damaged">Damaged/Issue</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Payment</label>
        <select v-model="filters.payment_status" @change="fetchRequests">
          <option value="">All</option>
          <option value="pending">Pending</option>
          <option value="verified">Verified</option>
          <option value="flagged">Flagged</option>
        </select>
      </div>

      <div class="filter-group search">
        <label>Search Student</label>
        <input
          v-model="filters.search"
          type="text"
          placeholder="Name or email..."
          @input="debouncedSearch"
        />
      </div>
    </div>

    <!-- Data Table -->
    <DataTable
      :columns="columns"
      :data="requests"
      :loading="loading"
      :error="error"
      :pagination="pagination"
      clickable-rows
      @row-click="(row) => goToDetails(row.id)"
      @page-change="goToPage"
      @retry="fetchRequests"
    >
      <!-- Custom Slot: Student Info -->
      <template #cell-user="{ row }">
        <div class="student-info">
          <span class="student-name">{{ row.user.name }}</span>
          <span class="student-email">{{ row.user.email }}</span>
        </div>
      </template>

      <!-- Custom Slot: Type with specific styling logic -->
      <template #cell-type="{ row }">
         <span class="type-badge" :class="row.type.code">
            {{ row.type.name_en }}
         </span>
      </template>

      <!-- Custom Slot: Amount -->
      <template #cell-amount="{ value }">
         <span class="amount-text">{{ value }} EGP</span>
      </template>

      <!-- Custom Slot: Status -->
      <template #cell-status_label="{ row }">
        <StatusBadge :status="row.status" :label="row.status_label" :variant="row.status_color" />
      </template>

      <!-- Custom Slot: Payment Status -->
      <template #cell-payment_status_label="{ row }">
        <StatusBadge :status="row.payment_status" :label="row.payment_status_label" :variant="row.payment_status_color" />
      </template>

      <!-- Custom Slot: Actions -->
      <template #cell-actions="{ row }">
          <button class="btn-icon" @click.stop="goToDetails(row.id)" title="View Details">
            👁️
          </button>
      </template>
    </DataTable>

  </PortalLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { PageHeader, DataTable, StatusBadge } from '@/components/ui';
import { adminIdCardApi } from '../api/adminIdCard.api';

const router = useRouter();
const route = useRoute();

const loading = ref(false);
const error = ref('');
const requests = ref([]);
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
});

const columns = [
  { key: 'id', label: 'ID', width: '60px' },
  { key: 'user', label: 'Student' },
  { key: 'type', label: 'Type' },
  { key: 'amount', label: 'Amount' },
  { key: 'status_label', label: 'Status' },
  { key: 'payment_status_label', label: 'Payment' },
  { key: 'created_at', label: 'Date', format: 'date' },
  { key: 'actions', label: 'Actions', align: 'center', width: '80px' }
];

const filters = reactive({
  status: '',
  type_code: '',
  payment_status: '',
  search: '',
  page: 1
});

let searchTimeout = null;

const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    filters.page = 1;
    fetchRequests();
  }, 300);
};

const fetchRequests = async () => {
  loading.value = true;
  error.value = '';
  
  try {
    const params = { ...filters };
    // Remove empty params
    Object.keys(params).forEach(key => {
      if (!params[key]) delete params[key];
    });
    
    const response = await adminIdCardApi.getRequests(params);
    requests.value = response.data?.requests || [];
    Object.assign(pagination, response.data?.pagination || {});
  } catch (err) {
    console.error('Failed to load requests:', err);
    error.value = err.message || 'Failed to load requests';
  } finally {
    loading.value = false;
  }
};

const goToDetails = (id) => {
  router.push(`/admin/id-card/requests/${id}`);
};

const goToPage = (page) => {
  filters.page = page;
  fetchRequests();
};

onMounted(() => {
  // Initialize from URL params
  if (route.query.status) filters.status = route.query.status;
  if (route.query.type_code) filters.type_code = route.query.type_code;
  if (route.query.payment_status) filters.payment_status = route.query.payment_status;
  
  fetchRequests();
});
</script>

<style scoped>
.filters-bar {
  display: flex;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-xl);
  flex-wrap: wrap;
  padding: var(--spacing-lg);
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.filter-group.search {
  flex: 1;
  min-width: 200px;
}

.filter-group label {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--color-textMuted);
  text-transform: uppercase;
}

.filter-group select,
.filter-group input {
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-background);
  color: var(--color-text-primary);
  min-width: 140px;
}

/* Custom styling for specific cells */
.student-info {
  display: flex;
  flex-direction: column;
}

.student-name {
  font-weight: 500;
  color: var(--color-textMain);
}

.student-email {
  font-size: 0.8rem;
  color: var(--color-textMuted);
}

.type-badge {
  font-size: 0.75rem;
  padding: 4px 8px;
  border-radius: var(--radius-full);
}

.type-badge.lost {
  background: rgba(240, 147, 251, 0.15);
  color: #f093fb;
}

.type-badge.photo_change {
  background: rgba(79, 172, 254, 0.15);
  color: #4facfe;
}

.type-badge.damaged {
  background: rgba(250, 112, 154, 0.15);
  color: #fa709a;
}

.amount-text {
  font-weight: 600;
  color: var(--color-primary);
}

.btn-icon {
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  padding: var(--spacing-xs);
  border-radius: var(--radius-sm);
  transition: background 0.2s ease;
}

.btn-icon:hover {
  background: var(--color-background);
}
</style>
