<template>
  <a 
    href="#" 
    @click.prevent="handleClick" 
    class="action-card"
  >
    <div class="action-icon-bg">
      <component :is="icon" class="action-icon" />
    </div>
    <div class="action-details">
      <span class="action-name">{{ title }}</span>
      <span class="action-desc">{{ description }}</span>
    </div>
  </a>
</template>

<script setup>
import { useRouter } from 'vue-router';

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    default: ''
  },
  icon: {
    type: Object,
    required: true
  },
  to: {
    type: String,
    required: true
  }
});

const router = useRouter();

const handleClick = () => {
  if (props.to) {
    router.push(props.to);
  }
};
</script>

<style scoped>
.action-card {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  padding: var(--spacing-lg);
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  text-decoration: none;
  transition: all 0.2s ease;
  cursor: pointer;
}

.action-card:hover {
  border-color: var(--color-primary);
  background-color: var(--color-surfaceHighlight);
}

.action-icon-bg {
  width: 40px;
  height: 40px;
  background-color: #f3f4f6;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-textMain);
  transition: all 0.2s ease;
}

.action-card:hover .action-icon-bg {
  background-color: white;
  color: var(--color-primary);
}

.action-icon {
  width: 20px;
  height: 20px;
}

.action-details {
  display: flex;
  flex-direction: column;
}

.action-name {
  font-size: 15px;
  font-weight: 600;
  color: var(--color-textMain);
}

.action-desc {
  font-size: 13px;
  color: var(--color-textMuted);
}
</style>
