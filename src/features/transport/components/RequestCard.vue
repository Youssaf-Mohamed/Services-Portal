<template>
  <div class="request-card">
    <div class="card-header">
      <div class="header-main">
        <div class="icon-circle">
          <Bus class="icon" />
        </div>
        <div class="request-info">
          <div class="header-row">
            <h3 class="request-title">{{ routeName }}</h3>
            <StatusBadge :status="request.status" />
          </div>
          <div v-if="request.selected_days && request.selected_days.length > 0" class="days-badges">
             <span v-for="day in request.selected_days" :key="day" class="day-badge">
               {{ capitalize(day).substring(0, 3) }}
             </span>
          </div>
          <p v-else class="request-meta">{{ slotInfo }}</p>
        </div>
      </div>
    </div>

    <div class="card-body">
      <div class="info-grid">
        <div class="info-item">
          <div class="info-icon"><CreditCard class="w-4 h-4" /></div>
          <div class="info-content">
            <span class="info-label">Payment</span>
            <span class="info-value">EGP {{ amountPaid.toFixed(2) }}</span>
            <span class="info-sub">{{ request.payment_method?.name || 'Cash' }}</span>
          </div>
        </div>
        
        <div class="info-item">
          <div class="info-icon"><Calendar class="w-4 h-4" /></div>
          <div class="info-content">
            <span class="info-label">Submitted On</span>
            <span class="info-value">{{ formatDate(request.created_at) }}</span>
            <span class="info-sub">{{ formatTime(request.created_at) }}</span>
          </div>
        </div>
        
        <div class="info-item">
          <div class="info-icon"><Ticket class="w-4 h-4" /></div>
          <div class="info-content">
            <span class="info-label">Plan Type</span>
            <span class="info-value">{{ formatPlanType(request) }}</span>
          </div>
        </div>
      </div>

      <!-- Rejection Reason -->
      <div v-if="request.status === 'rejected' && request.rejection_reason" class="rejection-notice">
        <div class="notice-header">
           <AlertCircle class="w-4 h-4" />
           <strong>Rejection Reason</strong>
        </div>
        <p>{{ request.rejection_reason }}</p>
      </div>

      <!-- Actions -->
      <div v-if="request.status === 'pending'" class="card-actions">
        <span class="pending-note">
          <Clock class="w-3 h-3" />
          Awaiting Admin Approval
        </span>
      </div>

      <div v-else-if="request.status === 'rejected'" class="card-actions">
        <Button variant="primary" size="sm" @click="$emit('resubmit', request)">
          Resubmit Request
        </Button>
      </div>
    </div>
    
    <!-- Verified Watermark Stamp (Relative to whole card) -->
    <VerifiedSeal v-if="request.status === 'approved' || request.status === 'active'" />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import StatusBadge from './StatusBadge.vue';
import VerifiedSeal from './VerifiedSeal.vue';
import { Button } from '@/components/ui';
import { 
  Bus, 
  CreditCard, 
  Calendar, 
  Ticket, 
  AlertCircle,
  Clock
} from 'lucide-vue-next';

defineEmits(['resubmit']);

const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const props = defineProps({
  request: {
    type: Object,
    required: true
  }
});

// Computed properties to handle nested API response
const routeName = computed(() => {
  return props.request.route?.name_en || props.request.route_name || 'Unknown Route';
});

const slotInfo = computed(() => {
  if (props.request.selected_days && props.request.selected_days.length > 0) {
    return props.request.selected_days.map(d => capitalize(d)).join(', ');
  }
  // Fallback for legacy
  if (props.request.slot) {
    const dayName = DAY_NAMES[props.request.slot.day_of_week] || 'Day';
    return `${dayName}, ${props.request.slot.time}`;
  }
  return props.request.slot_time || 'Unknown Time';
});

const capitalize = (s) => s.charAt(0).toUpperCase() + s.slice(1);

