<template>
  <Modal v-model="isOpen" @close="handleClose" size="lg" :title="`Subscribe to ${route.name_en}`">
    <!-- Success State -->
    <div v-if="submitted" class="success-state">
      <div class="success-icon-wrapper">
        <CheckCircle class="success-icon-svg" />
      </div>
      <h3>Request Submitted!</h3>
      <p>Your subscription request has been received and is pending review. You can track its status in your dashboard.</p>
      <div class="success-actions">
        <Button variant="primary" @click="navigateToRequests">
          Track Status
        </Button>
        <Button variant="text" @click="handleClose">Close</Button>
      </div>
    </div>

    <!-- Form State -->
    <div v-else class="subscription-form">
      <!-- Error Alert -->
      <div v-if="errorMessage" class="error-alert">
        <strong>Error:</strong> {{ errorMessage }}
        <ul v-if="validationErrors">
          <li v-for="(errors, field) in validationErrors" :key="field">
            {{ field }}: {{ errors.join(', ') }}
          </li>
        </ul>
      </div>

      <!-- Route Info -->
      <div class="form-section">
        <h3>Route Details</h3>
        <p><strong>Route:</strong> {{ route.name_en }}</p>
        <p><strong>One-way price:</strong> EGP {{ route.pricing?.price_one_way }}</p>
      </div>

      <!-- Plan Selection -->
      <div class="form-section">
        <h3>Select Subscription Plan</h3>
        <div v-if="loadingPlans" class="loading-inline">Loading plans...</div>
          <!-- Monthly Plans -->
          <div v-if="monthlyPlans.length > 0" class="plan-group">
            <h4 class="plan-group-title">Monthly Plans</h4>
            <div class="plan-list">
              <label 
                v-for="plan in monthlyPlans" 
                :key="plan.id"
                class="radio-option" 
                :class="{ selected: selectedPlanId === plan.id }"
              >
                <input type="radio" :value="plan.id" v-model="selectedPlanId" />
                <div class="plan-info">
                  <span class="plan-name">{{ plan.name_en }}</span>
                  <span class="plan-meta">{{ plan.allowed_days_per_week }} days/week</span>
                  <span class="plan-discount" v-if="planDiscount(plan) > 0">{{ planDiscount(plan) }}% off</span>
                </div>
              </label>
            </div>
          </div>

          <!-- Term Plans -->
          <div v-if="termPlans.length > 0" class="plan-group">
            <h4 class="plan-group-title">Term Plans (3 Months)</h4>
            <div class="plan-list">
               <label 
                v-for="plan in termPlans" 
                :key="plan.id"
                class="radio-option" 
                :class="{ selected: selectedPlanId === plan.id }"
              >
                <input type="radio" :value="plan.id" v-model="selectedPlanId" />
                <div class="plan-info">
                  <span class="plan-name">{{ plan.name_en }}</span>
                  <span class="plan-meta">{{ plan.allowed_days_per_week }} days/week</span>
                  <span class="plan-discount" v-if="planDiscount(plan) > 0">{{ planDiscount(plan) }}% off</span>
                </div>
              </label>
            </div>
          </div>
      </div>

      <!-- Days Selection -->
      <div v-if="selectedPlan" class="form-section">
        <DaysSelector
          v-model="selectedDays"
          :allowedDays="selectedPlan.allowed_days_per_week"
          label="Select Your Transport Days"
        />
      </div>

      <!-- Price Display -->
      <div v-if="computedPrice" class="form-section">
        <div class="price-display">
          <div class="price-breakdown">
            <div class="breakdown-row">
              <span>Daily round-trip cost:</span>
              <span>EGP {{ (route.pricing?.price_one_way * 2).toFixed(2) }}</span>
            </div>
            <div class="breakdown-row">
              <span>Selected days:</span>
              <span>{{ selectedDays.length }} days/week</span>
            </div>
            <div class="breakdown-row">
              <span>Duration:</span>
              <span>{{ selectedPlan.plan_type === 'monthly' ? `${settings?.weeks_in_month || 4} weeks` : `${settings?.weeks_in_term || 12} weeks (3 months)` }}</span>
            </div>
            <div class="breakdown-row">
              <span>Discount:</span>
              <span>{{ planDiscount(selectedPlan) }}%</span>
            </div>
            <div class="breakdown-row total">
              <strong>Amount to pay:</strong>
              <strong>EGP {{ computedPrice }}</strong>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Method Selection -->
      <div class="form-section">
        <h3>Payment Method</h3>
        <div v-if="loadingPaymentMethods" class="loading-inline">Loading payment methods...</div>
        <select v-else v-model="selectedPaymentMethod" class="form-control">
          <option value="">Select payment method</option>
          <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
            {{ method.name }} - {{ method.account_number }}
          </option>
        </select>
        <p v-if="selectedPaymentMethodDetails?.instructions" class="help-text">
          {{ selectedPaymentMethodDetails.instructions }}
        </p>
      </div>

      <!-- Payment Details -->
      <div class="form-section">
        <h3>Payment Details</h3>
        <div class="form-row">
          <div class="form-group">
            <label>Payment Phone Number</label>
            <input 
              type="text" 
              v-model="paidFromNumber" 
              placeholder="e.g., 01012345678"
              class="form-control"
            />
          </div>
          <div class="form-group">
            <label>Payment Date</label>
            <input 
              type="date" 
              v-model="paidAt" 
              class="form-control"
              :max="todayDate"
            />
          </div>
        </div>
      </div>

      <!-- Upload Payment Proof -->
      <div class="form-section">
        <h3>Upload Payment Proof</h3>
        <input 
          type="file" 
          accept="image/jpeg,image/png,image/webp"
          @change="handleFileUpload"
          class="file-input"
          ref="fileInput"
        />
        <p class="help-text">Upload receipt/proof of payment (JPEG, PNG, or WebP, max 2MB)</p>
        <div v-if="proofPreview" class="file-preview">
          <img :src="proofPreview" alt="Payment proof preview" />
          <span>{{ proofFile?.name }}</span>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="modal-actions">
        <Button variant="text" @click="handleClose" :disabled="submitting">Cancel</Button>
        <Button 
          variant="primary" 
          @click="handleSubmit" 
          :disabled="!canSubmit || submitting"
        >
          {{ submitting ? 'Submitting...' : 'Submit Request' }}
        </Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { Modal, Button } from '@/components/ui';
