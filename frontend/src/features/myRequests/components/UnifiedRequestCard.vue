<template>
  <div class="request-card" @click="navigateToDetail">
    <div class="card-left">
      <div class="module-icon-wrapper" :style="{ background: moduleBackground }">
        <component :is="moduleIcon" class="module-icon" />
      </div>
      <div class="card-content">
        <div class="card-header">
          <span class="module-badge" :class="request.module">{{ moduleName }}</span>
          <span :class="['status-badge', request.status_color]">{{ request.status_label }}</span>
        </div>
        <h4 class="type-label">{{ request.type_label }}</h4>
        <p class="type-label-ar">{{ request.type_label_ar }}</p>
      </div>
    </div>
    
    <div class="card-right">
      <div class="amount">{{ request.amount }} EGP</div>
      <div class="date">{{ formattedDate }}</div>
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
  return props.request.module === 'transport' ? 'Transport' : 'ID Card';
});

const moduleIcon = computed(() => {
  return props.request.module === 'transport' ? Bus : CreditCard;
});

const moduleBackground = computed(() => {
  return props.request.module === 'transport'
    ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
    : 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)';
});

const formattedDate = computed(() => {
  const date = new Date(props.request.created_at);
  return date.toLocaleDateString('en-US', {
    month: 'short',
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
.request-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg);
  cursor: pointer;
  transition: all 0.2s ease;
}

.request-card:hover {
  border-color: var(--color-primary);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.card-left {
  display: flex;
  gap: var(--spacing-lg);
  align-items: center;
}

.module-icon-wrapper {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white; /* Assuming icons should be white on colorful background */
  flex-shrink: 0;
}

.module-icon {
  width: 24px;
  height: 24px;
}

.card-content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.card-header {
  display: flex;
  gap: var(--spacing-sm);
  align-items: center;
}

.module-badge {
  font-size: 0.7rem;
  padding: 2px 8px;
  border-radius: var(--radius-full);
  text-transform: uppercase;
  font-weight: 600;
}

.module-badge.transport {
  background: rgba(102, 126, 234, 0.15);
  color: #667eea;
}

.module-badge.id_card {
  background: rgba(240, 147, 251, 0.15);
  color: #f093fb;
}

.status-badge {
  font-size: 0.7rem;
  padding: 2px 8px;
  border-radius: var(--radius-full);
  font-weight: 500;
}

.status-badge.warning {
  background: rgba(245, 158, 11, 0.15);
  color: #f59e0b;
}

.status-badge.success {
  background: rgba(34, 197, 94, 0.15);
  color: #22c55e;
}

.status-badge.danger {
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

.type-label {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text-primary);
}

.type-label-ar {
  margin: 0;
  font-size: 0.8rem;
  color: var(--color-text-tertiary);
  direction: rtl;
}

.card-right {
  text-align: right;
  flex-shrink: 0;
}

.amount {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--color-primary);
}

.date {
  font-size: 0.8rem;
  color: var(--color-text-tertiary);
  margin-top: var(--spacing-xs);
}

@media (max-width: 640px) {
  .request-card {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--spacing-md);
  }
  
  .card-right {
    text-align: left;
    width: 100%;
    display: flex;
    justify-content: space-between;
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--color-border);
  }
}
</style>
