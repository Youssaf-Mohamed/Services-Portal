<template>
  <div class="activity-timeline">
    <div v-if="loading" class="loading-state">
       <SkeletonLoader v-for="i in 3" :key="i" height="60px" class="mb-4" />
    </div>

    <div v-else-if="activities.length === 0" class="empty-state">
      <div class="empty-icon-wrapper">
        <Clock class="empty-icon" />
      </div>
      <p>No recent activity found.</p>
    </div>

    <div v-else class="timeline-container">
      <div v-for="(group, date) in groupedActivities" :key="date" class="timeline-group">
        <div class="timeline-date">{{ date }}</div>
        
        <div class="timeline-items">
          <div 
            v-for="item in group" 
            :key="item.id" 
            class="timeline-item"
            :class="{ 'is-last': false }"
          >
            <!-- Timeline Connector -->
            <div class="timeline-connector">
              <div class="timeline-line"></div>
              <div class="timeline-dot" :class="getStatusColor(item.status)">
                <component :is="getIcon(item.module)" class="dot-icon" />
              </div>
            </div>

            <!-- Content -->
            <div class="timeline-content">
              <div class="content-header">
                <span class="activity-title">{{ getTitle(item) }}</span>
                <span class="activity-time">{{ formatTime(item.updated_at || item.created_at) }}</span>
              </div>
              <p class="activity-desc">
                {{ getDescription(item) }}
              </p>
              
              <!-- Status Chip -->
              <div class="status-chip" :class="getStatusColor(item.status)">
                {{ item.status_label }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { SkeletonLoader } from '@/components/ui';
import { Clock, Bus, CreditCard, CheckCircle, XCircle, AlertCircle, FileText } from 'lucide-vue-next';

const props = defineProps({
  activities: {
    type: Array,
    default: () => []
  },
  loading: Boolean
});

// Group activities by date (Today, Yesterday, etc.)
const groupedActivities = computed(() => {
  const groups = {};
  
  props.activities.forEach(item => {
    const date = new Date(item.updated_at || item.created_at);
    const day = getDayLabel(date);
    
    if (!groups[day]) groups[day] = [];
    groups[day].push(item);
  });
  
  return groups;
});

const getDayLabel = (date) => {
  const today = new Date();
  const yesterday = new Date();
  yesterday.setDate(today.getDate() - 1);

  if (isSameDay(date, today)) return 'Today';
  if (isSameDay(date, yesterday)) return 'Yesterday';
  
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const isSameDay = (d1, d2) => {
  return d1.getDate() === d2.getDate() &&
         d1.getMonth() === d2.getMonth() &&
         d1.getFullYear() === d2.getFullYear();
};

const formatTime = (dateString) => {
  return new Date(dateString).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
};

const getIcon = (module) => {
  return module === 'transport' ? Bus : CreditCard;
};

const getStatusColor = (status) => {
  if (['approved', 'active', 'delivered', 'ready_for_pickup'].includes(status)) return 'success';
  if (['rejected', 'cancelled'].includes(status)) return 'danger';
  if (['pending', 'processing'].includes(status)) return 'warning';
  return 'default';
};

const getTitle = (item) => {
  const action = item.updated_at !== item.created_at ? 'Update' : 'New Request';
  const service = item.module === 'transport' ? 'Transportation' : 'ID Card';
  return `${service} ${action}`;
};

const getDescription = (item) => {
  if (item.module === 'transport') {
    return `Request for route "${item.route_name || 'Bus Service'}" is currently ${item.status_label.toLowerCase()}.`;
  }
  return `Request for "${item.type_label || 'ID Service'}" is currently ${item.status_label.toLowerCase()}.`;
};

</script>

<style scoped>
.activity-timeline {
  width: 100%;
}

.timeline-group {
  margin-bottom: 24px;
}

.timeline-date {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--color-textMuted);
  margin-bottom: 16px;
  padding-left: 8px;
}

.timeline-item {
  display: flex;
  gap: 16px;
  position: relative;
  padding-bottom: 24px;
}

.timeline-item:last-child {
  padding-bottom: 0;
}

.timeline-connector {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 32px;
}

.timeline-dot {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
  background: white;
  border: 2px solid;
}

.dot-icon {
  width: 14px;
  height: 14px;
}

/* Status Colors for Dot */
.timeline-dot.success { border-color: #22c55e; color: #22c55e; background: #f0fdf4; }
.timeline-dot.warning { border-color: #f59e0b; color: #f59e0b; background: #fffbeb; }
.timeline-dot.danger { border-color: #ef4444; color: #ef4444; background: #fef2f2; }
.timeline-dot.default { border-color: #94a3b8; color: #94a3b8; background: #f8fafc; }

.timeline-line {
  position: absolute;
  top: 32px;
  bottom: -4px; /* Connect to next */
  left: 15px; /* Center of 32px dot */
  width: 2px;
  background: var(--color-borderLight);
  z-index: 1;
}

.timeline-item:last-child .timeline-line {
  display: none;
}

.timeline-content {
  flex: 1;
  background: var(--color-surface);
  border: 1px solid var(--color-borderLight);
  border-radius: 12px;
  padding: 12px 16px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.02);
  transition: all 0.2s;
}

.timeline-content:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  border-color: var(--color-primaryLight);
}

.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.activity-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-textMain);
}

.activity-time {
  font-size: 11px;
  color: var(--color-textMuted);
}

.activity-desc {
  font-size: 13px;
  color: var(--color-textSecondary);
  line-height: 1.4;
  margin-bottom: 8px;
}

.status-chip {
  display: inline-block;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  padding: 3px 8px;
  border-radius: 99px;
}

.status-chip.success { background: #dcfce7; color: #166534; }
.status-chip.warning { background: #fef3c7; color: #92400e; }
.status-chip.danger { background: #fee2e2; color: #991b1b; }
.status-chip.default { background: #f1f5f9; color: #475569; }

.empty-state {
  text-align: center;
  padding: 32px;
  color: var(--color-textMuted);
}

.empty-icon-wrapper {
  width: 48px;
  height: 48px;
  background: var(--color-surfaceHighlight);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 12px;
}

.empty-icon {
  width: 24px;
  height: 24px;
  color: var(--color-textMuted);
}
</style>
