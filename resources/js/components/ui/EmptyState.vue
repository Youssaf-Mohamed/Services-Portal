<template>
  <div class="ui-empty-state">
    <div class="empty-icon-wrapper">
        <component :is="icon" v-if="isValidComponent" class="empty-icon-svg" />
        <span v-else class="empty-icon-text">{{ icon }}</span>
    </div>
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
import { computed } from 'vue';
import Button from './Button.vue';
import { useRouter } from 'vue-router';
import { ClipboardList } from 'lucide-vue-next';

const props = defineProps({
  icon: {
    type: [String, Object],
    default: () => ClipboardList
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

const isValidComponent = computed(() => {
  // Lucide icons are functional components (type function) or objects
  return typeof props.icon === 'object' || typeof props.icon === 'function';
});

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
  background: white;
  border-radius: var(--radius-lg);
  border: 1px dashed var(--color-border);
  min-height: 400px;
  transition: all 0.3s ease;
}

.empty-icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 96px;
  height: 96px;
  border-radius: 50%;
  background: var(--color-background);
  margin-bottom: var(--spacing-xl);
  color: var(--color-textMuted);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
}

.empty-icon-svg {
  width: 48px;
  height: 48px;
  color: var(--color-textLight);
}

.empty-icon-text {
  font-size: 48px;
}

.empty-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-textMain);
  margin: 0 0 var(--spacing-sm) 0;
  letter-spacing: -0.025em;
}

.empty-message {
  font-size: 1rem;
  color: var(--color-textMuted);
  margin: 0 0 var(--spacing-xl) 0;
  max-width: 320px;
  line-height: 1.6;
}
</style>
