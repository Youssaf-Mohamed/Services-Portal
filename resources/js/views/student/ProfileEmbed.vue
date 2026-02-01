<template>
    <PortalLayout>
        <div class="profile-container-view">

            <!-- Header / Cover Area -->
            <div class="profile-header">
                <div class="profile-avatar-wrapper">
                    <img v-if="authStore.user?.avatar_url" :src="authStore.user.avatar_url" class="profile-image-large"
                        alt="Profile" />
                    <div v-else class="profile-initials-large">
                        {{ userInitials }}
                    </div>
                </div>
                <div class="profile-header-info">
                    <h1>{{ authStore.user?.name }}</h1>
                    <p class="email-text">{{ authStore.user?.email }}</p>
                    <div class="badges">
                        <span class="role-badge">{{ authStore.userRole }}</span>
                        <span class="id-badge" v-if="authStore.user?.academic_id">ID: {{ authStore.user.academic_id
                            }}</span>
                    </div>
                </div>
            </div>

            <!-- Info Grid -->
            <div class="profile-details-grid">

                <!-- Academic Info -->
                <div class="detail-card">
                    <h3>Academic Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Program</span>
                        <span class="detail-value">{{ authStore.user?.program_name || 'Not Assigned' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Level / Year</span>
                        <span class="detail-value">{{ authStore.user?.level_name || 'Not Assigned' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Academic ID</span>
                        <span class="detail-value">{{ authStore.user?.academic_id || 'N/A' }}</span>
                    </div>
                </div>

                <!-- Account Info -->
                <div class="detail-card">
                    <h3>Account Status</h3>
                    <div class="detail-row">
                        <span class="detail-label">Role</span>
                        <span class="detail-value capitalize">{{ authStore.userRole }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Joined</span>
                        <span class="detail-value">Via Batechu SSO</span>
                    </div>

                    <div class="actions-row">
                        <a href="https://batechu.com/lms/profile/students" target="_blank" class="external-link-btn">
                            Edit Profile on LMS
                            <ExternalLink class="icon-sm" />
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </PortalLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { ExternalLink } from 'lucide-vue-next';

const authStore = useAuthStore();

const userInitials = computed(() => {
    if (!authStore.user?.name) return 'U';
    return authStore.user.name.charAt(0).toUpperCase();
});
</script>

<style scoped>
.profile-container-view {
    max-width: 800px;
    margin: 0 auto;
    animation: fadeIn 0.3s ease-out;
}

.profile-header {
    background: white;
    padding: 40px;
    border-radius: var(--radius-xl);
    border: 1px solid var(--color-border);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    margin-bottom: var(--spacing-xl);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
}

.profile-avatar-wrapper {
    width: 120px;
    height: 120px;
    margin-bottom: var(--spacing-lg);
    position: relative;
}

.profile-image-large {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid white;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.profile-initials-large {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: var(--color-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: 700;
    border: 4px solid white;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.profile-header-info h1 {
    font-size: 24px;
    font-weight: 800;
    color: var(--color-textMain);
    margin: 0 0 8px 0;
}

.email-text {
    color: var(--color-textMuted);
    font-size: 16px;
    margin-bottom: 16px;
}

.badges {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.role-badge {
    background: var(--color-primaryLight);
    color: var(--color-primary);
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
}

.id-badge {
    background: #f1f5f9;
    color: #64748b;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 600;
}

/* Grid */
.profile-details-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--spacing-lg);
}

@media (min-width: 768px) {
    .profile-details-grid {
        grid-template-columns: 1fr 1fr;
    }
}

.detail-card {
    background: white;
    padding: 24px;
    border-radius: var(--radius-xl);
    border: 1px solid var(--color-border);
}

.detail-card h3 {
    font-size: 16px;
    font-weight: 700;
    color: var(--color-textMain);
    margin-bottom: 20px;
    border-bottom: 1px solid var(--color-borderLight);
    padding-bottom: 12px;
}

.detail-row {
    display: flex;
    flex-direction: column;
    margin-bottom: 16px;
}

.detail-row:last-child {
    margin-bottom: 0;
}

.detail-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--color-textMuted);
    margin-bottom: 4px;
    text-transform: uppercase;
}

.detail-value {
    font-size: 15px;
    color: var(--color-textMain);
    font-weight: 500;
}

.capitalize {
    text-transform: capitalize;
}

.actions-row {
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid var(--color-borderLight);
}

.external-link-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--color-primary);
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
}

.external-link-btn:hover {
    text-decoration: underline;
}

.icon-sm {
    width: 14px;
    height: 14px;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
