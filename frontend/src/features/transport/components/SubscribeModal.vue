<template>
  <Modal v-model="isOpen" @close="handleClose" size="lg" :title="`Subscribe to ${route.name_en}`">
    <!-- Success State -->
    <div v-if="submitted" class="success-state">
      <div class="success-icon">✅</div>
      <h3>Request Submitted Successfully!</h3>
      <p>Your subscription request has been submitted and is pending review.</p>
      <Button variant="primary" @click="handleClose">Close</Button>
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

      <!-- Schedule Selection -->
      <div class="form-section">
        <h3>Select Day & Time</h3>
        <div class="form-row">
          <div class="form-group">
            <label>Day of Week</label>
            <select v-model="selectedDay" class="form-control">
              <option value="">Select day</option>
              <option v-for="day in availableDays" :key="day.value" :value="day.value">
                {{ day.label }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Departure Time</label>
            <select v-model="selectedTime" class="form-control" :disabled="!selectedDay">
              <option value="">Select time</option>
              <option v-for="slot in availableTimes" :key="slot.time" :value="slot.time">
                {{ slot.time }} 
                <span v-if="settings?.show_capacity !== false">({{ slot.seats_available > 0 ? `${slot.seats_available} seats` : 'Waitlist' }})</span>
                <span v-else>({{ slot.seats_available > 0 ? 'Available' : 'Waitlist' }})</span>
              </option>
            </select>
          </div>
        </div>

        <!-- Slot Capacity Indicator -->
        <div v-if="selectedSlot" class="slot-capacity mt-2">
           <div v-if="selectedSlot.seats_available > 0" class="capacity-badge available">
              <span class="dot"></span>
              {{ settings?.show_capacity !== false ? `${selectedSlot.seats_available} seats available` : 'Seats Available' }}
           </div>
           <div v-else class="capacity-badge waitlist">
              <span class="dot red"></span>
              Full - Waitlist Only
           </div>
        </div>
      </div>

      <!-- Subscription Plan Selection -->
      <div class="form-section">
        <h3>Subscription Plan</h3>
        <div class="plan-options">
          <label class="radio-option" :class="{ selected: selectedPlan === 'monthly' }">
            <input type="radio" value="monthly" v-model="selectedPlan" />
            <div class="plan-info">
              <span class="plan-name">Monthly Plan</span>
              <span class="plan-discount">{{ route.pricing?.monthly_discount_percent }}% off</span>
            </div>
          </label>
          <label class="radio-option" :class="{ selected: selectedPlan === 'term' }">
            <input type="radio" value="term" v-model="selectedPlan" />
            <div class="plan-info">
              <span class="plan-name">Term Plan</span>
              <span class="plan-discount">{{ route.pricing?.term_discount_percent }}% off</span>
            </div>
          </label>
        </div>
        <div v-if="computedPrice" class="price-display">
          <strong>Amount to pay:</strong> EGP {{ computedPrice }}
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
          {{ submitting ? 'Submitting...' : (isWaitlist ? 'Join Waitlist' : 'Submit Request') }}
        </Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Modal, Button } from '@/components/ui';
import { transportApi } from '../api/transport.api';
import { useToast } from '@/composables/useToast';

const toast = useToast();

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

const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// State
const isOpen = ref(true); // Modal visibility for v-model
const selectedPlan = ref('monthly');
const selectedDay = ref('');
const selectedTime = ref('');
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

const availableDays = computed(() => {
  if (!props.route.slots) return [];
  return Object.keys(props.route.slots).map(dayNum => ({
    value: parseInt(dayNum),
    label: DAY_NAMES[dayNum] || `Day ${dayNum}`
  }));
});

const availableTimes = computed(() => {
  if (!selectedDay.value || !props.route.slots) return [];
  return props.route.slots[selectedDay.value] || [];
});

const selectedSlot = computed(() => {
    if (!selectedTime.value || !availableTimes.value) return null;
    return availableTimes.value.find(slot => slot.time === selectedTime.value);
});

