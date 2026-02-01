<template>
  <PortalLayout>
    <PageHeader
      title="ID Card Settings"
      subtitle="Configure ID card service and payment settings"
      :breadcrumbs="[
        { label: 'Admin', to: '/admin/id-card' },
        { label: 'Settings' }
      ]"
    />

    <div class="settings-tabs">
        <button 
           v-for="tab in tabs" 
           :key="tab.id"
           class="tab-btn"
           :class="{ active: currentTab === tab.id }"
           @click="currentTab = tab.id"
        >
           {{ tab.label }}
        </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
       <SkeletonLoader height="300px" border-radius="var(--radius-lg)" />
    </div>

    <div v-else class="tab-content">
        <!-- GENERAL SETTINGS TAB -->
        <div v-if="currentTab === 'general'">
            <Card title="Service Configuration">
                <form @submit.prevent="saveSettings" class="settings-form">
                    <div class="form-group checkbox-group">
                        <label class="toggle-switch-label">
                            <div class="toggle-info">
                                <span class="label-text">Enable ID Card Services</span>
                                <span class="help-text">When disabled, students will not be able to submit new requests.</span>
                            </div>
                            <label class="toggle-switch">
                              <input type="checkbox" v-model="form.service_enabled" :disabled="saving" />
                              <span class="toggle-slider"></span>
                            </label>
                        </label>
                    </div>

                    <div class="form-actions">
                        <Button variant="secondary" @click="resetForm" :disabled="saving" type="button">
                            Reset
                        </Button>
                        <Button variant="primary" type="submit" :disabled="saving || !hasChanges">
                            {{ saving ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </div>
                </form>
            </Card>
        </div>

        <!-- SERVICE TYPES TAB -->
        <div v-if="currentTab === 'types'">
             <div class="actions-bar">
                <Button variant="primary" @click="openTypeModal">
                    + Add Service Type
                </Button>
            </div>
            
             <Card title="Service Types">
                <div v-if="loadingTypes" class="loading-state">
                    <SkeletonLoader height="60px" count="3" />
                </div>

                <table v-else class="data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name (EN)</th>
                            <th>Fee</th>
                            <th>Status</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="type in types" :key="type.id">
                            <td class="font-medium">{{ type.code }}</td>
                            <td>{{ type.name_en }}</td>
                            <td>{{ formatCurrency(type.fee) }}</td>
                            <td>
                                <Badge :variant="type.active ? 'success' : 'neutral'">
                                    {{ type.active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td>{{ type.sort_order }}</td>
                            <td>
                                <div class="actions-cell">
                                    <Button variant="secondary" size="sm" @click="editType(type)">Edit</Button>
                                    <Button variant="danger" size="sm" @click="confirmDeleteType(type)">Delete</Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="types.length === 0">
                            <td colspan="6" class="text-center py-4 text-muted">No service types found.</td>
                        </tr>
                    </tbody>
                </table>
             </Card>
        </div>

        <!-- PAYMENT SETTINGS TAB -->
        <div v-if="currentTab === 'payment'">
             <div class="actions-bar">
                <Button variant="primary" @click="openPaymentModal">
                    + Add Payment Method
                </Button>
            </div>
            
             <Card title="Payment Methods">
                <div v-if="loadingPayments" class="loading-state">
                    <SkeletonLoader height="60px" count="3" />
                </div>

                <table v-else class="data-table">
                    <thead>
                        <tr>
                            <th>Method Name</th>
                            <th>Account Details</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="method in paymentMethods" :key="method.id">
                            <td class="font-medium">{{ method.name }}</td>
                            <td>{{ method.account_number || '-' }}</td>
                            <td>
                                <Badge :variant="method.active ? 'success' : 'neutral'">
                                    {{ method.active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <Button variant="secondary" size="sm" @click="editPaymentMethod(method)">Edit</Button>
                                    <Button variant="danger" size="sm" @click="confirmDeletePayment(method)">Delete</Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="paymentMethods.length === 0">
                            <td colspan="4" class="text-center py-4 text-muted">No payment methods found.</td>
                        </tr>
                    </tbody>
                </table>
             </Card>
        </div>
    </div>

    <!-- Payment Method Modal -->
    <Modal v-if="showPaymentModal" :model-value="true" @close="closePaymentModal">
        <template #header>
            <h3>{{ editingPayment ? 'Edit Payment Method' : 'Add Payment Method' }}</h3>
        </template>
        <div class="modal-body">
            <div class="form-group">
                <label>Method Name *</label>
                <input type="text" v-model="paymentForm.name" placeholder="e.g. Vodafone Cash" />
            </div>
            <div class="form-group">
                <label>Account Number / ID</label>
                <input type="text" v-model="paymentForm.account_number" placeholder="e.g. 010xxxxxxx" />
            </div>
            <div class="form-group">
                <label>Instructions</label>
                <textarea v-model="paymentForm.instructions" rows="3" placeholder="Payment instructions for the student..."></textarea>
            </div>
            <div class="form-group checkbox-group">
                <label style="flex-direction: row; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" v-model="paymentForm.active" />
                    Active
                </label>
            </div>
        </div>
        <template #footer>
            <Button variant="secondary" @click="closePaymentModal">Cancel</Button>
            <Button variant="primary" @click="savePaymentMethod" :disabled="savingPayment">
                {{ savingPayment ? 'Saving...' : 'Save' }}
            </Button>
        </template>
    </Modal>

    <!-- Last Updated Info -->
    <div v-if="settings?.updated_at && !loading" class="last-updated">
        Last updated: {{ formatDate(settings.updated_at) }}
        <span v-if="settings.updated_by">by {{ settings.updated_by.name }}</span>
    </div>

    <!-- Service Type Modal -->
    <Modal v-if="showTypeModal" :model-value="true" @close="closeTypeModal">
        <template #header>
            <h3>{{ editingType ? 'Edit Service Type' : 'Add Service Type' }}</h3>
        </template>
        <div class="modal-body">
            <div class="form-group">
                <label>Code *</label>
                <input type="text" v-model="typeForm.code" placeholder="e.g. NEW_CARD" :disabled="!!editingType" />
                <span class="help-text">Unique identifier (cannot be changed later).</span>
            </div>
            <div class="form-grid">
                 <div class="form-group">
                    <label>Name (English) *</label>
                    <input type="text" v-model="typeForm.name_en" placeholder="e.g. New ID Card" />
                </div>
                <div class="form-group">
                    <label>Name (Arabic) *</label>
                    <input type="text" v-model="typeForm.name_ar" placeholder="e.g. استخراج كارنيه جديد" />
                </div>
            </div>
            <div class="form-group">
                <label>Fee (EGP) *</label>
                <input type="number" v-model="typeForm.fee" placeholder="0.00" min="0" step="0.01" />
            </div>
            <div class="form-group">
                <label>Description (English)</label>
                <textarea v-model="typeForm.description_en" rows="2"></textarea>
            </div>
             <div class="form-group">
                <label>Description (Arabic)</label>
                <textarea v-model="typeForm.description_ar" rows="2"></textarea>
            </div>
            <div class="form-grid">
                 <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" v-model="typeForm.sort_order" />
                </div>
            </div>
            <div class="checkbox-row">
                 <label>
                    <input type="checkbox" v-model="typeForm.active" />
                    Active
                </label>
                <label>
                    <input type="checkbox" v-model="typeForm.requires_photo" />
                    Requires Photo
                </label>
                <label>
                    <input type="checkbox" v-model="typeForm.requires_description" />
                    Requires Description
                </label>
            </div>
        </div>
        <template #footer>
            <Button variant="secondary" @click="closeTypeModal">Cancel</Button>
            <Button variant="primary" @click="saveType" :disabled="savingType">
                {{ savingType ? 'Saving...' : 'Save' }}
            </Button>
        </template>
    </Modal>

  </PortalLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { PageHeader, Card, Button, SkeletonLoader, Badge, Modal } from '@/components/ui';
import { adminIdCardApi } from '../api/adminIdCard.api';
import { useToast } from '@/composables/useToast';

const toast = useToast();

const tabs = [
    { id: 'general', label: 'General Configuration' },
    { id: 'payment', label: 'Payment Methods' },
];
const currentTab = ref('general');

// General Settings State
const loading = ref(false);
const saving = ref(false);
const settings = ref(null);
const validationErrors = ref({});

const form = reactive({
  service_enabled: true
});

const originalForm = ref(null);

const hasChanges = computed(() => {
  if (!originalForm.value) return false;
  return JSON.stringify(form) !== JSON.stringify(originalForm.value);
});

// Payment Methods State
const loadingPayments = ref(false);
const savingPayment = ref(false);
const paymentMethods = ref([]);
const showPaymentModal = ref(false);
const editingPayment = ref(null);
const paymentForm = reactive({
    name: '',
    account_number: '',
    instructions: '',
    active: true,
});

// Initialization
onMounted(() => {
  fetchSettings();
  fetchPaymentMethods();
  fetchTypes();
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-EG', { style: 'currency', currency: 'EGP' }).format(value);
};

// General Settings Logic
const fetchSettings = async () => {
  loading.value = true;
  try {
    const response = await adminIdCardApi.getSettings();
    settings.value = response.data || {};
    
    const s = response.data?.settings || {};
    // We only care about service_enabled for now as payment is moved
    form.service_enabled = s.service_enabled !== false;
    
    originalForm.value = { ...form };
  } catch (err) {
    console.error('Failed to load settings:', err);
    toast.error('Failed to load settings');
  } finally {
    loading.value = false;
  }
};

const saveSettings = async () => {
  saving.value = true;
  validationErrors.value = {};
  
  try {
    // We send only service_enabled, backend should handle partial updates or merge
    // If backend expects all fields, we might need to send current stored values for payment fields?
    // Assuming backend handles partial updates or we don't care about overwriting unused fields.
    await adminIdCardApi.updateSettings(form);
    originalForm.value = { ...form };
    toast.success('Settings updated successfully');
    await fetchSettings();
  } catch (err) {
    console.error('Failed to save settings:', err);
    if (err.errors) {
       Object.keys(err.errors).forEach(key => {
        validationErrors.value[key] = err.errors[key][0] || err.errors[key];
      });
      toast.error('Please check validation errors.');
    } else {
      toast.error(err.message || 'Failed to save settings');
    }
  } finally {
    saving.value = false;
  }
};

const resetForm = () => {
  if (originalForm.value) {
    Object.assign(form, originalForm.value);
    validationErrors.value = {};
  }
};

// Payment Methods Logic
const fetchPaymentMethods = async () => {
    try {
        loadingPayments.value = true;
        const response = await adminIdCardApi.getPaymentMethods();
        if (response.success || response.data) {
             paymentMethods.value = response.data || (Array.isArray(response) ? response : []);
        }
    } catch (err) {
        console.error('Failed to load payment methods', err);
        // toast.error('Failed to load payment methods'); // generic error might be annoying on load
    } finally {
        loadingPayments.value = false;
    }
};

const openPaymentModal = () => {
    editingPayment.value = null;
    paymentForm.name = '';
    paymentForm.account_number = '';
    paymentForm.instructions = '';
    paymentForm.active = true;
    showPaymentModal.value = true;
};

const editPaymentMethod = (method) => {
    editingPayment.value = method;
    paymentForm.name = method.name;
    paymentForm.account_number = method.account_number;
    paymentForm.instructions = method.instructions;
    paymentForm.active = method.active;
    showPaymentModal.value = true;
};

const closePaymentModal = () => {
    showPaymentModal.value = false;
};

const savePaymentMethod = async () => {
    try {
        savingPayment.value = true;
        const payload = { ...paymentForm };
        
        let response;
        if (editingPayment.value) {
            response = await adminIdCardApi.updatePaymentMethod(editingPayment.value.id, payload);
        } else {
            response = await adminIdCardApi.createPaymentMethod(payload);
        }

        if (response.success || response.data) {
            toast.success(editingPayment.value ? 'Payment method updated' : 'Payment method created');
            closePaymentModal();
            fetchPaymentMethods();
        }
    } catch (err) {
        toast.error(err.message || 'Failed to save payment method');
    } finally {
        savingPayment.value = false;
    }
};

const confirmDeletePayment = async (method) => {
    if (confirm(`Are you sure you want to delete ${method.name}?`)) {
        try {
            await adminIdCardApi.deletePaymentMethod(method.id);
            toast.success('Payment method deleted');
            fetchPaymentMethods();
        } catch (err) {
            toast.error('Failed to delete payment method');
        }
    }
};

const formatDate = (dateStr) => {
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
};

// Service Types Logic
const loadingTypes = ref(false);
const savingType = ref(false);
const types = ref([]);
const showTypeModal = ref(false);
const editingType = ref(null);
const typeForm = reactive({
    code: '',
    name_ar: '',
    name_en: '',
    description_ar: '',
    description_en: '',
    fee: 0,
    requires_photo: false,
    requires_description: false,
    active: true,
    sort_order: 0,
});

const fetchTypes = async () => {
    try {
        loadingTypes.value = true;
        const response = await adminIdCardApi.getTypes();
        types.value = response.data || [];
    } catch (err) {
        console.error('Failed to load types', err);
    } finally {
        loadingTypes.value = false;
    }
};

const openTypeModal = () => {
    editingType.value = null;
    Object.assign(typeForm, {
        code: '',
        name_ar: '',
        name_en: '',
        description_ar: '',
        description_en: '',
        fee: 0,
        requires_photo: false,
        requires_description: false,
        active: true,
        sort_order: types.value.length + 1,
    });
    showTypeModal.value = true;
};

const editType = (type) => {
    editingType.value = type;
    Object.assign(typeForm, {
        code: type.code,
        name_ar: type.name_ar,
        name_en: type.name_en,
        description_ar: type.description_ar,
        description_en: type.description_en,
        fee: type.fee,
        requires_photo: Boolean(type.requires_photo),
        requires_description: Boolean(type.requires_description),
        active: Boolean(type.active),
        sort_order: type.sort_order,
    });
    showTypeModal.value = true;
};

const closeTypeModal = () => {
    showTypeModal.value = false;
};

const saveType = async () => {
    try {
        savingType.value = true;
        
        let response;
        if (editingType.value) {
            response = await adminIdCardApi.updateType(editingType.value.id, typeForm);
        } else {
            response = await adminIdCardApi.createType(typeForm);
        }

        if (response.success || response.data) {
            toast.success(editingType.value ? 'Service type updated' : 'Service type created');
            closeTypeModal();
            fetchTypes();
        }
    } catch (err) {
        toast.error(err.message || 'Failed to save service type');
    } finally {
        savingType.value = false;
    }
};

const confirmDeleteType = async (type) => {
    if (confirm(`Are you sure you want to delete ${type.name_en}? This may fail if there are existing requests.`)) {
        try {
            await adminIdCardApi.deleteType(type.id);
            toast.success('Service type deleted');
            fetchTypes();
        } catch (err) {
            toast.error(err.message || 'Failed to delete service type');
        }
    }
};
</script>

<style scoped>
.settings-tabs {
    display: flex;
    gap: var(--spacing-sm);
    border-bottom: 1px solid var(--color-border);
    margin-bottom: var(--spacing-lg);
}

.tab-btn {
    padding: var(--spacing-md) var(--spacing-lg);
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    font-weight: 500;
    color: var(--color-textMuted);
    transition: all 0.2s;
}

.tab-btn:hover {
    color: var(--color-primary);
}

.tab-btn.active {
    color: var(--color-primary);
    border-bottom-color: var(--color-primary);
}

.loading-container {
    max-width: 800px;
}

.settings-form {
    max-width: 600px;
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
    font-weight: 500;
    font-size: 14px;
    color: var(--color-text-primary);
}

.form-group input, .form-group textarea {
    padding: 10px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-background);
    color: var(--color-text-primary);
}

.form-group input:focus, .form-group textarea:focus {
    outline: none;
    border-color: var(--color-primary);
}

.help-text {
    font-size: 12px;
    color: var(--color-textMuted);
}

.error-text {
  font-size: 12px;
  color: var(--color-danger);
}

.section-description {
    color: var(--color-textMuted);
    margin-bottom: var(--spacing-lg);
}

/* Toggle Switch Styles */
.toggle-switch-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    cursor: pointer;
}

.toggle-info {
    display: flex;
    flex-direction: column;
}

.label-text {
    font-weight: 500;
    font-size: 15px;
}

.toggle-switch {
  position: relative;
  width: 50px;
  height: 26px;
  flex-shrink: 0;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  inset: 0;
  background: var(--color-border);
  border-radius: var(--radius-full);
  transition: background 0.2s ease;
}

.toggle-slider::before {
  content: '';
  position: absolute;
  width: 20px;
  height: 20px;
  left: 3px;
  bottom: 3px;
  background: white;
  border-radius: 50%;
  transition: transform 0.2s ease;
}

.toggle-switch input:checked + .toggle-slider {
  background: var(--color-success);
}

.toggle-switch input:checked + .toggle-slider::before {
  transform: translateX(24px);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--spacing-md);
    margin-top: var(--spacing-md);
}

.last-updated {
  margin-top: var(--spacing-lg);
  font-size: 0.875rem;
  color: var(--color-text-tertiary);
  text-align: right;
  max-width: 800px;
}

/* Data Table Styles */
.actions-bar {
    display: flex;
    justify-content: flex-end;
    margin-bottom: var(--spacing-md);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th, .data-table td {
    padding: var(--spacing-md);
    border-bottom: 1px solid var(--color-border);
    text-align: left;
}

.data-table th {
    background: var(--color-surfaceHighlight);
    font-size: 12px;
    text-transform: uppercase;
    color: var(--color-textMuted);
}

.actions-cell {
    display: flex;
    gap: 8px;
}

.font-medium {
    font-weight: 500;
}

.text-center { text-align: center; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
.text-muted { color: var(--color-textMuted); }

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-md);
}

.checkbox-row {
    display: flex;
    gap: var(--spacing-lg);
    margin-top: var(--spacing-sm);
}

.checkbox-row label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 14px;
}
</style>
