<template>
  <PortalLayout>
    <PageHeader
      title="ID Card Types"
      subtitle="Manage available ID card request types and fees"
      :breadcrumbs="[
        { label: 'Admin', to: '/admin/id-card' },
        { label: 'Types' }
      ]"
    >
      <template #actions>
        <Button @click="openCreateModal">
          <Plus class="w-4 h-4 mr-2" />
          Add New Type
        </Button>
      </template>
    </PageHeader>

    <!-- Data Table -->
    <DataTable
      :columns="columns"
      :data="types"
      :loading="loading"
      :pagination="null" 
    >
      <!-- Custom Slot: Name -->
      <template #cell-name_en="{ row }">
        <div class="flex flex-col">
          <span class="font-medium text-gray-900">{{ row.name_en }}</span>
          <span class="text-xs text-gray-500 font-arabic">{{ row.name_ar }}</span>
        </div>
      </template>

      <!-- Custom Slot: Code -->
      <template #cell-code="{ value }">
        <code class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-600 font-mono">{{ value }}</code>
      </template>

      <!-- Custom Slot: Fee -->
      <template #cell-fee="{ value }">
        <span class="font-bold text-gray-900">{{ value }} <span class="text-xs font-normal text-gray-500">EGP</span></span>
      </template>

      <!-- Custom Slot: Requirements -->
      <template #cell-requirements="{ row }">
        <div class="flex gap-2">
          <Badge v-if="row.requires_photo" variant="info" size="sm">Photo</Badge>
          <Badge v-if="row.requires_description" variant="warning" size="sm">Description</Badge>
          <span v-if="!row.requires_photo && !row.requires_description" class="text-xs text-gray-400">-</span>
        </div>
      </template>

      <!-- Custom Slot: Status -->
      <template #cell-active="{ value }">
        <Badge :variant="value ? 'success' : 'secondary'">
          {{ value ? 'Active' : 'Inactive' }}
        </Badge>
      </template>

      <!-- Custom Slot: Actions -->
      <template #cell-actions="{ row }">
        <div class="flex justify-end gap-2">
          <button 
            @click.stop="openEditModal(row)"
            class="p-1.5 text-gray-400 hover:text-primary hover:bg-primary-50 rounded-lg transition-all duration-200 hover:scale-110 active:scale-90 ease-in-out"
            title="Edit"
          >
            <Edit2 class="w-4 h-4" />
          </button>
          <button 
            @click.stop="toggleStatus(row)"
            class="p-1.5 text-gray-400 hover:bg-gray-100 rounded-lg transition-all duration-200 hover:scale-110 active:scale-90 ease-in-out"
            :class="row.active ? 'hover:text-warning hover:bg-warning-50' : 'hover:text-success hover:bg-success-50'"
            :title="row.active ? 'Deactivate' : 'Activate'"
          >
            <component :is="row.active ? Ban : CheckCircle" class="w-4 h-4" />
          </button>
          <button 
            @click.stop="confirmDelete(row)"
            class="p-1.5 text-gray-400 hover:text-danger hover:bg-danger-50 rounded-lg transition-all duration-200 hover:scale-110 active:scale-90 ease-in-out"
            title="Delete"
          >
            <Trash2 class="w-4 h-4" />
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <Modal
      v-model="showModal"
      :title="editingType ? 'Edit ID Card Type' : 'New ID Card Type'"
      @close="closeModal"
    >
      <form @submit.prevent="handleSubmit" class="request-form">
        <div class="form-row">
          <div class="form-group">
            <label>Name (English)</label>
            <input
              v-model="form.name_en"
              type="text"
              class="form-control"
              placeholder="e.g. Lost ID Card"
              required
            />
          </div>
          <div class="form-group">
            <label>Name (Arabic)</label>
            <input
              v-model="form.name_ar"
              type="text"
              class="form-control font-arabic"
              placeholder="مثال: بدل فاقد"
              dir="rtl"
              required
            />
          </div>
        </div>

        <div class="form-group">
          <label>Code</label>
          <input
            v-model="form.code"
            type="text"
            class="form-control"
            placeholder="e.g. lost_card"
            :disabled="!!editingType"
            required
          />
          <p class="help-text">Unique identifier used in code logic. Cannot be changed. / معرف فريد يستخدم في النظام. لا يمكن تغييره.</p>
        </div>

        <div class="form-row">
           <div class="form-group">
            <label>Fee (EGP)</label>
            <input
              v-model.number="form.fee"
              type="number"
              class="form-control"
              min="0"
              step="0.01"
              required
            />
          </div>
          <div class="form-group">
            <label>Sort Order</label>
            <input
              v-model.number="form.sort_order"
              type="number"
              class="form-control"
              min="0"
            />
            <p class="help-text">Lower numbers appear first / الأرقام الأقل تظهر أولاً</p>
          </div>
        </div>

         <div class="form-group">
          <label>Description (English)</label>
          <textarea
            v-model="form.description_en"
            rows="2"
            class="form-control"
          ></textarea>
        </div>
        
         <div class="form-group">
          <label>Description (Arabic)</label>
          <textarea
            v-model="form.description_ar"
            rows="2"
            class="form-control font-arabic"
            dir="rtl"
          ></textarea>
        </div>

        <div class="config-section">
          <h4 class="section-title">Configuration</h4>
          
          <label class="config-item group">
            <input type="checkbox" v-model="form.requires_photo" class="checkbox-input group-active:scale-95">
            <div>
              <div class="config-label">Requires Photo</div>
              <div class="config-desc">User must upload a new photo</div>
            </div>
          </label>

          <label class="config-item group">
            <input type="checkbox" v-model="form.requires_description" class="checkbox-input group-active:scale-95">
            <div>
              <div class="config-label">Requires Description</div>
              <div class="config-desc">User must provide additional details</div>
            </div>
          </label>

           <label class="config-item group">
            <input type="checkbox" v-model="form.active" class="checkbox-input group-active:scale-95">
            <div>
              <div class="config-label">Active</div>
              <div class="config-desc">Visible to students</div>
            </div>
          </label>
        </div>

        <div class="modal-actions">
          <Button type="button" variant="secondary" @click="closeModal">Cancel</Button>
          <Button type="submit" :loading="submitting">
            {{ editingType ? 'Update Type' : 'Create Type' }}
          </Button>
        </div>
      </form>
    </Modal>
  </PortalLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { PageHeader, Button, DataTable, Badge, Modal } from '@/components/ui';
