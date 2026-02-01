<template>
  <Modal v-model="isOpen" @close="handleClose" size="lg" :title="`Request: ${type?.name_en || 'ID Card Service'}`">
    <!-- Success State -->
    <div v-if="submitted" class="success-state">
      <div class="success-icon-wrapper">
        <CheckCircle class="success-icon-svg" />
      </div>
      <h3>Request Submitted!</h3>
      <p>Your ID card service request has been submitted successfully. You can track its status in "My Requests".</p>
      <div class="success-actions">
        <Button variant="primary" @click="navigateToMyRequests">
          View My Requests
        </Button>
        <Button variant="text" @click="handleClose">Close</Button>
      </div>
    </div>

    <!-- Form State -->
    <div v-else class="request-form">
      <!-- Error Alert Removed -->

      <!-- Service Info -->
      <div class="form-section">
        <h3>Service Details</h3>
        <div class="service-info">
          <div class="service-icon-wrapper" :style="{ background: iconBackground }">
            <component :is="iconComponent" class="service-icon" />
          </div>
          <div class="service-details">
            <p class="service-name">{{ type.name_en }}</p>
            <p class="service-name-ar">{{ type.name_ar }}</p>
            <p class="service-fee">Fee: <strong>EGP {{ type.fee }}</strong></p>
          </div>
        </div>
      </div>

      <!-- Payment Information -->
      <div class="form-section">
        <h3>Payment Information</h3>
        <div class="payment-info-card">
          <div class="payment-row">
            <span>Account Name:</span>
            <span>{{ settings.payment_account_name }}</span>
          </div>
          <div class="payment-row">
            <span>Account Number:</span>
            <span class="copyable" @click="copyToClipboard(settings.payment_account_number)">
              {{ settings.payment_account_number }}
              <Copy class="copy-icon" />
            </span>
          </div>
          <div class="payment-row">
            <span>Amount to Pay:</span>
            <strong class="amount">EGP {{ type.fee }}</strong>
          </div>
          <p v-if="settings.payment_instructions" class="payment-instructions">
            {{ settings.payment_instructions }}
          </p>
        </div>
      </div>

      <!-- Payment Details Form -->
      <div class="form-section">
        <h3>Payment Details</h3>
        <div class="form-row">
          <div class="form-group">
            <label for="transaction_number">Transaction Number *</label>
            <input
              id="transaction_number"
              v-model.trim="form.transaction_number"
              type="text"
              class="form-control"
              placeholder="Enter transaction number"
              :disabled="submitting"
            />
            />
            <p class="help-text">أدخل رقم المعاملة الموجود في إيصال الدفع</p>
          </div>
          <div class="form-group">
            <label for="transfer_time">Transfer Date & Time *</label>
            <input
              id="transfer_time"
              v-model="form.transfer_time"
              type="datetime-local"
              class="form-control"
              :max="maxDateTime"
              :disabled="submitting"
            />
            />
            <p class="help-text">يرجى تحديد وقت وتاريخ التحويل بدقة</p>
          </div>
        </div>

        <div v-if="isEditMode && editRequest.transfer_screenshot_url && !screenshotFile" class="existing-proof">
             <p class="section-label">Current Screenshot:</p>
             <a :href="editRequest.transfer_screenshot_url" target="_blank" class="view-proof-link">
               <img :src="editRequest.transfer_screenshot_url" alt="Current Screenshot" class="proof-thumbnail" />
               <span>View Current Screenshot</span>
             </a>
             <p class="help-text">Upload a new file below only if you want to replace this one.</p>
        </div>

        <FileUpload
          v-model="screenshotFile"
          :label="isEditMode ? 'Replace Transfer Screenshot' : 'Transfer Screenshot'"
          accept="image/jpeg,image/png"
          :max-size="5 * 1024 * 1024"
          help-text="صورة واضحة لإيصال الدفع (JPG/PNG، بحد أقصى 5 ميجابايت)"
          :error="validationErrors.transfer_screenshot?.[0]"
          :disabled="submitting"
          :required="!isEditMode"
        />
      </div>

      <!-- Conditional: New Photo (for photo_change) -->
      <div v-if="type.requires_photo" class="form-section">
        <h3>New Photo</h3>
        
        <div v-if="isEditMode && editRequest.new_photo_url && !photoFile" class="existing-proof">
             <p class="section-label">Current Photo:</p>
             <a :href="editRequest.new_photo_url" target="_blank" class="view-proof-link">
               <img :src="editRequest.new_photo_url" alt="Current Photo" class="proof-thumbnail" />
               <span>View Current Photo</span>
             </a>
             <p class="help-text">Upload a new file below only if you want to replace this one.</p>
        </div>

        <FileUpload
          v-model="photoFile"
          :label="isEditMode ? 'Replace New Photo' : 'Upload New Photo'"
          accept="image/jpeg,image/png"
          :max-size="2 * 1024 * 1024"
          help-text="صورة شخصية حديثة بخلفية بيضاء (JPG/PNG، بحد أقصى 2 ميجابايت)"
          :error="validationErrors.new_photo?.[0]"
          :disabled="submitting"
          :required="!isEditMode && !editRequest?.new_photo_url"
        />
      </div>

      <!-- Conditional: Issue Description (for damaged) -->
      <div v-if="type.requires_description" class="form-section">
        <h3>Issue Description</h3>
        <div class="form-group">
          <label for="issue_description">Describe the Issue *</label>
          <textarea
            id="issue_description"
            v-model.trim="form.issue_description"
            class="form-control"
            rows="4"
            placeholder="Please describe the damage or issue..."
            :disabled="submitting"
            maxlength="1000"
          ></textarea>
          <p class="help-text">يرجى وصف المشكلة الموجودة في البطاقة الحالية بالتفصيل</p>
          <span class="help-text">{{ form.issue_description.length }}/1000 characters</span>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="modal-actions">
        <Button variant="text" @click="handleClose" :disabled="submitting">Cancel</Button>
        <Button 
          variant="primary" 
          type="button"
          @click.prevent="handleSubmit" 
          :disabled="!canSubmit || submitting"
          :loading="submitting"
        >
          {{ submitting ? (isEditMode ? 'Updating...' : 'Submitting...') : (isEditMode ? 'Update Request' : 'Submit Request') }}
        </Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { Modal, Button, FileUpload } from '@/components/ui';
