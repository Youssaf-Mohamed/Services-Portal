<template>
  <div v-if="hasAttachment" class="attachment-preview-wrapper">
    <label v-if="label" class="attachment-label">{{ label }}</label>
    
    <!-- Loading State -->
    <div v-if="loading" class="state-container loading">
      <div class="spinner-sm"></div>
      <span>Loading preview...</span>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="state-container error">
       <div class="error-content">
         <AlertTriangleIcon class="error-icon" />
         <span class="error-message">Preview unavailable</span>
       </div>
       <div class="error-actions">
         <button class="btn-text" @click="load">Retry</button>
         <span class="divider">•</span>
         <a :href="downloadUrl" target="_blank" class="link-text">Download File</a>
       </div>
    </div>

    <!-- Success State -->
    <div v-else-if="imageUrl" class="preview-container" @click="showViewer = true">
       <img :src="imageUrl" :alt="label" class="preview-image" />
       <div class="hover-overlay">
         <span class="zoom-icon">
             <ZoomInIcon class="icon-lg" />         </span>
         <span class="hint-text">Click to expand</span>
       </div>
    </div>

    <!-- Viewer -->
    <ImageViewer 
      v-if="showViewer" 
      :src="imageUrl" 
      :alt="label"
      @close="showViewer = false" 
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import ImageViewer from '@/components/ui/ImageViewer.vue';
import { AlertTriangle as AlertTriangleIcon, ZoomIn as ZoomInIcon } from 'lucide-vue-next';

const props = defineProps({
  hasAttachment: {
    type: Boolean,
    default: false
  },
  label: {
    type: String,
    default: ''
  },
  downloadUrl: {
    type: String,
    required: true
  },
  fetchFn: {
    type: Function,
    required: true
  }
});

const loading = ref(false);
const error = ref(false);
const imageUrl = ref(null);
const showViewer = ref(false);

const load = async () => {
  if (!props.hasAttachment) return;
  
  loading.value = true;
  error.value = false;
  
  try {
    const blob = await props.fetchFn();
    if (imageUrl.value) URL.revokeObjectURL(imageUrl.value);
    imageUrl.value = URL.createObjectURL(blob);
  } catch (e) {
    console.error('Failed to load attachment:', e);
    error.value = true;
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (props.hasAttachment) {
    load();
  }
});

onUnmounted(() => {
  if (imageUrl.value) {
    URL.revokeObjectURL(imageUrl.value);
  }
});

// Watch for prop changes in case the component is reused or data comes in late
watch(() => props.hasAttachment, (newVal) => {
    if (newVal && !imageUrl.value && !loading.value) {
        load();
    }
});
</script>

<style scoped>
.attachment-preview-wrapper {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.attachment-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  color: var(--color-text-tertiary, #6b7280);
}

.state-container {
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 8px;
  background: var(--color-background, #ffffff);
  height: 160px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: var(--color-text-secondary, #4b5563);
  font-size: 0.875rem;
}

.loading .spinner-sm {
  width: 24px;
  height: 24px;
  border: 2px solid var(--color-border, #e5e7eb);
  border-top-color: var(--color-primary, #3b82f6);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.error-content {
  display: flex;
  align-items: center;
  gap: 6px;
  width: 24px;
  height: 24px;
  width: 24px;
  height: 24px;
  color: var(--color-danger, #ef4444);
}

.icon-lg {
  width: 24px;
  height: 24px;
}

.error-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.8rem;
}

.btn-text {
  background: none;
  border: none;
  color: var(--color-primary, #3b82f6);
  cursor: pointer;
  padding: 0;
  font-weight: 500;
}

.btn-text:hover {
  text-decoration: underline;
}

.link-text {
  color: var(--color-text-secondary, #4b5563);
  text-decoration: none;
}

.link-text:hover {
  text-decoration: underline;
}

.divider {
  color: var(--color-border, #e5e7eb);
}

/* Preview */
.preview-container {
  position: relative;
  height: 160px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 8px;
  overflow: hidden;
  cursor: zoom-in;
  background: #f9fafb;
}

.preview-image {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transition: transform 0.3s ease;
}

.hover-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.2s ease;
  color: white;
  gap: 8px;
}

.preview-container:hover .hover-overlay {
  opacity: 1;
}

.preview-container:hover .preview-image {
  transform: scale(1.05);
}

.hint-text {
  font-size: 0.8rem;
  font-weight: 500;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
