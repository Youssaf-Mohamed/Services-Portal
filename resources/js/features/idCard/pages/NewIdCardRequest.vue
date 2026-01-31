<template>
  <PortalLayout>
    <PageHeader
      :title="pageTitle"
      :subtitle="pageSubtitle"
      :breadcrumbs="[
        { label: 'Home', to: '/student' },
        { label: 'ID Card Services', to: '/student/id-card' },
        { label: 'New Request' }
      ]"
    />

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <SkeletonLoader height="400px" border-radius="var(--radius-lg)" />
    </div>

    <!-- Error State -->
    <EmptyState
      v-else-if="error"
      :icon="AlertCircle"
      title="Error"
      :message="error"
      actionText="Go Back"
      @action="goBack"
    />

    <!-- Type Not Found -->
    <EmptyState
      v-else-if="!selectedType"
      :icon="HelpCircle"
      title="Service Not Found"
      message="The requested service type was not found."
      actionText="Go Back"
      @action="goBack"
    />

    <!-- Request Form -->
    <div v-else class="form-container">
      <!-- Service Summary -->
      <section class="section service-summary">
        <div class="summary-icon-wrapper" :style="{ background: iconBackground }">
          <component :is="iconComponent" class="summary-icon" />
        </div>
        <div class="summary-content">
          <h2>{{ selectedType.name_en }}</h2>
          <p class="ar-text">{{ selectedType.name_ar }}</p>
          <p class="description">{{ selectedType.description_en }}</p>
          <div class="fee-display">
            <span class="fee-label">Service Fee:</span>
            <span class="fee-amount">{{ selectedType.fee }} EGP</span>
          </div>
        </div>
      </section>

      <!-- Payment Information -->
      <section class="section">
        <h3 class="section-title">
          <CreditCard class="section-icon" /> Payment Information
        </h3>
        <div v-if="settings" class="payment-info-card">
          <div class="payment-row">
            <span class="label">Account Name:</span>
            <span class="value">{{ settings.payment_account_name }}</span>
          </div>
          <div class="payment-row">
            <span class="label">Account Number:</span>
            <span class="value copyable" @click="copyToClipboard(settings.payment_account_number)">
              {{ settings.payment_account_number }}
              <Copy class="copy-icon" />
            </span>
          </div>
          <div class="payment-row">
            <span class="label">Amount to Pay:</span>
            <span class="value amount">{{ selectedType.fee }} EGP</span>
          </div>
          <div v-if="settings.payment_instructions" class="payment-instructions">
            <p>{{ settings.payment_instructions }}</p>
          </div>
        </div>
      </section>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="request-form">
        <!-- Payment Details Section -->
        <section class="section">
          <h3 class="section-title">
            <FileText class="section-icon" /> Payment Details
          </h3>
          
          <div class="form-group">
            <label for="transaction_number" class="form-label">Transaction Number *</label>
            <input
              id="transaction_number"
              v-model.trim="form.transaction_number"
              type="text"
              class="form-input"
              placeholder="Enter the transaction/reference number"
              required
              :disabled="submitting"
            />
            <span v-if="validationErrors.transaction_number" class="error-text">
              {{ validationErrors.transaction_number }}
            </span>
          </div>

          <div class="form-group">
            <label for="transfer_time" class="form-label">Transfer Date & Time *</label>
            <input
              id="transfer_time"
              v-model="form.transfer_time"
              type="datetime-local"
              class="form-input"
              required
              :max="maxDateTime"
              :disabled="submitting"
            />
            <span v-if="validationErrors.transfer_time" class="error-text">
              {{ validationErrors.transfer_time }}
            </span>
          </div>

          <div class="form-group">
            <label for="transfer_screenshot" class="form-label">Transfer Screenshot *</label>
            <div class="file-input-wrapper">
              <input
                id="transfer_screenshot"
                ref="screenshotInput"
                type="file"
                accept="image/jpeg,image/png"
                @change="handleScreenshotChange"
                required
                :disabled="submitting"
              />
              <div v-if="screenshotPreview" class="file-preview">
                <img :src="screenshotPreview" alt="Screenshot preview" />
                <button type="button" class="remove-file" @click="removeScreenshot">
                  <X class="remove-icon" />
                </button>
              </div>
            </div>
            <span class="help-text">Upload a screenshot of your payment confirmation (JPG/PNG, max 5MB)</span>
            <span v-if="validationErrors.transfer_screenshot" class="error-text">
              {{ validationErrors.transfer_screenshot }}
            </span>
          </div>
        </section>

        <!-- Conditional: New Photo (for photo_change) -->
        <section v-if="selectedType.requires_photo" class="section">
          <h3 class="section-title">
            <Camera class="section-icon" /> New Photo
          </h3>
          
          <div class="form-group">
            <label for="new_photo" class="form-label">Upload New Photo *</label>
            <div class="file-input-wrapper">
              <input
                id="new_photo"
                ref="photoInput"
                type="file"
                accept="image/jpeg,image/png"
                @change="handlePhotoChange"
                required
                :disabled="submitting"
              />
              <div v-if="photoPreview" class="file-preview">
                <img :src="photoPreview" alt="Photo preview" />
                <button type="button" class="remove-file" @click="removePhoto">
                  <X class="remove-icon" />
                </button>
              </div>
            </div>
            <span class="help-text">Upload a passport-style photo for your new ID card (JPG/PNG, max 2MB)</span>
            <span v-if="validationErrors.new_photo" class="error-text">
              {{ validationErrors.new_photo }}
            </span>
          </div>
        </section>

        <!-- Conditional: Issue Description (for damaged) -->
        <section v-if="selectedType.requires_description" class="section">
          <h3 class="section-title">
            <ClipboardList class="section-icon" /> Issue Description
          </h3>
          
          <div class="form-group">
            <label for="issue_description" class="form-label">Describe the Issue *</label>
            <textarea
              id="issue_description"
              v-model.trim="form.issue_description"
              class="form-textarea"
              rows="4"
              placeholder="Please describe the damage or issue with your ID card..."
              required
              :disabled="submitting"
            ></textarea>
            <span class="help-text">{{ form.issue_description.length }}/1000 characters</span>
            <span v-if="validationErrors.issue_description" class="error-text">
              {{ validationErrors.issue_description }}
            </span>
          </div>
        </section>

        <!-- Submit -->
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="goBack" :disabled="submitting">
            Cancel
          </button>
          <button type="submit" class="btn btn-primary" :disabled="submitting || !isFormValid">
            <span v-if="submitting">Submitting...</span>
            <span v-else>Submit Request</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="modal-overlay" @click.self="closeSuccessModal">
      <div class="modal success-modal">
        <div class="modal-icon-wrapper success">
          <CheckCircle class="modal-icon" />
        </div>
        <h2>Request Submitted!</h2>
        <p>Your ID card service request has been submitted successfully. You can track its status in "My Requests".</p>
        <button class="btn btn-primary" @click="goToMyRequests">
          View My Requests
        </button>
      </div>
    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { PageHeader, EmptyState, SkeletonLoader } from '@/components/ui';
