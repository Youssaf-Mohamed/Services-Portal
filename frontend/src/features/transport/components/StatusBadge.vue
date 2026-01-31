<template>
  <span class="status-badge" :class="`status-${status}`">
    <component :is="getStatusIcon(status)" class="status-icon" />
    {{ formatStatus(status) }}
  </span>
</template>

<script setup>
import { Clock, CheckCircle, XCircle, AlertTriangle, Ban } from 'lucide-vue-next';

defineProps({
  status: {
    type: String,
    required: true,
    validator: (value) => ['pending', 'approved', 'rejected', 'active', 'waitlisted', 'expired', 'cancelled'].includes(value)
  }
});

const formatStatus = (status) => {
  const statusMap = {
    pending: 'Pending',
    approved: 'Approved',
    rejected: 'Rejected',
    active: 'Active',
    waitlisted: 'Waitlisted',
    expired: 'Expired',
    cancelled: 'Cancelled'
  };
  return statusMap[status] || status;
};

const getStatusIcon = (status) => {
  const iconMap = {
    pending: Clock,
    approved: CheckCircle,
    rejected: XCircle,
    active: CheckCircle,
    waitlisted: AlertTriangle,
    expired: Ban,
    cancelled: XCircle
  };
  return iconMap[status] || Clock;
};
</script>

<style scoped>
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
}

.status-icon {
  width: 14px;
  height: 14px;
}

/* Pending - Blue/Info style */
.status-pending {
  background: #eff6ff; /* Blue 50 */
  color: #1d4ed8; /* Blue 700 */
}

/* Approved - Green */
.status-approved {
  background: #ecfdf5; /* Emerald 50 */
  color: #059669; /* Emerald 600 */
}

/* Rejected - Red */
.status-rejected {
  background: #fef2f2; /* Red 50 */
  color: #dc2626; /* Red 600 */
}

/* Active - Green */
.status-active {
  background: #ecfdf5;
  color: #059669;
}

/* Waitlisted - Amber/Orange */
.status-waitlisted {
  background: #fffbeb; /* Amber 50 */
  color: #d97706; /* Amber 600 */
}

/* Expired - Gray */
.status-expired {
  background: #f3f4f6; /* Gray 100 */
  color: #6b7280; /* Gray 500 */
}

/* Cancelled - Red */
.status-cancelled {
  background: #fef2f2;
  color: #dc2626;
}
</style>
