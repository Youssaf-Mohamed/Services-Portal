<template>
  <div class="data-list-container">
    <!-- Loading State -->
    <div v-if="loading" :class="['loading-state', layoutClass]">
      <slot name="loading">
        <div v-for="i in skeletonCount" :key="i" class="skeleton-item">
          <SkeletonLoader :height="skeletonHeight" width="100%" border-radius="var(--radius-lg)" />
        </div>
      </slot>
    </div>

    <!-- Error State -->
    <EmptyState
      v-else-if="error"
      icon="alert-circle"
      title="Failed to load data"
      :message="error"
      actionText="Retry"
      @action="$emit('retry')"
    />

    <!-- Empty State -->
    <EmptyState
      v-else-if="!data || data.length === 0"
      :icon="emptyIcon || 'clipboard-list'"
      :title="emptyTitle || 'No Data Found'"
      :message="emptyMessage || 'There are no items to display.'"
      :actionText="emptyActionText"
      :actionTo="emptyActionTo"
      @action="$emit('empty-action')"
    >
        <template #icon v-if="$slots['empty-icon']">
            <slot name="empty-icon"></slot>
        </template>
    </EmptyState>

    <!-- Data List -->
    <div v-else :class="['list-content', layoutClass]">
      <slot v-for="(item, index) in data" :key="item.id || index" :item="item" :index="index">
        <!-- Default content if no slot provided (debug) -->
        <div class="debug-item">{{ item }}</div>
      </slot>
    </div>
    
    <!-- Pagination (Optional) -->
    <div v-if="pagination && !loading && data.length > 0" class="list-pagination">
         <Button 
            variant="secondary" 
            size="sm" 
            :disabled="pagination.current_page === 1"
            @click="$emit('page-change', pagination.current_page - 1)"
        >
            Previous
        </Button>
        <span class="page-info">
            Page {{ pagination.current_page }} of {{ pagination.last_page }}
        </span>
        <Button 
            variant="secondary" 
            size="sm" 
            :disabled="pagination.current_page === pagination.last_page"
            @click="$emit('page-change', pagination.current_page + 1)"
        >
            Next
        </Button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import EmptyState from './EmptyState.vue';
import SkeletonLoader from './SkeletonLoader.vue';
import Button from './Button.vue';

const props = defineProps({
  data: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: null
  },
  // Skeleton Config
  skeletonCount: {
    type: Number,
    default: 3
  },
  skeletonHeight: {
    type: String,
    default: '120px'
  },
  // Layout Config
  gridLayout: {
    type: Boolean,
    default: false
  },
  // Empty State Config
  emptyIcon: String,
  emptyTitle: String,
  emptyMessage: String,
  emptyActionText: String,
  emptyActionTo: String,
  // Pagination
  pagination: Object
});

const emit = defineEmits(['retry', 'empty-action', 'page-change']);

const layoutClass = computed(() => props.gridLayout ? 'grid-layout' : 'stack-layout');
</script>

<style scoped>
.data-list-container {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.list-content, .loading-state {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

/* Grid Layout */
.list-content.grid-layout, .loading-state.grid-layout {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: var(--spacing-lg);
}

/* Stack Layout (Default) */
.list-content.stack-layout, .loading-state.stack-layout {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.skeleton-item {
    background: white;
    padding: var(--spacing-lg);
    border-radius: var(--radius-lg);
    border: 1px solid var(--color-border);
}

.list-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: var(--spacing-md);
    padding-top: var(--spacing-md);
}

.page-info {
    font-size: 14px;
    color: var(--color-textMuted);
}
</style>