import DaysSelector from './DaysSelector.vue';
import { transportApi } from '../api/transport.api';
import { useToast } from '@/composables/useToast';
import { CheckCircle } from 'lucide-vue-next';

const toast = useToast();
const router = useRouter();

const props = defineProps({
  route: {
    type: Object,
    required: true
  },
  settings: {
    type: Object,
    default: () => ({ days_per_week: 5, weeks_in_month: 4, weeks_in_term: 12 })
  }
});

const emit = defineEmits(['close', 'submitted']);

// State
const isOpen = ref(true);
const plans = ref([]);
const loadingPlans = ref(false);
const selectedPlanId = ref(null);
const selectedDays = ref([]);
const selectedPaymentMethod = ref('');
const paidFromNumber = ref('');
const paidAt = ref('');
const proofFile = ref(null);
const proofPreview = ref(null);
const paymentMethods = ref([]);
const loadingPaymentMethods = ref(false);
const submitting = ref(false);
const submitted = ref(false);
const errorMessage = ref('');
const validationErrors = ref(null);

// Computed
const todayDate = computed(() => new Date().toISOString().split('T')[0]);

const selectedPlan = computed(() => {
  return plans.value.find(p => p.id === selectedPlanId.value);
});

const monthlyPlans = computed(() => plans.value.filter(p => p.plan_type === 'monthly'));
const termPlans = computed(() => plans.value.filter(p => p.plan_type === 'term'));

const planDiscount = (plan) => {
  if (!plan || !props.route.pricing) return 0;
  return plan.plan_type === 'monthly' 
    ? props.route.pricing.monthly_discount_percent 
    : props.route.pricing.term_discount_percent;
};

const canSubmit = computed(() => {
  return selectedPlanId.value && 
         selectedDays.value.length === selectedPlan.value?.allowed_days_per_week &&
         selectedPaymentMethod.value && 
         paidFromNumber.value.length >= 8 &&
         paidAt.value &&
         proofFile.value;
});

const selectedPaymentMethodDetails = computed(() => {
  return paymentMethods.value.find(m => m.id === selectedPaymentMethod.value);
});

const computedPrice = computed(() => {
  if (!selectedPlan.value || !props.route.pricing || !props.settings || selectedDays.value.length === 0) return null;
  
  const priceOneWay = props.route.pricing.price_one_way;
  const dailyRoundTrip = priceOneWay * 2;
  const selectedDaysCount = selectedDays.value.length;
  
  if (selectedPlan.value.plan_type === 'monthly') {
    const weeksInMonth = props.settings.weeks_in_month || 4;
    const baseTotal = dailyRoundTrip * selectedDaysCount * weeksInMonth;
    const discount = props.route.pricing.monthly_discount_percent || 0;
    return (baseTotal * (1 - discount / 100)).toFixed(2);
  } else {
    const weeksInTerm = props.settings.weeks_in_term || 12;
    const baseTotal = dailyRoundTrip * selectedDaysCount * weeksInTerm;
    const discount = props.route.pricing.term_discount_percent || 0;
    return (baseTotal * (1 - discount / 100)).toFixed(2);
  }
});

// Watch for plan change to reset days
watch(selectedPlanId, () => {
  selectedDays.value = [];
});

// Methods
const fetchPlans = async () => {
  loadingPlans.value = true;
  try {
    const response = await transportApi.getPlans();
    plans.value = response.data.plans || [];
  } catch (error) {
    console.error('Failed to load plans:', error);
    errorMessage.value = 'Failed to load subscription plans. Please try again.';
  } finally {
    loadingPlans.value = false;
  }
};

const fetchPaymentMethods = async () => {
  loadingPaymentMethods.value = true;
  try {
    const response = await transportApi.getPaymentMethods();
    paymentMethods.value = response.data || [];
  } catch (error) {
    console.error('Failed to load payment methods:', error);
    errorMessage.value = 'Failed to load payment methods. Please try again.';
  } finally {
    loadingPaymentMethods.value = false;
  }
};

const handleFileUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    proofFile.value = file;
    proofPreview.value = URL.createObjectURL(file);
  }
};

const handleSubmit = async () => {
  if (!canSubmit.value || submitting.value) return;
  
  submitting.value = true;
  errorMessage.value = '';
  validationErrors.value = null;
  
  try {
    // Build FormData
    const formData = new FormData();
    formData.append('route_id', props.route.id);
    formData.append('plan_id', selectedPlanId.value);
    formData.append('plan_type', selectedPlan.value.plan_type);
    
    // Append selected days as JSON array
    selectedDays.value.forEach((day) => {
      formData.append('selected_days[]', day);
    });
    
    formData.append('payment_method_id', selectedPaymentMethod.value);
    formData.append('paid_from_number', paidFromNumber.value);
    formData.append('paid_at', paidAt.value);
    formData.append('amount_paid', computedPrice.value);
    formData.append('proof', proofFile.value);
    
    // Submit to API
    await transportApi.submitSubscriptionRequest(formData);
    
    // Success
    submitted.value = true;
    emit('submitted');
    
  } catch (error) {
    console.error('Submission error:', error);
    
    if (error.status === 409) {
      const msg = error.message || 'You already have a pending or active subscription.';
      toast.error(msg, 6000);
      handleClose();
    } else if (error.status === 422) {
      errorMessage.value = error.message || 'Validation failed. Please check your inputs.';
      validationErrors.value = error.errors;
      toast.error('Please check the form for errors.');
    } else {
      toast.error(error.message || 'Failed to submit request. Please try again.');
    }
  } finally {
    submitting.value = false;
  }
};

const handleClose = () => {
  if (proofPreview.value) {
    URL.revokeObjectURL(proofPreview.value);
  }
  emit('close');
};

const navigateToRequests = () => {
  router.push('/student/transport/my-requests');
  handleClose();
};

// Lifecycle
onMounted(() => {
  fetchPlans();
  fetchPaymentMethods();
  paidAt.value = todayDate.value;
});
</script>

<style scoped>
.subscription-form {
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
  background: var(--color-successBg, #ecfdf5);
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
  margin-bottom: var(--spacing-lg);
  font-size: var(--font-sm);
}

.error-alert ul {
  margin: var(--spacing-sm) 0 0 var(--spacing-lg);
  padding: 0;
}

.form-section h3 {
  margin: 0 0 var(--spacing-md) 0;
  font-size: var(--font-md);
  font-weight: var(--fw-bold);
  color: var(--color-textStrong);
  border-bottom: 1px solid var(--color-borderLight);
  padding-bottom: var(--spacing-xs);
}

.form-section p {
  margin: var(--spacing-xs) 0;
  color: var(--color-textMain);
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

.plan-options {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.plan-group {
  margin-bottom: var(--spacing-md);
}

.plan-group:last-child {
  margin-bottom: 0;
}

.plan-group-title {
  font-size: var(--font-sm);
  font-weight: var(--fw-bold);
  color: var(--color-textMuted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0 0 var(--spacing-sm) 0;
}

.plan-list {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-sm);
}

.radio-option {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  padding: var(--spacing-md);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.radio-option:hover {
  background: var(--color-surfaceHighlight);
}

.radio-option.selected {
  border-color: var(--color-primary);
  background: var(--color-surfaceHighlight);
  box-shadow: 0 0 0 1px var(--color-primary) inset;
}

.plan-info {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.plan-name {
  font-weight: var(--fw-medium);
  color: var(--color-textMain);
}

.plan-meta {
  font-size: var(--font-sm);
  color: var(--color-textMuted);
}

.plan-discount {
  font-size: var(--font-xs);
  color: var(--color-success);
  font-weight: var(--fw-medium);
}

.price-display {
  background: var(--color-surfaceHighlight);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: var(--spacing-lg);
}

.price-breakdown {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.breakdown-row {
  display: flex;
  justify-content: space-between;
  font-size: var(--font-sm);
  color: var(--color-textMain);
}

.breakdown-row.total {
  margin-top: var(--spacing-md);
  padding-top: var(--spacing-md);
  border-top: 2px solid var(--color-border);
  font-size: var(--font-lg);
  color: var(--color-textStrong);
}

.loading-inline {
  padding: var(--spacing-md);
  color: var(--color-textMuted);
  font-style: italic;
}

.file-input {
  padding: var(--spacing-md);
  border: 2px dashed var(--color-border);
  border-radius: var(--radius-md);
  width: 100%;
  cursor: pointer;
  transition: border-color var(--transition-fast);
}

.file-input:hover {
  border-color: var(--color-primaryLight);
}

.file-preview {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  margin-top: var(--spacing-md);
  padding: var(--spacing-sm);
  background: var(--color-surfaceHighlight);
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-borderLight);
}

.file-preview img {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: var(--radius-sm);
}

.help-text {
  font-size: var(--font-xs);
  color: var(--color-textMuted);
  margin-top: var(--spacing-xs);
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
