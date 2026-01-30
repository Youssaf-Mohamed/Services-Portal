<template>
  <div class="portal-layout">
    <!-- Top Header -->
    <header class="portal-header">
      <div class="header-left">
        <button class="sidebar-toggle" @click="sidebarOpen = !sidebarOpen">
          <Menu class="hamburger" />
        </button>
        <div class="portal-logo">
          <span class="logo-text">Services Portal</span>
        </div>
      </div>
      <div class="header-right">
        <div class="user-avatar">
          <span>{{ userInitials }}</span>
        </div>
      </div>
    </header>

    <!-- Left Sidebar Navigation -->
    <aside class="portal-sidebar" :class="{ 'sidebar-open': sidebarOpen }">
      <nav class="sidebar-nav">
        <!-- MAIN MENU Section (Student) -->
        <div v-if="isStudent" class="nav-section">
          <div class="nav-section-title">MAIN MENU</div>
          <router-link to="/student/transport" class="nav-item" @click="closeSidebarOnMobile">
            <Home class="nav-icon" />
            <span class="nav-label">Home</span>
          </router-link>
          <router-link to="/student/transport" class="nav-item" @click="closeSidebarOnMobile">
            <Bus class="nav-icon" />
            <span class="nav-label">Transportation</span>
          </router-link>
          <router-link to="/student/transport/my-requests" class="nav-item" @click="closeSidebarOnMobile">
            <ClipboardList class="nav-icon" />
            <span class="nav-label">My Requests</span>
          </router-link>
          <router-link to="/student/transport/my-subscription" class="nav-item" @click="closeSidebarOnMobile">
            <Ticket class="nav-icon" />
            <span class="nav-label">My Subscription</span>
          </router-link>
        </div>

        <!-- SERVICES Section (Student) -->
        <div v-if="isStudent" class="nav-section">
          <div class="nav-section-title">SERVICES</div>
          <router-link to="/student/transport" class="nav-item" @click="closeSidebarOnMobile">
            <Bus class="nav-icon" />
            <span class="nav-label">Transportation System</span>
          </router-link>
        </div>

        <!-- ADMIN MAIN MENU Section -->
        <div v-if="isAdmin" class="nav-section">
          <div class="nav-section-title">ADMIN</div>
          <router-link to="/admin" class="nav-item" @click="closeSidebarOnMobile">
            <LayoutDashboard class="nav-icon" />
            <span class="nav-label">Dashboard</span>
          </router-link>
        </div>

        <!-- TRANSPORT MANAGEMENT Section (Admin) -->
        <div v-if="isAdmin" class="nav-section">
          <div class="nav-section-title">TRANSPORT MANAGEMENT</div>
          <router-link to="/admin/transport" class="nav-item" @click="closeSidebarOnMobile">
            <BarChart3 class="nav-icon" />
            <span class="nav-label">Overview</span>
          </router-link>
          <router-link to="/admin/transport/requests" class="nav-item" @click="closeSidebarOnMobile">
            <FileText class="nav-icon" />
            <span class="nav-label">Requests</span>
          </router-link>
          <router-link to="/admin/transport/routes" class="nav-item" @click="closeSidebarOnMobile">
            <Map class="nav-icon" />
            <span class="nav-label">Routes</span>
          </router-link>
          <router-link to="/admin/transport/slots" class="nav-item" @click="closeSidebarOnMobile">
            <Calendar class="nav-icon" />
            <span class="nav-label">Slots</span>
          </router-link>
          <router-link to="/admin/transport/stops" class="nav-item" @click="closeSidebarOnMobile">
            <MapPin class="nav-icon" />
            <span class="nav-label">Stops</span>
          </router-link>
          <router-link to="/admin/transport/manifest" class="nav-item" @click="closeSidebarOnMobile">
            <Users class="nav-icon" />
            <span class="nav-label">Manifest</span>
          </router-link>
          <router-link to="/admin/transport/settings" class="nav-item" @click="closeSidebarOnMobile">
            <Settings class="nav-icon" />
            <span class="nav-label">Settings</span>
          </router-link>
        </div>

        <!-- Logout -->
        <div class="nav-section">
          <a href="#" @click.prevent="handleLogout" class="nav-item logout-item">
            <LogOut class="nav-icon" />
            <span class="nav-label">Logout</span>
          </a>
        </div>
      </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="portal-main">
      <div class="content-container">
        <slot></slot>
      </div>
    </main>

    <!-- Overlay for mobile sidebar -->
    <div v-if="sidebarOpen" class="sidebar-overlay" @click="sidebarOpen = false"></div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import { 
  Home, 
  Bus, 
  ClipboardList, 
  Ticket, 
  LayoutDashboard, 
  BarChart3, 
  FileText, 
  Map, 
  Calendar, 
  MapPin, 
  Users, 
  Settings, 
  LogOut,
  Menu
} from 'lucide-vue-next';

const authStore = useAuthStore();
const router = useRouter();
const sidebarOpen = ref(false);

