<template>
  <div class="ui-page-header">
    <nav v-if="breadcrumbs.length" class="breadcrumb">
      <template v-for="(crumb, index) in breadcrumbs" :key="index">
        <router-link
          v-if="crumb.to"
          :to="crumb.to"
          class="breadcrumb-item"
        >
          {{ crumb.label }}
        </router-link>
        <span v-else class="breadcrumb-item" :class="{ active: index === breadcrumbs.length - 1 }">
          {{ crumb.label }}
        </span>
        <span v-if="index < breadcrumbs.length - 1" class="breadcrumb-separator">›</span>
      </template>
    </nav>
    <h1 class="page-title">{{ title }}</h1>
    <p v-if="subtitle" class="page-subtitle">{{ subtitle }}</p>
  </div>
</template>

<script setup>
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
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-md);
  border-bottom: 1px solid var(--color-borderLight);
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  font-size: var(--font-sm);
  margin-bottom: var(--spacing-md);
}

.breadcrumb-item {
  color: var(--color-textMuted);
  text-decoration: none;
  transition: color var(--transition-fast);
}

.breadcrumb-item:hover {
  color: var(--color-primary);
}

.breadcrumb-item.active {
  color: var(--color-textStrong);
  font-weight: var(--fw-medium);
}

.breadcrumb-separator {
  color: var(--color-textMuted); /* Was --color-border, increased contrast */
  font-weight: bold;
  opacity: 0.7;
}

.page-title {
  font-size: var(--font-2xl);
  font-weight: var(--fw-bold);
  color: var(--color-textStrong);
  margin: 0;
  letter-spacing: -0.5px; /* Tighter tracking for headers */
}

.page-subtitle {
  font-size: var(--font-base);
  color: var(--color-textMuted);
  margin: var(--spacing-xs) 0 0 0;
}
</style>
