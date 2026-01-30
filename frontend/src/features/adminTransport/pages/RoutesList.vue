<template>
  <PortalLayout>
    <PageHeader
      title="Routes Management"
      subtitle="Manage transport routes and stops"
      :breadcrumbs="[
        { label: 'Admin', to: '/admin/transport' },
        { label: 'Routes' }
      ]"
    />

    <!-- Actions Bar -->
    <div class="actions-bar">
      <Button variant="primary" @click="showCreateModal = true">
        + Add Route
      </Button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="routes-grid">
      <div v-for="i in 6" :key="i" class="skeleton-card">
        <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
          <SkeletonLoader height="28px" width="150px" />
          <SkeletonLoader height="24px" width="60px" border-radius="var(--radius-full)" />
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px;">
           <SkeletonLoader height="20px" width="100%" />
           <SkeletonLoader height="20px" width="100%" />
           <SkeletonLoader height="20px" width="100%" />
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
      <Button variant="primary" @click="fetchRoutes">Retry</Button>
    </div>

    <!-- Routes List -->
    <div v-else class="routes-grid">
      <Card v-for="route in routes" :key="route.id" class="route-card">
        <div class="route-header">
          <div class="route-names">
            <h3>{{ route.name_en }}</h3>
            <p class="name-ar">{{ route.name_ar }}</p>
          </div>
          <Badge :variant="route.active ? 'success' : 'secondary'">
            {{ route.active ? 'Active' : 'Inactive' }}
          </Badge>
        </div>

        <div class="route-info">
          <div class="info-row">
            <span class="label">Price (One Way)</span>
            <span class="value">{{ route.pricing.price_one_way }} EGP</span>
          </div>
          <div class="info-row">
            <span class="label">Monthly Discount</span>
            <span class="value">{{ route.pricing.monthly_discount_percent }}%</span>
          </div>
          <div class="info-row">
            <span class="label">Term Discount</span>
            <span class="value">{{ route.pricing.term_discount_percent }}%</span>
          </div>
          <div class="info-row">
            <span class="label">Stops</span>
            <span class="value">{{ route.stops_count }}</span>
          </div>
          <div class="info-row">
            <span class="label">Slots</span>
            <span class="value">{{ route.slots_count }}</span>
          </div>
        </div>

        <div class="route-actions">
          <Button variant="outline" size="sm" @click="manageStops(route)">
            Manage Stops
          </Button>
          <Button variant="secondary" size="sm" @click="editRoute(route)">
            Edit
          </Button>
          <Button 
            :variant="route.active ? 'danger' : 'primary'" 
            size="sm" 
            @click="toggleActive(route)"
          >
            {{ route.active ? 'Deactivate' : 'Activate' }}
          </Button>
        </div>
      </Card>
    </div>

    <!-- Create/Edit Modal -->
    <Modal v-if="showCreateModal || editingRoute" :model-value="true" @close="closeModal">
      <template #header>
        <h3>{{ editingRoute ? 'Edit Route' : 'Create Route' }}</h3>
      </template>
      <div class="modal-body">
        <div class="form-group">
          <label>Name (English) *</label>
          <input type="text" v-model="form.name_en" placeholder="Route name in English" />
        </div>
        <div class="form-group">
          <label>Name (Arabic) *</label>
          <input type="text" v-model="form.name_ar" placeholder="اسم الخط بالعربي" dir="rtl" />
        </div>
        <div class="form-group">
          <label>Price (One Way) *</label>
          <input type="number" v-model="form.price_one_way" min="0" step="0.01" />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Monthly Discount %</label>
            <input type="number" v-model="form.monthly_discount_percent" min="0" max="100" />
          </div>
          <div class="form-group">
            <label>Term Discount %</label>
            <input type="number" v-model="form.term_discount_percent" min="0" max="100" />
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
        <Button variant="primary" @click="saveRoute" :disabled="saving">
          {{ saving ? 'Saving...' : 'Save' }}
        </Button>
      </template>
    </Modal>
    
    <!-- Manage Stops Modal -->
    <Modal v-if="showStopsModal" :model-value="true" @close="closeStopsModal">
      <template #header>
        <h3>Manage Stops for {{ currentRoute?.name_en }}</h3>
      </template>
      <div class="modal-body stops-modal-body">
        
        <div class="stops-container">
           <div class="assigned-stops">
              <h4>Assigned Stops (In Order)</h4>
              <div v-if="assignedStops.length === 0" class="empty-list">
                No stops assigned yet.
              </div>
              <ul v-else class="stops-list">
                 <li v-for="(stop, index) in assignedStops" :key="stop.id" class="stop-item">
                    <span class="stop-order">{{ index + 1 }}</span>
                    <span class="stop-name">{{ stop.name_en }}</span>
                    <div class="stop-actions">
                       <button 
                         @click="moveStop(index, -1)" 
                         :disabled="index === 0"
                         class="icon-btn"
                         title="Move Up"
                       >⬆️</button>
                       <button 
                         @click="moveStop(index, 1)" 
                         :disabled="index === assignedStops.length - 1"
                         class="icon-btn"
                         title="Move Down"
                       >⬇️</button>
                       <button 
                         @click="removeStop(index)" 
                         class="icon-btn remove-btn"
                         title="Remove"
                       >❌</button>
                    </div>
                 </li>
              </ul>
           </div>

           <div class="available-stops">
              <h4>Available Stops</h4>
              <div class="search-box">
                <input type="text" v-model="stopSearchQuery" placeholder="Search stops..." />
              </div>
              <ul class="stops-list">
                 <li v-for="stop in filteredAvailableStops" :key="stop.id" class="stop-item available">
                    <span class="stop-name">{{ stop.name_en }}</span>
                    <button class="add-btn" @click="addStop(stop)">+</button>
                 </li>
              </ul>
           </div>
        </div>

        <div v-if="stopsError" class="error-text">{{ stopsError }}</div>
      </div>
      <template #footer>
        <Button variant="secondary" @click="closeStopsModal" :disabled="savingStops">
          Cancel
        </Button>
        <Button variant="primary" @click="saveStops" :disabled="savingStops">
          {{ savingStops ? 'Saving...' : 'Save Changes' }}
        </Button>
      </template>
    </Modal>
  </PortalLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Button from '@/components/ui/Button.vue';
