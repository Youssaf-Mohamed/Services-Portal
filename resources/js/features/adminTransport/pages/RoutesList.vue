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

    <!-- Data List (Grid) -->
    <DataList
      :data="routes"
      :loading="loading"
      :error="error"
      grid-layout
      :skeleton-count="6"
      skeleton-height="200px"
      empty-title="No Routes Found"
      empty-message="Get started by creating your first transport route."
      empty-action-text="Create Route"
      @empty-action="showCreateModal = true"
      @retry="fetchRoutes"
    >
        <template #default="{ item: route }">
            <Card class="route-card">
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
        </template>
        
        <!-- Custom Loading Skeleton matching the card design roughly -->
        <template #loading>
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
        </template>
    </DataList>

    <!-- Create/Edit Modal (Wizard Style) -->
    <Modal v-if="showCreateModal || editingRoute" :model-value="true" @close="closeModal" size="lg">
      <template #header>
        <h3>{{ editingRoute ? 'Edit Route' : 'Create New Route' }}</h3>
      </template>
      
      <div class="modal-body">
        <!-- Wizard Steps Indicator -->
        <div class="wizard-steps" v-if="!editingRoute">
            <div class="step" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">
                <div class="step-circle">1</div>
                <span>Details</span>
            </div>
            <div class="step-line" :class="{ fill: currentStep > 1 }"></div>
            <div class="step" :class="{ active: currentStep >= 2 }">
                <div class="step-circle">2</div>
                <span>Stops</span>
            </div>
        </div>

        <!-- Step 1: Basic Information -->
        <div v-show="currentStep === 1" class="wizard-content">
            <div class="form-group">
            <label>Name (English) *</label>
            <input type="text" v-model="form.name_en" placeholder="Route name (e.g. 10th of Ramadan)" />
            </div>
            <div class="form-group">
            <label>Name (Arabic) *</label>
            <input type="text" v-model="form.name_ar" placeholder="Ø§Ø³Ù… Ø§Ù„Ø®Ø· Ø¨Ø§Ù„Ø¹Ø±Ø¨ÙŠ" dir="rtl" />
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
                Active Route
            </label>
            </div>
        </div>

        <!-- Step 2: Stops Selection -->
        <div v-show="currentStep === 2" class="wizard-content">
            <div style="margin-bottom: 12px;">
                <h4 style="margin: 0; font-size: 1rem;">Configure Stops</h4>
                <p style="margin: 4px 0 0; color: var(--color-textMuted); font-size: 0.85rem;">
                    Select and order the stops for this route.
                </p>
            </div>
            
            <StopsSelector 
                v-model="assignedStops" 
                :all-stops="allStops" 
            />
        </div>

        <div v-if="formError" class="error-text">{{ formError }}</div>
      </div>

      <template #footer>
        <div class="wizard-footer">
            <Button variant="secondary" @click="closeModal" :disabled="saving">
                Cancel
            </Button>
            
            <div class="wizard-actions">
                <Button 
                    v-if="currentStep > 1 && !editingRoute" 
                    variant="outline" 
                    @click="currentStep--" 
                    :disabled="saving"
                >
                    Back
                </Button>

                <Button 
                    v-if="currentStep === 1 && !editingRoute" 
                    variant="primary" 
                    @click="nextStep"
                >
                    Next: Stops
                </Button>

                <Button 
                    v-else 
                    variant="primary" 
                    @click="saveRoute" 
                    :disabled="saving"
                >
                    {{ saving ? 'Saving...' : (editingRoute ? 'Update Route' : 'Create Route') }}
                </Button>
            </div>
        </div>
      </template>
    </Modal>
    
    <!-- Manage Stops Modal (Standalone) -->
    <Modal v-if="showStopsModal" :model-value="true" @close="closeStopsModal" size="lg">
      <template #header>
        <h3>Manage Stops: {{ currentRoute?.name_en }}</h3>
      </template>
      <div class="modal-body">
        <StopsSelector 
            v-model="assignedStops" 
            :all-stops="allStops" 
        />
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
import { PageHeader, Card, Button, Badge, Modal, SkeletonLoader, DataList } from '@/components/ui';
import { adminTransportApi } from '../api/adminTransport.api';
import { useToast } from '@/composables/useToast';
import { ArrowUp, ArrowDown, Trash2, Plus, Search } from 'lucide-vue-next';
import StopsSelector from '../components/StopsSelector.vue';

