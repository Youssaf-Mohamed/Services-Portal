<template>
  <Teleport to="body">
    <TransitionGroup name="toast-slide" tag="div" class="toast-container">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="toast"
        :class="`toast-${toast.type}`"
        @click="dismiss(toast.id)"
      >
        <span class="toast-icon">{{ getIcon(toast.type) }}</span>
        <span class="toast-message">{{ toast.message }}</span>
        <button class="toast-close" @click.stop="dismiss(toast.id)" aria-label="Close">
          ×
        </button>
      </div>
    </TransitionGroup>
  </Teleport>
</template>

<script setup>
import { useToast } from '@/composables/useToast';

const { toasts, dismissToast } = useToast();

const dismiss = (id) => {
  dismissToast(id);
};

const getIcon = (type) => {
  switch (type) {
    case 'success': return '✓';
    case 'error': return '✕';
    case 'warning': return '⚠';
    case 'info': return 'ℹ';
    default: return 'ℹ';
  }
};
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 80px;
  right: var(--spacing-lg);
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  max-width: 400px;
  pointer-events: none;
}

.toast {
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-md);
  padding: var(--spacing-md) var(--spacing-lg);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-left: 4px solid;
  pointer-events: auto;
  cursor: pointer;
  transition: transform var(--transition-normal), opacity var(--transition-normal);
}

.toast:hover {
  transform: translateX(-4px);
  box-shadow: var(--shadow-lg);
}

.toast-icon {
  font-size: 18px;
  font-weight: bold;
  flex-shrink: 0;
  margin-top: 2px;
}

.toast-message {
  flex: 1;
  font-size: var(--font-base);
  color: var(--color-textMain);
  line-height: 1.5;
  font-weight: var(--fw-medium);
}

.toast-close {
  background: none;
  border: none;
  font-size: 20px;
  color: var(--color-textMuted);
  cursor: pointer;
  padding: 0;
  line-height: 1;
  flex-shrink: 0;
  margin-top: 2px;
  opacity: 0.6;
  transition: opacity var(--transition-fast);
}

.toast-close:hover {
  opacity: 1;
  color: var(--color-textMain);
}

/* Toast types */
.toast-success {
  border-left-color: var(--color-success);
  background: var(--color-successBg);
}

.toast-success .toast-icon {
  color: var(--color-successText);
}

.toast-error {
  border-left-color: var(--color-danger);
  background: var(--color-dangerBg);
}

.toast-error .toast-icon {
  color: var(--color-dangerText);
}

.toast-warning {
  border-left-color: var(--color-warning);
  background: var(--color-warningBg);
}

.toast-warning .toast-icon {
  color: var(--color-warningText);
}

.toast-info {
  border-left-color: var(--color-info);
  background: var(--color-infoBg);
}

.toast-info .toast-icon {
  color: var(--color-infoText);
}

/* Transitions */
.toast-slide-enter-active,
.toast-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.toast-slide-enter-from {
  transform: translateX(100%);
  opacity: 0;
}

.toast-slide-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

/* Mobile */
@media (max-width: 480px) {
  .toast-container {
    left: var(--spacing-md);
    right: var(--spacing-md);
    max-width: none;
  }
}
</style>
