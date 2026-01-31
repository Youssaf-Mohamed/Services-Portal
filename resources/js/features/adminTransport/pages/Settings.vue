<template>
  <PortalLayout>
    <PageHeader
      title="Transport Settings"
      subtitle="Configure system parameters and payment methods"
      :breadcrumbs="[
        { label: 'Admin', to: '/admin/transport' },
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

    <div class="tab-content">
        <!-- GENERAL SETTINGS TAB -->
        <div v-if="currentTab === 'general'">
            <Card title="General Configuration">
                <div v-if="loadingSettings" class="loading-state">
                    <SkeletonLoader height="40px" count="3" />
                </div>
                <div v-else class="settings-form">
                    <div class="form-group">
                        <label>Working Days Per Week</label>
                        <input type="number" v-model="settingsForm.days_per_week" min="1" max="7" />
                        <span class="help-text">Number of days per week buses operate (used for pricing calculation)</span>
                    </div>
                    <div class="form-group">
                        <label>Weeks In Month</label>
                        <input type="number" v-model="settingsForm.weeks_in_month" min="1" max="5" />
                        <span class="help-text">Average weeks in a month for monthly subscriptions</span>
                    </div>
                    <div class="form-group">
                        <label>Weeks In Term</label>
                        <input type="number" v-model="settingsForm.weeks_in_term" min="1" max="20" />
                        <span class="help-text">Total weeks in a semester/term for full-term subscriptions</span>
                        <span class="help-text">Total weeks in a semester/term for full-term subscriptions</span>
                    </div>

                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" v-model="settingsForm.show_capacity" />
                            Show Capacity to Students
                        </label>
                        <span class="help-text">If disabled, students will not see "Seats Available" counts (only Waitlist warning if full).</span>
                    </div>
                    
                    <div class="form-actions">
                        <Button variant="primary" @click="saveSettings" :disabled="savingSettings">
                            {{ savingSettings ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </div>
                </div>
            </Card>
        </div>

        <!-- PAYMENT METHODS TAB -->
        <div v-if="currentTab === 'payments'">
            <div class="actions-bar">
                <Button variant="primary" @click="openPaymentModal">
                    + Add Payment Method
                </Button>
            </div>
            
            <Card>
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
                                <Badge :variant="method.active ? 'success' : 'secondary'">
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
                <label>
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

  </PortalLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
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

const tabs = [
    { id: 'general', label: 'General Settings' },
    { id: 'payments', label: 'Payment Methods' },
];
const currentTab = ref('general');

// General Settings State
const loadingSettings = ref(false);
const savingSettings = ref(false);
const settingsForm = reactive({
    days_per_week: 5,
    weeks_in_month: 4,
    weeks_in_month: 4,
    weeks_in_term: 12,
    show_capacity: true,
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
});

// Settings Logic
const fetchSettings = async () => {
    try {
        loadingSettings.value = true;
        const response = await adminTransportApi.getSettings();
        if (response.data.success) {
            Object.assign(settingsForm, response.data.data);
        }
    } catch (err) {
        console.error('Failed to load settings', err);
        // Defaults are already set in reactive state
    } finally {
        loadingSettings.value = false;
    }
};

const saveSettings = async () => {
    try {
        savingSettings.value = true;
        const response = await adminTransportApi.updateSettings(settingsForm);
        if (response.data.success) {
            toast.success('Settings updated successfully');
        }
    } catch (err) {
        toast.error(err.response?.data?.message || 'Failed to update settings');
    } finally {
        savingSettings.value = false;
    }
};

// Payment Methods Logic
const fetchPaymentMethods = async () => {
    try {
        loadingPayments.value = true;
        const response = await adminTransportApi.getPaymentMethods();
        if (response.data.success) {
            paymentMethods.value = response.data.data;
        }
    } catch (err) {
        console.error('Failed to load payment methods', err);
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
            response = await adminTransportApi.updatePaymentMethod(editingPayment.value.id, payload);
        } else {
            response = await adminTransportApi.createPaymentMethod(payload);
        }

        if (response.data.success) {
            toast.success(editingPayment.value ? 'Payment method updated' : 'Payment method created');
            closePaymentModal();
            fetchPaymentMethods();
        }
    } catch (err) {
        toast.error(err.response?.data?.message || 'Failed to save payment method');
    } finally {
        savingPayment.value = false;
    }
};

const confirmDeletePayment = async (method) => {
    if (confirm(`Are you sure you want to delete ${method.name}?`)) {
        try {
            await adminTransportApi.deletePaymentMethod(method.id);
            toast.success('Payment method deleted');
            fetchPaymentMethods();
        } catch (err) {
            toast.error('Failed to delete payment method');
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
}

.form-group input, .form-group textarea {
    padding: 10px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}

.help-text {
    font-size: 12px;
    color: var(--color-textMuted);
}

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

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.font-medium {
    font-weight: 500;
}

.text-center { text-align: center; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
.text-muted { color: var(--color-textMuted); }

.actions-cell {
    display: flex;
    gap: 8px;
}
</style>