const isWaitlist = computed(() => {
    return selectedSlot.value && selectedSlot.value.seats_available <= 0;
});

const canSubmit = computed(() => {
  return selectedPlan.value && 
         selectedDay.value !== '' && 
         selectedTime.value && 
         selectedPaymentMethod.value && 
         paidFromNumber.value.length >= 8 &&
         paidAt.value &&
         proofFile.value;
});

const selectedPaymentMethodDetails = computed(() => {
  return paymentMethods.value.find(m => m.id === selectedPaymentMethod.value);
});

const computedPrice = computed(() => {
  if (!props.route.pricing || !props.settings) return null;
  
  const priceOneWay = props.route.pricing.price_one_way;
  const dailyRoundTrip = priceOneWay * 2;
  const daysPerWeek = props.settings.days_per_week || 5;
  
  if (selectedPlan.value === 'monthly') {
    const weeksInMonth = props.settings.weeks_in_month || 4;
    const baseTotal = dailyRoundTrip * daysPerWeek * weeksInMonth;
    const discount = props.route.pricing.monthly_discount_percent || 0;
    return (baseTotal * (1 - discount / 100)).toFixed(2);
  } else {
    const weeksInTerm = props.settings.weeks_in_term || 12;
    const baseTotal = dailyRoundTrip * daysPerWeek * weeksInTerm;
    const discount = props.route.pricing.term_discount_percent || 0;
    return (baseTotal * (1 - discount / 100)).toFixed(2);
  }
});

// Watch for day change to reset time
watch(selectedDay, () => {
  selectedTime.value = '';
});

// Methods
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
    formData.append('day_of_week', selectedDay.value);
    formData.append('time', selectedTime.value);
    formData.append('plan_type', selectedPlan.value);
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
      // Use Toast for conflict error as requested
      const msg = error.message || 'You already have a pending or active subscription.';
      toast.error(msg, 6000);
      handleClose(); // Optional: close modal since they can't subscribe? 
      // User didn't say to close, but if they already have one, they can't proceed. 
      // But maybe they want to see the error and then close.
      // Let's just show toast and keep modal open or close it? 
      // If I close it, they see the toast on the main page.
      // "You already have..." implies looking at the list.
      // Let's close the modal so they are back on the grid and see the toast.
      handleClose();
    } else if (error.status === 422) {
      errorMessage.value = error.message || 'Validation failed. Please check your inputs.';
      validationErrors.value = error.errors;
      // Also show a toast for better visibility
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

// Lifecycle
onMounted(() => {
  fetchPaymentMethods();
  // Set default date to today
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
}

.success-icon {
  font-size: 48px;
  margin-bottom: var(--spacing-lg);
}

.success-state h3 {
  color: var(--color-success);
  margin-bottom: var(--spacing-md);
  color: var(--color-textStrong);
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
}

.plan-name {
  font-weight: var(--fw-medium);
  color: var(--color-textMain);
}

.plan-discount {
  font-size: var(--font-xs);
  color: var(--color-success);
  font-weight: var(--fw-medium);
}

.price-display {
  margin-top: var(--spacing-lg);
  padding: var(--spacing-lg);
  background: var(--color-surfaceHighlight);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  color: var(--color-textStrong);
  font-size: var(--font-xl);
  text-align: center;
  font-weight: var(--fw-bold);
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

.capacity-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: var(--radius-full);
    font-size: 13px;
    font-weight: 600;
    margin-top: 8px;
}

.capacity-badge.available {
    background: var(--color-successBg);
    color: var(--color-success);
}

.capacity-badge.waitlist {
    background: var(--color-warningBg);
    color: var(--color-warningText);
    background: #FFF7ED;
    color: #C2410C;
    border: 1px solid #FED7AA;
}

.dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
}

.dot.red {
    background: #C2410C;
}
</style>

