<template>
  <PortalLayout>
    <PageHeader 
      title="Reports Center" 
      subtitle="Access and export system data in CSV/Excel format"
      :breadcrumbs="[
        { label: 'Admin', to: '/admin/transport' },
        { label: 'Reports' }
      ]"
    />

    <div class="reports-container">
      <div class="reports-grid">
        <!-- Active Subscriptions Report -->
        <div class="report-card">
          <div class="card-header-accent primary"></div>
          <div class="card-body">
            <div class="icon-wrapper primary">
              <Ticket class="icon" />
            </div>
            <div class="content">
              <h3>Active Subscriptions</h3>
              <p class="description">
                Full list of currently enrolled students. Includes route details, slot timings, and plan types.
              </p>
              <div class="meta-info">
                <span class="meta-item"><FileSpreadsheet class="meta-icon" /> CSV Format</span>
                <span class="meta-item"><Clock class="meta-icon" /> Real-time active</span>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <a :href="activeSubscriptionsUrl" download class="download-link">
              <Button variant="success" fullWidth>
                <Download class="btn-icon" /> Download Excel
              </Button>
            </a>
          </div>
        </div>

        <!-- Waitlist Report -->
        <div class="report-card">
          <div class="card-header-accent warning"></div>
          <div class="card-body">
            <div class="icon-wrapper warning">
              <Hourglass class="icon" />
            </div>
            <div class="content">
              <h3>Waitlist Request</h3>
              <p class="description">
                Students waiting for slot availability. Ordered by request date priority.
              </p>
               <div class="meta-info">
                <span class="meta-item"><FileSpreadsheet class="meta-icon" /> CSV Format</span>
                <span class="meta-item"><Clock class="meta-icon" /> Queue status</span>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <a :href="waitlistUrl" download class="download-link">
              <Button variant="success" fullWidth>
                <Download class="btn-icon" /> Download Excel
              </Button>
            </a>
          </div>
        </div>

        <!-- Manifest Data -->
        <div class="report-card">
          <div class="card-header-accent info"></div>
          <div class="card-body">
            <div class="icon-wrapper info">
              <Users class="icon" />
            </div>
            <div class="content">
              <h3>Passenger Manifest</h3>
              <p class="description">
                Daily passenger lists. To export specific days or routes, use the filters on the Manifest page.
              </p>
              <div class="meta-info">
                <span class="meta-item"><Filter class="meta-icon" /> Custom Filters</span>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <router-link to="/admin/transport/manifest" class="download-link">
              <Button variant="secondary" fullWidth>
                <ArrowRight class="btn-icon" /> Go to Manifest
              </Button>
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </PortalLayout>
</template>

<script setup>
import { computed } from 'vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Button from '@/components/ui/Button.vue';
import { 
  Ticket, 
  Hourglass, 
  Download, 
  Users, 
  ArrowRight,
  FileSpreadsheet,
  Clock,
  Filter
} from 'lucide-vue-next';
import { adminTransportApi } from '../api/adminTransport.api';
import axios from 'axios';

// Get base URL for download links
const baseURL = axios.defaults.baseURL || '';

const activeSubscriptionsUrl = computed(() => {
  return `${baseURL}${adminTransportApi.getActiveSubscriptionsReportUrl()}`;
});

const waitlistUrl = computed(() => {
  return `${baseURL}${adminTransportApi.getWaitlistReportUrl()}`;
});
</script>

<style scoped>
.reports-container {
  max-width: 1000px;
  margin: 0 auto;
}

.reports-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: var(--spacing-lg);
}

.report-card {
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  position: relative;
}

.report-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

/* Color Accents */
.card-header-accent {
  height: 4px;
  width: 100%;
}
.card-header-accent.primary { background-color: var(--color-active); /* Using active color for active subs */ }
.card-header-accent.warning { background-color: var(--color-warning); }
.card-header-accent.info { background-color: var(--color-info); }

.card-body {
  padding: var(--spacing-xl);
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.icon-wrapper {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: var(--spacing-lg);
}

.icon-wrapper.primary {
  background: #ecfdf5; /* Emerald 50 */
  color: #10b981; /* Emerald 500 */
}
.icon-wrapper.warning {
  background: #fffbeb; 
  color: #f59e0b;
}
.icon-wrapper.info {
  background: #eff6ff;
  color: #3b82f6;
}

.icon {
  width: 24px;
  height: 24px;
}

.content h3 {
  font-size: 18px;
  font-weight: 600;
  color: var(--color-textMain);
  margin-bottom: var(--spacing-sm);
}

.description {
  font-size: 14px;
  color: var(--color-textMuted);
  line-height: 1.5;
  margin-bottom: var(--spacing-lg);
}

.meta-info {
  display: flex;
  gap: var(--spacing-md);
  margin-top: auto;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: var(--color-textMuted);
  background: var(--color-background);
  padding: 2px 8px;
  border-radius: var(--radius-full);
  border: 1px solid var(--color-borderLight);
}

.meta-icon {
  width: 12px;
  height: 12px;
}

.card-footer {
  padding: var(--spacing-md) var(--spacing-xl) var(--spacing-xl);
  background: white;
}

.download-link {
  text-decoration: none;
  display: block;
}

.btn-icon {
  width: 18px;
  height: 18px;
  margin-right: 8px;
}
</style>
