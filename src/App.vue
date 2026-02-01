<template>
  <div id="app">
    <router-view />
    <ToastHost />
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import ToastHost from '@/components/ui/ToastHost.vue';

const router = useRouter();
const authStore = useAuthStore();

onMounted(async () => {
  // Check for auth_token in URL (SSO Handoff)
  const urlParams = new URLSearchParams(window.location.search);
  const authToken = urlParams.get('auth_token');

  if (authToken) {
    // Save token to localStorage
    localStorage.setItem('token', authToken);

    // Clean URL
    const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
    window.history.replaceState({ path: newUrl }, '', newUrl);

    // Hydrate auth store
    await authStore.fetchMe();

    // Redirect to home/dashboard if stuck on login
    if (router.currentRoute.value.path === '/login') {
      router.push('/');
    }
  }
});
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen',
    'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue',
    sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

#app {
  width: 100%;
  min-height: 100vh;
}
</style>
