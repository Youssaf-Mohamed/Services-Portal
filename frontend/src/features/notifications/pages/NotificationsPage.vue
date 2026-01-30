<template>
  <PortalLayout>
    <PageHeader 
      title="Notifications" 
      subtitle="Stay updated with your latest activities"
    >
      <template #actions>
        <Button 
          v-if="hasUnread"
          variant="secondary" 
          @click="markAllRead"
          :disabled="loading"
        >
          <CheckCheck class="btn-icon" /> Mark all as read
        </Button>
      </template>
    </PageHeader>

    <div class="notifications-page">
      <!-- Loading State -->
      <div v-if="loading && !notifications.length" class="loading-state">
        <div class="spinner"></div>
      </div>

      <!-- Empty State -->
      <div v-else-if="notifications.length === 0" class="empty-state">
        <BellOff class="empty-icon" />
        <h3>No notifications yet</h3>
        <p>We'll notify you when something important happens.</p>
      </div>

      <!-- Notifications List -->
      <div v-else class="notifications-list">
        <div 
          v-for="notification in notifications" 
          :key="notification.id"
          class="notification-item"
          :class="{ 'unread': !notification.read }"
          @click="handleNotificationClick(notification)"
        >
          <div class="notification-icon" :class="getIconClass(notification.type)">
            <component :is="getIcon(notification.type)" />
          </div>
          
          <div class="notification-content">
            <div class="notification-header">
              <h4 class="title">{{ notification.title }}</h4>
              <span class="time">{{ formatTime(notification.created_at) }}</span>
            </div>
            <p class="message">{{ notification.message }}</p>
          </div>
          
          <div class="notification-actions">
             <div v-if="!notification.read" class="unread-dot"></div>
          </div>
        </div>

        <!-- Load More -->
        <div v-if="hasMore" class="load-more">
          <Button variant="secondary" @click="loadMore" :disabled="loadingMore">
            {{ loadingMore ? 'Loading...' : 'Load More' }}
          </Button>
        </div>
      </div>
    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import PortalLayout from '@/layouts/PortalLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Button from '@/components/ui/Button.vue';
import { notificationsApi } from '../api/notifications.api';
import { useToast } from '@/composables/useToast';
import { 
  Bell, BellOff, CheckCheck, Info, CheckCircle, XCircle, 
  AlertTriangle, Clock, FileText 
} from 'lucide-vue-next';

const router = useRouter();
const toast = useToast();

const notifications = ref([]);
const loading = ref(true);
const loadingMore = ref(false);
const page = ref(1);
const hasMore = ref(false);

const hasUnread = computed(() => notifications.value.some(n => !n.read));

const fetchNotifications = async (isLoadMore = false) => {
  try {
    if (isLoadMore) {
      loadingMore.value = true;
    } else {
      loading.value = true;
    }

    const response = await notificationsApi.getNotifications({ page: page.value });
    
    if (response.data.success) {
      const newNotifications = response.data.data.notifications;
      hasMore.value = response.data.data.has_more;
      
      if (isLoadMore) {
        notifications.value.push(...newNotifications);
      } else {
        notifications.value = newNotifications;
      }
    }
  } catch (error) {
    console.error('Failed to load notifications', error);
    toast.error('Failed to load notifications');
  } finally {
    loading.value = false;
    loadingMore.value = false;
  }
};

const loadMore = () => {
  page.value++;
  fetchNotifications(true);
};

const markAllRead = async () => {
  try {
    const response = await notificationsApi.markAllAsRead();
    if (response.data.success) {
      notifications.value.forEach(n => n.read = true);
      toast.success('All notifications marked as read');
    }
  } catch (error) {
    console.error('Failed to mark all as read', error);
  }
};

const handleNotificationClick = async (notification) => {
  // Mark as read if unread
  if (!notification.read) {
    try {
      await notificationsApi.markAsRead(notification.id);
      notification.read = true;
    } catch (error) {
      console.error('Failed to mark as read', error);
    }
  }

  // Navigate if link exists
  if (notification.link) {
    router.push(notification.link);
  }
};

const formatTime = (dateString) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffInSeconds = Math.floor((now - date) / 1000);
  
  if (diffInSeconds < 60) return 'Just now';
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
  
  return date.toLocaleDateString();
};

const getIcon = (type) => {
  switch (type) {
    case 'request_approved': return CheckCircle;
    case 'request_rejected': return XCircle;
    case 'request_waitlisted': return Clock;
    case 'payment_flagged': return AlertTriangle;
    case 'request_submitted': return FileText;
    case 'subscription_expiring': return AlertTriangle;
    default: return Info;
  }
};

const getIconClass = (type) => {
  switch (type) {
    case 'request_approved': return 'icon-success';
    case 'request_rejected': return 'icon-danger';
    case 'request_waitlisted': return 'icon-warning';
    case 'payment_flagged': return 'icon-danger';
    case 'subscription_expiring': return 'icon-warning';
    default: return 'icon-info';
  }
};

onMounted(() => {
  fetchNotifications();
});
</script>

<style scoped>
.notifications-page {
  max-width: 800px;
  margin: 0 auto;
}

.notifications-list {
  background: white;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  overflow: hidden;
}

.notification-item {
  display: flex;
  gap: var(--spacing-md);
  padding: var(--spacing-lg);
  border-bottom: 1px solid var(--color-border);
  cursor: pointer;
  transition: background-color 0.2s;
}

.notification-item:last-child {
  border-bottom: none;
}

.notification-item:hover {
  background-color: var(--color-bgHover);
}

.notification-item.unread {
  background-color: var(--color-bgSurface);
}

.notification-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.icon-success {
  background-color: var(--color-successLight);
  color: var(--color-success);
}

.icon-danger {
  background-color: var(--color-dangerLight);
  color: var(--color-danger);
}

.icon-warning {
  background-color: var(--color-warningLight);
  color: var(--color-warning);
}

.icon-info {
  background-color: var(--color-primaryLight);
  color: var(--color-primary);
}

.notification-content {
  flex: 1;
}

.notification-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: var(--spacing-xs);
}

.title {
  font-weight: 600;
  font-size: 15px;
  color: var(--color-textMain);
}

.time {
  font-size: 12px;
  color: var(--color-textMuted);
  white-space: nowrap;
  margin-left: var(--spacing-sm);
}

.message {
  font-size: 14px;
  color: var(--color-textMuted);
  line-height: 1.4;
}

.unread-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: var(--color-primary);
  margin-top: 6px;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
}

.empty-icon {
  width: 48px;
  height: 48px;
  color: var(--color-textMuted);
  margin-bottom: var(--spacing-md);
}

.empty-state h3 {
  font-size: 18px;
  margin-bottom: var(--spacing-sm);
  color: var(--color-textMain);
}

.empty-state p {
  color: var(--color-textMuted);
}

.loading-state {
  display: flex;
  justify-content: center;
  padding: 40px;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.load-more {
  padding: var(--spacing-md);
  text-align: center;
  border-top: 1px solid var(--color-border);
}

.btn-icon {
  width: 16px;
  height: 16px;
  margin-right: 6px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