import { idCardApi } from '../api/idCard.api';
import { 
  AlertCircle, HelpCircle, CreditCard, Copy, 
  FileText, Camera, ClipboardList, CheckCircle,
  Wrench, X
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const submitting = ref(false);
const error = ref('');
const types = ref([]);
const settings = ref(null);
const selectedType = ref(null);
const validationErrors = ref({});
const showSuccessModal = ref(false);

// Form data
const form = ref({
  transaction_number: '',
  transfer_time: '',
  issue_description: ''
});

// File handling
const screenshotFile = ref(null);
const screenshotPreview = ref(null);
const photoFile = ref(null);
const photoPreview = ref(null);
const screenshotInput = ref(null);
const photoInput = ref(null);

// Computed
const typeCode = computed(() => route.params.typeCode);

const pageTitle = computed(() => {
  if (selectedType.value) {
    return `Request: ${selectedType.value.name_en}`;
  }
  return 'New ID Card Request';
});

const pageSubtitle = computed(() => {
  if (selectedType.value) {
    return selectedType.value.description_en;
  }
  return 'Submit a new ID card service request';
});

const iconComponent = computed(() => {
  const icons = {
    new: CreditCard,
    renew: CreditCard,
    lost: ClipboardList,
    photo_change: Camera,
    damaged: Wrench
  };
  return icons[typeCode.value] || ClipboardList;
});

const iconBackground = computed(() => {
  const backgrounds = {
    new: 'var(--color-primaryBg)',
    renew: 'var(--color-infoBg)',
    lost: 'var(--color-warningBg)',
    photo_change: 'var(--color-successBg)',
    damaged: 'var(--color-dangerBg)'
  };
  return backgrounds[typeCode.value] || 'var(--color-surfaceHighlight)';
});

const maxDateTime = computed(() => {
  const now = new Date();
  return now.toISOString().slice(0, 16);
});

const isFormValid = computed(() => {
  if (!form.value.transaction_number || !form.value.transfer_time || !screenshotFile.value) {
    return false;
  }
  if (selectedType.value?.requires_photo && !photoFile.value) {
    return false;
  }
  if (selectedType.value?.requires_description && !form.value.issue_description) {
    return false;
  }
  return true;
});

// Methods
const fetchData = async () => {
  loading.value = true;
  error.value = '';
  
  try {
    const [typesResponse, settingsResponse] = await Promise.all([
      idCardApi.getTypes(),
      idCardApi.getSettings()
    ]);
    
    types.value = typesResponse.data || [];
    settings.value = settingsResponse.data || null;
    
    // Find the selected type by code
    selectedType.value = types.value.find(t => t.code === typeCode.value) || null;
    
    if (!selectedType.value) {
      error.value = 'The requested service type is not available.';
    }
  } catch (err) {
    console.error('Failed to load ID card data:', err);
    error.value = err.message || 'Failed to load service data';
  } finally {
    loading.value = false;
  }
};

const handleScreenshotChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      validationErrors.value.transfer_screenshot = 'File size must be less than 5MB';
      return;
    }
    screenshotFile.value = file;
    screenshotPreview.value = URL.createObjectURL(file);
    validationErrors.value.transfer_screenshot = null;
  }
};

