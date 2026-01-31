<template>
  <div class="stops-selector">
    <div class="selector-container">
      <!-- Assigned Stops (Left Side for LTR/Logical Order) -->
      <div class="panel assigned-panel">
        <div class="panel-header">
          <h4>
            Assigned Stops 
            <span class="count">({{ modelValue.length }})</span>
          </h4>
          <p class="subtitle">Ordered sequence of stops</p>
        </div>
        
        <div v-if="modelValue.length === 0" class="empty-state">
          <div class="empty-icon">📍</div>
          <p>No stops added yet</p>
          <small>Select stops from the list to build the route</small>
        </div>

        <transition-group name="list" tag="ul" class="stops-list" v-else>
          <li v-for="(stop, index) in modelValue" :key="stop.id" class="stop-item assigned">
            <div class="stop-info">
              <span class="stop-order">{{ index + 1 }}</span>
              <span class="stop-name">{{ stop.name_en }}</span>
            </div>
            <div class="stop-actions">
              <button 
                @click="moveStop(index, -1)" 
                :disabled="index === 0"
                class="icon-btn"
                title="Move Up"
              >
                <ArrowUp class="icon" />
              </button>
              <button 
                @click="moveStop(index, 1)" 
                :disabled="index === modelValue.length - 1"
                class="icon-btn"
                title="Move Down"
              >
                <ArrowDown class="icon" />
              </button>
              <button 
                @click="removeStop(index)" 
                class="icon-btn remove-btn"
                title="Remove"
              >
                <Trash2 class="icon" />
              </button>
            </div>
          </li>
        </transition-group>
      </div>

      <!-- Arrow Indicator (Visual separation) -->
      <div class="transfer-indicator">
        <ArrowLeft class="icon" />
      </div>

      <!-- Available Stops (Right Side) -->
      <div class="panel available-panel">
        <div class="panel-header">
          <h4>Available Stops</h4>
          <div class="search-box">
             <Search class="search-icon" />
             <input 
               type="text" 
               v-model="searchQuery" 
               placeholder="Search stops..." 
             />
          </div>
        </div>

        <ul class="stops-list">
           <li v-for="stop in filteredAvailable" :key="stop.id" class="stop-item available">
              <span class="stop-name">{{ stop.name_en }}</span>
              <button class="add-btn" @click="addStop(stop)" title="Add to Route">
                  <Plus class="icon" />
              </button>
           </li>
           <li v-if="filteredAvailable.length === 0" class="no-results">
              No matching stops found.
           </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { ArrowUp, ArrowDown, Trash2, Plus, Search, ArrowLeft } from 'lucide-vue-next';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  allStops: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:modelValue']);

const searchQuery = ref('');

// Computed
const availableStops = computed(() => {
  const assignedIds = new Set(props.modelValue.map(s => s.id));
  return props.allStops.filter(s => !assignedIds.has(s.id));
});

const filteredAvailable = computed(() => {
  const q = searchQuery.value.toLowerCase();
  if (!q) return availableStops.value;
  return availableStops.value.filter(s => 
    s.name_en.toLowerCase().includes(q) || 
    (s.name_ar && s.name_ar.includes(q))
  );
});

// Actions
const addStop = (stop) => {
  const newStops = [...props.modelValue, stop];
  emit('update:modelValue', newStops);
};

const removeStop = (index) => {
  const newStops = [...props.modelValue];
  newStops.splice(index, 1);
  emit('update:modelValue', newStops);
};

const moveStop = (index, direction) => {
  const newIndex = index + direction;
  if (newIndex < 0 || newIndex >= props.modelValue.length) return;
  
  const newStops = [...props.modelValue];
  const temp = newStops[index];
  newStops[index] = newStops[newIndex];
  newStops[newIndex] = temp;
  
  emit('update:modelValue', newStops);
};
</script>

<style scoped>
.selector-container {
  display: flex;
  height: 500px; /* Fixed height for consistency */
  gap: var(--spacing-lg);
  align-items: center;
}

.panel {
  flex: 1;
  height: 100%;
  display: flex;
  flex-direction: column;
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}

.panel-header {
  padding: var(--spacing-md);
  border-bottom: 1px solid var(--color-border);
  background: #f8fafc;
}

.panel-header h4 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--color-textMain);
}

.panel-header .subtitle {
  margin: 4px 0 0 0;
  font-size: 0.75rem;
  color: var(--color-textMuted);
}

.count {
  font-size: 0.8rem;
  color: var(--color-textMuted);
  font-weight: normal;
}

.search-box {
  margin-top: 8px;
  position: relative;
}

.search-box input {
  width: 100%;
  padding: 8px 10px 8px 32px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 0.85rem;
  transition: all 0.2s;
}

.search-box input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px var(--color-primaryLight);
}

.search-icon {
  position: absolute;
  left: 8px;
  top: 50%;
  transform: translateY(-50%);
  width: 14px;
  height: 14px;
  color: var(--color-textMuted);
}

.stops-list {
  flex: 1;
  overflow-y: auto;
  padding: var(--spacing-sm);
  margin: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 6px;
  background: #fff;
}

.stop-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  transition: all 0.2s;
}

.stop-item:hover {
  border-color: var(--color-primaryLight);
  transform: translateX(2px);
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.stop-item.assigned {
  background: var(--color-surfaceHighlight);
}

.stop-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.stop-order {
  width: 24px;
  height: 24px;
  background: var(--color-primary);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 600;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stop-name {
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--color-textMain);
}

.stop-actions {
  display: flex;
  gap: 4px;
  opacity: 0; /* Hidden by default for cleaner look */
  transition: opacity 0.2s;
}

.stop-item:hover .stop-actions {
  opacity: 1;
}

.icon-btn {
  width: 26px;
  height: 26px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  border-radius: 4px;
  cursor: pointer;
  color: var(--color-textMuted);
  transition: all 0.2s;
}

.icon-btn:hover:not(:disabled) {
  background: white;
  color: var(--color-primary);
  box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.icon-btn.remove-btn:hover {
  color: var(--color-danger);
  background: #fee2e2;
}

.icon-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.icon {
  width: 14px;
  height: 14px;
}

.add-btn {
  width: 26px;
  height: 26px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-surfaceHighlight);
  color: var(--color-primary);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.add-btn:hover {
  background: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: var(--color-textMuted);
  text-align: center;
  padding: 20px;
}

.empty-icon {
  font-size: 2rem;
  opacity: 0.5;
  margin-bottom: 8px;
}

.transfer-indicator {
  color: var(--color-textMuted);
  opacity: 0.5;
}

.no-results {
  text-align: center;
  padding: 20px;
  color: var(--color-textMuted);
  font-size: 0.85rem;
}

/* List Transitions */
.list-move,
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(10px);
}

.list-leave-active {
  position: absolute;
}
</style>
