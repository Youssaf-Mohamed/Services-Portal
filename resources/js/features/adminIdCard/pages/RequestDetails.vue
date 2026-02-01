<template>
  <AdminLayout>
    <PageHeader
      :title="`Request #${requestId}`"
      subtitle="ID Card Service Request Details"
      :breadcrumbs="[
        { label: 'Admin', to: '/admin' },
        { label: 'ID Card Services', to: '/admin/id-card' },
        { label: 'Requests', to: '/admin/id-card/requests' },
        { label: `#${requestId}` }
      ]"
    />

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <SkeletonLoader height="500px" border-radius="var(--radius-lg)" />
    </div>

    <!-- Error State -->
    <EmptyState
      v-else-if="error"
      icon="AlertTriangleIcon"
      title="Failed to Load Request"
      :message="error"
      actionText="Go Back"
      @action="goBack"
    />

    <!-- Request Details -->
    <div v-else-if="request" class="details-layout">
      <!-- Main Content -->
      <div class="main-content">
        <!-- Student Info Section -->
        <section class="section">
          <h3 class="section-title">
            <UserIcon class="icon-inline" /> Student Information
          </h3>
          <div class="info-grid">
            <div class="info-item">
              <label>Name</label>
              <span>{{ request.user.name }}</span>
            </div>
            <div class="info-item">
              <label>Email</label>
              <span>{{ request.user.email }}</span>
            </div>
            <div class="info-item">
              <label>Student ID</label>
              <span>{{ request.user.student_id || 'N/A' }}</span>
            </div>
          </div>
        </section>

        <!-- Request Details Section -->
        <section class="section">
          <h3 class="section-title">
            <ClipboardListIcon class="icon-inline" /> Request Details
          </h3>
          <div class="info-grid">
            <div class="info-item">
              <label>Service Type</label>
              <span class="type-badge" :class="request.type.code">
                {{ request.type.name_en }}
              </span>
            </div>
            <div class="info-item">
              <label>Amount</label>
              <span class="amount">{{ request.amount }} EGP</span>
            </div>
            <div class="info-item">
              <label>Submitted</label>
              <span>{{ formatDate(request.created_at) }}</span>
            </div>
          </div>
          
          <!-- Description (for damaged type) -->
          <div v-if="request.issue_description" class="description-box">
            <label>Issue Description</label>
            <p>{{ request.issue_description }}</p>
          </div>
        </section>

        <!-- Payment Info Section -->
        <section class="section">
          <h3 class="section-title">
            <CreditCardIcon class="icon-inline" /> Payment Information
          </h3>
          <div class="info-grid">
            <div class="info-item">
              <label>Transaction #</label>
              <span class="mono">{{ request.transaction_number }}</span>
            </div>
            <div class="info-item">
              <label>Transfer Time</label>
              <span>{{ formatDate(request.transfer_time) }}</span>
            </div>
            <div class="info-item">
              <label>Payment Status</label>
              <span :class="['payment-badge', request.payment.status_color]">
                {{ request.payment.status_label }}
              </span>
            </div>
          </div>
          
          <div v-if="request.payment.flag_reason" class="flag-box">
            <label>Flag Reason</label>
            <p>{{ request.payment.flag_reason }}</p>
          </div>
        </section>

        <!-- Attachments Section -->
        <section class="section">
          <h3 class="section-title">
            <PaperclipIcon class="icon-inline" /> Attachments
          </h3>
          <div class="attachments-grid">
            <AttachmentPreview 
               label="Payment Screenshot"
               :has-attachment="request.has_screenshot"
               :download-url="getAttachmentUrl('screenshot')"
               :fetch-fn="fetchScreenshot"
            />
            <AttachmentPreview 
               label="New Photo"
               :has-attachment="request.has_new_photo"
               :download-url="getAttachmentUrl('new_photo')"
               :fetch-fn="fetchNewPhoto"
            />
          </div>
        </section>

        <!-- Rejection Reason (if rejected) -->
        <section v-if="request.status === 'rejected' && request.rejection_reason" class="section rejection-section">
          <h3 class="section-title">
            <XCircleIcon class="icon-inline text-danger" /> Rejection
          </h3>
          <div class="rejection-box">
            <p>{{ request.rejection_reason }}</p>
            <span class="rejection-by">
              By {{ request.reviewed_by?.name || 'Admin' }} on {{ formatDate(request.reviewed_at) }}
            </span>
          </div>
        </section>
      </div>

      <!-- Sidebar - Status & Actions -->
      <div class="sidebar">
        <!-- Current Status -->
        <div class="status-card">
          <h4>Current Status</h4>
          <div :class="['status-display', request.status_color]">
            {{ request.status_label }}
          </div>
        </div>

        <!-- Workflow Timeline -->
        <div class="timeline-card">
          <h4>Workflow</h4>
          <div class="workflow-timeline">
            <div :class="['timeline-step', { active: true, current: request.status === 'pending' }]">
              <span class="step-dot"></span>
              <div class="step-content">
                <span class="step-label">Submitted</span>
                <span class="step-date">{{ formatDate(request.created_at) }}</span>
              </div>
            </div>
            <div :class="['timeline-step', { active: isReviewed, current: ['approved', 'rejected'].includes(request.status) }]">
              <span class="step-dot"></span>
              <div class="step-content">
                <span class="step-label">{{ request.status === 'rejected' ? 'Rejected' : 'Approved' }}</span>
                <span v-if="request.reviewed_at" class="step-date">{{ formatDate(request.reviewed_at) }}</span>
              </div>
            </div>
            <div :class="['timeline-step', { active: isReady, current: request.status === 'ready_for_pickup' }]">
              <span class="step-dot"></span>
              <div class="step-content">
                <span class="step-label">Ready for Pickup</span>
                <span v-if="request.ready_at" class="step-date">{{ formatDate(request.ready_at) }}</span>
              </div>
            </div>
            <div :class="['timeline-step', { active: isDelivered, current: request.status === 'delivered' }]">
              <span class="step-dot"></span>
              <div class="step-content">
                <span class="step-label">Delivered</span>
                <span v-if="request.delivered_at" class="step-date">{{ formatDate(request.delivered_at) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div v-if="hasActions" class="actions-card">
          <h4>Actions</h4>
          
          <!-- Payment Actions -->
          <template v-if="request.can.verify_payment">
            <button class="btn btn-success" @click="verifyPayment">
              <CheckCircleIcon class="btn-icon" /> Verify Payment
            </button>
          </template>
          <template v-if="request.can.flag_payment">
            <button class="btn btn-warning" @click="showFlagModal = true">
              <FlagIcon class="btn-icon" /> Flag Payment
            </button>
          </template>

          <!-- Status Actions -->
          <template v-if="request.can.approve">
            <button class="btn btn-primary" @click="approveRequest">
              <CheckCircleIcon class="btn-icon" /> Approve Request
            </button>
          </template>
          
          <template v-if="request.can.reject">
             <!-- Return for Correction (Soft Reject) -->
            <button class="btn btn-warning" @click="openRejectModal('return')">
              <RefreshCwIcon class="btn-icon" /> Return for Correction
            </button>
            
            <!-- Hard Reject -->
            <button class="btn btn-danger" @click="openRejectModal('reject')">
              <XCircleIcon class="btn-icon" /> Reject Request
            </button>
          </template>

          <template v-if="request.can.ready">
            <button class="btn btn-info" @click="markReady">
              <PackageIcon class="btn-icon" /> Mark Ready for Pickup
            </button>
          </template>
          <template v-if="request.can.deliver">
            <button class="btn btn-success" @click="markDelivered">
              <TruckIcon class="btn-icon" /> Mark Delivered
            </button>
          </template>
        </div>
      </div>
    </div>

    <!-- Flag Payment Modal -->
    <div v-if="showFlagModal" class="modal-overlay" @click.self="showFlagModal = false">
      <div class="modal">
        <h3>Flag Payment</h3>
        <p>Please provide a reason for flagging this payment:</p>
        <textarea v-model="flagReason" placeholder="Enter reason..." rows="4"></textarea>
         <div class="modal-actions">
          <button class="btn btn-secondary" @click="showFlagModal = false">Cancel</button>
          <button class="btn btn-warning" @click="flagPayment" :disabled="!flagReason.trim()">
            Flag Payment
          </button>
        </div>
      </div>
    </div>

    <!-- Reject/Return Modal -->
    <div v-if="showRejectModal" class="modal-overlay" @click.self="showRejectModal = false">
      <div class="modal">
        <h3 :class="rejectMode === 'return' ? 'text-warning' : 'text-danger'">
          {{ rejectMode === 'return' ? 'Return Request for Correction' : 'Reject Request' }}
        </h3>
        
        <p v-if="rejectMode === 'return'">
          Explain what needs to be corrected (e.g., "Photo blurry", "Wrong ID"). The student will be notified to update and resubmit.
        </p>
        <p v-else>
          Please provide a reason for rejection (this will be visible to the student).
        </p>
        
        <textarea 
          v-model="rejectReason" 
          :placeholder="rejectMode === 'return' ? 'Enter correction details...' : 'Enter rejection reason...'" 
          rows="4"
        ></textarea>
        
        <div class="modal-actions">
          <button class="btn btn-secondary" @click="showRejectModal = false">Cancel</button>
          <button 
            :class="rejectMode === 'return' ? 'btn btn-warning' : 'btn btn-danger'" 
            @click="rejectRequest" 
            :disabled="!rejectReason.trim()"
          >
            {{ rejectMode === 'return' ? 'Return Request' : 'Reject Request' }}
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { PageHeader, EmptyState, SkeletonLoader, AttachmentPreview } from '@/components/ui';
import { adminIdCardApi } from '../api/adminIdCard.api';
import { 
  User as UserIcon, 
  ClipboardList as ClipboardListIcon, 
  CreditCard as CreditCardIcon, 
  Paperclip as PaperclipIcon, 
  AlertTriangle as AlertTriangleIcon, 
  CheckCircle as CheckCircleIcon, 
  XCircle as XCircleIcon, 
  Package as PackageIcon, 
  Truck as TruckIcon,
  Flag as FlagIcon,
  RefreshCw as RefreshCwIcon
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();

const requestId = computed(() => route.params.id);
const loading = ref(false);
const error = ref('');
const request = ref(null);
const selectedImageUrl = ref(null);

const fetchScreenshot = () => adminIdCardApi.downloadAttachment(requestId.value, 'screenshot');
const fetchNewPhoto = () => adminIdCardApi.downloadAttachment(requestId.value, 'new_photo');
const showFlagModal = ref(false);
const showRejectModal = ref(false);
const rejectMode = ref('reject'); // 'reject' or 'return'
const flagReason = ref('');
const rejectReason = ref('');


const isReviewed = computed(() => {
  if (!request.value) return false;
  return ['approved', 'rejected', 'ready_for_pickup', 'delivered'].includes(request.value.status);
});

const isReady = computed(() => {
  if (!request.value) return false;
  return ['ready_for_pickup', 'delivered'].includes(request.value.status);
});

const isDelivered = computed(() => {
  if (!request.value) return false;
  return request.value.status === 'delivered';
});

const hasActions = computed(() => {
  if (!request.value?.can) return false;
  return Object.values(request.value.can).some(v => v);
});

const fetchRequest = async () => {
  loading.value = true;
  error.value = '';
  
  try {
    const response = await adminIdCardApi.getRequest(requestId.value);
    request.value = response.data;
  } catch (err) {
    console.error('Failed to load request:', err);
    error.value = err.message || 'Failed to load request';
  } finally {
    loading.value = false;
  }
};

const getAttachmentUrl = (kind) => {
  return adminIdCardApi.getAttachmentUrl(requestId.value, kind);
};



const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const verifyPayment = async () => {
  try {
    await adminIdCardApi.verifyPayment(requestId.value);
    await fetchRequest();
  } catch (err) {
    alert(err.message);
  }
};

const flagPayment = async () => {
  try {
    await adminIdCardApi.flagPayment(requestId.value, flagReason.value);
    showFlagModal.value = false;
    flagReason.value = '';
    await fetchRequest();
  } catch (err) {
    alert(err.message);
  }
};

const approveRequest = async () => {
  try {
    await adminIdCardApi.approve(requestId.value);
    await fetchRequest();
  } catch (err) {
    alert(err.message);
  }
};

const openRejectModal = (mode) => {
  rejectMode.value = mode;
  showRejectModal.value = true;
};

const rejectRequest = async () => {
  try {
    await adminIdCardApi.reject(requestId.value, rejectReason.value);
    showRejectModal.value = false;
    rejectReason.value = '';
    await fetchRequest();
  } catch (err) {
    alert(err.message);
  }
};

const markReady = async () => {
  try {
    await adminIdCardApi.markReady(requestId.value);
    await fetchRequest();
  } catch (err) {
    alert(err.message);
  }
};

const markDelivered = async () => {
  try {
    await adminIdCardApi.markDelivered(requestId.value);
    await fetchRequest();
  } catch (err) {
    alert(err.message);
  }
};

const goBack = () => {
  router.push('/admin/id-card/requests');
};

onMounted(() => {
  fetchRequest();
});

onMounted(() => {
  fetchRequest();
});
</script>

<style scoped>
.loading-container {
  max-width: 1200px;
}

.details-layout {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: var(--spacing-xl);
}

.main-content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xl);
}

.section {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-xl);
}