const toast = useToast();

const loading = ref(true);
const error = ref(null);
const routes = ref([]);
const showCreateModal = ref(false);
const editingRoute = ref(null);
const saving = ref(false);
const formError = ref(null);
const currentStep = ref(1);

// Stops Management State
const showStopsModal = ref(false);
const currentRoute = ref(null);
const allStops = ref([]); // Pool of all available stops
const assignedStops = ref([]); // Local copy of assigned stops for editing
const savingStops = ref(false);
const stopsError = ref(null);

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
    // Only fetch if empty to save calls, or force refresh if needed
    if (allStops.value.length > 0) return;
    
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
  currentStep.value = 1; // Direct edit usually implies tabs or single view, but here keeping simple
  // Pre-fill
  form.name_en = route.name_en;
  form.name_ar = route.name_ar;
  form.price_one_way = route.pricing.price_one_way;
  form.monthly_discount_percent = route.pricing.monthly_discount_percent;
  form.term_discount_percent = route.pricing.term_discount_percent;
  form.active = route.active;
  
  // Note: Edit currently doesn't fetch stops into the wizard. 
  // It's cleaner to keep "Edit Route" for basic details and "Manage Stops" for stops.
  // But if we wanted to merge, we'd need to fetch stops here.
  // For now, "Add" uses the wizard, "Edit" just edits details (as per existing logical separation usually).
  // However, the user said "Add Route needs stops". For Edit, 'Manage Stops' button exists on card.
  // We'll stick to that separation unless requested otherwise.
};

const closeModal = () => {
  showCreateModal.value = false;
  editingRoute.value = null;
  formError.value = null;
  assignedStops.value = [];
  currentStep.value = 1;
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

// Wizard Logic
const nextStep = async () => {
    if (currentStep.value === 1) {
        // Validation Step 1
        if (!form.name_en || !form.price_one_way) {
            formError.value = "Name and Price are required.";
            return;
        }
        formError.value = null;
        
        // Load eligible stops
        await fetchAllStops();
        currentStep.value = 2;
    }
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

    let routeId;

    if (editingRoute.value) {
      const response = await adminTransportApi.updateRoute(editingRoute.value.id, payload);
      routeId = editingRoute.value.id;
    } else {
      const response = await adminTransportApi.createRoute(payload);
      if (response.data.success) {
          routeId = response.data.data.id; // Assuming API returns the created route object with ID
      }
    }

    // If it was a CREATE operation and we have stops selected, save them too
    if (!editingRoute.value && routeId && assignedStops.value.length > 0) {
        const stopsPayload = {
            stops: assignedStops.value.map((stop, index) => ({
                id: stop.id,
                sort_order: index + 1
            }))
        };
        await adminTransportApi.updateStops(routeId, stopsPayload);
    }

    toast.success(editingRoute.value ? 'Route updated successfully' : 'Route created successfully');
    closeModal();
    await fetchRoutes();
    
  } catch (err) {
    console.error(err);
    formError.value = err.response?.data?.message || err.message || 'Failed to save route';
    // If route was created but stops failed, we should probably warn user, but for now just error.
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

// Stops Logic (Standalone Modal)
const manageStops = async (route) => {
    currentRoute.value = route;
    
    // Fetch stops logic similar to before
    try {
        const response = await adminTransportApi.getRoute(route.id);
        if (response.data.success) {
             assignedStops.value = response.data.data.stops.map(s => ({...s}));
        }
    } catch (err) {
        toast.error('Failed to load route stops');
        return;
    }

    await fetchAllStops();
    showStopsModal.value = true;
};

const closeStopsModal = () => {
    showStopsModal.value = false;
    currentRoute.value = null;
    assignedStops.value = [];
    stopsError.value = null;
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
            fetchRoutes();
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
/* Actions Bar */
.actions-bar { 
  margin-bottom: var(--spacing-xl); 
  display: flex;
  justify-content: flex-end;
}

/* Route Card - Professional Formal Styling */
.route-card { 
  display: flex; 
  flex-direction: column; 
  background: white;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  transition: all 0.2s ease;
  overflow: hidden;
  position: relative;
  height: 100%;
}

.route-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: var(--color-primary);
  opacity: 0.8;
}

.route-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px -10px rgba(0, 0, 0, 0.1);
  border-color: var(--color-primaryLight);
}

.route-header { 
  padding: var(--spacing-lg);
  display: flex; 
  justify-content: space-between; 
  align-items: flex-start; 
  border-bottom: 1px solid var(--color-border);
  background: #fcfcfc;
}

.route-names {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.route-names h3 { 
  font-size: 1.125rem; 
  font-weight: 700; 
  color: var(--color-textMain); 
  margin: 0; 
  line-height: 1.4;
}

.name-ar { 
  font-size: 0.875rem; 
  color: var(--color-textMuted); 
  margin: 0;
}

/* Route Info Grid */
.route-info { 
  padding: var(--spacing-lg);
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-md) var(--spacing-lg); 
  flex: 1; /* Pushes actions to bottom */
}

.info-row { 
  display: flex; 
  flex-direction: column;
  gap: 2px;
}

.info-row .label { 
  color: var(--color-textMuted); 
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
}

.info-row .value { 
  color: var(--color-textMain); 
  font-weight: 600; 
  font-size: 0.95rem;
}

/* Route Actions */
.route-actions { 
  padding: var(--spacing-lg);
  background: var(--color-surface);
  border-top: 1px solid var(--color-border);
  display: flex; 
  gap: var(--spacing-sm); 
  justify-content: flex-end;
}

/* Wizard Styles */
.wizard-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--color-border);
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    color: var(--color-textMuted);
    position: relative;
    z-index: 2;
}

