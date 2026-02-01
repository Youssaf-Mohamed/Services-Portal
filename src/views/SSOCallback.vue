<template>
    <div class="sso-callback-container">
        <div class="loading-state" v-if="loading">
            <div class="spinner"></div>
            <p>Loading...</p>
        </div>
        <div class="error-state" v-else-if="error">
            <p class="error-text">{{ error }}</p>
            <router-link to="/login" class="back-link">Return to Login</router-link>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import axios from '@/utils/axios';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const loading = ref(true);
const error = ref(null);

onMounted(async () => {
    const token = route.query.token;

    if (!token) {
        error.value = 'No token provided.';
        loading.value = false;
        return;
    }

    try {
        // Call the backend API to verify the token
        // Note: We use the axios instance which handles the base URL proxy
        const response = await axios.get(`/api/sso/verify?token=${token}`);

        if (response.data.success) {
            const authToken = response.data.data.token;

            // Store token
            localStorage.setItem('token', authToken);

            // Update store
            await authStore.fetchMe();

            // Redirect to dashboard
            router.push('/student');
        } else {
            console.log(response);
            error.value = 'Verification failed: ' + (response.data.message || 'Unknown error');
        }
    } catch (err) {
        console.error('SSO Error:', err);
        error.value = 'Failed to verify token. Please try again.';
    } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
.sso-callback-container {
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
}

.loading-state,
.error-state {
    text-align: center;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e2e8f0;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 16px;
}

.error-text {
    color: #ef4444;
    margin-bottom: 16px;
}

.back-link {
    color: #3b82f6;
    text-decoration: underline;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
