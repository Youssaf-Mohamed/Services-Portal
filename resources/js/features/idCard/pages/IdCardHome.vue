<template>
  <PortalLayout>
    <PageHeader
      title="ID Card Services"
      subtitle="Request ID card services - replacement, photo change, or report issues"
      :breadcrumbs="[
        { label: 'Home', to: '/student' },
        { label: 'ID Card Services' }
      ]"
    />

    <!-- Loading State -->
    <div v-if="loading" class="loading-grid">
      <div v-for="i in 3" :key="i" class="skeleton-card">
        <SkeletonLoader height="80px" border-radius="var(--radius-md)" />
        <SkeletonLoader height="24px" width="70%" />
        <SkeletonLoader height="36px" width="120px" border-radius="var(--radius-full)" />
      </div>
    </div>

    <!-- Service Disabled State -->
    <EmptyState
      v-else-if="!serviceEnabled"
      :icon="Ban"
      title="Service Temporarily Unavailable"
      message="ID card services are temporarily disabled. Please check back later."
    />

    <!-- Error State -->
    <EmptyState
      v-else-if="error"
      :icon="AlertCircle"
      title="Failed to Load Services"
      :message="error"
      actionText="Retry"
      @action="fetchData"
    />

    <!-- Service Types Grid -->
    <div v-else class="services-grid">
      <ServiceTypeCard
        v-for="type in types"
        :key="type.id"
        :type="type"
        @select="openSubmitModal"
      />
    </div>

    <!-- Quick Actions -->
    <section v-if="!loading && serviceEnabled && types.length" class="quick-actions">
      <h3>My ID Card Requests</h3>
      <p>View the status of your previous ID card service requests.</p>
      <Button variant="secondary" @click="navigateToMyRequests">
        View My Requests →
      </Button>
    </section>

    <!-- Submit Request Modal -->
    <SubmitRequestModal
      v-if="showModal && selectedType"
      :type="selectedType"
      :settings="settings"
      @close="closeSubmitModal"
      @submitted="handleSubmissionComplete"
    />
  </PortalLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { PageHeader, EmptyState, SkeletonLoader, Button } from '@/components/ui';
import ServiceTypeCard from '../components/ServiceTypeCard.vue';
import SubmitRequestModal from '../components/SubmitRequestModal.vue';
import { idCardApi } from '../api/idCard.api';
import { Ban, AlertCircle } from 'lucide-vue-next';

const router = useRouter();

const loading = ref(false);
const error = ref('');
const types = ref([]);
const settings = ref(null);
const showModal = ref(false);
const selectedType = ref(null);

const serviceEnabled = computed(() => settings.value?.service_enabled !== false);

/**
 * Fetch types and settings from backend
 */
const fetchData = async () => {
  loading.value = true;
  error.value = '';
  
  try {
    const [typesResponse, settingsResponse] = await Promise.all([
      idCardApi.getTypes(),
      idCardApi.getSettings()
    ]);
    
    types.value = typesResponse.data || [];
    settings.value = settingsResponse.data || null;
  } catch (err) {
    console.error('Failed to load ID card data:', err);
    error.value = err.message || 'Failed to load services';
  } finally {
    loading.value = false;
  }
};

const openSubmitModal = (type) => {
  selectedType.value = type;
  showModal.value = true;
};

const closeSubmitModal = () => {
  showModal.value = false;
  selectedType.value = null;
};

const handleSubmissionComplete = () => {
  // Refresh data if needed
  fetchData();
};

const navigateToMyRequests = () => {
  router.push('/student/my-requests');
};

onMounted(() => {
  fetchData();
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
  height: 200px;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.services-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--spacing-xl);
}

.quick-actions {
  margin-top: var(--spacing-2xl);
  padding: var(--spacing-xl);
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  text-align: center;
}

.quick-actions h3 {
  margin: 0 0 var(--spacing-sm) 0;
  color: var(--color-text-primary);
}

.quick-actions p {
  margin: 0 0 var(--spacing-lg) 0;
  color: var(--color-text-secondary);
}

@media (max-width: 1200px) {
  .services-grid, .loading-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .services-grid, .loading-grid {
    grid-template-columns: 1fr;
  }
}
</style>
