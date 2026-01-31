<template>
  <span class="ui-badge" :class="badgeClasses">
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'neutral',
    validator: (v) => ['success', 'warning', 'danger', 'info', 'neutral', 'primary'].includes(v)
  },
  label: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v)
  }
});

const badgeClasses = computed(() => ({
  [`variant-${props.variant}`]: true,
  [`size-${props.size}`]: true
}));
</script>

<style scoped>
.ui-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-full);
  font-weight: var(--fw-medium);
  white-space: nowrap;
  line-height: 1.2;
}

/* Size variants */
.ui-badge.size-sm {
  padding: 2px 8px;
  font-size: 11px;
}

.ui-badge.size-md {
  padding: 4px 10px;
  font-size: 12px;
}

.ui-badge.size-lg {
  padding: 6px 14px;
  font-size: 13px;
}

/* Variant colors */
.ui-badge.variant-success {
  background: var(--color-successBg);
  color: var(--color-successText);
  border: 1px solid rgba(21, 115, 71, 0.1);
}

.ui-badge.variant-warning {
  background: var(--color-warningBg);
  color: var(--color-warningText);
  border: 1px solid rgba(181, 129, 5, 0.1);
}

.ui-badge.variant-danger {
  background: var(--color-dangerBg);
  color: var(--color-dangerText);
  border: 1px solid rgba(187, 45, 59, 0.1);
}

.ui-badge.variant-info {
  background: var(--color-infoBg);
  color: var(--color-infoText);
  border: 1px solid rgba(13, 110, 253, 0.1);
}

.ui-badge.variant-neutral {
  background: var(--color-neutralBg);
  color: var(--color-neutralText);
  border: 1px solid var(--color-border);
}

.ui-badge.variant-primary {
  background: var(--color-primary);
  color: white;
}
</style>
