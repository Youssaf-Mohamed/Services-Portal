<template>
  <div class="stat-card" :class="color" @click="$emit('click')">
    <div class="stat-icon">{{ icon }}</div>
    <div class="stat-content">
      <div class="stat-value">{{ value }}</div>
      <div class="stat-title">{{ title }}</div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [Number, String],
    required: true
  },
  icon: {
    type: String,
    default: '📊'
  },
  color: {
    type: String,
    default: 'primary'
  }
});

defineEmits(['click']);
</script>

<style scoped>
.stat-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-xl);
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  cursor: pointer;
  transition: all 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  font-size: 2.5rem;
  width: 64px;
  height: 64px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-card.warning .stat-icon {
  background: rgba(245, 158, 11, 0.15);
}

.stat-card.success .stat-icon {
  background: rgba(34, 197, 94, 0.15);
}

.stat-card.info .stat-icon {
  background: rgba(59, 130, 246, 0.15);
}

.stat-card.danger .stat-icon {
  background: rgba(239, 68, 68, 0.15);
}

.stat-card.secondary .stat-icon {
  background: rgba(107, 114, 128, 0.15);
}

.stat-card.primary .stat-icon {
  background: rgba(99, 102, 241, 0.15);
}

.stat-content {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--color-text-primary);
}

.stat-title {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}
</style>