.step-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--color-surface);
    border: 2px solid var(--color-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    transition: all 0.3s;
}

.step span {
    font-size: 0.8rem;
    font-weight: 500;
}

.step.active .step-circle {
    border-color: var(--color-primary);
    background: white;
    color: var(--color-primary);
}

.step.active span {
    color: var(--color-primary);
    font-weight: 600;
}

.step.completed .step-circle {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: white;
}

.step-line {
    width: 60px;
    height: 2px;
    background: var(--color-border);
    margin: 0 12px;
    margin-bottom: 20px; /* Align with circle */
}

.step-line.fill {
    background: var(--color-primary);
}

.wizard-footer {
    display: flex;
    justify-content: space-between;
    width: 100%;
}

.wizard-actions {
    display: flex;
    gap: 8px;
}

/* Modal Forms */
.modal-body { 
  display: flex; 
  flex-direction: column; 
  gap: var(--spacing-lg); 
}

.form-group { 
  display: flex; 
  flex-direction: column; 
  gap: 6px; 
}

.form-group label { 
  font-size: 0.875rem; 
  font-weight: 600; 
  color: var(--color-textMain); 
}

.form-group input[type="text"], 
.form-group input[type="number"] { 
  padding: 10px 12px; 
  border: 1px solid var(--color-border); 
  border-radius: var(--radius-md); 
  font-size: 0.95rem; 
  color: var(--color-textMain);
  transition: border-color 0.2s, box-shadow 0.2s;
  background: white;
}

.form-group input:focus { 
  outline: none; 
  border-color: var(--color-primary); 
  box-shadow: 0 0 0 3px var(--color-primaryLight); 
}

.form-group.checkbox-group {
    flex-direction: row;
    align-items: center;
    background: var(--color-surface);
    padding: var(--spacing-md);
    border-radius: var(--radius-md);
    border: 1px solid var(--color-border);
}

.form-group.checkbox-group label {
    margin: 0;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    width: 100%;
}

.form-row { 
  display: grid; 
  grid-template-columns: 1fr 1fr; 
  gap: var(--spacing-lg); 
}

.error-text { 
  color: var(--color-danger); 
  font-size: 0.875rem; 
  background: #fef2f2;
  padding: var(--spacing-md);
  border-radius: var(--radius-md);
  border: 1px solid #fee2e2;
}

.skeleton-card { 
  background: var(--color-surface); 
  border: 1px solid var(--color-border); 
  border-radius: var(--radius-lg); 
  padding: var(--spacing-xl); 
  display: flex; 
  flex-direction: column; 
}

</style>
