<template>
  <div class="service-card" @click="handleSelect">
    <div class="card-icon-wrapper" :style="{ background: iconBackground }">
      <component :is="iconComponent" class="card-icon" />
    </div>
    
    <div class="card-content">
      <h3 class="card-title">{{ type.name_en }}</h3>
      <p class="card-title-ar">{{ type.name_ar }}</p>
      <p class="card-description">{{ type.description_en }}</p>
    </div>

    <div class="card-footer">
      <div class="card-fee">
        <span class="fee-label">Fee:</span>
        <span class="fee-amount">{{ type.fee }} EGP</span>
      </div>
      
      <div class="card-requirements">
        <span v-if="type.requires_photo" class="requirement-badge photo">
          <Camera class="badge-icon" /> Photo Required
        </span>
        <span v-if="type.requires_description" class="requirement-badge description">
          <FileText class="badge-icon" /> Description Required
        </span>
      </div>
      
      <button class="btn-select">
        Request Service <ArrowRight class="btn-icon" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { 
  CreditCard, Camera, Wrench, ClipboardList, 
  FileText, ArrowRight 
} from 'lucide-vue-next';

const props = defineProps({
  type: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['select']);

const iconComponent = computed(() => {
  const icons = {
    new: CreditCard,
    renew: CreditCard,
    lost: ClipboardList, // Or a "Search" icon, but clipboard implies process
    photo_change: Camera,
    damaged: Wrench
  };
  return icons[props.type.code] || ClipboardList;
});

const iconBackground = computed(() => {
  const backgrounds = {
    new: 'var(--color-primaryBg)',
    renew: 'var(--color-infoBg)',
    lost: 'var(--color-warningBg)',
    photo_change: 'var(--color-successBg)',
    damaged: 'var(--color-dangerBg)'
  };
  return backgrounds[props.type.code] || 'var(--color-surfaceHighlight)';
});

// We can also compute icon color if needed, but simple inheritance works for now
// or specific colors for specific types
const handleSelect = () => {
  emit('select', props.type);
};
</script>

<style scoped>
.service-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: var(--spacing-xl);
  cursor: pointer;
  transition: all var(--transition-normal);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
  position: relative;
  overflow: hidden;
}

.service-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
  border-color: var(--color-primary);
}

.card-icon-wrapper {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-textMain); /* Fallback */
}

/* Specific icon colors based on type logic can be handled via style binding or classes, 
   but for now let's use the type code logic in script or just generic primary */
.card-icon-wrapper {
  color: var(--color-primary);
}

.card-icon {
  width: 28px;
  height: 28px;
}

.card-content {
  flex: 1;
}

.card-title {
  margin: 0 0 var(--spacing-xs) 0;
  font-size: var(--font-lg);
  font-weight: var(--fw-bold);
  color: var(--color-textMain);
}

.card-title-ar {
  margin: 0 0 var(--spacing-sm) 0;
  font-size: var(--font-sm);
  color: var(--color-textMuted);
  direction: rtl;
  font-family: 'Tahoma', sans-serif; /* Example fallback */
}

.card-description {
  margin: 0;
  font-size: var(--font-sm);
  color: var(--color-textSecondary);
  line-height: 1.5;
}

.card-footer {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  margin-top: auto;
}

.card-fee {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.fee-label {
  font-size: var(--font-sm);
  color: var(--color-textMuted);
}

.fee-amount {
  font-size: var(--font-lg);
  font-weight: var(--fw-bold);
  color: var(--color-primary);
}

.card-requirements {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-sm);
}

.requirement-badge {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: var(--font-xs);
  padding: 4px 8px;
  border-radius: var(--radius-md);
  font-weight: var(--fw-medium);
}

.badge-icon {
  width: 12px;
  height: 12px;
}

.requirement-badge.photo {
  background: var(--color-infoBg);
  color: var(--color-infoText);
}

.requirement-badge.description {
  background: var(--color-warningBg);
  color: var(--color-warningText);
}

.btn-select {
  width: 100%;
  padding: var(--spacing-sm) var(--spacing-lg);
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: var(--radius-md);
  font-weight: var(--fw-medium);
  cursor: pointer;
  transition: all var(--transition-fast);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-md);
}

.btn-select:hover {
  background: var(--color-primaryDark);
}

.btn-icon {
  width: 16px;
  height: 16px;
}
</style>
