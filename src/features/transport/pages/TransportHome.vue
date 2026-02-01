<template>
  <PortalLayout>
    <PageHeader
      title="Transportation"
      subtitle="Browse available routes and subscribe to your preferred transportation plan"
      :breadcrumbs="[
        { label: 'Home', to: '/student/transport' },
        { label: 'Transportation' }
      ]"
    />

    <!-- Loading State -->
    <div v-if="loading" class="loading-grid">
      <div v-for="i in 6" :key="i" class="skeleton-card">
        <SkeletonLoader height="180px" border-radius="var(--radius-md)" />
        <SkeletonLoader height="24px" width="70%" />
        <div style="display: flex; gap: 8px;">
          <SkeletonLoader height="32px" width="100px" border-radius="var(--radius-full)" />
          <SkeletonLoader height="32px" width="80px" border-radius="var(--radius-full)" />
        </div>
      </div>
    </div>

    <!-- Error State -->
    <EmptyState
      v-else-if="error"
      icon="⚠️"
      title="Failed to Load Routes"
      :message="error"
      actionText="Retry"
      @action="fetchRoutes"
    />

    <!-- Routes Grid -->
    <div v-else class="routes-grid">
      <RouteCard
        v-for="route in routes"
        :key="route.id"
        :route="route"
        :active-subscription="activeSubscription"
        :show-capacity="settings?.show_capacity ?? true"
        @subscribe="openSubscriptionModal"
      />
    </div>

    <!-- Subscription Modal -->
    <SubscribeModal
      v-if="showModal"
      :route="selectedRoute"
      :settings="settings"
      @close="closeSubscriptionModal"
      @submitted="handleSubmissionComplete"
    />
  </PortalLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { PageHeader, EmptyState, SkeletonLoader } from '@/components/ui';
import RouteCard from '../components/RouteCard.vue';
import SubscribeModal from '../components/SubscribeModal.vue';
import { transportApi } from '../api/transport.api';

const loading = ref(false);
const error = ref('');
const routes = ref([]);
const settings = ref(null);
const activeSubscription = ref(null);
const showModal = ref(false);
const selectedRoute = ref(null);

/**
 * Fetch routes and settings from backend
 */
const fetchRoutes = async () => {
  loading.value = true;
  error.value = '';
  
  try {
    // Fetch routes and settings in parallel
    const [routesResponse, settingsResponse, subscriptionResponse] = await Promise.all([
      transportApi.getRoutes(),
      transportApi.getSettings(),
      transportApi.getMySubscription()
    ]);
    
    routes.value = routesResponse.data || [];
    settings.value = settingsResponse.data || null;
    activeSubscription.value = subscriptionResponse.data || null;
  } catch (err) {
    console.error('Failed to load transport data:', err);
    error.value = err.message || 'Failed to load routes';
  } finally {
    loading.value = false;
  }
};

const openSubscriptionModal = (route) => {
  selectedRoute.value = route;
  showModal.value = true;
};

const closeSubscriptionModal = () => {
  showModal.value = false;
  selectedRoute.value = null;
};

const handleSubmissionComplete = () => {
  // Refresh data to update UI with new subscription status
  fetchRoutes();
  console.log('Subscription request submitted successfully');
};

onMounted(() => {
  fetchRoutes();
});
</script>

<style scoped>
.loading-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--spacing-xl);
}

.skeleton-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-xl);
  height: 280px;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.routes-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--spacing-xl);
}

@media (max-width: 1200px) {
  .routes-grid, .loading-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .routes-grid, .loading-grid {
    grid-template-columns: 1fr;
  }
}
</style>
