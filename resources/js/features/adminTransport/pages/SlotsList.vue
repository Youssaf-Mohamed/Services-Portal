<template>
  <PortalLayout>
    <PageHeader
      title="Schedule Slots"
      subtitle="Manage transport schedule slots and capacity"
      :breadcrumbs="[
        { label: 'Admin', to: '/admin/transport' },
        { label: 'Slots' }
      ]"
    />

    <!-- Filters & Actions -->
    <div class="actions-bar">
      <div class="filters">
        <select v-model="filterRouteId" @change="fetchSlots">
          <option value="">All Routes</option>
          <option v-for="route in routes" :key="route.id" :value="route.id">
            {{ route.name_en }}
          </option>
        </select>
      </div>
      <Button variant="primary" @click="showCreateModal = true">
        + Add Slot
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
      <Button variant="primary" @click="fetchSlots">Retry</Button>
    </div>

    <!-- Slots Table -->
    <div v-else class="table-container">
      <table class="slots-table">
        <thead>
          <tr>
            <th>Route</th>
            <th>Day</th>
            <th>Time</th>
            <th>Direction</th>
            <th>Capacity</th>
            <th>Reserved</th>
            <th>Available</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="slot in slots" :key="slot.id">
            <td>{{ slot.route?.name_en || 'N/A' }}</td>
            <td>{{ getDayName(slot.day_of_week) }}</td>
            <td>{{ slot.time }}</td>
            <td>{{ formatDirection(slot.direction) }}</td>
            <td>{{ slot.capacity }}</td>
            <td>{{ slot.active_reservations_count }}</td>
            <td>
              <Badge :variant="slot.capacity_remaining > 0 ? 'success' : 'danger'">
                {{ slot.capacity_remaining }}
              </Badge>
            </td>
            <td>
              <Badge :variant="slot.active ? 'success' : 'secondary'">
                {{ slot.active ? 'Active' : 'Inactive' }}
              </Badge>
            </td>
            <td>
              <Button variant="secondary" size="sm" @click="editSlot(slot)">
                Edit
              </Button>
            </td>
          </tr>
        </tbody>
      </table>

      <EmptyState
        v-if="slots.length === 0"
        icon="📅"
        title="No Slots Found"
        message="Create a new schedule slot to get started."
      />
    </div>

    <!-- Create/Edit Modal -->
    <Modal v-if="showCreateModal || editingSlot" :model-value="true" @close="closeModal">
      <template #header>
        <h3>{{ editingSlot ? 'Edit Slot' : 'Create Slot' }}</h3>
      </template>
      <div class="modal-body">
        <div class="form-group">
          <label>Route *</label>
          <select v-model="form.route_id" :disabled="!!editingSlot">
            <option value="">Select Route</option>
            <option v-for="route in routes" :key="route.id" :value="route.id">
              {{ route.name_en }}
            </option>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Day of Week *</label>
            <select v-model="form.day_of_week">
              <option v-for="(day, index) in dayNames" :key="index" :value="index">
                {{ day }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Time *</label>
            <input type="time" v-model="form.time" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Direction *</label>
            <select v-model="form.direction">
              <option value="one_way">One Way</option>
              <option value="round_trip">Round Trip</option>
            </select>
          </div>
          <div class="form-group">
            <label>Capacity *</label>
            <input type="number" v-model="form.capacity" min="1" />
          </div>
        </div>
        <div class="form-group checkbox-group">
          <label>
            <input type="checkbox" v-model="form.active" />
            Active
          </label>
        </div>

        <div v-if="formError" class="error-text">{{ formError }}</div>
      </div>
      <template #footer>
        <Button variant="secondary" @click="closeModal" :disabled="saving">
          Cancel
        </Button>
        <Button variant="primary" @click="saveSlot" :disabled="saving">
          {{ saving ? 'Saving...' : 'Save' }}
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
const slots = ref([]);
const routes = ref([]);
const filterRouteId = ref('');
const showCreateModal = ref(false);
const editingSlot = ref(null);
const saving = ref(false);
const formError = ref(null);

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const form = reactive({
  route_id: '',
  day_of_week: 0,
  time: '08:00',
  direction: 'one_way',
  capacity: 30,
  active: true,
});

const fetchRoutes = async () => {
  try {
    const response = await adminTransportApi.getRoutes();
    if (response.data.success) {
      routes.value = response.data.data.routes;
    }
  } catch (err) {
    console.error('Failed to fetch routes:', err);
  }
};

const fetchSlots = async () => {
  try {
    loading.value = true;
    error.value = null;
    const params = {};
    if (filterRouteId.value) params.route_id = filterRouteId.value;
    
    const response = await adminTransportApi.getSlots(params);
    if (response.data.success) {
      slots.value = response.data.data.slots;
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load slots';
  } finally {
    loading.value = false;
  }
};

const editSlot = (slot) => {
  editingSlot.value = slot;
  form.route_id = slot.route?.id || '';
  form.day_of_week = slot.day_of_week;
  form.time = slot.time;
  form.direction = slot.direction;
  form.capacity = slot.capacity;
  form.active = slot.active;
};

const closeModal = () => {
  showCreateModal.value = false;
  editingSlot.value = null;
  formError.value = null;
  resetForm();
};

const resetForm = () => {
  form.route_id = '';
  form.day_of_week = 0;
  form.time = '08:00';
  form.direction = 'one_way';
  form.capacity = 30;
  form.active = true;
};

const saveSlot = async () => {
  try {
    console.log('Starting saveSlot...');
    saving.value = true;
    formError.value = null;

    if (!form.route_id) {
       formError.value = 'Please select a route';
       saving.value = false;
       return;
    }

    const payload = {
      route_id: parseInt(form.route_id),
      day_of_week: parseInt(form.day_of_week),
      time: form.time,
      direction: form.direction,
      capacity: parseInt(form.capacity),
      active: Boolean(form.active),
    };
    
    console.log('Slot payload:', payload);

    let response;
    if (editingSlot.value) {
      console.log('Updating slot:', editingSlot.value.id);
      response = await adminTransportApi.updateSlot(editingSlot.value.id, payload);
    } else {
      console.log('Creating new slot');
      response = await adminTransportApi.createSlot(payload);
    }
    
    console.log('Save response:', response);

    if (response.data.success) {
      toast.success(editingSlot.value ? 'Slot updated successfully' : 'Slot created successfully');
      closeModal();
      await fetchSlots();
    } else {
       throw new Error(response.data.message || 'Unknown error occurred');
    }
  } catch (err) {
    console.error('Save slot error:', err);
    formError.value = err.response?.data?.message || err.message || 'Failed to save slot';
    toast.error(formError.value);
  } finally {
    saving.value = false;
  }
};

const getDayName = (day) => dayNames[day] || 'Unknown';

const formatDirection = (dir) => {
  return dir === 'round_trip' ? 'Round Trip' : 'One Way';
};

onMounted(() => {
  fetchRoutes();
  fetchSlots();
});
</script>

<style scoped>
.actions-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-xl);
}

.filters select {
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 14px;
  min-width: 200px;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-3xl);
  color: var(--color-textMuted);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: var(--spacing-md);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-state {
  text-align: center;
  padding: var(--spacing-3xl);
  color: var(--color-danger);
}

.table-container {
  background: white;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  overflow: hidden;
}

.slots-table {
  width: 100%;
  border-collapse: collapse;
}

.slots-table th,
.slots-table td {
  padding: var(--spacing-md) var(--spacing-lg);
  text-align: left;
  border-bottom: 1px solid var(--color-border);
}

.slots-table th {
  background: var(--color-surfaceHighlight);
  font-size: var(--font-xs);
  font-weight: var(--fw-bold);
  color: var(--color-textMuted);
  text-transform: uppercase;
}

.slots-table tbody tr:hover {
  background: var(--color-surfaceHighlight);
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

.form-group select,
.form-group input {
  padding: 10px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 14px;
}

.form-group select:focus,
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

.checkbox-group label {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  cursor: pointer;
}

.error-text {
  color: var(--color-danger);
  font-size: 14px;
}
</style>
