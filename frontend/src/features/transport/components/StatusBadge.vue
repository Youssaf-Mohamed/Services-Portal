<template>
  <span class="status-badge" :class="`status-${status}`">
    <span class="status-icon">{{ getStatusIcon(status) }}</span>
    {{ formatStatus(status) }}
  </span>
</template>

<script setup>
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
    pending: '⏳',
    approved: '✓',
    rejected: '✕',
    active: '✓',
    waitlisted: '⏳',
    expired: '⊘',
    cancelled: '✕'
  };
  return iconMap[status] || '';
};
</script>

<style scoped>
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-xs);
  padding: 6px 12px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
}

.status-icon {
  font-size: 10px;
}

.status-pending {
  background: var(--color-neutral);
  color: var(--color-textMuted);
}

.status-approved {
  background: var(--color-successLight);
  color: var(--color-successDark);
}

.status-rejected {
  background: var(--color-dangerLight);
  color: var(--color-dangerDark);
}

.status-active {
  background: var(--color-successLight);
  color: var(--color-successDark);
}

.status-waitlisted {
  background: var(--color-warningLight);
  color: var(--color-warningDark);
}

.status-expired {
  background: var(--color-neutralDark);
  color: var(--color-textMuted);
}

.status-cancelled {
  background: var(--color-dangerLight);
  color: var(--color-dangerDark);
}
</style>
