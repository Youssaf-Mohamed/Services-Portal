<template>
  <div class="kpi-card" :class="variant">
    <div class="kpi-header">
      <span class="kpi-title">{{ title }}</span>
      <div class="kpi-icon-wrapper">
        <component :is="icon" class="kpi-icon" />
      </div>
    </div>
    
    <div class="kpi-body">
      <span class="kpi-value">{{ value }}</span>
      
      <!-- Optional Trend / Subtext -->
      <span v-if="trend" class="kpi-trend" :class="trendType">
        {{ trend }}
      </span>
      <span v-else-if="subtext" class="kpi-subtext">
        {{ subtext }}
      </span>
    </div>

    <!-- Optional Footer Link -->
    <a 
      v-if="to" 
      href="#" 
      @click.prevent="handleClick" 
      class="kpi-footer-link"
    >
      {{ linkText || 'View Details' }} 
      <ChevronRight class="link-arrow" />
    </a>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { ChevronRight } from 'lucide-vue-next';

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [String, Number],
    required: true
  },
  icon: {
    type: Object, // Component
    required: true
  },
  variant: {
    type: String, // primary, info, warning, success
    default: 'primary',
    validator: (val) => ['primary', 'info', 'warning', 'success', 'danger'].includes(val)
  },
  subtext: {
    type: String,
    default: ''
  },
  trend: {
    type: String,
    default: ''
  },
  trendType: {
    type: String, // positive, negative, neutral
    default: 'neutral'
  },
  to: {
    type: String,
    default: ''
  },
  linkText: {
    type: String,
    default: ''
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
.kpi-card {
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-xl);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 160px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  position: relative;
  overflow: hidden;
}

.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

/* Top colored line */
.kpi-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
}

.kpi-card.primary::before { background-color: var(--color-primary); }
.kpi-card.info::before { background-color: var(--color-info); }
.kpi-card.warning::before { background-color: var(--color-warning); }
.kpi-card.success::before { background-color: var(--color-success); }
.kpi-card.danger::before { background-color: var(--color-danger); }

.kpi-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: var(--spacing-md);
}

.kpi-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-textMuted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.kpi-icon-wrapper {
  background: var(--color-background);
  padding: 8px;
  border-radius: var(--radius-md);
  color: var(--color-textMuted);
}

.kpi-card.primary .kpi-icon-wrapper { color: var(--color-primary); background-color: #f0fdf4; }
.kpi-card.info .kpi-icon-wrapper { color: var(--color-info); background-color: #eff6ff; }
.kpi-card.warning .kpi-icon-wrapper { color: var(--color-warning); background-color: #fffbeb; }
.kpi-card.success .kpi-icon-wrapper { color: var(--color-success); background-color: #f0fdf4; }
.kpi-card.danger .kpi-icon-wrapper { color: var(--color-danger); background-color: #fef2f2; }

.kpi-icon {
  width: 20px;
  height: 20px;
}

.kpi-body {
  margin-bottom: var(--spacing-lg);
}

.kpi-value {
  font-size: 32px;
  font-weight: 700;
  color: var(--color-textMain);
  display: block;
  line-height: 1.2;
}

.kpi-subtext {
  font-size: 13px;
  color: var(--color-textMuted);
  display: block;
  margin-top: 4px;
}

.kpi-trend {
  font-size: 13px;
  font-weight: 500;
  display: block;
  margin-top: 4px;
}

.kpi-trend.positive { color: var(--color-success); }
.kpi-trend.negative { color: var(--color-danger); }

.kpi-footer-link {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-primary);
  text-decoration: none;
  margin-top: auto;
}

.kpi-footer-link:hover .link-arrow {
  transform: translateX(4px);
}

.link-arrow {
  width: 16px;
  height: 16px;
  transition: transform 0.2s ease;
}
</style>
