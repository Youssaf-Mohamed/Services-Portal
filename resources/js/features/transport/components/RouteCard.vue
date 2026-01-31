<template>
  <div class="route-card" :class="{ 'is-active-plan': isApprovedPlan, 'is-pending-plan': isPendingPlan }">
    <!-- Route Header -->
    <div class="card-header">
      <div class="header-content">
        <h3 class="route-title">{{ route.name_ar }}</h3>
        <p class="route-subtitle">{{ route.name_en }}</p>
        <div class="capacity-status-container" v-if="!isActivePlan">
             <Badge :variant="capacityStatus.variant" size="sm">
                 <span v-if="showCapacity && capacityStatus.count > 0">
                     {{ capacityStatus.count }} Seats Available
                 </span>
                 <span v-else>
                     {{ capacityStatus.label }}
                 </span>
             </Badge>
        </div>
      </div>
      <div v-if="isApprovedPlan" class="header-seal">
        <VerifiedSeal class="mini-seal" />
      </div>
    </div>

    <!-- Stops Timeline -->
    <div class="stops-timeline">
      <div
        v-for="(stop, index) in route.stops"
        :key="stop.id"
        class="stop-item"
      >
        <div class="stop-indicator">
          <div class="stop-dot" :class="{ 'first': index === 0, 'last': index === route.stops.length - 1 }"></div>
          <div v-if="index < route.stops.length - 1" class="stop-line"></div>
        </div>
        <div class="stop-info">
          <span class="stop-name">{{ stop.name_en }}</span>
        </div>
      </div>
    </div>

    <!-- Pricing Section -->
    <div class="pricing-section">
      <div class="price-item">
        <span class="price-label">Monthly Plan</span>
        <span class="price-value">
          <span class="original-price" v-if="monthlyDiscount">
            EGP {{ monthlyOriginalPrice }}
          </span>
          <span class="final-price">EGP {{ monthlyPrice }}</span>
          <span class="discount-badge" v-if="monthlyDiscount">
            -{{ monthlyDiscount }}%
          </span>
        </span>
      </div>
      <div class="price-item">
        <span class="price-label">Term Plan</span>
        <span class="price-value">
          <span class="original-price" v-if="termDiscount">
            EGP {{ termOriginalPrice }}
          </span>
          <span class="final-price">EGP {{ termPrice }}</span>
          <span class="discount-badge" v-if="termDiscount">
            -{{ termDiscount }}%
          </span>
        </span>
      </div>
    </div>


    <!-- Subscribe Button -->
    <button 
      v-if="!isActivePlan" 
      class="btn-subscribe" 
      @click="$emit('subscribe', route)"
    >
      Subscribe
    </button>
    <div v-else-if="isPendingPlan" class="active-plan-indicator pending">
      <span class="indicator-icon">⏳</span>
      Request Pending
    </div>
    <div v-else-if="isApprovedPlan" class="active-plan-indicator">
      <span class="indicator-icon">✓</span>
      Your Active Plan
    </div>
    <div v-else class="active-plan-indicator pending">
         <!-- Fallback for other statuses like 'waitlisted' -->
      <span class="indicator-icon">ℹ️</span>
      Subscription: {{ activeSubscription?.status }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Badge } from '@/components/ui';
import VerifiedSeal from './VerifiedSeal.vue';

const props = defineProps({
  route: {
    type: Object,
    required: true
  },
  activeSubscription: {
    type: Object,
    default: null
  },
  showCapacity: {
    type: Boolean,
    default: true
  }
});

defineEmits(['subscribe']);

const isActivePlan = computed(() => {
  if (!props.activeSubscription) return false;
  const subRouteId = props.activeSubscription.route_id || props.activeSubscription.route?.id;
  return subRouteId === props.route.id;
});

const isApprovedPlan = computed(() => {
    // Valid backend statuses: 'active', 'waitlisted', 'approved'
    return isActivePlan.value && ['active', 'approved'].includes(props.activeSubscription.status);
});

const isPendingPlan = computed(() => {
    return isActivePlan.value && props.activeSubscription.status === 'pending';
});

// Capacity Logic
const capacityStatus = computed(() => {
    // Aggregate slots data
    if (!props.route.slots) return { label: 'Unknown', variant: 'secondary', count: 0 };

    let totalSeats = 0;
    let availableSeats = 0;
    let hasWaitlist = false;

    // Slots are grouped by day: { "1": [slot, slot], "2": [...] }
    Object.values(props.route.slots).flat().forEach(slot => {
        totalSeats += slot.capacity;
        availableSeats += slot.seats_available;
        if (slot.seats_available <= 0) hasWaitlist = true;
    });

    if (availableSeats === 0) {
        return { label: 'Waitlist Only', variant: 'warning', count: 0 };
    } else if (availableSeats < 10) {
        return { label: 'Selling Fast', variant: 'warning', count: availableSeats };
    } else {
        return { label: 'Available', variant: 'success', count: availableSeats };
    }
});

// Access pricing from nested object (API response shape)
const pricing = computed(() => props.route.pricing || {});
const priceOneWay = computed(() => pricing.value.price_one_way || 0);
const monthlyDiscount = computed(() => pricing.value.monthly_discount_percent || 0);
const termDiscount = computed(() => pricing.value.term_discount_percent || 0);

