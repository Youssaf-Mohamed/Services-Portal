<template>
  <PortalLayout>
    <PageHeader
      title="Request Details"
      subtitle="Review subscription request and take action"
      :breadcrumbs="[
        { label: 'Admin', to: '/admin/transport' },
        { label: 'Requests', to: '/admin/transport/requests' },
        { label: `Request #${requestId}` }
      ]"
    />

    <!-- Loading State -->
    <div v-if="loading" class="loading-grid">
      <div class="skeleton-wide">
        <SkeletonLoader height="160px" />
      </div>
      <SkeletonLoader height="160px" />
      <SkeletonLoader height="200px" />
      <SkeletonLoader height="240px" />
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
      <Button variant="primary" @click="fetchRequest">Retry</Button>
    </div>

    <!-- Request Details -->
    <div v-else-if="request" class="details-content">
      <div class="details-grid">
        <!-- Status Card -->
        <Card class="status-card">
          <template #header>
            <h3>Status</h3>
          </template>
          <div class="status-display">
            <Badge :variant="getStatusVariant(request.status)" size="lg">
              {{ request.status }}
            </Badge>
            <p class="status-date">
              Submitted: {{ formatDate(request.created_at) }}
            </p>
          </div>

          <!-- Actions for pending requests -->
          <div v-if="request.status === 'pending'" class="actions-section">
            <div v-if="request.payment_status !== 'verified'" class="payment-warning">
              <AlertTriangle class="warning-icon" />
              <span>Payment must be verified before approval</span>
            </div>
            <Button 
              variant="primary" 
              @click="showApproveModal = true"
              :disabled="request.payment_status !== 'verified'"
            >
              <Check class="btn-icon" /> Approve
            </Button>
            <Button variant="danger" @click="showRejectModal = true">
              <X class="btn-icon" /> Reject
            </Button>
          </div>

          <!-- Approval info -->
          <div v-if="request.approver" class="approval-info">
            <p v-if="request.status === 'approved'">
              Approved by <strong>{{ request.approver.name }}</strong>
              on {{ formatDate(request.approved_at) }}
            </p>
            <p v-if="request.status === 'rejected'">
              Rejected by <strong>{{ request.approver.name }}</strong>
              on {{ formatDate(request.approved_at) }}
            </p>
            <p v-if="request.rejection_reason" class="rejection-reason">
              Reason: {{ request.rejection_reason }}
            </p>
          </div>

          <!-- Subscription info -->
          <div v-if="request.subscription" class="subscription-info">
            <h4>Subscription Created</h4>
            <Badge :variant="request.subscription.status === 'active' ? 'success' : 'warning'">
              {{ request.subscription.status }}
            </Badge>
            <p>Start: {{ request.subscription.start_date }}</p>
            <p>End: {{ request.subscription.end_date }}</p>
            <p v-if="request.subscription.days_remaining !== null">
              Days remaining: {{ request.subscription.days_remaining }}
            </p>
          </div>
        </Card>

        <!-- Payment Review Card (for pending requests) -->
        <Card v-if="request.status === 'pending'" class="payment-review-card">
          <template #header>
            <div class="card-header-row">
              <h3>Payment Review</h3>
              <Badge :variant="getPaymentStatusVariant(request.payment_status)">
                {{ formatPaymentStatus(request.payment_status) }}
              </Badge>
            </div>
          </template>

          <!-- Payment Status Description -->
          <div v-if="request.payment_status === 'pending_verification'" class="status-info pending-info">
            <Clock class="status-icon" />
            <p>Payment proof submitted. Please review and verify.</p>
          </div>
          <div v-else-if="request.payment_status === 'verified'" class="status-info success-info">
            <CheckCircle class="status-icon" />
            <p>Payment verified. Ready for approval.</p>
          </div>
          <div v-else-if="request.payment_status === 'flagged'" class="status-info danger-info">
            <AlertCircle class="status-icon" />
            <div class="flag-details">
              <p><strong>Payment flagged for review</strong></p>
              <p v-if="request.payment_flag_reason" class="flag-reason">
                Reason: {{ request.payment_flag_reason }}
              </p>
            </div>
          </div>

          <!-- Proof Preview -->
          <div v-if="request.proof_exists" class="proof-preview-section">
            <h4>Payment Proof</h4>
            <div v-if="proofLoading" class="proof-loading">Loading proof...</div>
            <div v-else-if="proofImageUrl" class="proof-image-container">
              <img :src="proofImageUrl" alt="Payment proof" class="proof-image" @click="openProofInNewTab" />
              <p class="proof-hint">Click to view full size</p>
            </div>
            <div v-else class="proof-fallback">
              <a :href="proofUrl" target="_blank" class="proof-link">
                <FileText class="link-icon" /> Download Proof File
              </a>
            </div>
          </div>

          <!-- Verification Actions -->
          <div v-if="request.payment_status !== 'verified'" class="payment-actions">
            <Button 
              variant="primary" 
              @click="handleVerifyPayment"
              :disabled="verifying"
            >
              <CheckCircle class="btn-icon" />
              {{ verifying ? 'Verifying...' : 'Verify Payment' }}
            </Button>
            <Button 
              variant="warning" 
              @click="showFlagModal = true"
              :disabled="verifying"
            >
              <Flag class="btn-icon" />
              Flag Payment
            </Button>
          </div>

          <!-- Verification Info -->
          <div v-if="request.payment_verified_at" class="verification-info">
            <p>
              Verified on {{ formatDate(request.payment_verified_at) }}
            </p>
          </div>
        </Card>

        <!-- Student Info Card -->
        <Card>
          <template #header>
            <h3>Student Information</h3>
          </template>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Name</span>
              <span class="info-value">{{ request.user.name }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Email</span>
              <span class="info-value">{{ request.user.email }}</span>
            </div>
            <div class="info-item" v-if="request.user.student_id">
              <span class="info-label">Student ID</span>
              <span class="info-value">{{ request.user.student_id }}</span>
            </div>
          </div>
        </Card>

        <!-- Route & Slot Card -->
        <Card>
          <template #header>
            <h3>Route & Schedule</h3>
          </template>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Route</span>
              <span class="info-value">{{ request.route?.name_en || 'N/A' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Day</span>
              <span class="info-value">{{ getDayName(request.slot?.day_of_week) }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Time</span>
              <span class="info-value">{{ request.slot?.time || 'N/A' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Direction</span>
              <span class="info-value">{{ request.slot?.direction || 'N/A' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Plan Type</span>
              <Badge :variant="request.plan_type === 'monthly' ? 'info' : 'secondary'">
                {{ request.plan_type }}
              </Badge>
            </div>
          </div>
        </Card>

        <!-- Payment Card -->
        <Card>
          <template #header>
            <h3>Payment Details</h3>
          </template>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Payment Method</span>
              <span class="info-value">{{ request.payment_method?.name || 'N/A' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Paid From</span>
              <span class="info-value">{{ request.paid_from_number || 'N/A' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Amount Expected</span>
              <span class="info-value amount">{{ request.amount_expected }} EGP</span>
            </div>
            <div class="info-item" v-if="request.pricing_snapshot">
              <span class="info-label">Discount</span>
              <span class="info-value">{{ request.pricing_snapshot.discount_percent || 0 }}%</span>
            </div>
          </div>

          <!-- Proof -->
          <div v-if="request.proof_exists" class="proof-section">
            <h4>Payment Proof</h4>
            <div v-if="proofLoading" class="proof-loading">Loading proof...</div>
            <div v-else-if="proofImageUrl" class="proof-image-container">
              <img :src="proofImageUrl" alt="Payment proof" class="proof-image" @click="openProofInNewTab" />
              <p class="proof-hint">Click image to view full size</p>
            </div>
            <div v-else class="proof-error">
              <p>Could not load proof image</p>
              <a :href="proofUrl" target="_blank" class="proof-link">📄 Download Proof</a>
            </div>
          </div>
        </Card>
      </div>
    </div>

    <!-- Approve Modal -->
    <Modal v-model="showApproveModal" title="Approve Request">
      <div class="modal-body">
        <p>Are you sure you want to approve this subscription request?</p>
        
        <div class="form-group">
          <label>Start Date (optional)</label>
          <input type="date" v-model="approveForm.start_date" :min="today" />
          <p class="help-text">Leave empty to use today's date</p>
        </div>

        <div v-if="approving" class="processing">
          <div class="spinner-small"></div>
          Processing...
        </div>
        <div v-if="approveError" class="error-text">{{ approveError }}</div>
      </div>
      <template #footer>
        <Button variant="secondary" @click="showApproveModal = false" :disabled="approving">
          Cancel
        </Button>
        <Button variant="primary" @click="handleApprove" :disabled="approving">
          Confirm Approval
        </Button>
      </template>
    </Modal>

    <!-- Reject Modal -->
    <Modal v-model="showRejectModal" title="Reject Request">
      <div class="modal-body">
        <p>Please provide a reason for rejecting this request:</p>
        
        <div class="form-group">
          <label>Rejection Reason *</label>
          <textarea 
            v-model="rejectForm.rejection_reason" 
            rows="4"
            placeholder="Enter at least 10 characters..."
          ></textarea>
          <p class="char-count">{{ rejectForm.rejection_reason.length }}/500</p>
        </div>

        <div v-if="rejecting" class="processing">
          <div class="spinner-small"></div>
          Processing...
        </div>
        <div v-if="rejectError" class="error-text">{{ rejectError }}</div>
      </div>
      <template #footer>
        <Button variant="secondary" @click="showRejectModal = false" :disabled="rejecting">
          Cancel
        </Button>
        <Button variant="danger" @click="handleReject" :disabled="rejecting || rejectForm.rejection_reason.length < 10">
          Confirm Rejection
        </Button>
      </template>
    </Modal>

    <!-- Flag Payment Modal -->
    <Modal v-model="showFlagModal" title="Flag Payment">
      <div class="modal-body">
        <p>Flag this payment proof for review. Please provide a reason:</p>
        
        <div class="form-group">
          <label>Reason for Flagging *</label>
          <textarea 
            v-model="flagForm.reason" 
            rows="4"
            placeholder="e.g., Proof image is unclear, Amount doesn't match, etc."
          ></textarea>
          <p class="char-count">{{ flagForm.reason.length }}/500</p>
        </div>

        <div v-if="flagging" class="processing">
          <div class="spinner-small"></div>
          Processing...
        </div>
        <div v-if="flagError" class="error-text">{{ flagError }}</div>
      </div>
      <template #footer>
        <Button variant="secondary" @click="showFlagModal = false" :disabled="flagging">
          Cancel
        </Button>
        <Button variant="warning" @click="handleFlagPayment" :disabled="flagging || flagForm.reason.length < 5">
          Flag Payment
        </Button>
      </template>
    </Modal>
  </PortalLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PortalLayout from '@/layouts/PortalLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Button from '@/components/ui/Button.vue';
import Badge from '@/components/ui/Badge.vue';
import Modal from '@/components/ui/Modal.vue';
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue';
import { adminTransportApi } from '../api/adminTransport.api';
import { useToast } from '@/composables/useToast';
import { 
  Check, X, AlertTriangle, Clock, CheckCircle, 
  AlertCircle, FileText, Flag 
} from 'lucide-vue-next';

const toast = useToast();

const route = useRoute();
const router = useRouter();
const requestId = computed(() => route.params.id);

const loading = ref(true);
const error = ref(null);
const request = ref(null);

const showApproveModal = ref(false);
const showRejectModal = ref(false);
const showFlagModal = ref(false);
const approving = ref(false);
const rejecting = ref(false);
const verifying = ref(false);
const flagging = ref(false);
const approveError = ref(null);
const rejectError = ref(null);
const flagError = ref(null);

const approveForm = reactive({ start_date: '' });
const rejectForm = reactive({ rejection_reason: '' });
const flagForm = reactive({ reason: '' });

const proofLoading = ref(false);
const proofImageUrl = ref(null);

const today = computed(() => new Date().toISOString().split('T')[0]);
const proofUrl = computed(() => adminTransportApi.getProofUrl(requestId.value));

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const fetchRequest = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await adminTransportApi.getRequest(requestId.value);
    if (response.data.success) {
      request.value = response.data.data;
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load request';
  } finally {
    loading.value = false;
  }
};

const handleApprove = async () => {
  try {
    console.log('Starting approval process for request:', requestId.value);
    approving.value = true;
    approveError.value = null;
    
    const payload = {};
    if (approveForm.start_date) {
      payload.start_date = approveForm.start_date;
    }
    console.log('Approval payload:', payload);

    const response = await adminTransportApi.approveRequest(requestId.value, payload);
    console.log('Approval response:', response);

    if (response.data.success) {
      showApproveModal.value = false;
      toast.success(response.data.data.message || 'Request approved successfully');
      // Refresh request data to ensure consistency
      await fetchRequest();
    } else {
        throw new Error(response.data.message || 'Unknown error occurred');
    }
  } catch (err) {
    console.error('Approval error:', err);
    approveError.value = err.response?.data?.message || err.message || 'Failed to approve request';
  } finally {
    approving.value = false;
  }
};

const handleReject = async () => {
  try {
    console.log('Starting rejection process for request:', requestId.value);
    rejecting.value = true;
    rejectError.value = null;

    const response = await adminTransportApi.rejectRequest(requestId.value, {
      rejection_reason: rejectForm.rejection_reason,
    });
    console.log('Rejection response:', response);
    
    if (response.data.success) {
      showRejectModal.value = false;
      toast.success(response.data.data.message || 'Request rejected successfully');
      // Refresh request data to ensure consistency
      await fetchRequest();
    } else {
        throw new Error(response.data.message || 'Unknown error occurred');
    }
  } catch (err) {
    console.error('Rejection error:', err);
    rejectError.value = err.response?.data?.message || err.message || 'Failed to reject request';
  } finally {
    rejecting.value = false;
  }
};

const handleVerifyPayment = async () => {
  try {
    verifying.value = true;
    const response = await adminTransportApi.verifyPayment(requestId.value);
    if (response.data.success) {
      toast.success('Payment verified successfully');
      await fetchRequest();
    }
  } catch (err) {
    console.error('Verification error:', err);
    toast.error(err.response?.data?.message || 'Failed to verify payment');
  } finally {
    verifying.value = false;
  }
};

const handleFlagPayment = async () => {
  try {
    flagging.value = true;
    flagError.value = null;
    const response = await adminTransportApi.flagPayment(requestId.value, flagForm.reason);
    if (response.data.success) {
      showFlagModal.value = false;
      flagForm.reason = '';
      toast.warning('Payment flagged for review');
      await fetchRequest();
    }
  } catch (err) {
    console.error('Flag error:', err);
    flagError.value = err.response?.data?.message || 'Failed to flag payment';
  } finally {
    flagging.value = false;
  }
};

const getStatusVariant = (status) => {
  switch (status) {
    case 'pending': return 'warning';
    case 'approved': return 'success';
    case 'rejected': return 'danger';
    default: return 'secondary';
  }
};

const getPaymentStatusVariant = (status) => {
  switch (status) {
    case 'pending_verification': return 'warning';
    case 'verified': return 'success';
    case 'flagged': return 'danger';
    default: return 'secondary';
  }
};

const formatPaymentStatus = (status) => {
  switch (status) {
    case 'pending_verification': return 'Pending Verification';
    case 'verified': return 'Verified';
    case 'flagged': return 'Flagged';
    default: return status || 'Unknown';
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const getDayName = (day) => {
  return dayNames[day] || 'N/A';
};

const loadProofImage = async () => {
  if (!request.value?.proof_exists) return;
  
  try {
    proofLoading.value = true;
    const response = await adminTransportApi.downloadProof(requestId.value);
    const blob = new Blob([response.data], { type: response.headers['content-type'] });
    proofImageUrl.value = URL.createObjectURL(blob);
  } catch (err) {
    console.error('Failed to load proof image:', err);
    proofImageUrl.value = null;
  } finally {
    proofLoading.value = false;
  }
};

const openProofInNewTab = () => {
  if (proofImageUrl.value) {
    window.open(proofImageUrl.value, '_blank');
  }
};

onMounted(fetchRequest);

// Watch for request changes to load proof image
watch(() => request.value?.proof_exists, (hasProof) => {
  if (hasProof) {
    loadProofImage();
  }
}, { immediate: true });
</script>

<style scoped>
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-3xl);
  color: var(--color-textMuted);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: var(--spacing-md);
}

.spinner-small {
  width: 20px;
  height: 20px;
  border: 2px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-state {
  text-align: center;
  padding: var(--spacing-3xl);
  color: var(--color-danger);
}

.details-content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xl);
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-lg);
}

.status-card {
  grid-column: span 2;
}

.status-display {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  margin-bottom: var(--spacing-lg);
}

.status-date {
  color: var(--color-textMuted);
  font-size: 14px;
}

.actions-section {
  display: flex;
  gap: var(--spacing-md);
  padding-top: var(--spacing-lg);
  border-top: 1px solid var(--color-border);
}

.approval-info {
  margin-top: var(--spacing-lg);
  padding-top: var(--spacing-lg);
  border-top: 1px solid var(--color-border);
  font-size: 14px;
  color: var(--color-textMuted);
}

.rejection-reason {
  margin-top: var(--spacing-sm);
  padding: var(--spacing-md);
  background: #fef2f2;
  border-radius: var(--radius-md);
  color: var(--color-danger);
}

.subscription-info {
  margin-top: var(--spacing-lg);
  padding-top: var(--spacing-lg);
  border-top: 1px solid var(--color-border);
}

.subscription-info h4 {
  margin-bottom: var(--spacing-sm);
  font-size: 14px;
  font-weight: 600;
}

.subscription-info p {
  font-size: 14px;
  color: var(--color-textMuted);
  margin-top: var(--spacing-xs);
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-md);
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-textMuted);
  text-transform: uppercase;
}

.info-value {
  font-size: 15px;
  color: var(--color-textMain);
}

.info-value.amount {
  font-weight: 600;
  color: var(--color-primary);
}

.proof-section {
  margin-top: var(--spacing-lg);
  padding-top: var(--spacing-lg);
  border-top: 1px solid var(--color-border);
}

.proof-section h4 {
  margin-bottom: var(--spacing-sm);
  font-size: 14px;
  font-weight: 600;
}

.proof-link {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-sm);
  color: var(--color-primary);
  text-decoration: none;
  font-weight: 500;
}

.proof-link:hover {
  text-decoration: underline;
}

.modal-body {
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
  font-size: 14px;
  font-weight: 500;
  color: var(--color-textMain);
}

.form-group input,
.form-group textarea {
  padding: 10px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 14px;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: var(--shadow-focusRing);
}

.help-text {
  font-size: 12px;
  color: var(--color-textMuted);
}

.char-count {
  font-size: 12px;
  color: var(--color-textMuted);
  text-align: right;
}

.processing {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  color: var(--color-textMuted);
}

.error-text {
  color: var(--color-danger);
  font-size: 14px;
}

@media (max-width: 768px) {
  .details-grid {
    grid-template-columns: 1fr;
  }

  .status-card {
    grid-column: span 1;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .actions-section {
    flex-direction: column;
  }
}

/* Proof image styles */
.proof-loading {
  padding: var(--spacing-lg);
  color: var(--color-textMuted);
  text-align: center;
}

.proof-image-container {
  margin-top: var(--spacing-md);
}

.proof-image {
  max-width: 100%;
  max-height: 400px;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  cursor: pointer;
  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.proof-image:hover {
  transform: scale(1.02);
  box-shadow: var(--shadow-lg);
}

.proof-hint {
  font-size: 12px;
  color: var(--color-textMuted);
  margin-top: var(--spacing-sm);
}

.proof-error {
  padding: var(--spacing-md);
  background: var(--color-dangerLight);
  border-radius: var(--radius-md);
  color: var(--color-dangerDark);
}

.loading-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-lg);
}

.skeleton-wide {
  grid-column: span 2;
}

@media (max-width: 768px) {
  .loading-grid, .skeleton-wide {
    grid-template-columns: 1fr;
    grid-column: span 1;
  }
}

/* Payment Review Card Styles */
.payment-review-card {
  grid-column: span 2;
}

.card-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.status-info {
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-md);
  padding: var(--spacing-md);
  border-radius: var(--radius-md);
  margin-bottom: var(--spacing-lg);
}

.status-info .status-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.pending-info {
  background: var(--color-warningLight, #fef3c7);
  color: var(--color-warningDark, #92400e);
}

.success-info {
  background: var(--color-successLight, #d1fae5);
  color: var(--color-successDark, #065f46);
}

.danger-info {
  background: var(--color-dangerLight, #fee2e2);
  color: var(--color-dangerDark, #991b1b);
}

.flag-details {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.flag-reason {
  font-size: 14px;
  opacity: 0.9;
}

.proof-preview-section {
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-lg);
  border-bottom: 1px solid var(--color-border);
}

.proof-preview-section h4 {
  margin-bottom: var(--spacing-md);
  font-size: 14px;
  font-weight: 600;
}

.proof-fallback {
  padding: var(--spacing-md);
  background: var(--color-bgSurface);
  border-radius: var(--radius-md);
}

.link-icon {
  width: 16px;
  height: 16px;
}

.payment-actions {
  display: flex;
  gap: var(--spacing-md);
  padding-top: var(--spacing-md);
}

.verification-info {
  margin-top: var(--spacing-lg);
  padding-top: var(--spacing-lg);
  border-top: 1px solid var(--color-border);
  font-size: 14px;
  color: var(--color-textMuted);
}

.payment-warning {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--color-warningLight, #fef3c7);
  color: var(--color-warningDark, #92400e);
  border-radius: var(--radius-md);
  font-size: 13px;
  width: 100%;
  margin-bottom: var(--spacing-md);
}

.payment-warning .warning-icon {
  width: 16px;
  height: 16px;
}

.btn-icon {
  width: 16px;
  height: 16px;
  margin-right: 4px;
}

@media (max-width: 768px) {
  .payment-review-card {
    grid-column: span 1;
  }
  
  .payment-actions {
    flex-direction: column;
  }
}
</style>
