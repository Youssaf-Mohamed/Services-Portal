<template>
  <div class="ui-page-header">
    <nav class="breadcrumb">
      <router-link to="/student" class="breadcrumb-item home-icon">
        <Home class="w-3 h-3" />
      </router-link>
      <span class="breadcrumb-separator">/</span>
      
      <template v-for="(crumb, index) in breadcrumbs" :key="index">
        <router-link
          v-if="crumb.to"
          :to="crumb.to"
          class="breadcrumb-item"
        >
          {{ crumb.label }}
        </router-link>
        <span v-else class="breadcrumb-item active">
          {{ crumb.label }}
        </span>
        <span v-if="index < breadcrumbs.length - 1" class="breadcrumb-separator">/</span>
      </template>
    </nav>
    
    <div class="header-content">
      <h1 class="page-title">{{ title }}</h1>
      <p v-if="subtitle" class="page-subtitle">{{ subtitle }}</p>
    </div>
  </div>
</template>

<script setup>
import { Home } from 'lucide-vue-next';

defineProps({
  title: {
    type: String,
    required: true
  },
  subtitle: {
    type: String,
    default: ''
  },
  breadcrumbs: {
    type: Array,
    default: () => []
  }
});
</script>

<style scoped>
.ui-page-header {
  margin-bottom: var(--spacing-xl);
  /* Removed border-bottom for cleaner look like reference */
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--color-textMuted);
  margin-bottom: var(--spacing-sm);
}

.breadcrumb-item {
  color: var(--color-textMuted);
  text-decoration: none;
  transition: color 0.2s;
  display: flex;
  align-items: center;
}

.breadcrumb-item:hover {
  color: var(--color-primary);
}

.breadcrumb-item.active {
  color: var(--color-textMain);
  cursor: default;
}

.home-icon {
  display: flex;
  align-items: center;
  color: var(--color-textMuted);
}

.breadcrumb-separator {
  color: var(--color-border);
  font-size: 12px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: var(--color-textMain);
  margin: 0;
  line-height: 1.3;
}

.page-subtitle {
  font-size: 14px;
  color: var(--color-textMuted);
  margin: 4px 0 0 0;
}
</style>
