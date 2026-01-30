<template>
  <PortalLayout>
    <PageHeader 
      title="Reports & Exports" 
      subtitle="Download CSV reports of transport data"
    />

    <div class="reports-grid">
      <!-- Active Subscriptions Report -->
      <div class="report-card">
        <div class="report-icon">
          <Ticket class="icon" />
        </div>
        <div class="report-content">
          <h3>Active Subscriptions</h3>
          <p>Complete list of students with active transport subscriptions, including route, slot, and plan details.</p>
          <a :href="activeSubscriptionsUrl" download class="download-link">
            <Button variant="primary">
              <Download class="btn-icon" /> Download CSV
            </Button>
          </a>
        </div>
      </div>

      <!-- Waitlist Report -->
      <div class="report-card">
        <div class="report-icon">
          <Clock class="icon" />
        </div>
        <div class="report-content">
          <h3>Waitlist Report</h3>
          <p>List of students currently on the waitlist, ordered by request date.</p>
          <a :href="waitlistUrl" download class="download-link">
            <Button variant="outline">
              <Download class="btn-icon" /> Download CSV
            </Button>
          </a>
        </div>
      </div>

      <!-- Manifest Export (Redirect) -->
      <div class="report-card">
        <div class="report-icon">
          <Users class="icon" />
        </div>
        <div class="report-content">
          <h3>Passenger Manifest Data</h3>
          <p>Full passenger manifest data, filterable by route, day, and status. Go to Manifest page to export with filters.</p>
          <router-link to="/admin/transport/manifest">
            <Button variant="outline">
              <ArrowRight class="btn-icon" /> Go to Manifest
            </Button>
          </router-link>
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
import { Ticket, Clock, Download, Users, ArrowRight } from 'lucide-vue-next';
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
.reports-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: var(--spacing-xl);
  margin-top: var(--spacing-lg);
}

.report-card {
  background: white;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  padding: var(--spacing-xl);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  transition: box-shadow 0.2s, transform 0.2s;
}

.report-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.report-icon {
  width: 48px;
  height: 48px;
  background-color: var(--color-bgSurface);
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: var(--spacing-lg);
  color: var(--color-primary);
}

.icon {
  width: 24px;
  height: 24px;
}

.report-content h3 {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: var(--spacing-sm);
  color: var(--color-textMain);
}

.report-content p {
  color: var(--color-textMuted);
  font-size: 14px;
  line-height: 1.5;
  margin-bottom: var(--spacing-xl);
  flex-grow: 1;
}

.download-link {
  text-decoration: none;
  width: 100%;
}

.download-link :deep(button) {
  width: 100%;
  justify-content: center;
}

.btn-icon {
  width: 18px;
  height: 18px;
  margin-right: 8px;
}
</style>
