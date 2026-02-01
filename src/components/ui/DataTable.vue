<template>
  <div class="data-table-container">
    <!-- Loading State -->
    <div v-if="loading" class="dt-loading">
      <div class="skeleton-header">
        <SkeletonLoader height="40px" width="100%" border-radius="var(--radius-md)" />
      </div>
      <div v-for="i in 5" :key="i" class="skeleton-row">
        <SkeletonLoader height="50px" width="100%" border-radius="var(--radius-md)" />
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="dt-error">
      <div class="error-content">
        <p>{{ error }}</p>
        <Button variant="outline" size="sm" @click="$emit('retry')">Retry</Button>
      </div>
    </div>

    <!-- Empty State -->
    <EmptyState
      v-else-if="!data || data.length === 0"
      :icon="emptyIcon || 'clipboard-list'"
      :title="emptyTitle || 'No Data Found'"
      :message="emptyMessage || 'There are no records to display.'"
      :actionText="emptyActionText"
      @action="$emit('empty-action')"
    />

    <!-- Data Table -->
    <div v-else class="dt-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <!-- Selection Checkbox -->
            <th v-if="selectable" class="col-checkbox">
              <input 
                type="checkbox" 
                :checked="isAllSelected" 
                @change="toggleSelectAll" 
                :disabled="selectionDisabled"
              />
            </th>
            
            <th 
              v-for="col in columns" 
              :key="col.key"
              :class="['dt-th', col.align ? `align-${col.align}` : '']"
              :style="{ width: col.width }"
            >
              {{ col.label }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr 
            v-for="row in data" 
            :key="row.id" 
            @click="handleRowClick(row)"
            :class="{ 'clickable': clickableRows }"
          >
            <!-- Selection Checkbox -->
            <td v-if="selectable" class="col-checkbox" @click.stop>
              <input 
                type="checkbox" 
                :checked="selectedIds.includes(row.id)"
                @change="toggleSelection(row.id)"
                :disabled="isRowSelectionDisabled(row)"
              />
            </td>

            <td 
              v-for="col in columns" 
              :key="col.key"
              :class="['dt-td', col.align ? `align-${col.align}` : '']"
            >
              <!-- Slot for custom cell content -->
              <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                {{ formatCell(row[col.key], col.format) }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && !loading && data.length > 0" class="dt-pagination">
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
import Button from './Button.vue';
import EmptyState from './EmptyState.vue';
import SkeletonLoader from './SkeletonLoader.vue';

const props = defineProps({
  columns: {
    type: Array, // [{ key, label, width, align, format }]
    required: true
  },
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
  pagination: {
    type: Object, // { current_page, last_page, total, per_page }
    default: null
  },
  selectable: {
    type: Boolean,
    default: false
  },
  selectedIds: {
    type: Array,
    default: () => []
  },
  selectionDisabled: {
    type: Boolean,
    default: false
  },
  // Function to determine if a specific row's checkbox should be disabled
  disableRowSelection: {
    type: Function,
    default: () => false
  },
  clickableRows: {
    type: Boolean,
    default: false
  },
  // Empty State Props
  emptyIcon: String,
  emptyTitle: String,
  emptyMessage: String,
  emptyActionText: String
});

const emit = defineEmits(['update:selectedIds', 'page-change', 'row-click', 'retry', 'empty-action']);

// Selection Logic
const selectableRows = computed(() => {
    return props.data.filter(row => !props.disableRowSelection(row));
});

const isAllSelected = computed(() => {
  return selectableRows.value.length > 0 && 
         selectableRows.value.every(row => props.selectedIds.includes(row.id));
});

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    emit('update:selectedIds', []);
  } else {
    emit('update:selectedIds', selectableRows.value.map(r => r.id));
  }
};

const toggleSelection = (id) => {
  const newSelection = [...props.selectedIds];
  const index = newSelection.indexOf(id);
  
  if (index === -1) {
    newSelection.push(id);
  } else {
    newSelection.splice(index, 1);
  }
  
  emit('update:selectedIds', newSelection);
};

const isRowSelectionDisabled = (row) => {
    return props.selectionDisabled || props.disableRowSelection(row);
};

// Utils
const handleRowClick = (row) => {
  if (props.clickableRows) {
    emit('row-click', row);
  }
};

const formatCell = (value, format) => {
  if (!value && value !== 0) return '-'; // Handle null/undefined
  
  if (format === 'date') {
    return new Date(value).toLocaleDateString('en-US', { 
        year: 'numeric', month: 'short', day: 'numeric' 
    });
  }
  
  if (format === 'currency') {
      return new Intl.NumberFormat('en-EG', { style: 'currency', currency: 'EGP', maximumFractionDigits: 0 }).format(value);
  }
  
  return value;
};
</script>

<style scoped>
.data-table-container {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.dt-wrapper {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden; /* For rounded corners on table */
  overflow-x: auto; /* Responsive */
  background: white;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 600px;
}

.dt-th {
  background: var(--color-surfaceHighlight);
  padding: var(--spacing-md) var(--spacing-lg);
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  color: var(--color-textMuted);
  text-transform: uppercase;
  border-bottom: 1px solid var(--color-border);
  white-space: nowrap;
}

.dt-td {
  padding: var(--spacing-md) var(--spacing-lg);
  border-bottom: 1px solid var(--color-border);
  color: var(--color-textMain);
  font-size: 14px;
}

/* Alignments */
.align-center { text-align: center; }
.align-right { text-align: right; }

.col-checkbox {
  width: 40px;
  text-align: center;
  padding: var(--spacing-md) var(--spacing-sm);
  border-bottom: 1px solid var(--color-border);
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.clickable {
  cursor: pointer;
  transition: background-color 0.2s;
}

.clickable:hover {
  background-color: var(--color-surfaceHighlight);
}

/* Pagination */
.dt-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-md);
  padding-top: var(--spacing-sm);
}

.page-info {
  font-size: 14px;
  color: var(--color-textMuted);
}

/* Loading */
.dt-loading {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

/* Error */
.dt-error {
  padding: var(--spacing-2xl);
  display: flex;
  justify-content: center;
  background: var(--color-surface);
  border-radius: var(--radius-lg);
  border: 1px dashed var(--color-danger);
}

.error-content {
  text-align: center;
  color: var(--color-danger);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--spacing-md);
}
</style>