.section-title {
  margin: 0 0 var(--spacing-lg) 0;
  font-size: 1rem;
  color: var(--color-text-primary);
  display: flex;
  align-items: center;
  gap: 8px;
}
.icon-inline {
  width: 18px;
  height: 18px;
}
.text-danger {
  color: var(--color-danger);
}
.btn-icon {
  width: 18px;
  height: 18px;
  margin-right: 6px;
  vertical-align: text-bottom;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--spacing-lg);
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.info-item label {
  font-size: 0.75rem;
  text-transform: uppercase;
  color: var(--color-text-tertiary);
  font-weight: 500;
}

.info-item span {
  color: var(--color-text-primary);
  font-weight: 500;
}

.mono {
  font-family: 'SF Mono', 'Monaco', 'Inconsolata', monospace;
}

.amount {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-primary) !important;
}

.type-badge {
  display: inline-block;
  font-size: 0.8rem;
  padding: 4px 12px;
  border-radius: var(--radius-full);
}

.type-badge.lost {
  background: rgba(240, 147, 251, 0.15);
  color: #f093fb;
}

.type-badge.photo_change {
  background: rgba(79, 172, 254, 0.15);
  color: #4facfe;
}

.type-badge.damaged {
  background: rgba(250, 112, 154, 0.15);
  color: #fa709a;
}

