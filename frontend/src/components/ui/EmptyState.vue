<template>
  <div class="ui-empty-state">
    <div class="empty-icon">{{ icon }}</div>
    <h3 class="empty-title">{{ title }}</h3>
    <p class="empty-message">{{ message }}</p>
    <Button
      v-if="actionText"
      :variant="actionVariant"
      @click="handleAction"
    >
      {{ actionText }}
    </Button>
  </div>
</template>

<script setup>
import Button from './Button.vue';
import { useRouter } from 'vue-router';

const props = defineProps({
  icon: {
    type: String,
    default: '📋'
  },
  title: {
    type: String,
    required: true
  },
  message: {
    type: String,
    required: true
  },
  actionText: {
    type: String,
    default: ''
  },
  actionTo: {
    type: String,
    default: ''
  },
  actionVariant: {
    type: String,
    default: 'primary'
  }
});

const emit = defineEmits(['action']);
const router = useRouter();

const handleAction = () => {
  if (props.actionTo) {
    router.push(props.actionTo);
  }
  emit('action');
};
</script>

<style scoped>
.ui-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-4xl) var(--spacing-xl);
  text-align: center;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: var(--spacing-lg);
  opacity: 0.5;
}

.empty-title {
  font-size: 24px;
  font-weight: bold;
  color: var(--color-textMain);
  margin: 0 0 var(--spacing-md) 0;
}

.empty-message {
  font-size: 16px;
  color: var(--color-textMuted);
  margin: 0 0 var(--spacing-2xl) 0;
  max-width: 400px;
}
</style>
