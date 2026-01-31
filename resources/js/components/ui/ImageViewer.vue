<template>
  <div class="image-viewer-overlay" @click.self="close" @keyup.esc="close" tabindex="0">
    <div class="toolbar">
      <div class="zoom-controls">
        <button class="tool-btn" @click="zoomOut" title="Zoom Out">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
        </button>
        <span class="zoom-level">{{ Math.round(scale * 100) }}%</span>
        <button class="tool-btn" @click="zoomIn" title="Zoom In">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
        </button>
        <button class="tool-btn" @click="resetZoom" title="Reset Zoom">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
        </button>
      </div>

      <div class="right-controls">
        <a :href="src" download target="_blank" class="tool-btn" title="Download">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        </a>
        <button class="tool-btn close-btn" @click="close" title="Close">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>
    </div>

    <div class="image-container" @click.self="close">
      <img 
        :src="src" 
        :alt="alt" 
        class="viewer-image"
        :style="{ transform: `scale(${scale})` }"
        draggable="false"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  src: {
    type: String,
    required: true
  },
  alt: {
    type: String,
    default: 'Image'
  }
});

const emit = defineEmits(['close']);

const scale = ref(1);

const zoomIn = () => {
  scale.value = Math.min(scale.value + 0.25, 3);
};

const zoomOut = () => {
  scale.value = Math.max(scale.value - 0.25, 0.25);
};

const resetZoom = () => {
  scale.value = 1;
};

const close = () => {
  emit('close');
};

const handleKeydown = (e) => {
  if (e.key === 'Escape') close();
};

onMounted(() => {
  document.body.style.overflow = 'hidden';
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  document.body.style.overflow = '';
  window.removeEventListener('keydown', handleKeydown);
});
</script>

<style scoped>
.image-viewer-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.9);
  z-index: 9999;
  display: flex;
  flex-direction: column;
  animation: fadeIn 0.2s ease;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 10;
}

.zoom-controls, .right-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.zoom-level {
  color: white;
  min-width: 3rem;
  text-align: center;
  font-feature-settings: "tnum";
}

.tool-btn {
  background: transparent;
  border: none;
  color: rgba(255, 255, 255, 0.8);
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.tool-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  color: white;
}

.close-btn {
  margin-left: 1rem;
  color: white;
}

.close-btn:hover {
  background: rgba(239, 68, 68, 0.5);
}

.image-container {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  cursor: zoom-out;
}

.viewer-image {
  max-width: 90%;
  max-height: 90%;
  object-fit: contain;
  transition: transform 0.2s ease;
  cursor: grab;
}

.viewer-image:active {
  cursor: grabbing;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
</style>
