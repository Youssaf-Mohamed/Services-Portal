<template>
  <div class="service-card" @click="handleSelect">
    <div class="card-content">
      <!-- Icon & Header -->
      <div class="card-header">
        <div class="card-icon" :class="iconVariant">
          <component :is="iconComponent" />
        </div>
        <div class="header-text">
          <h3 class="card-title">{{ type.name_en }}</h3>
          <span class="card-subtitle">{{ type.name_ar }}</span>
        </div>
      </div>

      <!-- Description -->
      <p class="card-description">{{ type.description_en }}</p>

      <!-- Footer Info -->
      <div class="card-footer">
        <div class="fee-info">
          <span class="label">Service Fee</span>
          <span class="amount">{{ type.fee }} <span class="currency">EGP</span></span>
        </div>
        
        <div class="requirements-badges">
          <div v-if="type.requires_photo" class="req-badge photo" title="Photo Required">
            <Camera />
          </div>
          <div v-if="type.requires_description" class="req-badge desc" title="Description Required">
            <FileText />
          </div>
        </div>
      </div>
    </div>

    <!-- Action Bar (visible on hover) -->
    <div class="card-action">
      <span>Request Service</span>
      <ArrowRight class="action-arrow" />
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
    lost: ClipboardList,
    photo_change: Camera,
    damaged: Wrench
  };
  return icons[props.type.code] || ClipboardList;
});

const iconVariant = computed(() => {
  // Returns class suffix for coloring
  const variants = {
    new: 'primary',
    renew: 'primary',
    lost: 'warning',
    photo_change: 'success',
    damaged: 'danger'
  };
  return variants[props.type.code] || 'default';
});

const handleSelect = () => {
  emit('select', props.type);
};
</script>

<style scoped>
.service-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 16px; /* Professional rounded corners */
  padding: 24px; /* Spacious internal padding */
  cursor: pointer;
  position: relative;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.service-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.1); /* Deep, soft shadow */
  border-color: var(--color-primaryLight);
}

.card-content {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.card-header {
  display: flex;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-md);
}

/* Icon Style (Unified Professional Look) */
.card-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 1px solid var(--color-borderLight);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
  color: var(--color-primary);
  transition: all 0.3s ease;
}

.card-icon svg {
  width: 24px;
  height: 24px;
}

.service-card:hover .card-icon {
  transform: scale(1.05);
  border-color: var(--color-primary);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15); /* Primary glow */
}

.header-text {
  flex: 1;
  min-width: 0;
}

.card-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--color-textMain);
  margin: 0 0 2px 0;
  transition: color 0.2s ease;
}

.service-card:hover .card-title {
  color: var(--color-primary);
}

.card-subtitle {
  font-size: 13px;
  color: var(--color-textMuted);
  font-family: var(--font-arabic, sans-serif);
}

.card-description {
  font-size: 14px;
  line-height: 1.6;
  color: var(--color-textSecondary);
  margin-bottom: var(--spacing-xl);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2; /* Standard property */
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-footer {
  margin-top: auto;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  padding-top: var(--spacing-md);
  border-top: 1px solid var(--color-border);
}

.fee-info {
  display: flex;
  flex-direction: column;
}

.fee-info .label {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--color-textMuted);
  font-weight: 600;
}

.fee-info .amount {
  font-size: 18px;
  font-weight: 700;
  color: var(--color-textMain);
}

.fee-info .currency {
  font-size: 12px;
  font-weight: 500;
  color: var(--color-textSecondary);
}

.requirements-badges {
  display: flex;
  gap: var(--spacing-xs);
}

.req-badge {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.req-badge svg { width: 14px; height: 14px; }

.req-badge.photo { background: var(--color-infoBg); color: var(--color-info); }
.req-badge.desc { background: var(--color-warningBg); color: var(--color-warning); }

.card-action {
  margin-top: var(--spacing-md);
  background: var(--color-surfaceHighlight);
  color: var(--color-textMain);
  padding: 10px;
  border-radius: var(--radius-md);
  font-size: 13px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.3s ease;
}

.service-card:hover .card-action {
  background: var(--color-primary);
  color: white;
}

.action-arrow {
  width: 16px;
  height: 16px;
  transition: transform 0.2s ease;
}

.service-card:hover .action-arrow {
  transform: translateX(4px);
}
</style>