import Badge from '@/components/ui/Badge.vue';
import Modal from '@/components/ui/Modal.vue';
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue';
import { adminTransportApi } from '../api/adminTransport.api';
import { useToast } from '@/composables/useToast';

const toast = useToast();

const loading = ref(true);
const error = ref(null);
const routes = ref([]);
const showCreateModal = ref(false);
const editingRoute = ref(null);
const saving = ref(false);
const formError = ref(null);

// Stops Management State
const showStopsModal = ref(false);
const currentRoute = ref(null);
const allStops = ref([]); // Pool of all available stops
const assignedStops = ref([]); // Local copy of assigned stops for editing
const savingStops = ref(false);
const stopsError = ref(null);
const stopSearchQuery = ref('');

const form = reactive({
  name_en: '',
  name_ar: '',
  price_one_way: 0,
  monthly_discount_percent: 0,
  term_discount_percent: 0,
  active: true,
});

const fetchRoutes = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await adminTransportApi.getRoutes();
    if (response.data.success) {
      routes.value = response.data.data.routes;
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load routes';
  } finally {
    loading.value = false;
  }
};

const fetchAllStops = async () => {
    try {
        const response = await adminTransportApi.getStops();
        if (response.data.success) {
            allStops.value = response.data.data;
        }
    } catch (err) {
        console.error('Failed to load stops:', err);
        toast.error('Failed to load available bus stops');
    }
};

