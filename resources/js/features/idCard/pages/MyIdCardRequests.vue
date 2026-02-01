<template>
  <PortalLayout>
    <PageHeader
      title="My ID Card Requests"
      subtitle="View the status of your ID card service requests"
      :breadcrumbs="[
        { label: 'Home', to: '/student' },
        { label: 'ID Card Services', to: '/student/id-card' },
        { label: 'My Requests' }
      ]"
    />

    <DataList
      :data="requests"
      :loading="loading"
      :error="error"
      empty-title="No Requests Yet"
      empty-message="You haven't submitted any ID card service requests yet."
      empty-action-text="Browse Services"
      @empty-action="goToServices"
      @retry="fetchRequests"
    >
       <template #default="{ item }">
          <IdCardRequestCard :request="item" @resubmit="handleResubmit" />
       </template>

       <!-- Custom Icon for Empty State -->
       <template #empty-icon>
          <ClipboardList class="feature-icon" style="width: 48px; height: 48px; color: var(--color-textMuted);" />
       </template>
    </DataList>

    <!-- Resubmit Modal -->
    <SubmitRequestModal
      v-if="showModal && editingRequest && settings"
      :type="editingRequest.type"
      :settings="settings"
      :edit-request="editingRequest"
      @close="closeModal"
      @submitted="handleSubmitted"
    />

  </PortalLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { PageHeader, DataList } from '@/components/ui';
import IdCardRequestCard from '../components/IdCardRequestCard.vue';
import SubmitRequestModal from '../components/SubmitRequestModal.vue';
import { idCardApi } from '../api/idCard.api';
import { ClipboardList } from 'lucide-vue-next';

const router = useRouter();

const loading = ref(false);
const error = ref('');
const requests = ref([]);
const settings = ref(null);

// Modal State
const showModal = ref(false);
const editingRequest = ref(null);

const fetchRequests = async () => {
  loading.value = true;
  error.value = '';
  
  try {
    const response = await idCardApi.getMyRequests();
    // Handle wrapped resource collection (response.data.data) or simple array
    requests.value = response.data?.data || response.data || [];
  } catch (err) {
    console.error('Failed to load requests:', err);
    error.value = err.message || 'Failed to load requests';
  } finally {
    loading.value = false;
  }
};

const fetchSettings = async () => {
  try {
    const response = await idCardApi.getSettings();
    settings.value = response.data;
  } catch (err) {
    console.error('Failed to load settings:', err);
  }
};

const handleResubmit = async (request) => {
  if (!settings.value) {
      await fetchSettings();
  }
  editingRequest.value = request;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingRequest.value = null;
};

const handleSubmitted = () => {
  closeModal();
  fetchRequests();
};

const goToServices = () => {
  router.push('/student/id-card');
};

onMounted(() => {
  fetchRequests();
  fetchSettings();
});
</script>

<style scoped>
/* Scoped styles mostly removed as DataList handles layout */
</style>