import { idCardApi } from '../api/idCard.api';
import { useToast } from '@/composables/useToast';
import { CheckCircle, Copy, CreditCard, Camera, Wrench } from 'lucide-vue-next';

const toast = useToast();
const router = useRouter();

const props = defineProps({
  type: {
    type: Object,
    required: true
  },
  settings: {
    type: Object,
    required: true
  },
  editRequest: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'submitted']);

// State
const isOpen = ref(true);
const submitting = ref(false);
const submitted = ref(false);
const errorMessage = ref('');
const validationErrors = ref({});

// Form data
const form = ref({
  transaction_number: '',
  transfer_time: '',
  issue_description: ''
});

const screenshotFile = ref(null);
const photoFile = ref(null);

// Computed
const isEditMode = computed(() => !!props.editRequest);

const iconComponent = computed(() => {
  const icons = {
    lost: CreditCard,
    photo_change: Camera,
    damaged: Wrench,
    new: CreditCard,
    renew: CreditCard
  };
  return icons[props.type.code] || CreditCard;
});

const iconBackground = computed(() => {
  const backgrounds = {
    lost: 'var(--color-warningBg)',
    photo_change: 'var(--color-successBg)',
    damaged: 'var(--color-dangerBg)',
    new: 'var(--color-primaryBg)',
    renew: 'var(--color-infoBg)'
  };
  return backgrounds[props.type.code] || 'var(--color-surfaceHighlight)';
});