.payment-badge {
  display: inline-block;
  font-size: 0.75rem;
  padding: 4px 12px;
  border-radius: var(--radius-full);
  font-weight: 500;
}

.payment-badge.warning {
  background: rgba(245, 158, 11, 0.15);
  color: #f59e0b;
}

.payment-badge.success {
  background: rgba(34, 197, 94, 0.15);
  color: #22c55e;
}

.payment-badge.danger {
  background: rgba(239, 68, 68, 0.15);
  color: #ef4444;
}

.description-box, .flag-box, .rejection-box {
  margin-top: var(--spacing-lg);
  padding: var(--spacing-md);
  background: var(--color-background);
  border-radius: var(--radius-md);
}

.description-box label, .flag-box label {
  display: block;
  font-size: 0.75rem;
  text-transform: uppercase;
  color: var(--color-text-tertiary);
  margin-bottom: var(--spacing-xs);
}

.description-box p, .flag-box p, .rejection-box p {
  margin: 0;
  color: var(--color-text-secondary);
}

.flag-box {
  border-left: 3px solid #f59e0b;
  background: rgba(245, 158, 11, 0.05);
}

.rejection-section .rejection-box {
  border-left: 3px solid #ef4444;
  background: rgba(239, 68, 68, 0.05);
}

.rejection-by {
  display: block;
  margin-top: var(--spacing-sm);
  font-size: 0.75rem;
  color: var(--color-text-tertiary);
}

