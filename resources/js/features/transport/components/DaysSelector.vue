<template>
  <div class="days-selector">
    <div class="selector-header">
      <h4>{{ label }}</h4>
      <p class="help-text">يرجى تحديد {{ allowedDays }} أيام بالضبط</p>
    </div>
    
    <div class="days-grid">
      <button
        v-for="day in availableDays"
        :key="day.value"
        type="button"
        :class="['day-button', { 
          selected: selectedDays.includes(day.value),
          disabled: !canSelectMore && !selectedDays.includes(day.value)
        }]"
        :disabled="!canSelectMore && !selectedDays.includes(day.value)"
        @click="toggleDay(day.value)"
      >
        <span class="day-abbr">{{ day.abbr }}</span>
        <span class="day-name">{{ day.name }}</span>
      </button>
    </div>
    
    <div class="selection-count">
      <span>{{ selectedDays.length }} / {{ allowedDays }} days selected</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  allowedDays: {
    type: Number,
    required: true
  },
  label: {
    type: String,
    default: 'Select Your Days'
  }
});

const emit = defineEmits(['update:modelValue']);

const DAYS_OF_WEEK = [
  { value: 'saturday', name: 'Saturday', abbr: 'Sat' },
  { value: 'sunday', name: 'Sunday', abbr: 'Sun' },
  { value: 'monday', name: 'Monday', abbr: 'Mon' },
  { value: 'tuesday', name: 'Tuesday', abbr: 'Tue' },
  { value: 'wednesday', name: 'Wednesday', abbr: 'Wed' },
  { value: 'thursday', name: 'Thursday', abbr: 'Thu' },
];

const selectedDays = ref([...props.modelValue]);

const availableDays = computed(() => DAYS_OF_WEEK);

const canSelectMore = computed(() => {
  return selectedDays.value.length < props.allowedDays;
});

const toggleDay = (day) => {
  const index = selectedDays.value.indexOf(day);
  if (index > -1) {
    // Deselect
    selectedDays.value.splice(index, 1);
  } else if (canSelectMore.value) {
    // Select
    selectedDays.value.push(day);
  }
  emit('update:modelValue', selectedDays.value);
};

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  selectedDays.value = [...newValue];
}, { deep: true });
</script>

<style scoped>
.days-selector {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.selector-header h4 {
  margin: 0;
  font-size: var(--font-base);
  font-weight: var(--fw-semibold);
  color: var(--color-textStrong);
}

.help-text {
  font-size: 11px;
  color: var(--color-textMuted);
  margin: var(--spacing-xs) 0 0 0;
}

.days-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: var(--spacing-sm);
}

@media (max-width: 768px) {
  .days-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

.day-button {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: var(--spacing-md);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface);
  cursor: pointer;
  transition: all var(--transition-fast);
  font-family: inherit;
}

.day-button:hover:not(:disabled) {
  background: var(--color-surfaceHighlight);
  border-color: var(--color-primary);
  transform: translateY(-2px);
}

.day-button.selected {
  background: var(--color-primaryBg);
  border-color: var(--color-primary);
  color: var(--color-primary);
  font-weight: var(--fw-semibold);
}

.day-button.disabled {
  opacity: 0.4;
  cursor: not-allowed;
  background: var(--color-background);
}

.day-abbr {
  font-size: var(--font-lg);
  font-weight: var(--fw-bold);
  color: inherit;
}

.day-name {
  font-size: var(--font-xs);
  color: currentColor;
  opacity: 0.8;
  margin-top: var(--spacing-xs);
}

.selection-count {
  text-align: center;
  font-size: var(--font-sm);
  color: var(--color-textMuted);
  padding: var(--spacing-sm);
  background: var(--color-surfaceHighlight);
  border-radius: var(--radius-md);
}
</style>