const userInitials = computed(() => {
  if (!authStore.user?.name) return 'U';
  const names = authStore.user.name.split(' ');
  if (names.length >= 2) {
    return `${names[0][0]}${names[1][0]}`.toUpperCase();
  }
  return authStore.user.name[0].toUpperCase();
});

const isAdmin = computed(() => authStore.hasRole('admin'));
const isStudent = computed(() => authStore.hasRole('student'));

const closeSidebarOnMobile = () => {
  if (window.innerWidth < 768) {
    sidebarOpen.value = false;
  }
};

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};
</script>

<style scoped>
.portal-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: var(--color-background);
}

/* Header */
.portal-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 64px;
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 var(--spacing-xl);
  z-index: 1000;
  box-shadow: var(--shadow-sm); /* Subtle visual separation */
}

.header-left {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
}

.sidebar-toggle {
  display: none;
  background: none;
  border: none;
  color: var(--color-textMain);
  cursor: pointer;
  padding: var(--spacing-sm);
}

.hamburger {
  display: block;
  width: 24px;
  height: 2px;
  background: currentColor;
  position: relative;
}

.hamburger::before,
.hamburger::after {
  content: '';
  position: absolute;
  width: 24px;
  height: 2px;
  background: currentColor;
  left: 0;
}

.hamburger::before { top: -8px; }
.hamburger::after { top: 8px; }

.portal-logo {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.logo-text {
  color: var(--color-primary);
  font-weight: var(--fw-bold);
  font-size: 20px;
  letter-spacing: -0.5px;
}

.header-right {
  display: flex;
  align-items: center;
}

.user-avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: var(--color-surfaceHighlight);
  color: var(--color-primary);
  border: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: var(--fw-semibold);
  font-size: 14px;
  cursor: pointer;
  transition: all var(--transition-normal);
}

.user-avatar:hover {
  background: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

/* Sidebar */
.portal-sidebar {
  position: fixed;
  top: 64px;
  left: 0;
  width: 260px;
  height: calc(100vh - 64px);
  background: var(--color-surface);
  border-right: 1px solid var(--color-border);
  overflow-y: auto;
  z-index: 900;
  transition: transform var(--transition-normal);
}

.sidebar-nav {
  padding: var(--spacing-xl) 0;
}

.nav-section {
  margin-bottom: var(--spacing-xl);
}

.nav-section-title {
  font-size: 11px;
  font-weight: var(--fw-bold);
  color: var(--color-textMuted);
  text-transform: uppercase;
  letter-spacing: 1px;
  padding: 0 var(--spacing-lg) var(--spacing-xs) var(--spacing-lg);
  margin-bottom: var(--spacing-xs);
}

.nav-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  padding: 10px var(--spacing-lg);
  color: var(--color-textMain);
  text-decoration: none;
  transition: all var(--transition-fast);
  border-left: 3px solid transparent;
  cursor: pointer;
  font-size: 14px;
  font-weight: var(--fw-medium);
}

.nav-item:hover {
  background: var(--color-sidebarHover);
  color: var(--color-primary);
}

.nav-item:focus-visible {
  outline: none;
  box-shadow: inset 0 0 0 2px var(--color-primaryLight);
}

.nav-item.router-link-active {
  background: var(--color-activeBg); 
  border-left-color: var(--color-primary);
  color: var(--color-primary);
  font-weight: var(--fw-bold);
}

.nav-icon {
  font-size: 18px;
  width: 24px;
  text-align: center;
  flex-shrink: 0;
  opacity: 0.8;
}

.router-link-active .nav-icon {
  opacity: 1;
}

.nav-label {
  flex: 1;
}

.logout-item {
  color: var(--color-danger);
  margin-top: var(--spacing-lg);
}

.logout-item:hover {
  background: var(--color-dangerBg);
  color: var(--color-dangerText);
}

/* Main Content */
.portal-main {
  margin-top: 64px;
  margin-left: 260px;
  min-height: calc(100vh - 64px);
  background: var(--color-background);
  padding: var(--spacing-2xl);
  transition: margin-left var(--transition-normal);
}

.content-container {
  max-width: 1200px; /* Reduced max-width for better reading measure */
  margin: 0 auto;
}

/* Sidebar Overlay (Mobile) */
.sidebar-overlay {
  display: none;
  position: fixed;
  top: 64px;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(2px);
  z-index: 850;
}

/* Tablet Responsive */
@media (max-width: 1024px) {
  .portal-sidebar {
    width: 240px;
  }
  
  .portal-main {
    margin-left: 240px;
    padding: var(--spacing-xl);
  }
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .sidebar-toggle {
    display: block;
  }
  
  .portal-header {
    padding: 0 var(--spacing-md);
  }

  .portal-sidebar {
    transform: translateX(-100%);
    box-shadow: none;
    top: 64px; /* Ensure it stays below header */
  }

  .portal-sidebar.sidebar-open {
    transform: translateX(0);
    box-shadow: var(--shadow-lg);
  }

  .sidebar-overlay {
    display: block;
  }

  .portal-main {
    margin-left: 0;
    padding: var(--spacing-md);
  }
}
</style>
