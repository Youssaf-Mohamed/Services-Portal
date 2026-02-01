<template>
  <span :class="['status-badge', computedVariant]">
    <slot>{{ label || status }}</slot>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: {
    type: String,
    default: ''
  },
  variant: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    default: ''
  }
});

const computedVariant = computed(() => {
  if (props.variant) return props.variant;
  
  const statusLower = props.status.toLowerCase();
  
  if (['approved', 'active', 'ready_for_pickup', 'delivered', 'verified', 'completed'].includes(statusLower)) {
    return 'success';
  }
  
  if (['pending', 'processing', 'waitlisted'].includes(statusLower)) {
    return 'warning';
  }
  
  if (['rejected', 'cancelled', 'inactive', 'flagged', 'expired'].includes(statusLower)) {
    return 'danger';
  }
  
  if (['info', 'new'].includes(statusLower)) {
    return 'info';
  }
  
  return 'secondary';
});
</script>

<style scoped>
.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 500;
  border-radius: 9999px;
  line-height: 1;
  white-space: nowrap;
}

.status-badge.success {
  background-color: #d1fae5;
  color: #065f46;
}

.status-badge.warning {
  background-color: #fef3c7;
  color: #92400e;
}

.status-badge.danger {
  background-color: #fee2e2;
  color: #b91c1c;
}

.status-badge.info {
  background-color: #dbeafe;
  color: #1e40af;
}

.status-badge.secondary {
  background-color: #f3f4f6;
  color: #374151;
}
</style>
