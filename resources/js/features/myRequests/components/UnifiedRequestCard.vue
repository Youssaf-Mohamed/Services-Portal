<template>
  <div class="request-row" @click="navigateToDetail">
    <!-- Service Column -->
    <div class="col service">
      <div class="icon-box" :class="request.module">
        <component :is="moduleIcon" class="icon-svg" />
      </div>
      <div class="service-info">
        <span class="service-name">{{ moduleName }}</span>
        <span class="request-id">#{{ request.id }}</span>
      </div>
    </div>

    <!-- Type Column -->
    <div class="col type">
      <span class="type-text">{{ request.type_label }}</span>
      <span v-if="request.type_label_ar" class="type-sub">{{ request.type_label_ar }}</span>
    </div>

    <!-- Status Column -->
    <div class="col status">
      <span :class="['status-badge', request.status_color]">{{ request.status_label }}</span>
    </div>

    <!-- Amount Column -->
    <div class="col amount">
      <span v-if="request.amount && request.amount > 0" class="amount-val">{{ request.amount }} EGP</span>
      <span v-else class="amount-dash">-</span>
    </div>

    <!-- Date Column -->
    <div class="col date">
      {{ formattedDate }}
    </div>

    <!-- Actions Column -->
    <div class="col actions">
      <button class="action-btn">View Details</button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { Bus, CreditCard } from 'lucide-vue-next';

const props = defineProps({
  request: {
    type: Object,
    required: true
  }
});

const router = useRouter();

const moduleName = computed(() => {
  return props.request.module === 'transport' ? 'Transportation' : 'ID Card Services';
});

const moduleIcon = computed(() => {
  return props.request.module === 'transport' ? Bus : CreditCard;
});

const formattedDate = computed(() => {
  const date = new Date(props.request.created_at);
  return date.toLocaleDateString('en-US', {
    month: 'numeric',
    day: 'numeric',
    year: 'numeric'
  });
});

const navigateToDetail = () => {
  if (props.request.detail_url) {
    router.push(props.request.detail_url);
  }
};
</script>

<style scoped>
.request-row {
  display: grid;
  grid-template-columns: 280px 1.5fr 140px 100px 120px 100px; /* Aligned with Header */
  gap: var(--spacing-lg);
  padding: 16px var(--spacing-xl);
  background: var(--color-surface);
  border: 1px solid transparent;
  border-bottom: 1px solid var(--color-border);
  align-items: center;
  transition: all 0.2s ease;
  cursor: pointer;
  border-radius: var(--radius-lg);
}

.request-row:hover {
  background: var(--color-surfaceHighlight);
  transform: scale(1.002);
  border-color: var(--color-borderLight);
  box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

/* Service Column */
.col.service {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
}

.icon-box {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-box.transport {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.icon-box.id_card {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.icon-svg {
  width: 18px;
  height: 18px;
}

.service-info {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.service-name {
  font-weight: 600;
  font-size: 14px;
  color: var(--color-textMain);
}

.request-id {
  font-size: 11px;
  color: var(--color-textMuted);
  font-family: monospace;
}

/* Type Column */
.col.type {
  display: flex;
  flex-direction: column;
}

.type-text {
  font-size: 13px;
  font-weight: 500;
  color: var(--color-textMain);
}

.type-sub {
  font-size: 11px;
  color: var(--color-textMuted);
}

/* Status Column */
.col.status {
  display: flex;
  justify-content: center;
}

.status-badge {
  font-size: 11px;
  padding: 4px 10px;
  border-radius: 99px;
  font-weight: 600;
  text-transform: capitalize;
}

.status-badge.warning { background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; }
.status-badge.success { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
.status-badge.danger { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
.status-badge.info { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
.status-badge.secondary { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }

/* Amount & Date */
.col.amount { text-align: right; font-weight: 600; font-size: 13px; color: var(--color-textMain); }
.amount-dash { color: var(--color-textMuted); }

.col.date { font-size: 13px; color: var(--color-textMuted); }

/* Actions */
.col.actions { text-align: right; }

.action-btn {
  background: none;
  border: none;
  color: var(--color-primary);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
}

.action-btn:hover {
  text-decoration: underline;
}


/* Mobile Responsive Fallback */
@media (max-width: 1000px) {
  .request-row {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    height: auto;
  }

  .col { width: 100%; display: flex; justify-content: space-between; align-items: center; }
  
  .col.bg-hide { display: none; } /* Helper to hide things if needed */
  
  .icon-box { margin-bottom: 0; }
  
  .col.status { justify-content: flex-start; margin-top: 4px; }
  .col.amount, .col.actions { text-align: left; }
  
  .action-btn {
    width: 100%;
    padding: 8px;
    background: var(--color-surfaceHighlight);
    border-radius: 6px;
    text-align: center;
  }
}
</style>
