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

    <DataList
      :data="requests"
      :loading="loading"
      empty-title="No Requests Yet"
      empty-message="You haven't submitted any transportation requests yet."
      empty-action-text="Browse Routes"
      empty-action-to="/student/transport"
    >
      <template #default="{ item }">
        <RequestCard :request="item" @resubmit="handleResubmit" />
      </template>

      <!-- Custom Loading Slot (Optional, but keeping original look) -->
      <template #loading>
         <div v-for="i in 3" :key="i" class="skeleton-request">
            <div style="display: flex; justify-content: space-between;">
              <SkeletonLoader height="24px" width="150px" />
              <SkeletonLoader height="24px" width="80px" border-radius="var(--radius-full)" />
            </div>
            <SkeletonLoader height="20px" width="40%" />
         </div>
      </template>
    </DataList>

    <!-- Resubmit Modal -->
    <SubscribeModal
      v-if="showModal && editingRoute"
      :route="editingRoute"
      :edit-request="editingRequest"
      @close="closeModal"
      @submitted="handleSubmitted"
    />
  </PortalLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { PageHeader, DataList, SkeletonLoader } from '@/components/ui';
import RequestCard from '../components/RequestCard.vue';
import SubscribeModal from '../components/SubscribeModal.vue';
import { transportApi } from '../api/transport.api';

const loading = ref(false);
const requests = ref([]);

// Modal State
const showModal = ref(false);
const editingRequest = ref(null);
const editingRoute = ref(null);

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

const handleResubmit = (request) => {
  editingRequest.value = request;
  // Ensure route is available. It should be nested in request.
  if (request.route) {
      editingRoute.value = request.route;
      showModal.value = true;
  } else {
      console.error('Route data missing in request:', request);
      // Fallback or error? Usually request.route should be loaded.
  }
};

const closeModal = () => {
  showModal.value = false;
  editingRequest.value = null;
  editingRoute.value = null;
};

const handleSubmitted = () => {
  closeModal();
  fetchRequests(); // Refresh list to show updated status
};

onMounted(() => {
  fetchRequests();
});
</script>

<style scoped>
.skeleton-request {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}
</style>