const maxDateTime = computed(() => {
  const now = new Date();
  return now.toISOString().slice(0, 16);
});

const canSubmit = computed(() => {
  if (!form.value.transaction_number || !form.value.transfer_time) {
    return false;
  }
  
  // Screenshot check
  if (!isEditMode.value && !screenshotFile.value) {
    return false; // Required for new
  }
  if (isEditMode.value && !screenshotFile.value && !props.editRequest.has_transfer_screenshot) {
      // If editing, no new file AND no old file -> invalid
      return false;
  }

  // Photo check
  if (props.type.requires_photo) {
      if (!isEditMode.value && !photoFile.value) {
          return false;
      }
      if (isEditMode.value && !photoFile.value && !props.editRequest.has_new_photo) {
          return false;
      }
  }

  if (props.type.requires_description && !form.value.issue_description) {
    return false;
  }
  return true;
});

// Methods
const handleSubmit = async () => {
  console.log('handleSubmit called');
  console.log('canSubmit:', canSubmit.value);
  console.log('submitting:', submitting.value);
  
  if (!canSubmit.value || submitting.value) {
      console.warn('Submission blocked. canSubmit:', canSubmit.value, 'submitting:', submitting.value);
      return;
  }

  submitting.value = true;
  errorMessage.value = '';
  validationErrors.value = {};

  try {
    const formData = new FormData();
    formData.append('type_code', props.type.code);
    formData.append('transaction_number', form.value.transaction_number);
    formData.append('transfer_time', form.value.transfer_time);
    
    if (screenshotFile.value) {
        formData.append('transfer_screenshot', screenshotFile.value);
    }

    if (props.type.requires_photo && photoFile.value) {
      formData.append('new_photo', photoFile.value);
    }

    if (props.type.requires_description && form.value.issue_description) {
      formData.append('issue_description', form.value.issue_description);
    }
    
    if (isEditMode.value) {
        await idCardApi.updateRequest(props.editRequest.id, formData);
        toast.success('Request updated successfully!');
    } else {
        await idCardApi.submitRequest(formData);
        toast.success('Request submitted successfully!');
    }

    // Success
    submitted.value = true;
    emit('submitted');

  } catch (error) {
    console.error('Submission error:', error);

    if (error.status === 422) {
      errorMessage.value = error.message || 'Validation failed. Please check your inputs.';
      validationErrors.value = error.errors || {};

      // Extract the first error message
      const firstError = Object.values(error.errors)[0]?.[0] || 'Please check the form for errors.';
      toast.error(firstError);
    } else if (error.status === 409) {
      const msg = error.message || 'You already have a pending request for this service.';
      toast.error(msg, 6000);
      if (!isEditMode.value) handleClose();
    } else {
      errorMessage.value = error.message || 'Failed to submit request. Please try again.';
      toast.error(errorMessage.value);
    }
  } finally {
    submitting.value = false;
  }
};

const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text);
  toast.success('Copied to clipboard!');
};

const handleClose = () => {
  emit('close');
};

const navigateToMyRequests = () => {
  router.push('/student/my-requests');
  handleClose();
};

onMounted(() => {
  if (isEditMode.value) {
      const r = props.editRequest;
      form.value.transaction_number = r.transaction_number;
      // Handle transfer_time format (might be YYYY-MM-DD HH:mm:ss, input wants YYYY-MM-DDTHH:mm)
      // Assuming r.transfer_time is SQL format or ISO
      // SQL: 2024-01-30 12:00:00 -> replace space with T, slice 0-16
      if (r.transfer_time) {
         form.value.transfer_time = r.transfer_time.replace(' ', 'T').slice(0, 16);
      }
      form.value.issue_description = r.issue_description || '';
  } else {
      // Set current datetime
      const now = new Date();
      form.value.transfer_time = now.toISOString().slice(0, 16);
  }
});
</script>

<style scoped>
.request-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xl);
}

