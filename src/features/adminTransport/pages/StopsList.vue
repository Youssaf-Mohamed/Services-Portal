<template>
  <PortalLayout>
    <PageHeader
      title="Bus Stops"
      subtitle="Manage bus stops and locations"
      :breadcrumbs="[
        { label: 'Admin', to: '/admin/transport' },
        { label: 'Stops' }
      ]"
    />

    <!-- Actions Bar -->
    <div class="actions-bar">
      <div class="search-bar">
         <!-- Future: Add search functionality -->
      </div>
      <Button variant="primary" @click="openCreateModal">
        + Add Stop
      </Button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="table-container">
      <div style="padding: 24px;">
         <div v-for="i in 6" :key="i" style="margin-bottom: 20px;">
           <SkeletonLoader height="40px" width="100%" />
         </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
      <Button variant="primary" @click="fetchStops">Retry</Button>
    </div>

    <!-- Stops Table -->
    <div v-else class="table-container">
      <table class="stops-table">
        <thead>
          <tr>
            <th>Name (EN)</th>
            <th>Name (AR)</th>
            <th>Coordinates</th>
            <th>Routes</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="stop in stops" :key="stop.id">
            <td>{{ stop.name_en }}</td>
            <td>{{ stop.name_ar }}</td>
            <td>
              <span v-if="stop.lat && stop.lng" class="coordinates">
                {{ Number(stop.lat).toFixed(4) }}, {{ Number(stop.lng).toFixed(4) }}
              </span>
              <span v-else class="text-muted">-</span>
            </td>
            <td>
               <!-- Showing number of routes passing through, if available from backend -->
               <Badge v-if="stop.routes_count !== undefined" variant="secondary">{{ stop.routes_count }} Routes</Badge>
               <span v-else>-</span>
            </td>
            <td>
              <div class="actions-cell">
                <Button variant="secondary" size="sm" @click="editStop(stop)">
                  Edit
                </Button>
                <Button variant="danger" size="sm" @click="confirmDelete(stop)">
                  Delete
                </Button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <EmptyState
        v-if="stops.length === 0"
        icon="🚏"
        title="No Stops Found"
        message="Create a new bus stop to get started."
      />
    </div>

    <!-- Create/Edit Modal -->
    <Modal v-if="showModal" :model-value="true" @close="closeModal">
      <template #header>
        <h3>{{ editingStop ? 'Edit Stop' : 'Create Stop' }}</h3>
      </template>
      <div class="modal-body">
        <div class="form-group">
          <label>Name (English) *</label>
          <input type="text" v-model="form.name_en" placeholder="e.g. Main Gate" />
        </div>
        <div class="form-group">
          <label>Name (Arabic) *</label>
          <input type="text" v-model="form.name_ar" placeholder="مثال: البوابة الرئيسية" dir="rtl" />
        </div>
        <div class="form-row">
           <div class="form-group">
             <label>Latitude</label>
             <input type="number" v-model="form.lat" step="any" placeholder="30.0444" />
           </div>
           <div class="form-group">
             <label>Longitude</label>
             <input type="number" v-model="form.lng" step="any" placeholder="31.2357" />
           </div>
        </div>

        <div v-if="formError" class="error-text">{{ formError }}</div>
      </div>
      <template #footer>
        <Button variant="secondary" @click="closeModal" :disabled="saving">
          Cancel
        </Button>
        <Button variant="primary" @click="saveStop" :disabled="saving">
          {{ saving ? 'Saving...' : 'Save' }}
        </Button>
      </template>
    </Modal>

    <!-- Delete Confirmation Modal -->
     <Modal v-if="showDeleteModal" :model-value="true" @close="closeDeleteModal">
      <template #header>
        <h3>Delete Stop</h3>
      </template>
      <div class="modal-body">
        <p>Are you sure you want to delete <strong>{{ stopToDelete?.name_en }}</strong>?</p>
        <p class="text-muted text-sm">This action cannot be undone. You cannot delete a stop that is assigned to a route.</p>

        <div v-if="deleteError" class="error-text">{{ deleteError }}</div>
      </div>
      <template #footer>
        <Button variant="secondary" @click="closeDeleteModal" :disabled="deleting">
          Cancel
        </Button>
        <Button variant="danger" @click="deleteStop" :disabled="deleting">
          {{ deleting ? 'Deleting...' : 'Delete' }}
        </Button>
      </template>
    </Modal>

  </PortalLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Button from '@/components/ui/Button.vue';
