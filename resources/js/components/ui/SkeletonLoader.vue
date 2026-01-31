<template>
  <div class="skeleton-loader" :style="style"></div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  width: {
    type: String,
    default: '100%'
  },
  height: {
    type: String,
    default: '1em'
  },
  borderRadius: {
    type: String,
    default: '4px' // var(--radius-sm)
  }
});

const style = computed(() => ({
  width: props.width,
  height: props.height,
  borderRadius: props.borderRadius
}));
</script>

<style scoped>
.skeleton-loader {
  background: linear-gradient(
    90deg,
    var(--color-surfaceHighlight) 25%,
    var(--color-borderLight) 37%,
    var(--color-surfaceHighlight) 63%
  );
  background-size: 400% 100%;
  animation: skeleton-loading 1.4s ease infinite;
}

@keyframes skeleton-loading {
  0% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0 50%;
  }
}
</style>
