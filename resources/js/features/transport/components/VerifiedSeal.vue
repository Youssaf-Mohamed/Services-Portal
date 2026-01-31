<template>
  <div class="verified-seal">
    <ShieldCheck class="seal-icon" />
    <span>VERIFIED</span>
  </div>
</template>

<script setup>
import { ShieldCheck } from 'lucide-vue-next';
</script>

<style scoped>
.verified-seal {
  display: flex;
  flex-direction: column;
  align-items: center;
  color: #059669; /* Success Text/Icon Color */
  border: 2px solid #059669;
  border-radius: 50%;
  width: 80px;
  height: 80px;
  justify-content: center;
  transform: rotate(-10deg);
  background: white; /* Make seal standout against background */
  box-shadow: 0 4px 10px rgba(5, 150, 105, 0.15);
  user-select: none;
}

.seal-icon {
  width: 28px;
  height: 28px;
}

.verified-seal span {
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 1px;
  margin-top: 2px;
}
</style>