// Pricing calculations (assuming 5 days/week, 4 weeks/month, 12 weeks/term, round_trip)
const dailyPrice = computed(() => priceOneWay.value * 2); // round_trip
const monthlyOriginalPrice = computed(() => (dailyPrice.value * 5 * 4).toFixed(2));
const termOriginalPrice = computed(() => (dailyPrice.value * 5 * 12).toFixed(2));

const monthlyPrice = computed(() => {
  const original = parseFloat(monthlyOriginalPrice.value);
  return (original * (1 - monthlyDiscount.value / 100)).toFixed(2);
});

const termPrice = computed(() => {
  const original = parseFloat(termOriginalPrice.value);
  return (original * (1 - termDiscount.value / 100)).toFixed(2);
});
</script>

<style scoped>
.route-card {
  background: var(--color-surface);
  border-radius: var(--radius-lg);
  padding: var(--spacing-xl);
  box-shadow: var(--shadow-md);
  transition: box-shadow var(--transition-normal);
  display: flex;
  flex-direction: column;
  border: 1px solid var(--color-border);
  position: relative;
  overflow: hidden;
}

.route-card.is-active-plan {
  border: 2px solid var(--color-success);
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
}

.route-card.is-pending-plan {
  border: 2px solid var(--color-warning);
  box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1);
}

.route-card:hover {
  box-shadow: var(--shadow-lg);
  border-color: var(--color-primaryLight);
}

.route-card.is-active-plan:hover {
    border-color: var(--color-success);
}

.route-card.is-pending-plan:hover {
    border-color: var(--color-warning);
}

.card-header {
  margin-bottom: var(--spacing-xl);
  padding-bottom: var(--spacing-md);
  border-bottom: 2px solid var(--color-surfaceHighlight);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.header-content {
    flex: 1;
}

.header-seal {
    margin-left: var(--spacing-md);
    margin-top: -10px;
    margin-right: -10px;
}

.mini-seal {
    transform: rotate(-10deg) scale(0.6);
}

.route-title {
  font-size: 18px;
  font-weight: var(--fw-bold);
  color: var(--color-primary); /* Use Primary color */
  margin: 0 0 var(--spacing-xs) 0;
}

.route-subtitle {
  font-size: 14px;
  color: var(--color-textMuted);
  margin: 0;
}

.capacity-status-container {
    margin-top: 8px;
}

/* Stops Timeline */
.stops-timeline {
  margin-bottom: var(--spacing-xl);
  border: 1px solid var(--color-borderLight);
  border-radius: var(--radius-md);
  background: var(--color-background);
  padding: var(--spacing-lg) var(--spacing-xl);
}

.stop-item {
  display: flex;
  gap: var(--spacing-md);
  position: relative;
}

.stop-item:not(:last-child) {
  margin-bottom: var(--spacing-md);
}

.stop-indicator {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stop-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: var(--color-primary);
  border: 2px solid var(--color-primary);
  position: relative;
  z-index: 1;
}

.stop-dot.first {
  background: var(--color-surface);
  border-width: 3px;
}

.stop-dot.last {
  background: var(--color-primary);
}

.stop-line {
  width: 2px;
  flex: 1;
  background: var(--color-border); /* Muted timeline line */
  min-height: 20px;
  margin-top: 2px;
}

.stop-info {
  flex: 1;
  padding-top: 0;
}

.stop-name {
  font-size: 13px;
  color: var(--color-textMain);
  line-height: 1.4;
  font-weight: var(--fw-medium);
}

/* Pricing Section */
.pricing-section {
  margin-bottom: var(--spacing-xl);
  padding: var(--spacing-lg);
  background: var(--color-surfaceHighlight);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-borderLight);
}

.price-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-md);
}

.price-item:last-child {
  margin-bottom: 0;
}

.price-label {
  font-size: 14px;
  color: var(--color-textMuted);
  font-weight: 500;
}

.price-value {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.original-price {
  font-size: 12px;
  color: var(--color-textMuted);
  text-decoration: line-through;
}

.final-price {
  font-size: 16px;
  font-weight: var(--fw-bold);
  color: var(--color-textStrong);
}

.discount-badge {
  background: var(--color-successBg);
  color: var(--color-successText);
  font-size: 11px;
  font-weight: 600;
  padding: 2px 6px;
  border-radius: var(--radius-sm);
  border: 1px solid rgba(21, 115, 71, 0.1);
}

/* Subscribe Button */
.btn-subscribe {
  width: 100%;
  background: var(--color-secondary);
  color: white;
  border: none;
  padding: var(--spacing-md) var(--spacing-2xl);
  border-radius: var(--radius-md);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition-normal);
  margin-top: auto;
}

.btn-subscribe:hover {
  background: var(--color-secondaryDark);
  box-shadow: 0 4px 6px rgba(31, 111, 255, 0.2);
}

.btn-subscribe:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(31, 111, 255, 0.25);
}

.btn-subscribe:active {
  transform: translateY(1px);
}

/* Active Plan Indicator */
.active-plan-indicator {
    width: 100%;
    padding: var(--spacing-md);
    background: var(--color-successBg);
    color: var(--color-success);
    border: 1px solid var(--color-success);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-sm);
    font-weight: var(--fw-bold);
    font-size: 14px;
    margin-top: auto;
}

.active-plan-indicator.pending {
    background: var(--color-warningBg);
    color: var(--color-warningText);
    border-color: var(--color-warning);
}

.indicator-icon {
    font-weight: 900;
}





@media (max-width: 768px) {
  .route-card {
    padding: var(--spacing-lg);
  }
  
  .stops-timeline {
    padding: var(--spacing-md);
  }
}
</style>
