<template>
  <button
    class="ui-button"
    :class="buttonClasses"
    :disabled="disabled || loading"
    :type="type"
    @click="handleClick"
  >
    <span v-if="loading" class="button-spinner"></span>
    <span :class="{ 'button-content': loading }">
      <slot></slot>
    </span>
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'text', 'danger', 'success'].includes(v)
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v)
  },
  fullWidth: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  type: {
    type: String,
    default: 'button'
  }
});

const emit = defineEmits(['click']);

const buttonClasses = computed(() => ({
  [`variant-${props.variant}`]: true,
  [`size-${props.size}`]: true,
  'full-width': props.fullWidth,
  'loading': props.loading
}));

const handleClick = (event) => {
  if (!props.disabled && !props.loading) {
    emit('click', event);
  }
};
</script>

<style scoped>
.ui-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-sm);
  border: 1px solid transparent; /* Explicit border for consistency */
  border-radius: var(--radius-md);
  font-weight: var(--fw-medium);
  cursor: pointer;
  transition: all var(--transition-normal);
  white-space: nowrap;
  position: relative;
  font-family: inherit;
}

.ui-button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
  filter: grayscale(100%);
}

.ui-button:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}

.ui-button.full-width {
  width: 100%;
}

/* Size variants */
.ui-button.size-sm {
  height: 32px;
  padding: 0 var(--spacing-md);
  font-size: var(--font-sm);
}

.ui-button.size-md {
  height: 40px;
  padding: 0 var(--spacing-lg);
  font-size: var(--font-base);
}

.ui-button.size-lg {
  height: 48px;
  padding: 0 var(--spacing-xl);
  font-size: var(--font-lg);
}

/* Variant styles */
.ui-button.variant-primary {
  background: var(--color-primary);
  color: white;
  border-color: transparent;
}

.ui-button.variant-primary:hover:not(:disabled) {
  background: var(--color-primaryDark);
}

.ui-button.variant-primary:focus-visible {
  box-shadow: 0 0 0 3px var(--color-focusRing);
}

/* Secondary is now Academic Blue */
.ui-button.variant-secondary {
  background: var(--color-secondary);
  color: white;
  border-color: transparent;
}

.ui-button.variant-secondary:hover:not(:disabled) {
  background: var(--color-secondaryDark);
}

.ui-button.variant-secondary:focus-visible {
  box-shadow: 0 0 0 3px rgba(31, 111, 255, 0.25);
}

/* Text / Subtle Variant */
.ui-button.variant-text {
  background: transparent;
  color: var(--color-textMuted);
  border-color: transparent;
}

.ui-button.variant-text:hover:not(:disabled) {
  background: var(--color-neutralBg);
  color: var(--color-primary);
}

.ui-button.variant-danger {
  background: var(--color-danger);
  color: white;
  border-color: transparent;
}

.ui-button.variant-danger:hover:not(:disabled) {
  background: #a52026; /* Darker red manually */
}

.ui-button.variant-danger:focus-visible {
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.25);
}

/* Success Variant */
.ui-button.variant-success {
  background: var(--color-success);
  color: white;
  border-color: transparent;
}

.ui-button.variant-success:hover:not(:disabled) {
  background: #0f5132;
}

/* Loading state */
.button-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.6);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.button-content {
  opacity: 0;
  visibility: hidden;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
