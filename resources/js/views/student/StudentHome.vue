<template>
  <PortalLayout>
    <div class="student-home">
      <!-- Welcome Section -->
      <section class="welcome-section">
        <h1 class="welcome-title">Welcome back, {{ authStore.user?.name?.split(' ')[0] }}! 👋</h1>
        <p class="welcome-subtitle">Access your university services and track your requests from one place.</p>
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

      <!-- Recent Activity / Quick Stats (Placeholder layout) -->
      <section class="dashboard-widgets">
        <div class="widget-card">
           <div class="widget-header">
             <Clock class="widget-icon" />
             <h3>Recent Activity</h3>
           </div>
           <div class="widget-body">
             <div class="empty-activity">
               <p>No recent activity to show.</p>
               <router-link to="/student/my-requests" class="text-link">View all requests</router-link>
             </div>
           </div>
        </div>

        <div class="widget-card">
           <div class="widget-header">
             <Bell class="widget-icon" />
             <h3>Announcements</h3>
           </div>
           <div class="widget-body">
             <div class="announcement-item">
                <span class="date">Feb 1, 2026</span>
                <p>New transportation routes valid for Spring 2026 are now available.</p>
             </div>
           </div>
        </div>
      </section>

    </div>
  </PortalLayout>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { Bus, CreditCard, ArrowRight, Clock, Bell } from 'lucide-vue-next';

const authStore = useAuthStore();
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

/* Dashboard Widgets */
.dashboard-widgets {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: var(--spacing-xl);
}

.widget-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg);
}

.widget-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-md);
  border-bottom: 1px solid var(--color-borderLight);
}

.widget-icon {
  width: 20px;
  height: 20px;
  color: var(--color-textMuted);
}

.widget-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: var(--color-textMain);
  margin: 0;
}

.widget-body {
  min-height: 100px;
}

.empty-activity {
  text-align: center;
  color: var(--color-textMuted);
  font-size: 14px;
  padding: var(--spacing-md);
}

.text-link {
  display: block;
  margin-top: var(--spacing-sm);
  color: var(--color-primary);
  font-weight: 500;
  text-decoration: none;
}

.text-link:hover {
  text-decoration: underline;
}

.announcement-item {
  padding: var(--spacing-sm);
  background: var(--color-surfaceHighlight);
  border-radius: var(--radius-md);
}

.announcement-item .date {
  font-size: 11px;
  font-weight: 700;
  color: var(--color-textMuted);
  text-transform: uppercase;
  display: block;
  margin-bottom: 4px;
}

.announcement-item p {
  margin: 0;
  font-size: 13px;
  color: var(--color-textMain);
  line-height: 1.4;
}
</style>
