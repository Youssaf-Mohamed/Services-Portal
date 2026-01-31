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
          <IdCardRequestCard :request="item" />
       </template>

       <!-- Custom Icon for Empty State -->
       <template #empty-icon>
          <ClipboardList class="feature-icon" style="width: 48px; height: 48px; color: var(--color-textMuted);" />
       </template>
    </DataList>

  </PortalLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { PageHeader, DataList } from '@/components/ui';
import IdCardRequestCard from '../components/IdCardRequestCard.vue';
import { idCardApi } from '../api/idCard.api';
import { ClipboardList } from 'lucide-vue-next';

const router = useRouter();

const loading = ref(false);
const error = ref('');
const requests = ref([]);

const fetchRequests = async () => {
  loading.value = true;
  error.value = '';
  
  try {
    const response = await idCardApi.getMyRequests();
    requests.value = response.data || [];
  } catch (err) {
    console.error('Failed to load requests:', err);
    error.value = err.message || 'Failed to load requests';
  } finally {
    loading.value = false;
  }
};

const goToServices = () => {
  router.push('/student/id-card');
};

onMounted(() => {
  fetchRequests();
});
</script>

<style scoped>
/* Scoped styles mostly removed as DataList handles layout */
</style>