const editRoute = (route) => {
  editingRoute.value = route;
  form.name_en = route.name_en;
  form.name_ar = route.name_ar;
  form.price_one_way = route.pricing.price_one_way;
  form.monthly_discount_percent = route.pricing.monthly_discount_percent;
  form.term_discount_percent = route.pricing.term_discount_percent;
  form.active = route.active;
};

const closeModal = () => {
  showCreateModal.value = false;
  editingRoute.value = null;
  formError.value = null;
  resetForm();
};

const resetForm = () => {
  form.name_en = '';
  form.name_ar = '';
  form.price_one_way = 0;
  form.monthly_discount_percent = 0;
  form.term_discount_percent = 0;
  form.active = true;
};

const saveRoute = async () => {
  try {
    saving.value = true;
    formError.value = null;

    const payload = {
      name_en: form.name_en,
      name_ar: form.name_ar,
      price_one_way: Number(form.price_one_way),
      monthly_discount_percent: Number(form.monthly_discount_percent) || 0,
      term_discount_percent: Number(form.term_discount_percent) || 0,
      active: Boolean(form.active),
    };

    let response;
    if (editingRoute.value) {
      response = await adminTransportApi.updateRoute(editingRoute.value.id, payload);
    } else {
      response = await adminTransportApi.createRoute(payload);
    }

    if (response.data.success) {
      toast.success(editingRoute.value ? 'Route updated successfully' : 'Route created successfully');
      closeModal();
      await fetchRoutes();
    }
  } catch (err) {
    formError.value = err.response?.data?.message || err.message || 'Failed to save route';
    toast.error(formError.value);
  } finally {
    saving.value = false;
  }
};

const toggleActive = async (route) => {
  try {
    await adminTransportApi.updateRoute(route.id, { active: !route.active });
    fetchRoutes();
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to update route');
  }
};

// Stops Logic
const manageStops = async (route) => {
    currentRoute.value = route;
    // We need to fetch the full route details to get the assigned stops in order
    // But since the list API might not include full stops relationship, we should fetch single route or use what we have if sufficient.
    // The index API does include stops_count but not stops list usually to save bandwidth.
    // Let's assume we need to fetch the single route to get current stops, OR we can check if they are already loaded.
    // Based on RouteController logic, index does NOT return full stops relation, only count.
    
    // So we fetch single route details
    try {
        const response = await adminTransportApi.getRoute(route.id);
        if (response.data.success) {
             assignedStops.value = response.data.data.stops.map(s => ({...s})); // Clone
        }
    } catch (err) {
        toast.error('Failed to load route stops');
        return;
    }

    // Also ensure we have the pool of all stops
    if (allStops.value.length === 0) {
        await fetchAllStops();
    }

    showStopsModal.value = true;
};

const closeStopsModal = () => {
    showStopsModal.value = false;
    currentRoute.value = null;
    assignedStops.value = [];
    stopsError.value = null;
    stopSearchQuery.value = '';
};

const availableStops = computed(() => {
    // Filter out stops that are already assigned
    const assignedIds = new Set(assignedStops.value.map(s => s.id));
    return allStops.value.filter(s => !assignedIds.has(s.id));
});

const filteredAvailableStops = computed(() => {
    const query = stopSearchQuery.value.toLowerCase();
    return availableStops.value.filter(s => 
        s.name_en.toLowerCase().includes(query) || 
        s.name_ar.includes(query)
    );
});

const addStop = (stop) => {
    assignedStops.value.push(stop);
};

const removeStop = (index) => {
    assignedStops.value.splice(index, 1);
};

const moveStop = (index, direction) => {
    const newIndex = index + direction;
    if (newIndex >= 0 && newIndex < assignedStops.value.length) {
        const temp = assignedStops.value[index];
        assignedStops.value[index] = assignedStops.value[newIndex];
        assignedStops.value[newIndex] = temp;
    }
};

