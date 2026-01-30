<template>
  <PortalLayout>
    <PageHeader
      title="My Requests"
      subtitle="Track the status of your transport subscription requests"
      :breadcrumbs="[
        { label: 'Home', to: '/student/transport' },
        { label: 'Transportation', to: '/student/transport' },
        { label: 'My Requests' }
      ]"
    />

    <!-- Loading State -->
    <div v-if="loading" class="loading-list">
      <div v-for="i in 3" :key="i" class="skeleton-request">
        <div style="display: flex; justify-content: space-between;">
          <SkeletonLoader height="24px" width="150px" />
          <SkeletonLoader height="24px" width="80px" border-radius="var(--radius-full)" />
        </div>
        <SkeletonLoader height="20px" width="40%" />
      </div>
    </div>

    <!-- Empty State -->
    <EmptyState
      v-else-if="requests.length === 0"
      icon="📋"
      title="No Requests Yet"
      message="You haven't submitted any transportation requests yet."
      actionText="Browse Routes"
      actionTo="/student/transport"
    />

    <!-- Requests Timeline -->
    <div v-else class="requests-timeline">
      <RequestCard
        v-for="request in requests"
        :key="request.id"
        :request="request"
      />
    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { PageHeader, EmptyState, SkeletonLoader } from '@/components/ui';
import RequestCard from '../components/RequestCard.vue';
import { transportApi } from '../api/transport.api';

const loading = ref(false);
const requests = ref([]);

const fetchRequests = async () => {
  loading.value = true;
  
  try {
    const response = await transportApi.getMyRequests();
    requests.value = response.data;
  } catch (error) {
    console.error('Failed to fetch requests:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchRequests();
});
</script>

<style scoped>
.loading-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.skeleton-request {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.requests-timeline {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}
</style>