const removeScreenshot = () => {
  screenshotFile.value = null;
  screenshotPreview.value = null;
  if (screenshotInput.value) {
    screenshotInput.value.value = '';
  }
};

const handlePhotoChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    if (file.size > 2 * 1024 * 1024) {
      validationErrors.value.new_photo = 'File size must be less than 2MB';
      return;
    }
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
    validationErrors.value.new_photo = null;
  }
};

const removePhoto = () => {
  photoFile.value = null;
  photoPreview.value = null;
  if (photoInput.value) {
    photoInput.value.value = '';
  }
};

const handleSubmit = async () => {
  if (!isFormValid.value || submitting.value) return;
  
  submitting.value = true;
  validationErrors.value = {};
  
  try {
    const formData = new FormData();
    formData.append('type_code', typeCode.value);
    formData.append('transaction_number', form.value.transaction_number);
    formData.append('transfer_time', form.value.transfer_time);
    formData.append('transfer_screenshot', screenshotFile.value);
    
    if (selectedType.value?.requires_photo && photoFile.value) {
      formData.append('new_photo', photoFile.value);
    }
    
    if (selectedType.value?.requires_description && form.value.issue_description) {
      formData.append('issue_description', form.value.issue_description);
    }
    
    await idCardApi.submitRequest(formData);
    showSuccessModal.value = true;
  } catch (err) {
    console.error('Submission error:', err);
    
    if (err.errors) {
      // Handle validation errors
      Object.keys(err.errors).forEach(key => {
        validationErrors.value[key] = err.errors[key][0] || err.errors[key];
      });
    } else {
      error.value = err.message || 'Failed to submit request';
    }
  } finally {
    submitting.value = false;
  }
};

const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text);
  // Could add a toast notification here
};

const goBack = () => {
  router.push('/student/id-card');
};

const closeSuccessModal = () => {
  showSuccessModal.value = false;
  router.push('/student/my-requests');
};

const goToMyRequests = () => {
  router.push('/student/my-requests');
};

onMounted(() => {
  fetchData();
});
</script>

<style scoped>
.loading-container,
.form-container {
  max-width: 800px;
  margin: 0 auto;
}

.section {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-xl);
  margin-bottom: var(--spacing-xl);
}

.section-title {
  margin: 0 0 var(--spacing-lg) 0;
  font-size: var(--font-lg);
  font-weight: var(--fw-semibold);
  color: var(--color-textMain);
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
}

.section-icon {
  width: 20px;
  height: 20px;
  color: var(--color-primary);
}

/* Service Summary */
.service-summary {
  display: flex;
  gap: var(--spacing-xl);
  align-items: flex-start;
}

.summary-icon-wrapper {
  width: 80px;
  height: 80px;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-primary);
  flex-shrink: 0;
}

.summary-icon {
  width: 40px;
  height: 40px;
}

.summary-content h2 {
  margin: 0 0 var(--spacing-xs) 0;
  color: var(--color-textMain);
  font-size: var(--font-xl);
}

.summary-content .ar-text {
  margin: 0 0 var(--spacing-sm) 0;
  color: var(--color-textMuted);
  direction: rtl;
  font-family: 'Tahoma', sans-serif;
}

.summary-content .description {
  margin: 0 0 var(--spacing-lg) 0;
  color: var(--color-textSecondary);
}

