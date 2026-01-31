    <template>
  <div class="stat-card" :class="variant">
    <div class="stat-header">
      <span class="stat-title">{{ title }}</span>
      <div class="stat-icon-wrapper">
        <component :is="icon" class="stat-icon" />
      </div>
    </div>
    
    <div class="stat-body">
      <span class="stat-value">{{ value }}</span>
      
      <!-- Optional Trend / Subtext -->
      <span v-if="trend" class="stat-trend" :class="trendType">
        {{ trend }}
      </span>
      <span v-else-if="subtext" class="stat-subtext">
        {{ subtext }}
      </span>
    </div>

    <!-- Optional Footer Link -->
    <a 
      v-if="to" 
      href="#" 
      @click.prevent="handleClick" 
      class="stat-footer-link"
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
.stat-card {
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

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

/* Top colored line */
.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
}

.stat-card.primary::before { background-color: var(--color-primary); }
.stat-card.info::before { background-color: var(--color-info); }
.stat-card.warning::before { background-color: var(--color-warning); }
.stat-card.success::before { background-color: var(--color-success); }
.stat-card.danger::before { background-color: var(--color-danger); }

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: var(--spacing-md);
}

.stat-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-textMuted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-icon-wrapper {
  background: var(--color-background);
  padding: 8px;
  border-radius: var(--radius-md);
  color: var(--color-textMuted);
}

.stat-card.primary .stat-icon-wrapper { color: var(--color-primary); background-color: #f0fdf4; }
.stat-card.info .stat-icon-wrapper { color: var(--color-info); background-color: #eff6ff; }
.stat-card.warning .stat-icon-wrapper { color: var(--color-warning); background-color: #fffbeb; }
.stat-card.success .stat-icon-wrapper { color: var(--color-success); background-color: #f0fdf4; }
.stat-card.danger .stat-icon-wrapper { color: var(--color-danger); background-color: #fef2f2; }

.stat-icon {
  width: 20px;
  height: 20px;
}

.stat-body {
  margin-bottom: var(--spacing-lg);
}

.stat-value {
  font-size: 32px;
  font-weight: 700;
  color: var(--color-textMain);
  display: block;
  line-height: 1.2;
}

.stat-subtext {
  font-size: 13px;
  color: var(--color-textMuted);
  display: block;
  margin-top: 4px;
}

.stat-trend {
  font-size: 13px;
  font-weight: 500;
  display: block;
  margin-top: 4px;
}

.stat-trend.positive { color: var(--color-success); }
.stat-trend.negative { color: var(--color-danger); }

.stat-footer-link {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-primary);
  text-decoration: none;
  margin-top: auto;
}

.stat-footer-link:hover .link-arrow {
  transform: translateX(4px);
}

.link-arrow {
  width: 16px;
  height: 16px;
  transition: transform 0.2s ease;
}
</style>
