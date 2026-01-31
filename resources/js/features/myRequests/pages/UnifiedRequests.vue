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

    <!-- Requests List -->
    <div v-else class="requests-list">
      <UnifiedRequestCard
        v-for="request in filteredRequests"
        :key="`${request.module}-${request.id}`"
        :request="request"
      />
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
.filter-tabs {
  display: flex;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-xl);
  padding: var(--spacing-sm);
  background: var(--color-surface);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
}

.filter-tab {
  padding: var(--spacing-sm) var(--spacing-lg);
  border: none;
  background: transparent;
  border-radius: var(--radius-md);
  cursor: pointer;
  font-weight: 500;
  color: var(--color-text-secondary);
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.filter-tab:hover {
  background: var(--color-background);
  color: var(--color-text-primary);
}

.filter-tab.active {
  background: var(--color-primary);
  color: white;
}

.count-badge {
  padding: 2px 8px;
  border-radius: var(--radius-full);
  background: rgba(255, 255, 255, 0.2);
  font-size: 0.75rem;
}

.filter-tab:not(.active) .count-badge {
  background: var(--color-background);
  color: var(--color-text-tertiary);
}

.loading-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.skeleton-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg);
}

.requests-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

@media (max-width: 768px) {
  .filter-tabs {
    flex-wrap: wrap;
  }
  
  .filter-tab {
    flex: 1;
    justify-content: center;
  }
}
</style>