import Badge from '@/components/ui/Badge.vue';
import Modal from '@/components/ui/Modal.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue';
import { adminTransportApi } from '../api/adminTransport.api';
import { useToast } from '@/composables/useToast';

const toast = useToast();

const loading = ref(true);
const error = ref(null);
const stops = ref([]);

const showModal = ref(false);
const editingStop = ref(null);
const saving = ref(false);
const formError = ref(null);

const showDeleteModal = ref(false);
const stopToDelete = ref(null);
const deleting = ref(false);
const deleteError = ref(null);

const form = reactive({
  name_en: '',
  name_ar: '',
  lat: '',
  lng: '',
});

const fetchStops = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await adminTransportApi.getStops();
    if (response.data.success) {
      stops.value = response.data.data;
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load stops';
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  editingStop.value = null;
  resetForm();
  showModal.value = true;
};

const editStop = (stop) => {
  editingStop.value = stop;
  form.name_en = stop.name_en;
  form.name_ar = stop.name_ar;
  form.lat = stop.lat;
  form.lng = stop.lng;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingStop.value = null;
  formError.value = null;
  resetForm();
};

const resetForm = () => {
  form.name_en = '';
  form.name_ar = '';
  form.lat = '';
  form.lng = '';
};

const saveStop = async () => {
  try {
    saving.value = true;
    formError.value = null;

    if (!form.name_en || !form.name_ar) {
        formError.value = 'Please fill in all required fields';
        saving.value = false;
        return;
    }

    const payload = {
        name_en: form.name_en,
        name_ar: form.name_ar,
        lat: form.lat ? Number(form.lat) : null,
        lng: form.lng ? Number(form.lng) : null,
    };

    let response;
    if (editingStop.value) {
      response = await adminTransportApi.updateStop(editingStop.value.id, payload);
    } else {
      response = await adminTransportApi.createStop(payload);
    }

    if (response.data.success) {
      toast.success(editingStop.value ? 'Stop updated successfully' : 'Stop created successfully');
      closeModal();
      await fetchStops();
    }
  } catch (err) {
    console.error('Save stop error:', err);
    formError.value = err.response?.data?.message || err.message || 'Failed to save stop';
    toast.error(formError.value);
  } finally {
    saving.value = false;
  }
};

const confirmDelete = (stop) => {
    stopToDelete.value = stop;
    deleteError.value = null;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    stopToDelete.value = null;
    deleteError.value = null;
};

const deleteStop = async () => {
    if (!stopToDelete.value) return;

    try {
        deleting.value = true;
        const response = await adminTransportApi.deleteStop(stopToDelete.value.id);
        
        if (response.data.success) {
            toast.success('Stop deleted successfully');
            closeDeleteModal();
            await fetchStops();
        }
    } catch (err) {
        console.error('Delete stop error:', err);
        deleteError.value = err.response?.data?.message || err.message || 'Failed to delete stop';
        toast.error(deleteError.value);
    } finally {
        deleting.value = false;
    }
};

onMounted(() => {
  fetchStops();
});
</script>

<style scoped>
.actions-bar {
  display: flex;
  justify-content: flex-end; /* Align to right if no search bar yet */
  align-items: center;
  margin-bottom: var(--spacing-xl);
}

.table-container {
  background: white;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  overflow: hidden;
}

.stops-table {
  width: 100%;
  border-collapse: collapse;
}

.stops-table th,
.stops-table td {
  padding: var(--spacing-md) var(--spacing-lg);
  text-align: left;
  border-bottom: 1px solid var(--color-border);
}

.stops-table th {
  background: var(--color-surfaceHighlight);
  font-size: var(--font-xs);
  font-weight: var(--fw-bold);
  color: var(--color-textMuted);
  text-transform: uppercase;
}

.stops-table tbody tr:hover {
  background: var(--color-surfaceHighlight);
}

.coordinates {
    font-family: monospace;
    font-size: 0.9em;
    color: var(--color-textMuted);
}

.text-muted {
    color: var(--color-textMuted);
}

.text-sm {
    font-size: 0.875rem;
}

.actions-cell {
    display: flex;
    gap: var(--spacing-sm);
}

.modal-body {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.form-group label {
  font-size: 14px;
  font-weight: 500;
  color: var(--color-textMain);
}

.form-group input {
  padding: 10px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 14px;
}

.form-group input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: var(--shadow-focusRing);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-md);
}

.error-text {
  color: var(--color-danger);
  font-size: 14px;
}
</style>
