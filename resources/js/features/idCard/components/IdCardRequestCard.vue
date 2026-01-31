<template>
  <div class="request-card">
    <div class="card-header">
      <div class="type-info">
        <div class="type-icon-wrapper" :style="{ background: iconBackground }">
          <component :is="iconComponent" class="type-icon" />
        </div>
        <div>
          <h4 class="type-name">{{ request.type.name_en }}</h4>
          <p class="type-name-ar">{{ request.type.name_ar }}</p>
        </div>
      </div>
      <div class="status-badges">
        <span :class="['status-badge', request.status_color]">{{ request.status_label }}</span>
        <span :class="['payment-badge', paymentColor]">{{ paymentLabel }}</span>
      </div>
    </div>
    
    <div class="card-body">
      <div class="info-row">
        <span class="label">Amount:</span>
        <span class="value amount">{{ request.amount }} EGP</span>
      </div>
      <div class="info-row">
        <span class="label">Transaction #:</span>
        <span class="value">{{ request.transaction_number }}</span>
      </div>
      <div class="info-row">
        <span class="label">Submitted:</span>
        <span class="value">{{ formattedDate }}</span>
      </div>
      <div v-if="request.status === 'rejected' && request.rejection_reason" class="rejection-box">
        <strong>Rejection Reason:</strong>
        <p>{{ request.rejection_reason }}</p>
      </div>
    </div>

    <div class="card-footer">
      <div class="timeline">
        <div :class="['timeline-step', { active: true }]">
          <span class="step-dot"></span>
          <span class="step-label">Submitted</span>
        </div>
        <div :class="['timeline-step', { active: isReviewed }]">
          <span class="step-dot"></span>
          <span class="step-label">Reviewed</span>
        </div>
        <div :class="['timeline-step', { active: isReady }]">
          <span class="step-dot"></span>
          <span class="step-label">Ready</span>
        </div>
        <div :class="['timeline-step', { active: isDelivered }]">
          <span class="step-dot"></span>
          <span class="step-label">Delivered</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { CreditCard, Camera, Wrench, ClipboardList } from 'lucide-vue-next';

const props = defineProps({
  request: {
    type: Object,
    required: true
  }
});

const iconComponent = computed(() => {
  const icons = {
    new: CreditCard,
    renew: CreditCard,
    lost: ClipboardList,
    photo_change: Camera,
    damaged: Wrench
  };
  return icons[props.request.type.code] || ClipboardList;
});

const iconBackground = computed(() => {
  const backgrounds = {
    new: 'var(--color-primaryBg)',
    renew: 'var(--color-infoBg)',
    lost: 'var(--color-warningBg)',
    photo_change: 'var(--color-successBg)',
    damaged: 'var(--color-dangerBg)'
  };
  return backgrounds[props.request.type.code] || 'var(--color-surfaceHighlight)';
});

const paymentColor = computed(() => {
  const colors = {
    pending: 'warning',
    verified: 'success',
    flagged: 'danger'
  };
  return colors[props.request.payment_status] || 'secondary';
});

const paymentLabel = computed(() => {
  const labels = {
    pending: 'Payment Pending',
    verified: 'Payment Verified',
    flagged: 'Payment Flagged'
  };
  return labels[props.request.payment_status] || props.request.payment_status;
});

const formattedDate = computed(() => {
  const date = new Date(props.request.created_at);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
});

const isReviewed = computed(() => {
  return ['approved', 'rejected', 'ready_for_pickup', 'delivered'].includes(props.request.status);
});

const isReady = computed(() => {
  return ['ready_for_pickup', 'delivered'].includes(props.request.status);
});

const isDelivered = computed(() => {
  return props.request.status === 'delivered';
});
</script>

<style scoped>
.request-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: var(--spacing-lg);
  border-bottom: 1px solid var(--color-border);
}

.type-info {
  display: flex;
  gap: var(--spacing-md);
  align-items: center;
}

.type-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.type-name {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-text-primary);
}

.type-name-ar {
  margin: var(--spacing-xs) 0 0;
  font-size: 0.875rem;
  color: var(--color-text-tertiary);
  direction: rtl;
}

.status-badges {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  align-items: flex-end;
}

.status-badge, .payment-badge {
  font-size: 0.75rem;
  padding: 4px 12px;
  border-radius: var(--radius-full);
  font-weight: 500;
}

.status-badge.warning, .payment-badge.warning {
  background: rgba(245, 158, 11, 0.15);
  color: #f59e0b;
}

.status-badge.success, .payment-badge.success {
  background: rgba(34, 197, 94, 0.15);
  color: #22c55e;
}

.status-badge.danger, .payment-badge.danger {
  background: rgba(239, 68, 68, 0.15);
  color: #ef4444;
}

.status-badge.info {
  background: rgba(59, 130, 246, 0.15);
  color: #3b82f6;
}

.status-badge.secondary {
  background: rgba(107, 114, 128, 0.15);
  color: #6b7280;
}

.card-body {
  padding: var(--spacing-lg);
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: var(--spacing-sm) 0;
  border-bottom: 1px solid var(--color-border);
}

.info-row:last-of-type {
  border-bottom: none;
}

.info-row .label {
  color: var(--color-text-secondary);
}

.info-row .value {
  font-weight: 500;
  color: var(--color-text-primary);
}

.info-row .value.amount {
  color: var(--color-primary);
  font-weight: 700;
}

.rejection-box {
  margin-top: var(--spacing-md);
  padding: var(--spacing-md);
  background: rgba(239, 68, 68, 0.1);
  border-radius: var(--radius-md);
  border-left: 3px solid var(--color-danger);
}

.rejection-box strong {
  color: var(--color-danger);
  font-size: 0.875rem;
}

.rejection-box p {
  margin: var(--spacing-xs) 0 0;
  color: var(--color-text-secondary);
  font-size: 0.875rem;
}

.card-footer {
  padding: var(--spacing-lg);
  background: var(--color-background);
}

.timeline {
  display: flex;
  justify-content: space-between;
}

.timeline-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--spacing-xs);
  flex: 1;
  position: relative;
}

.timeline-step::before {
  content: '';
  position: absolute;
  top: 8px;
  left: calc(50% + 12px);
  right: calc(-50% + 12px);
  height: 2px;
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
  border: 2px solid var(--color-background);
  z-index: 1;
}

.timeline-step.active .step-dot {
  background: var(--color-success);
}

.step-label {
  font-size: 0.75rem;
  color: var(--color-text-tertiary);
}

.timeline-step.active .step-label {
  color: var(--color-success);
  font-weight: 500;
}

@media (max-width: 640px) {
  .card-header {
    flex-direction: column;
    gap: var(--spacing-md);
  }
  
  .status-badges {
    flex-direction: row;
    align-items: flex-start;
  }
  
  .timeline-step {
    padding: 0 var(--spacing-xs);
  }
  
  .step-label {
    font-size: 0.65rem;
  }
}
</style>
