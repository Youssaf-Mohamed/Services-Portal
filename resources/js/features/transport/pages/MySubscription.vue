<template>
  <PortalLayout>
    <PageHeader
      title="My Subscription"
      subtitle="Manage your active transport subscription and view details"
      :breadcrumbs="[
        { label: 'Home', to: '/student/transport' },
        { label: 'Transportation', to: '/student/transport' },
        { label: 'My Subscription' }
      ]"
    />

    <!-- Loading State -->
    <div v-if="loading" class="loading-card">
        <SkeletonLoader height="220px" width="100%" border-radius="var(--radius-lg)" />
    </div>

    <!-- Empty State: No Subscription -->
    <div v-else-if="!subscription" class="empty-state-wrapper">
      <Card class="empty-subscription">
        <EmptyState
          icon="🎫"
          title="No Active Subscription"
          message="You don't have an active transportation subscription yet."
          actionText="Browse Routes"
          actionTo="/student/transport"
        />
      </Card>
    </div>

    <!-- Active Subscription -->
    <div v-else class="subscription-container">
      <Card padding="none" class="subscription-card">
        <div class="card-content-wrapper">
           
           <!-- Left Side: Route & Status -->
           <div class="card-main">
              <div class="header-row">
                 <div class="university-brand">
                    <span class="brand-text">University Transport</span>
                 </div>
                 <Badge :variant="getStatusVariant(subscription.status)" class="status-badge">
                   {{ subscription.status }}
                 </Badge>
              </div>

              <div class="route-info">
                 <span class="label">Assigned Route</span>
                 <h2 class="route-name">{{ routeName }}</h2>
                 <div class="slot-info">
                   <Clock class="w-4 h-4 text-muted" />
                   <div v-if="subscription.selected_days && subscription.selected_days.length > 0" class="days-badges">
                     <span v-for="day in subscription.selected_days" :key="day" class="day-badge">
                       {{ capitalize(day).substring(0, 3) }}
                     </span>
                   </div>
                   <span v-else>{{ selectedDaysDisplay }}</span>
                 </div>
              </div>

              <div class="meta-grid">
                <div class="meta-item">
                   <span class="meta-label">Plan</span>
                   <span class="meta-value">{{ planName }}</span>
                </div>
                <div class="meta-item">
                   <span class="meta-label">Valid Until</span>
                   <span class="meta-value">{{ formatDate(subscription.ends_at || subscription.end_date) }}</span>
                </div>
                 <div class="meta-item">
                   <span class="meta-label">Subscription ID</span>
                   <span class="meta-value font-mono">#{{ subscription.id }}</span>
                </div>
              </div>
           </div>

           <!-- Right Side: Verification Seal (Visual Anchor) -->
             <div class="card-visual">
              <VerifiedSeal v-if="subscription.status === 'active'" />
           </div>

        </div>

        <!-- Renewal Footer -->
        <div v-if="daysRemaining <= 7 && daysRemaining > 0" class="renewal-footer">
            <div class="warning-text">
              <AlertTriangle class="w-4 h-4" />
              Your subscription expires in {{ daysRemaining }} days
            </div>
            <Button size="sm" variant="outline-warning" @click="handleRenew">Renew Now</Button>
        </div>
      </Card>
    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { PageHeader, EmptyState, Card, Badge, Button, SkeletonLoader } from '@/components/ui';
import { transportApi } from '../api/transport.api';
import VerifiedSeal from '../components/VerifiedSeal.vue';
import { Clock, AlertTriangle } from 'lucide-vue-next';

const router = useRouter();
const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const loading = ref(false);
const subscription = ref(null);

const handleRenew = () => {
  router.push('/student/transport');
};

const fetchSubscription = async () => {
  loading.value = true;
  
  try {
    const response = await transportApi.getMySubscription();
    subscription.value = response.data;
  } catch (error) {
    console.error('Failed to fetch subscription:', error);
  } finally {
    loading.value = false;
  }
};

// Computed properties for nested API response
const routeName = computed(() => {
  return subscription.value?.route?.name_en || subscription.value?.route_name || 'Unknown Route';
});

const planName = computed(() => {
  return subscription.value?.plan?.name_en || 
         (subscription.value?.plan_type === 'monthly' ? 'Monthly Plan' : 'Term Plan');
});

