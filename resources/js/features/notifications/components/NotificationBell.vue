<template>
  <div class="notification-bell-container">
    <button 
      class="notification-btn" 
      @click="handleBellClick"
      aria-label="Notifications"
    >
      <Bell class="bell-icon" />
      <span v-if="unreadCount > 0" class="badge">
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { Bell } from 'lucide-vue-next';
import { notificationsApi } from '../api/notifications.api';

const router = useRouter();
const unreadCount = ref(0);
const pollInterval = ref(null);

const fetchUnreadCount = async () => {
  try {
    const response = await notificationsApi.getUnreadCount();
    if (response.data.success) {
      unreadCount.value = response.data.data.count;
    }
  } catch (error) {
    console.error('Failed to fetch notification count', error);
  }
};

const handleBellClick = () => {
  router.push('/notifications');
};

onMounted(() => {
  fetchUnreadCount();
  // Poll every 60 seconds
  pollInterval.value = setInterval(fetchUnreadCount, 60000);
});

onUnmounted(() => {
  if (pollInterval.value) {
    clearInterval(pollInterval.value);
  }
});
</script>

<style scoped>
.notification-bell-container {
  position: relative;
  display: flex;
  align-items: center;
}

.notification-btn {
  background: none;
  border: none;
  padding: 8px;
  cursor: pointer;
  color: var(--color-textMuted);
  border-radius: 50%;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.notification-btn:hover {
  background-color: var(--color-bgHover);
  color: var(--color-primary);
}

.bell-icon {
  width: 24px;
  height: 24px;
}

.badge {
  position: absolute;
  top: 0;
  right: 0;
  background-color: var(--color-danger);
  color: white;
  font-size: 11px;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  border: 2px solid var(--color-bgSurface);
  transform: translate(25%, -25%);
}
</style>
