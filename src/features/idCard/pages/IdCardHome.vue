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
    <div v-if="loading" class="services-grid">
      <div v-for="i in 3" :key="i" class="loading-card">
        <SkeletonLoader height="80px" border-radius="0.75rem" />
        <SkeletonLoader height="24px" width="70%" />
        <SkeletonLoader height="36px" width="120px" border-radius="9999px" />
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
    <section v-if="!loading && serviceEnabled && types.length" class="quick-actions-section">
      <div class="action-content">
        <h3 class="action-title">My ID Card Requests</h3>
        <p class="action-description">View the status of your previous ID card service requests, track approval progress, and view history.</p>
        <Button variant="secondary" @click="navigateToMyRequests" class="action-button">
          View My Requests →
        </Button>
      </div>
      
      <!-- Decorative background elements -->
      <div class="deco-circle circle-1"></div>
      <div class="deco-circle circle-2"></div>
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

.services-grid {
  display: grid;
  grid-template-columns: repeat(1, 1fr);
  gap: 24px; /* Consistent base gap */
  margin-bottom: 40px;
}

@media (min-width: 768px) {
  .services-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1100px) { /* Increased breakpoint for 3 columns */
  .services-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 32px; /* Standard Spacious Gap */
  }
}

/* Loading Skeleton Matches Grid */
.loading-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 16px;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  height: 300px; /* Approximate card height */
}

/* Refined Quick Actions (Footer Banner) */
.quick-actions-section {
  margin-top: 48px;
  background: linear-gradient(135deg, white 0%, #f8fafc 100%); /* Subtle gradient */
  border: 1px solid var(--color-border);
  border-radius: 20px; /* Slightly more rounded than cards */
  padding: 40px;
  text-align: center;
  position: relative;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); /* Soft floating shadow */
}

.action-content {
  position: relative;
  z-index: 10;
  max-width: 600px;
  margin: 0 auto;
}

.action-title {
  font-size: 22px;
  font-weight: 800;
  color: var(--color-textMain);
  margin-bottom: 8px;
  letter-spacing: -0.5px;
}

.action-description {
  font-size: 15px;
  color: var(--color-textSecondary);
  margin-bottom: 24px;
  line-height: 1.6;
}

.action-button {
  background: white;
  color: var(--color-primary);
  border: 1px solid var(--color-borderLight);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  padding: 10px 24px;
  font-weight: 600;
  border-radius: 50px; /* Pill shape */
  font-size: 14px;
  transition: all 0.2s ease;
}

.action-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
  border-color: var(--color-primary);
}

/* Decorative Circles */
.deco-circle {
  position: absolute;
  border-radius: 50%;
  filter: blur(50px);
  opacity: 0.5;
  pointer-events: none;
}

.circle-1 {
  width: 300px;
  height: 300px;
  top: -100px;
  right: -50px;
  background: var(--color-primaryLight);
  opacity: 0.15;
}

.circle-2 {
  width: 250px;
  height: 250px;
  bottom: -80px;
  left: -50px;
  background: var(--color-infoBg);
  opacity: 0.2;
}
</style>