.attachments-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-md);
}

.attachment-card label {
  display: block;
  font-size: 0.75rem;
  text-transform: uppercase;
  color: var(--color-text-tertiary);
  margin-bottom: var(--spacing-sm);
}

.attachment-preview {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-md);
  background: var(--color-background);
  border-radius: var(--radius-md);
  text-decoration: none;
  color: var(--color-primary);
  transition: all 0.2s ease;
}

.attachment-preview:hover {
  background: var(--color-primary);
  color: white;
}

.attachment-icon {
  font-size: 1.5rem;
}

/* Sidebar */
.sidebar {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.status-card, .timeline-card, .actions-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg);
}

.status-card h4, .timeline-card h4, .actions-card h4 {
  margin: 0 0 var(--spacing-md) 0;
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.status-display {
  font-size: 1.125rem;
  font-weight: 600;
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  text-align: center;
}

.status-display.warning {
  background: rgba(245, 158, 11, 0.15);
  color: #f59e0b;
}

.status-display.success {
  background: rgba(34, 197, 94, 0.15);
  color: #22c55e;
}

.status-display.danger {
  background: rgba(239, 68, 68, 0.15);
  color: #ef4444;
}

.status-display.info {
  background: rgba(59, 130, 246, 0.15);
  color: #3b82f6;
}

.status-display.secondary {
  background: rgba(107, 114, 128, 0.15);
  color: #6b7280;
}

/* Workflow Timeline */
.workflow-timeline {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.timeline-step {
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-md);
  position: relative;
  padding-left: var(--spacing-md);
}

.timeline-step::before {
  content: '';
  position: absolute;
  left: 7px;
  top: 20px;
  bottom: -12px;
  width: 2px;
  background: var(--color-border);
}

.timeline-step:last-child::before {
  display: none;
}

.timeline-step.active::before {
  background: var(--color-success);
}

.step-dot {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: var(--color-border);
  flex-shrink: 0;
  z-index: 1;
}

.timeline-step.active .step-dot {
  background: var(--color-success);
}

.timeline-step.current .step-dot {
  box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.2);
}