.fee-display {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.fee-label {
  color: var(--color-textMuted);
}

.fee-amount {
  font-size: var(--font-xl);
  font-weight: var(--fw-bold);
  color: var(--color-primary);
}

/* Payment Info */
.payment-info-card {
  background: var(--color-background);
  border-radius: var(--radius-md);
  padding: var(--spacing-lg);
}

.payment-row {
  display: flex;
  justify-content: space-between;
  padding: var(--spacing-sm) 0;
  border-bottom: 1px solid var(--color-border);
}

.payment-row:last-of-type {
  border-bottom: none;
}

.payment-row .label {
  color: var(--color-textSecondary);
}

.payment-row .value {
  font-weight: var(--fw-medium);
  color: var(--color-textMain);
  display: flex;
  align-items: center;
}

.payment-row .value.copyable {
  cursor: pointer;
  transition: color var(--transition-fast);
}

.payment-row .value.copyable:hover {
  color: var(--color-primary);
}

.payment-row .value.amount {
  color: var(--color-primary);
  font-weight: var(--fw-bold);
}

.copy-icon {
  width: 14px;
  height: 14px;
  margin-left: var(--spacing-xs);
  opacity: 0.6;
}

.payment-instructions {
  margin-top: var(--spacing-md);
  padding: var(--spacing-md);
  background: var(--color-infoBg);
  border-radius: var(--radius-sm);
  color: var(--color-infoText);
}

.payment-instructions p {
  margin: 0;
}

/* Form */
.form-group {
  margin-bottom: var(--spacing-lg);
}

.form-label {
  display: block;
  margin-bottom: var(--spacing-sm);
  font-weight: var(--fw-medium);
  color: var(--color-textMain);
}

.form-input,
.form-textarea {
  width: 100%;
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface);
  color: var(--color-textMain);
  font-size: var(--font-base);
  transition: border-color var(--transition-fast);
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px var(--color-primaryLight);
}

.form-input:disabled,
.form-textarea:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background: var(--color-background);
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

.file-input-wrapper {
  position: relative;
}

.file-input-wrapper input[type="file"] {
  width: 100%;
  padding: var(--spacing-md);
  border: 2px dashed var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-background);
  cursor: pointer;
  transition: border-color var(--transition-fast);
}

.file-input-wrapper input[type="file"]:hover {
  border-color: var(--color-primary);
}

.file-preview {
  position: relative;
  margin-top: var(--spacing-md);
  max-width: 200px;
}

.file-preview img {
  width: 100%;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}

.remove-file {
  position: absolute;
  top: -8px;
  right: -8px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: var(--color-danger);
  color: white;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform var(--transition-fast);
}

.remove-file:hover {
  transform: scale(1.1);
}

.remove-icon {
  width: 14px;
  height: 14px;
}

.help-text {
  display: block;
  margin-top: var(--spacing-xs);
  font-size: var(--font-xs);
  color: var(--color-textMuted);
}

.error-text {
  display: block;
  margin-top: var(--spacing-xs);
  font-size: var(--font-sm);
  color: var(--color-danger);
}

/* Form Actions */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-md);
  padding-top: var(--spacing-lg);
}

.btn {
  padding: var(--spacing-sm) var(--spacing-xl);
  border-radius: var(--radius-md);
  font-weight: var(--fw-medium);
  cursor: pointer;
  transition: all var(--transition-fast);
  border: none;
  font-size: var(--font-base);
}

.btn-primary {
  background: var(--color-primary);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--color-primaryDark);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: var(--color-neutralBg);
  color: var(--color-neutralText);
}

.btn-secondary:hover:not(:disabled) {
  background: var(--color-border);
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.modal {
  background: var(--color-surface);
  border-radius: var(--radius-lg);
  padding: var(--spacing-2xl);
  max-width: 400px;
  width: 90%;
  text-align: center;
  box-shadow: var(--shadow-xl);
}

.modal-icon-wrapper {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto var(--spacing-lg);
}

.modal-icon-wrapper.success {
  background: var(--color-successBg);
  color: var(--color-success);
}

.modal-icon {
  width: 40px;
  height: 40px;
}

.modal h2 {
  margin: 0 0 var(--spacing-md) 0;
  color: var(--color-textMain);
  font-size: var(--font-xl);
}

.modal p {
  margin: 0 0 var(--spacing-xl) 0;
  color: var(--color-textSecondary);
  line-height: 1.5;
}

@media (max-width: 768px) {
  .service-summary {
    flex-direction: column;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .form-actions .btn {
    width: 100%;
  }
}
</style>
