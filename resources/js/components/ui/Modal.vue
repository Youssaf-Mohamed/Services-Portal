<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="modelValue" class="modal-overlay" @click="handleOverlayClick">
        <Transition name="modal-slide">
          <div v-if="modelValue" class="modal-container" :class="sizeClass" @click.stop>
            <!-- Header -->
            <div class="modal-header">
              <h2 class="modal-title">{{ title }}</h2>
              <button class="modal-close" @click="close" aria-label="Close">×</button>
            </div>

            <!-- Body -->
            <div class="modal-body">
              <slot></slot>
            </div>

            <!-- Footer -->
            <div v-if="$slots.footer" class="modal-footer">
              <slot name="footer"></slot>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true
  },
  title: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v)
  },
  closeOnOverlay: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['update:modelValue', 'close']);

const sizeClass = computed(() => `size-${props.size}`);

const close = () => {
  emit('update:modelValue', false);
  emit('close');
};

const handleOverlayClick = () => {
  if (props.closeOnOverlay) {
    close();
  }
};

// Handle escape key
watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    document.addEventListener('keydown', handleEscape);
  } else {
    document.removeEventListener('keydown', handleEscape);
  }
});

const handleEscape = (e) => {
  if (e.key === 'Escape') {
    close();
  }
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6); /* Slightly darker for better focus */
  backdrop-filter: blur(2px);     /* Professional touch */
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: var(--spacing-lg);
  overflow-y: auto;
}

.modal-container {
  background: var(--color-surface);
  border-radius: var(--radius-xl);
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--color-border);
}

/* Size variants */
.modal-container.size-sm {
  max-width: 400px;
}

.modal-container.size-md {
  max-width: 600px;
}

.modal-container.size-lg {
  max-width: 800px;
}

.modal-container.size-xl {
  max-width: 1000px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-lg) var(--spacing-xl);
  border-bottom: 1px solid var(--color-borderLight);
  background: var(--color-surface);
  /* Primary Accent Line */
  border-top: 4px solid var(--color-primary); 
}

.modal-title {
  font-size: var(--font-xl);
  font-weight: var(--fw-bold);
  color: var(--color-primary); /* Use Primary color for title */
  margin: 0;
}

.modal-close {
  background: none;
  border: none;
  font-size: 28px;
  color: var(--color-textMuted);
  cursor: pointer;
  line-height: 1;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color var(--transition-fast);
}

.modal-close:hover {
  color: var(--color-danger);
}

.modal-close:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
  border-radius: var(--radius-sm);
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: var(--spacing-xl);
  max-height: calc(90vh - 140px);
  color: var(--color-textMain);
}

@media (max-width: 768px) {
  .modal-container {
    max-height: 95vh;
    margin: var(--spacing-sm);
}
  
  .modal-body {
    max-height: calc(95vh - 140px);
    padding: var(--spacing-lg);
  }
  
  .modal-header, .modal-footer {
    padding: var(--spacing-lg);
  }
}

.modal-footer {
  padding: var(--spacing-lg) var(--spacing-xl);
  border-top: 1px solid var(--color-borderLight);
  background: var(--color-surfaceHighlight);
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-md);
}

/* Transitions */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity var(--transition-normal);
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-slide-enter-active,
.modal-slide-leave-active {
  transition: transform var(--transition-normal), opacity var(--transition-normal);
}

.modal-slide-enter-from,
.modal-slide-leave-to {
  transform: translateY(10px) scale(0.98);
  opacity: 0;
}
</style>