import { Plus, Edit2, Trash2, Ban, CheckCircle } from 'lucide-vue-next';
import { adminIdCardApi } from '../api/adminIdCard.api';
import { useToast } from '@/composables/useToast';

const toast = useToast();
const loading = ref(false);
const submitting = ref(false);
const types = ref([]);
const showModal = ref(false);
const editingType = ref(null);

const columns = [
  { key: 'name_en', label: 'Name' },
  { key: 'code', label: 'Code' },
  { key: 'fee', label: 'Fee' },
  { key: 'requirements', label: 'Requirements' },
  { key: 'active', label: 'Status' },
  { key: 'actions', label: 'Actions', align: 'right' }
];

const form = ref({
  code: '',
  name_en: '',
  name_ar: '',
  description_en: '',
  description_ar: '',
  fee: 0,
  requires_photo: false,
  requires_description: false,
  active: true,
  sort_order: 0
});

const resetForm = () => {
  form.value = {
    code: '',
    name_en: '',
    name_ar: '',
    description_en: '',
    description_ar: '',
    fee: 0,
    requires_photo: false,
    requires_description: false,
    active: true,
    sort_order: 0
  };
  editingType.value = null;
};

const fetchTypes = async () => {
  loading.value = true;
  try {
    const data = await adminIdCardApi.getTypes();
    types.value = data;
  } catch (error) {
    toast.error('Failed to load ID card types');
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  resetForm();
  showModal.value = true;
};

const openEditModal = (type) => {
  editingType.value = type;
  // Ensure we copy properties to break reference, and handle potential nulls if needed
  form.value = JSON.parse(JSON.stringify(type));
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  resetForm();
};

const handleSubmit = async () => {
  submitting.value = true;
  try {
    if (editingType.value) {
      await adminIdCardApi.updateType(editingType.value.id, form.value);
      toast.success('Type updated successfully');
    } else {
      await adminIdCardApi.createType(form.value);
      toast.success('Type created successfully');
    }
    closeModal();
    fetchTypes();
  } catch (error) {
    toast.error(error.message || 'Failed to save type');
  } finally {
    submitting.value = false;
  }
};

const toggleStatus = async (type) => {
  try {
    await adminIdCardApi.toggleTypeActive(type.id);
    toast.success(`Type ${type.active ? 'deactivated' : 'activated'} successfully`);
    fetchTypes();
  } catch (error) {
    toast.error('Failed to update status');
  }
};

const confirmDelete = async (type) => {
  if (!confirm('Are you sure you want to delete this type? This cannot be undone.')) return;
  
  try {
    await adminIdCardApi.deleteType(type.id);
    toast.success('Type deleted successfully');
    fetchTypes();
  } catch (error) {
    toast.error(error.message || 'Failed to delete type');
  }
};

onMounted(fetchTypes);
</script>

<style scoped>
/* Reuse professional form styles */
.request-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-lg);
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.form-group label {
  font-size: var(--font-sm);
  font-weight: var(--fw-medium);
  color: var(--color-textMuted);
}

.form-control {
  padding: var(--spacing-md);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--font-base);
  background: var(--color-surface);
  color: var(--color-textMain);
  transition: all var(--transition-fast);
  font-family: inherit;
}

.form-control:disabled {
  background: var(--color-background);
  cursor: not-allowed;
  color: var(--color-textMuted);
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: var(--shadow-focus);
  transform: translateY(-1px);
}

.help-text {
  font-size: var(--font-xs);
  color: var(--color-textMuted);
  margin-top: 2px;
}

/* Config Section */
.config-section {
  background: var(--color-surfaceHighlight);
  padding: var(--spacing-lg);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-borderLight);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.section-title {
  font-size: var(--font-sm);
  font-weight: var(--fw-bold);
  color: var(--color-textStrong);
  margin: 0 0 var(--spacing-xs) 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.config-item {
  display: flex;
  gap: var(--spacing-md);
  align-items: flex-start;
  cursor: pointer;
  padding: var(--spacing-xs) 0;
}

.checkbox-input {
  width: 18px;
  height: 18px;
  margin-top: 2px;
  accent-color: var(--color-primary);
  cursor: pointer;
  border-radius: 4px; /* Note: native checkbox radius support varies */
  transition: transform 0.1s ease;
}

.config-label {
  font-size: var(--font-sm);
  font-weight: var(--fw-medium);
  color: var(--color-textMain);
}

.config-desc {
  font-size: var(--font-xs);
  color: var(--color-textMuted);
}

.modal-actions {
  display: flex;
  gap: var(--spacing-md);
  justify-content: flex-end;
  padding-top: var(--spacing-lg);
  border-top: 1px solid var(--color-borderLight);
}

/* Arabic Font Support */
.font-arabic {
  font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Fallback stack */
}
</style>