const saveStops = async () => {
    try {
        savingStops.value = true;
        stopsError.value = null;

        const payload = {
            stops: assignedStops.value.map((stop, index) => ({
                id: stop.id,
                sort_order: index + 1
            }))
        };

        const response = await adminTransportApi.updateStops(currentRoute.value.id, payload);

        if (response.data.success) {
            toast.success('Route stops updated successfully');
            closeStopsModal();
            fetchRoutes(); // Refresh list to update counts
        }
    } catch (err) {
        console.error('Failed to save stops:', err);
        stopsError.value = err.response?.data?.message || 'Failed to save changes';
    } finally {
        savingStops.value = false;
    }
};

onMounted(fetchRoutes);
</script>

<style scoped>
/* Existing styles */
.actions-bar { margin-bottom: var(--spacing-xl); }
.loading-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: var(--spacing-3xl); color: var(--color-textMuted); }
.spinner { width: 40px; height: 40px; border: 3px solid var(--color-border); border-top-color: var(--color-primary); border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: var(--spacing-md); }
@keyframes spin { to { transform: rotate(360deg); } }
.error-state { text-align: center; padding: var(--spacing-3xl); color: var(--color-danger); }
.routes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: var(--spacing-lg); }
.route-card { display: flex; flex-direction: column; gap: var(--spacing-md); }
.route-header { display: flex; justify-content: space-between; align-items: flex-start; }
.route-names h3 { font-size: 18px; font-weight: 600; color: var(--color-textMain); margin: 0; }
.name-ar { font-size: 14px; color: var(--color-textMuted); margin-top: 4px; }
.route-info { display: flex; flex-direction: column; gap: var(--spacing-xs); padding: var(--spacing-md) 0; border-top: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border); }
.info-row { display: flex; justify-content: space-between; font-size: 14px; }
.info-row .label { color: var(--color-textMuted); }
.info-row .value { color: var(--color-textMain); font-weight: 500; }
.route-actions { display: flex; gap: var(--spacing-sm); margin-top: auto; flex-wrap: wrap; }
.modal-body { display: flex; flex-direction: column; gap: var(--spacing-lg); }
.form-group { display: flex; flex-direction: column; gap: var(--spacing-xs); }
.form-group label { font-size: 14px; font-weight: 500; color: var(--color-textMain); }
.form-group input[type="text"], .form-group input[type="number"] { padding: 10px 12px; border: 1px solid var(--color-border); border-radius: var(--radius-md); font-size: 14px; }
.form-group input:focus { outline: none; border-color: var(--color-primary); box-shadow: var(--shadow-focusRing); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md); }
.checkbox-group label { display: flex; align-items: center; gap: var(--spacing-sm); cursor: pointer; }
.error-text { color: var(--color-danger); font-size: 14px; }
.skeleton-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: var(--spacing-xl); display: flex; flex-direction: column; }

/* New Styles for Stops Modal */
.stops-modal-body {
    min-height: 400px;
}
.stops-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-lg);
    height: 100%;
}
.assigned-stops, .available-stops {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    height: 400px;
    overflow-y: auto;
}
.stops-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.stop-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    background: var(--color-surfaceHighlight);
    border-radius: var(--radius-sm);
    font-size: 14px;
}
.stop-item.available {
    background: white;
    border: 1px solid var(--color-border);
}
.stop-order {
    font-weight: bold;
    color: var(--color-primary);
    margin-right: 8px;
    width: 20px;
}
.stop-name {
    flex: 1;
}
.stop-actions {
    display: flex;
    gap: 4px;
}
.icon-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    padding: 2px 4px;
    opacity: 0.7;
}
.icon-btn:hover:not(:disabled) {
    opacity: 1;
    transform: scale(1.1);
}
.icon-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}
.add-btn {
    background: var(--color-primary);
    color: white;
    border: none;
    border-radius: 4px;
    width: 24px;
    height: 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.search-box input {
    width: 100%;
    padding: 8px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    margin-bottom: 8px;
}
</style>