const amountPaid = computed(() => {
  return parseFloat(props.request.amount_paid || props.request.amount_expected || 0);
});

const formatPlanType = (request) => {
  if (request.plan?.name_en) return request.plan.name_en;
  return request.plan_type === 'monthly' ? 'Monthly Plan' : 'Term Plan';
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<style scoped>
.request-card {
  background: var(--color-surface);
  border-radius: var(--radius-lg);
  box-shadow: 0 1px 2px rgba(0,0,0,0.05); /* Softer initial shadow */
  border: 1px solid var(--color-border);
  overflow: hidden;
  transition: all 0.2s ease;
  position: relative; /* Ensure stamp is positioned relative to card */
}

.request-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: var(--color-primaryLight);
}

.card-header {
  padding: var(--spacing-lg) var(--spacing-xl);
  border-bottom: 1px solid var(--color-border);
  background: white; /* Clean white background */
  position: relative;
  overflow: hidden;
}

/* Verified Stamp styles moved to VerifiedSeal.vue */

.header-main {
  display: flex;
  gap: var(--spacing-lg);
  align-items: flex-start; /* Align to top */
}

.icon-circle {
  width: 52px;
  height: 52px;
  border-radius: 14px; /* Slightly softer edges */
  background: white; /* Clean background */
  color: var(--color-primary); /* Primary Colored Icon */
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 1px solid var(--color-borderLight); /* Subtle border */
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04); /* Soft floating shadow */
}

/* Optional: Icon inside */
.icon {
  width: 24px;
  height: 24px;
  stroke-width: 2px;
}

.request-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.request-title {
  font-size: 18px; /* Larger title */
  font-weight: 700;
  color: var(--color-textMain);
  margin: 0 0 4px 0;
  line-height: 1.2;
}

.request-meta {
  font-size: 14px;
  color: var(--color-textMuted);
  margin: 0;
}

.card-body {
  padding: var(--spacing-xl);
  background: #fcfcfc; /* Very subtle contrast for body */
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--spacing-xl); /* Increased gap */
}

.info-item {
  display: flex;
  gap: var(--spacing-md);
  align-items: flex-start;
}

.info-icon {
  color: var(--color-textLight);
  padding-top: 2px;
}

.info-content {
  display: flex;
  flex-direction: column;
}

.info-label {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--color-textMuted);
  margin-bottom: 4px;
  font-weight: 600;
}

.info-value {
  font-size: 15px;
  font-weight: 600;
  color: var(--color-textMain);
}

.info-sub {
  font-size: 12px;
  color: var(--color-textMuted);
  margin-top: 2px;
}

.rejection-notice {
  margin-top: var(--spacing-lg);
  background: #FEF2F2;
  border: 1px solid #FEE2E2;
  border-radius: var(--radius-md);
  padding: var(--spacing-md);
  color: #991B1B;
}

.notice-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-xs);
  font-size: 14px;
}

.rejection-notice p {
  margin: 0;
  font-size: 14px;
  padding-left: 24px;
  color: #B91C1C;
  line-height: 1.5;
}

.card-actions {
  margin-top: var(--spacing-lg);
  padding-top: var(--spacing-lg);
  border-top: 1px dashed var(--color-border);
  display: flex;
  justify-content: flex-end;
}

.pending-note {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #0369a1; /* Sky 700 */
  font-weight: 500;
  background: #f0f9ff; /* Sky 50 */
  padding: 6px 12px;
  border-radius: var(--radius-full);
  border: 1px solid #bae6fd; /* Sky 200 */
}

.days-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 4px;
}

.day-badge {
  font-size: 12px;
  font-weight: 500;
  padding: 4px 10px;
  background: white;
  border: 1px solid var(--color-border);
  color: var(--color-textMain);
  border-radius: var(--radius-full);
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

@media (max-width: 768px) {
  .info-grid {
    grid-template-columns: 1fr;
    gap: var(--spacing-lg);
  }
  
  .header-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
}
</style>
