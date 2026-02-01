<template>
  <PortalLayout>
    <div class="student-home">
      <!-- Welcome Section -->
      <section class="welcome-section">
        <h1 class="welcome-title">Welcome back, {{ authStore.user?.name?.split(' ')[0] }}! 👋</h1>
        <p class="welcome-subtitle">Access your university services and track your requests from one place.</p>

        <!-- Profile Verification Card (Temporary/Permanent) -->
        <div class="profile-container" v-if="authStore.user">
          <div class="profile-card">
            <div class="profile-avatar" v-if="authStore.user.avatar_url">
              <img :src="authStore.user.avatar_url" alt="Avatar" class="avatar-image-large" />
            </div>
            <div class="profile-avatar" v-else>
              {{ authStore.user.name.charAt(0) }}
            </div>
            <div class="profile-info">
              <h3>{{ authStore.user.name }}</h3>
              <p>{{ authStore.user.email }}</p>
              <span class="role-badge">{{ authStore.userRole }}</span>
            </div>
          </div>

          <a href="https://batechu.com/lms/dashboard" class="back-lms-btn">
            <ArrowLeft class="btn-icon" />
            Back to LMS
          </a>
        </div>
      </section>

      <!-- Services Grid -->
      <section class="services-grid">
        <!-- Transport Card -->
        <router-link to="/student/transport" class="service-card transport-card">
          <div class="card-content">
            <div class="icon-wrapper">
              <Bus class="service-icon" />
            </div>
            <div class="text-content">
              <h3>Transportation</h3>
              <p>Manage subscriptions, routes, and trips.</p>
            </div>
          </div>
          <div class="card-action">
            <span>Go to Transport</span>
            <ArrowRight class="action-icon" />
          </div>
        </router-link>

        <!-- ID Card Card -->
        <router-link to="/student/id-card" class="service-card id-card">
          <div class="card-content">
            <div class="icon-wrapper">
              <CreditCard class="service-icon" />
            </div>
            <div class="text-content">
              <h3>ID Card Services</h3>
              <p>Request new ID, replacement, or renewals.</p>
            </div>
          </div>
          <div class="card-action">
            <span>Go to ID Services</span>
            <ArrowRight class="action-icon" />
          </div>
        </router-link>
      </section>


      <!-- Recent Activity Timeline -->
      <section class="activity-section">
        <div class="widget-card timeline-widget">
          <div class="widget-header">
            <div class="header-left">
              <Clock class="widget-icon" />
              <h3>Activities</h3>
            </div>
            <router-link to="/student/my-requests" class="view-all-link">View All</router-link>
          </div>
          <div class="widget-body scrollable-body">
            <ActivityTimeline :activities="recentActivities" :loading="loadingActivities" />
          </div>
        </div>

        <!-- Announcements (Still relevant) -->
        <div class="widget-card announcements-widget">
          <div class="widget-header">
            <div class="header-left">
              <Bell class="widget-icon" />
              <h3>Announcements</h3>
            </div>
          </div>
          <div class="widget-body">
            <div class="announcement-item">
              <span class="date">Feb 1, 2026</span>
              <p>New transportation routes valid for Spring 2026 are now available.</p>
            </div>
            <div class="announcement-item">
              <span class="date">Jan 28, 2026</span>
              <p>ID Card renewal service is now open for all students.</p>
            </div>
          </div>
        </div>
      </section>

    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { Bus, CreditCard, ArrowRight, Clock, Bell, ArrowLeft } from 'lucide-vue-next';
import ActivityTimeline from '@/components/dashboard/ActivityTimeline.vue';
import { unifiedRequestsApi } from '@/features/idCard/api/idCard.api'; // Reuse existing unified api

const authStore = useAuthStore();

const loadingActivities = ref(false);
const activities = ref([]);

const recentActivities = computed(() => {
  return activities.value.slice(0, 5); // Show only top 5 recent
});

const fetchActivities = async () => {
  loadingActivities.value = true;
  try {
    const response = await unifiedRequestsApi.getAll();
    activities.value = response.data || [];
  } catch (error) {
    console.error('Failed to load activities', error);
  } finally {
    loadingActivities.value = false;
  }
};

onMounted(() => {
  fetchActivities();
});
</script>

<style scoped>
.student-home {
  max-width: 1000px;
  margin: 0 auto;
}

.welcome-section {
  margin-bottom: var(--spacing-2xl);
  text-align: center;
  padding: var(--spacing-xl) 0;
}

.welcome-title {
  font-size: 32px;
  font-weight: 800;
  color: var(--color-textStrong);
  margin: 0 0 var(--spacing-sm) 0;
  letter-spacing: -1px;
}