.step-content {
  display: flex;
  flex-direction: column;
}

.step-label {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.timeline-step.active .step-label {
  color: var(--color-text-primary);
  font-weight: 500;
}

.step-date {
  font-size: 0.75rem;
  color: var(--color-text-tertiary);
}

/* Actions */
.actions-card {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.btn {
  width: 100%;
  padding: var(--spacing-sm) var(--spacing-md);
  border: none;
  border-radius: var(--radius-md);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary {
  background: var(--color-primary);
  color: white;
}

.btn-success {
  background: #22c55e;
  color: white;
}

.btn-warning {
  background: #f59e0b;
  color: white;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-info {
  background: #3b82f6;
  color: white;
}

.btn-secondary {
  background: var(--color-secondary);
  color: white;
}

.btn:hover {
  filter: brightness(1.1);
}

/* Modals */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: var(--color-surface);
  border-radius: var(--radius-lg);
  padding: var(--spacing-xl);
  max-width: 500px;
  width: 90%;
}

.modal h3 {
  margin: 0 0 var(--spacing-md) 0;
  color: var(--color-text-primary);
}

.modal p {
  margin: 0 0 var(--spacing-lg) 0;
  color: var(--color-text-secondary);
}

.modal textarea {
  width: 100%;
  padding: var(--spacing-md);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-background);
  color: var(--color-text-primary);
  resize: vertical;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-md);
  margin-top: var(--spacing-lg);
}

@media (max-width: 1024px) {
  .details-layout {
    grid-template-columns: 1fr;
  }
  
  .sidebar {
    order: -1;
  }
  
  .info-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.proof-image-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: 8px;
  background: var(--color-background);
  cursor: pointer;
  transition: border-color 0.2s;
}

.proof-image-container:hover {
  border-color: var(--color-primary);
}

.proof-image {
  max-width: 100%;
  max-height: 200px;
  object-fit: contain;
  border-radius: var(--radius-sm);
}

.proof-hint {
  font-size: 0.75rem;
  color: var(--color-text-tertiary);
  margin: 0;
}

.loading-attachment {
  padding: 20px;
  text-align: center;
  color: var(--color-text-secondary);
  font-size: 0.875rem;
  background: var(--color-surface);
  border-radius: var(--radius-md);
}

@media (max-width: 640px) {
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .attachments-grid {
    grid-template-columns: 1fr;
  }
}
</style>
