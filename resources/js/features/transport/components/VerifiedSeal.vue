<template>
  <div class="verified-stamp">
    <div class="stamp-inner">
      <span>VERIFIED</span>
    </div>
  </div>
</template>

<script setup>
// No props needed for this specific bold watermark style
</script>

<style scoped>
.verified-stamp {
  position: absolute;
  top: 20px;
  right: 120px; /* Moved left to avoid overlapping status badge provided by user */
  z-index: 10;
  pointer-events: none;
  opacity: 0.3; /* Watermark opacity */
  transform: rotate(-12deg); /* Natural stamp tilt */
  user-select: none;
  mix-blend-mode: multiply; /* Blends nicely like ink */
}

.stamp-inner {
  border: 4px solid var(--color-success);
  color: var(--color-success);
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 900;
  font-size: 28px;
  letter-spacing: 4px;
  text-transform: uppercase;
  font-family: 'Courier New', Courier, monospace; /* Monospace for stamp look */
  background: transparent;
  
  /* Distressed ink effect attempt with double shadow */
  box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.4), 
              0 0 0 1px rgba(var(--color-success-rgb), 0.1); 
}
</style>