.welcome-subtitle {
  font-size: 16px;
  color: var(--color-textMuted);
  margin: 0;
}

/* Profile Card */
.profile-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  margin-top: 1.5rem;
}

.profile-card {
  background: white;
  border-radius: var(--radius-lg);
  padding: 1.5rem;
  display: inline-flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  border: 1px solid var(--color-border);
}

.profile-avatar {
  width: 48px;
  height: 48px;
  background: var(--color-primary);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: 700;
  overflow: hidden;
  /* Ensure image fits */
}

.avatar-image-large {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profile-info h3 {
  font-size: 16px;
  font-weight: 700;
  margin: 0;
  color: var(--color-textMain);
}

.profile-info p {
  font-size: 14px;
  color: var(--color-textMuted);
  margin: 2px 0 6px 0;
}

.role-badge {
  display: inline-block;
  padding: 2px 8px;
  background: #f3f4f6;
  color: #4b5563;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.back-lms-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background-color: transparent;
  border: 1px solid var(--color-primary);
  color: var(--color-primary);
  border-radius: var(--radius-md);
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s;
  font-size: 0.95rem;
}

.back-lms-btn:hover {
  background-color: var(--color-primaryLight);
  transform: translateY(-2px);
}

.btn-icon {
  width: 18px;
  height: 18px;
}

/* Services Grid */
.services-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: var(--spacing-xl);
  margin-bottom: var(--spacing-3xl);
}

.service-card {
  background: white;
  border-radius: var(--radius-xl);
  padding: var(--spacing-xl);
  text-decoration: none;
  border: 1px solid var(--color-border);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 200px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  position: relative;
  overflow: hidden;
}

.service-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 20px -5px rgba(0, 0, 0, 0.1);
  border-color: var(--color-primaryLight);
}

.card-content {
  position: relative;
  z-index: 2;
}

.icon-wrapper {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: var(--spacing-lg);
  transition: transform 0.3s ease;
}

.service-card:hover .icon-wrapper {
  transform: scale(1.1);
}

.transport-card .icon-wrapper {
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  color: #2563eb;
}

.id-card .icon-wrapper {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  color: #16a34a;
}

.service-icon {
  width: 28px;
  height: 28px;
}

.text-content h3 {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-textMain);
  margin: 0 0 var(--spacing-xs) 0;
}

.text-content p {
  font-size: 14px;
  color: var(--color-textMuted);
  margin: 0;
  line-height: 1.5;
}

.card-action {
  margin-top: var(--spacing-xl);
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  font-size: 14px;
  font-weight: 600;
  color: var(--color-primary);
  opacity: 0.8;
  transition: opacity 0.2s;
}

.service-card:hover .card-action {
  opacity: 1;
}

.action-icon {
  width: 16px;
  height: 16px;
  transition: transform 0.2s;
}

.service-card:hover .action-icon {
  transform: translateX(4px);
}


/* Activity Section Layout */
.activity-section {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-xl);
}

@media (min-width: 1024px) {
  .activity-section {
    grid-template-columns: 2fr 1fr;
    /* Activity takes more space */
    align-items: start;
  }
}

.widget-card {
  background: white;
  border-radius: var(--radius-xl);
  border: 1px solid var(--color-border);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  height: 100%;
}

.timeline-widget {
  min-height: 400px;
  /* Ensure space for timeline */
}

.widget-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--color-borderLight);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8fafc;
  /* Slight contrast for header */
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.widget-header h3 {
  font-size: 16px;
  font-weight: 700;
  color: var(--color-textMain);
  margin: 0;
}

.view-all-link {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-primary);
  text-decoration: none;
}

.view-all-link:hover {
  text-decoration: underline;
}

.widget-body {
  padding: 24px;
}

/* Scrollable area for timeline */
.scrollable-body {
  max-height: 500px;
  overflow-y: auto;
}

.announcement-item {
  margin-bottom: 16px;
  padding: 16px;
  background: var(--color-surfaceHighlight);
  border-radius: 12px;
  border: 1px solid var(--color-borderLight);
}

.announcement-item:last-child {
  margin-bottom: 0;
}

.announcement-item .date {
  font-size: 11px;
  font-weight: 700;
  color: var(--color-primary);
  text-transform: uppercase;
  display: block;
  margin-bottom: 6px;
  letter-spacing: 0.5px;
}

.announcement-item p {
  font-size: 14px;
  color: var(--color-textMain);
  line-height: 1.5;
  margin: 0;
}
</style>
