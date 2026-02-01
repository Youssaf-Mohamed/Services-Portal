<template>
  <PortalLayout>
    <PageHeader
      title="My Requests"
      subtitle="View and track all your service requests across the portal"
      :breadcrumbs="[
        { label: 'Home', to: '/student' },
        { label: 'My Requests' }
      ]"
    />

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <button
        v-for="filter in filters"
        :key="filter.value"
        :class="['filter-tab', { active: activeFilter === filter.value }]"
        @click="activeFilter = filter.value"
      >
        {{ filter.label }}
        <span v-if="getCount(filter.value)" class="count-badge">{{ getCount(filter.value) }}</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-list">
      <div v-for="i in 5" :key="i" class="skeleton-card">
        <SkeletonLoader height="80px" border-radius="var(--radius-md)" />
      </div>
    </div>

    <!-- Error State -->
    <EmptyState
      v-else-if="error"
      icon="⚠️"
      title="Failed to Load Requests"
      :message="error"
      actionText="Retry"
      @action="fetchRequests"
    />

    <!-- Empty State -->
    <EmptyState
      v-else-if="filteredRequests.length === 0"
      icon="📋"
      :title="emptyTitle"
      :message="emptyMessage"
      :actionText="activeFilter !== 'all' ? 'View All Requests' : null"
      @action="activeFilter = 'all'"
    />

    <!-- Table Header Row -->
    <div v-if="filteredRequests.length > 0" class="table-header">
      <div class="col service">Service</div>
      <div class="col type">Type</div>
      <div class="col status">Status</div>
      <div class="col amount">Amount</div>
      <div class="col date">Date</div>
      <div class="col actions">Actions</div>
    </div>

    <!-- Requests List -->
    <div v-if="!loading && filteredRequests.length" class="requests-list">
      <UnifiedRequestCard
        v-for="request in filteredRequests"
        :key="`${request.module}-${request.id}`"
        :request="request"
      />
    </div>

    <!-- Empty/Loading States -->
    <div v-else-if="loading" class="loading-list">
       <SkeletonLoader v-for="i in 4" :key="i" height="70px" width="100%" border-radius="var(--radius-lg)" />
    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { PageHeader, EmptyState, SkeletonLoader } from '@/components/ui';
import UnifiedRequestCard from '../components/UnifiedRequestCard.vue';
import { unifiedRequestsApi } from '@/features/idCard/api/idCard.api';

const loading = ref(false);
const error = ref('');
const requests = ref([]);
const activeFilter = ref('all');

const filters = [
  { label: 'All', value: 'all' },
  { label: 'Transport', value: 'transport' },
  { label: 'ID Card', value: 'id_card' }
];

const filteredRequests = computed(() => {
  if (activeFilter.value === 'all') {
    return requests.value;
  }
  return requests.value.filter(r => r.module === activeFilter.value);
});

const getCount = (filterValue) => {
  if (filterValue === 'all') return requests.value.length;
  return requests.value.filter(r => r.module === filterValue).length;
};

const emptyTitle = computed(() => {
  if (activeFilter.value === 'all') return 'No Requests Yet';
  if (activeFilter.value === 'transport') return 'No Transport Requests';
  return 'No ID Card Requests';
});

const emptyMessage = computed(() => {
  if (activeFilter.value === 'all') {
    return 'You haven\'t submitted any service requests yet. Browse our available services to get started.';
  }
  if (activeFilter.value === 'transport') {
    return 'You don\'t have any transport subscription requests.';
  }
  return 'You don\'t have any ID card service requests.';
});

const fetchRequests = async () => {
  loading.value = true;
  error.value = '';
  
  try {
    const response = await unifiedRequestsApi.getAll();
    requests.value = response.data || [];
  } catch (err) {
    console.error('Failed to load requests:', err);
    error.value = err.message || 'Failed to load requests';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchRequests();
});
</script>

<style scoped>
.page-container {
  max-width: 1200px;
  margin: 0 auto;
}

/* Modern Segmented Control Filters */
.filter-tabs {
  display: inline-flex;
  padding: 6px;
  margin-bottom: var(--spacing-xl);
  background: white;
  border-radius: var(--radius-xl);
  border: 1px solid var(--color-border);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); /* Clean floating look */
}

.filter-tab {
  padding: 8px 20px;
  border: none;
  background: transparent;
  border-radius: var(--radius-lg);
  cursor: pointer;
  font-weight: 600;
  font-size: 13px;
  color: var(--color-textMuted);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-tab:hover {
  color: var(--color-textMain);
}

.filter-tab.active {
  background: var(--color-surfaceHighlight); 
  color: var(--color-primary);
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.count-badge {
  padding: 1px 7px;
  min-width: 18px;
  text-align: center;
  border-radius: 99px;
  background: var(--color-border);
  font-size: 10px;
  font-weight: 700;
  color: var(--color-textMuted);
}

.filter-tab.active .count-badge {
  background: var(--color-primary);
  color: white;
}

/* Table Header */
.table-header {
  display: grid;
  grid-template-columns: 280px 1.5fr 140px 100px 120px 100px;
  gap: var(--spacing-lg);
  padding: var(--spacing-md) var(--spacing-xl);
  margin-bottom: var(--spacing-xs);
  border-bottom: 2px solid var(--color-border);
}

.col {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--color-textMuted);
}

/* Alignment */
.col.amount, .col.actions { text-align: right; }
.col.status { text-align: center; }

/* Requests List */
.requests-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.loading-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

@media (max-width: 1000px) {
  .table-header { display: none; } /* Hide header on mobile/tablet */
}
</style>