const selectedDaysDisplay = computed(() => {
  if (subscription.value?.selected_days && subscription.value.selected_days.length > 0) {
    return subscription.value.selected_days
      .map(day => capitalize(day))
      .join(', ');
  }
  // Fallback for legacy
  if (subscription.value?.slot) {
    const dayName = DAY_NAMES[subscription.value.slot.day_of_week] || 'Day';
    return `${dayName}, ${subscription.value.slot.time}`;
  }
  return 'No days selected';
});

const capitalize = (s) => s.charAt(0).toUpperCase() + s.slice(1);

const daysRemaining = computed(() => {
  // Use API-computed days_remaining if available
  if (subscription.value?.days_remaining !== undefined) {
    return subscription.value.days_remaining;
  }
  // Fallback to local calculation
  if (!subscription.value?.end_date && !subscription.value?.ends_at) return 0;
  
  const end = new Date(subscription.value.end_date || subscription.value.ends_at);
  const today = new Date();
  const diff = Math.ceil((end - today) / (1000 * 60 * 60 * 24));
  return Math.max(0, diff);
});

const amountPaid = computed(() => {
  return subscription.value?.amount_paid_expected || 
         subscription.value?.pricing?.final_amount || 
         0;
});

const formatPlanType = (type) => {
  return type === 'monthly' ? 'Monthly Plan' : 'Term Plan';
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};

const getStatusVariant = (status) => {
  const map = {
    active: 'success',
    waitlisted: 'warning',
    expired: 'neutral',
    cancelled: 'danger'
  };
  return map[status] || 'neutral';
};

onMounted(() => {
  fetchSubscription();
});
</script>

<style scoped>
.loading-card {
  max-width: 100%;
}

.empty-state-wrapper {
  max-width: 600px;
  margin: 0 auto;
}

.subscription-container {
  width: 100%;
}

.subscription-card {
  overflow: hidden;
  border: 1px solid var(--color-border);
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.card-content-wrapper {
  display: flex;
  position: relative;
  background: white;
}

.card-main {
  flex: 1;
  padding: var(--spacing-2xl);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xl);
}

.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.university-brand {
    display: flex;
    align-items: center;
    gap: 8px;
}

.brand-text {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--color-textMuted);
}

.route-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.label {
    font-size: 11px;
    text-transform: uppercase;
    color: var(--color-textMuted);
    font-weight: 600;
    letter-spacing: 0.5px;
}

.route-name {
    font-size: 24px;
    font-weight: 800;
    color: var(--color-textStrong);
    margin: 0;
    line-height: 1.2;
}

.slot-info {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: var(--color-textMain);
    margin-top: 4px;
}

.days-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.day-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 6px;
    background: var(--color-surfaceHighlight);
    border: 1px solid var(--color-border);
    color: var(--color-textMain);
    border-radius: var(--radius-sm);
    text-transform: uppercase;
}

.meta-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--spacing-xl);
    padding-top: var(--spacing-lg);
    border-top: 1px solid var(--color-borderLight);
    margin-top: var(--spacing-md);
}

.meta-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.meta-label {
    font-size: 11px;
    color: var(--color-textMuted);
    text-transform: uppercase;
    font-weight: 500;
}

.meta-value {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-textStrong);
}

.card-visual {
    width: 150px;
    background: linear-gradient(135deg, var(--color-surfaceHighlight) 0%, #f8fafc 100%);
    border-left: 1px solid var(--color-borderLight);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

/* Verified Seal - Handled by Component */

.renewal-footer {
  background: #FFF7ED;
  padding: 12px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid #FFEDD5;
}

.warning-text {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #C2410C;
  font-weight: 600;
  font-size: 13px;
}

.text-muted {
    color: var(--color-textMuted);
}

.font-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

@media (max-width: 640px) {
    .card-content-wrapper {
        flex-direction: column;
    }
    
    .card-visual {
        width: 100%;
        height: 100px;
        border-left: none;
        border-top: 1px solid var(--color-borderLight);
    }
    
    .meta-grid {
        grid-template-columns: 1fr;
        gap: var(--spacing-md);
    }
}
</style>
