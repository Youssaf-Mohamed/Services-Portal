<template>
  <div class="ui-card" :class="cardClasses">
    <div v-if="$slots.header" class="card-header">
      <slot name="header"></slot>
    </div>
    <div class="card-body">
      <slot></slot>
    </div>
    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer"></slot>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  padding: {
    type: String,
    default: 'md',
    validator: (v) => ['none', 'sm', 'md', 'lg'].includes(v)
  },
  shadow: {
    type: String,
    default: 'md',
    validator: (v) => ['none', 'sm', 'md', 'lg'].includes(v)
  },
  hover: {
    type: Boolean,
    default: false
  }
});

const cardClasses = computed(() => ({
  [`padding-${props.padding}`]: true,
  [`shadow-${props.shadow}`]: true,
  'card-hover': props.hover
}));
</script>

<style scoped>
.ui-card {
  background: var(--color-surface);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  transition: box-shadow var(--transition-normal);
}

/* Padding variants */
.ui-card.padding-none {
  padding: 0;
}

.ui-card.padding-sm .card-body {
  padding: var(--spacing-sm);
}

.ui-card.padding-md .card-body {
  padding: var(--spacing-md);
}

.ui-card.padding-lg .card-body {
  padding: var(--spacing-lg);
}

/* Shadow variants - Redefined for minimalism */
.ui-card.shadow-none {
  box-shadow: none;
}

.ui-card.shadow-sm {
  box-shadow: var(--shadow-sm);
}

.ui-card.shadow-md {
  box-shadow: var(--shadow-md);
}

.ui-card.shadow-lg {
  box-shadow: var(--shadow-lg);
}

/* Hover effect */
.ui-card.card-hover:hover {
  border-color: var(--color-primaryLight);
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

/* Header */
.card-header {
  padding: var(--spacing-md) var(--spacing-lg);
  border-bottom: 1px solid var(--color-borderLight);
  font-weight: var(--fw-bold);
  color: var(--color-primary); /* Primary Colored Header Text as requested */
  background: #ffffff;
  border-top-left-radius: var(--radius-lg);
  border-top-right-radius: var(--radius-lg);
}

/* Footer */
.card-footer {
  padding: var(--spacing-md) var(--spacing-lg);
  border-top: 1px solid var(--color-borderLight);
  background: var(--color-surfaceHighlight);
  border-bottom-left-radius: var(--radius-lg);
  border-bottom-right-radius: var(--radius-lg);
}
</style>
