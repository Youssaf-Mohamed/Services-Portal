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
      <!-- Error Alert -->
      <div v-if="errorMessage" class="error-alert">
        <strong>Error:</strong> {{ errorMessage }}
        <ul v-if="validationErrors && Object.keys(validationErrors).length">
          <li v-for="(errors, field) in validationErrors" :key="field">
            {{ field }}: {{ Array.isArray(errors) ? errors.join(', ') : errors }}
          </li>
        </ul>
      </div>

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
          </div>
        </div>

        <FileUpload
          v-model="screenshotFile"
          label="Transfer Screenshot"
          accept="image/jpeg,image/png"
          :max-size="5 * 1024 * 1024"
          help-text="Upload screenshot of payment (JPG/PNG, max 5MB)"
          :error="validationErrors.transfer_screenshot"
          :disabled="submitting"
          required
        />
      </div>

      <!-- Conditional: New Photo (for photo_change) -->
      <div v-if="type.requires_photo" class="form-section">
        <h3>New Photo</h3>
        <FileUpload
          v-model="photoFile"
          label="Upload New Photo"
          accept="image/jpeg,image/png"
          :max-size="2 * 1024 * 1024"
          help-text="Passport-style photo for new ID card (JPG/PNG, max 2MB)"
          :error="validationErrors.new_photo"
          :disabled="submitting"
          required
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
          <span class="help-text">{{ form.issue_description.length }}/1000 characters</span>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="modal-actions">
        <Button variant="text" @click="handleClose" :disabled="submitting">Cancel</Button>
        <Button 
          variant="primary" 
          @click="handleSubmit" 
          :disabled="!canSubmit || submitting"
          :loading="submitting"
        >
          {{ submitting ? 'Submitting...' : 'Submit Request' }}
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
  if (!form.value.transaction_number || !form.value.transfer_time || !screenshotFile.value) {
    return false;
  }
  if (props.type.requires_photo && !photoFile.value) {
    return false;
  }
  if (props.type.requires_description && !form.value.issue_description) {
    return false;
  }
  return true;
});

// Methods
const handleSubmit = async () => {
  if (!canSubmit.value || submitting.value) return;

  submitting.value = true;
  errorMessage.value = '';
  validationErrors.value = {};

  try {
    const formData = new FormData();
    formData.append('type_code', props.type.code);
    formData.append('transaction_number', form.value.transaction_number);
    formData.append('transfer_time', form.value.transfer_time);
    formData.append('transfer_screenshot', screenshotFile.value);

    if (props.type.requires_photo && photoFile.value) {
      formData.append('new_photo', photoFile.value);
    }

    if (props.type.requires_description && form.value.issue_description) {
      formData.append('issue_description', form.value.issue_description);
    }

    await idCardApi.submitRequest(formData);

    // Success
    submitted.value = true;
    emit('submitted');
    toast.success('Request submitted successfully!');

  } catch (error) {
    console.error('Submission error:', error);

    if (error.status === 422) {
      errorMessage.value = error.message || 'Validation failed. Please check your inputs.';
      validationErrors.value = error.errors || {};
      toast.error('Please check the form for errors.');
    } else if (error.status === 409) {
      const msg = error.message || 'You already have a pending request for this service.';
      toast.error(msg, 6000);
      handleClose();
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
  // Set current datetime
  const now = new Date();
  form.value.transfer_time = now.toISOString().slice(0, 16);
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
  font-size: var(--font-xs);
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