.success-state {
  text-align: center;
  padding: var(--spacing-2xl);
  display: flex;
  flex-direction: column;
  align-items: center;
}

.success-icon-wrapper {
  width: 80px;
  height: 80px;
  background: var(--color-successBg);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: var(--spacing-xl);
}

.success-icon-svg {
  width: 48px;
  height: 48px;
  color: var(--color-success);
}

.success-state h3 {
  font-size: 24px;
  font-weight: 700;
  color: var(--color-textStrong);
  margin-bottom: var(--spacing-md);
}

.success-state p {
  color: var(--color-textMuted);
  margin-bottom: var(--spacing-xl);
  max-width: 400px;
  line-height: 1.6;
}

.success-actions {
  display: flex;
  gap: var(--spacing-md);
  margin-top: var(--spacing-lg);
}

.error-alert {
  background: var(--color-dangerBg);
  border: 1px solid var(--color-danger);
  color: var(--color-dangerText);
  padding: var(--spacing-md);
  border-radius: var(--radius-md);
  font-size: var(--font-sm);
}

.error-alert ul {
  margin: var(--spacing-sm) 0 0 var(--spacing-lg);
  padding: 0;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.form-section h3 {
  margin: 0;
  font-size: var(--font-md);
  font-weight: var(--fw-bold);
  color: var(--color-textStrong);
  border-bottom: 1px solid var(--color-borderLight);
  padding-bottom: var(--spacing-xs);
}

.service-info {
  display: flex;
  gap: var(--spacing-lg);
  align-items: center;
  padding: var(--spacing-md);
  background: var(--color-surfaceHighlight);
  border-radius: var(--radius-md);
}

.service-icon-wrapper {
  width: 60px;
  height: 60px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.service-icon {
  width: 32px;
  height: 32px;
  color: var(--color-primary);
}

.service-details {
  flex: 1;
}

.service-name {
  font-weight: var(--fw-semibold);
  color: var(--color-textMain);
  margin: 0 0 var(--spacing-xs) 0;
}

.service-name-ar {
  font-size: var(--font-sm);
  color: var(--color-textMuted);
  direction: rtl;
  margin: 0 0 var(--spacing-xs) 0;
}

.service-fee {
  font-size: var(--font-sm);
  color: var(--color-textSecondary);
  margin: 0;
}

.service-fee strong {
  color: var(--color-primary);
  font-size: var(--font-base);
}

.payment-info-card {
  background: var(--color-surfaceHighlight);
  border-radius: var(--radius-md);
  padding: var(--spacing-lg);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.payment-row {
  display: flex;
  justify-content: space-between;
  padding: var(--spacing-sm) 0;
  border-bottom: 1px solid var(--color-borderLight);
  font-size: var(--font-sm);
}

.payment-row:last-of-type {
  border-bottom: none;
}

.payment-row span:first-child {
  color: var(--color-textSecondary);
}

.payment-row span:last-child {
  font-weight: var(--fw-medium);
  color: var(--color-textMain);
}

.copyable {
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-xs);
  transition: color var(--transition-fast);
}

.copyable:hover {
  color: var(--color-primary);
}

.copy-icon {
  width: 14px;
  height: 14px;
  opacity: 0.6;
}

.amount {
  color: var(--color-primary) !important;
  font-weight: var(--fw-bold) !important;
}

.payment-instructions {
  margin-top: var(--spacing-sm);
  padding: var(--spacing-md);
  background: var(--color-infoBg);
  border-radius: var(--radius-sm);
  color: var(--color-infoText);
  font-size: var(--font-sm);
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
}

textarea.form-control {
  resize: vertical;
  min-height: 100px;
}

.help-text {
  font-size: 11px;
  color: var(--color-textMuted);
}

.modal-actions {
  display: flex;
  gap: var(--spacing-md);
  justify-content: flex-end;
  padding-top: var(--spacing-lg);
  border-top: 1px solid var(--color-borderLight);
  margin-top: var(--spacing-md);
}
</style>
