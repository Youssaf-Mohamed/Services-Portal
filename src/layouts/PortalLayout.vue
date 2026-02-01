<template>
  <div class="portal-layout">
    <!-- Top Header -->
    <header class="portal-header">
      <div class="header-left">
        <button class="sidebar-toggle" @click="sidebarOpen = !sidebarOpen">
          <Menu class="hamburger" />
        </button>
        <div class="portal-logo">
          <img src="/logo.png" alt="Logo" class="header-logo" />
          <span class="logo-text">Services Portal</span>
        </div>
      </div>
      <div class="header-right">
        <NotificationBell />
        <router-link to="/student/profile" class="user-avatar-link">
          <div class="user-avatar" v-if="authStore.user?.avatar_url">
            <img :src="authStore.user.avatar_url" alt="Avatar" class="avatar-image" />
          </div>
          <div class="user-avatar" v-else>
            <span>{{ userInitials }}</span>
          </div>
        </router-link>
      </div>
    </header>

    <!-- Left Sidebar Navigation -->
    <aside class="portal-sidebar" :class="{ 'sidebar-open': sidebarOpen }">
      <nav class="sidebar-nav">
        <!-- MAIN MENU Section (Student) -->
        <div v-if="isStudent" class="nav-section">
          <div class="nav-section-title">MAIN MENU</div>
          <router-link to="/student" class="nav-item" @click="closeSidebarOnMobile">
            <Home class="nav-icon" />
            <span class="nav-label">Home</span>
          </router-link>

          <!-- Student Transportation Group -->
          <div class="nav-group">
            <div class="nav-group-header" @click="toggleGroup('studentTransport')">
              <Bus class="nav-icon" />
              <span class="nav-label">Transportation</span>
              <ChevronDown v-if="expandedGroups.studentTransport" class="group-arrow" />
              <ChevronRight v-else class="group-arrow" />
            </div>
            <div v-show="expandedGroups.studentTransport" class="nav-group-children">
              <router-link to="/student/transport" class="nav-item child-item" @click="closeSidebarOnMobile">
                <span class="nav-label">Dashboard</span>
              </router-link>
              <router-link to="/student/transport/my-requests" class="nav-item child-item"
                @click="closeSidebarOnMobile">
                <span class="nav-label">My Requests</span>
              </router-link>
              <router-link to="/student/transport/my-subscription" class="nav-item child-item"
                @click="closeSidebarOnMobile">
                <span class="nav-label">My Subscription</span>
              </router-link>
            </div>
          </div>

          <!-- Student ID Card Services Group -->
          <div class="nav-group">
            <div class="nav-group-header" @click="toggleGroup('studentIdCard')">
              <CreditCard class="nav-icon" />
              <span class="nav-label">ID Card Services</span>
              <ChevronDown v-if="expandedGroups.studentIdCard" class="group-arrow" />
              <ChevronRight v-else class="group-arrow" />
            </div>
            <div v-show="expandedGroups.studentIdCard" class="nav-group-children">
              <router-link to="/student/id-card" class="nav-item child-item" @click="closeSidebarOnMobile">
                <span class="nav-label">Services</span>
              </router-link>
              <router-link to="/student/id-card/my-requests" class="nav-item child-item" @click="closeSidebarOnMobile">
                <span class="nav-label">My Requests</span>
              </router-link>
            </div>
          </div>

          <!-- Unified My Requests -->
          <router-link to="/student/my-requests" class="nav-item" @click="closeSidebarOnMobile">
            <ClipboardList class="nav-icon" />
            <span class="nav-label">All My Requests</span>
          </router-link>
        </div>

        <!-- ADMIN MAIN MENU Section -->
        <div v-if="isAdmin" class="nav-section">
          <div class="nav-section-title">ADMIN</div>
          <router-link to="/admin" class="nav-item" @click="closeSidebarOnMobile">
            <LayoutDashboard class="nav-icon" />
            <span class="nav-label">Dashboard</span>
          </router-link>

          <!-- Admin Transport Management Group -->
          <div class="nav-group">
            <div class="nav-group-header" @click="toggleGroup('adminTransport')">
              <Bus class="nav-icon" />
              <span class="nav-label">TRANSPORT MANAGEMENT</span>
              <ChevronDown v-if="expandedGroups.adminTransport" class="group-arrow" />
              <ChevronRight v-else class="group-arrow" />
            </div>
            <div v-show="expandedGroups.adminTransport" class="nav-group-children">
              <router-link to="/admin/transport" class="nav-item child-item" @click="closeSidebarOnMobile">
                <BarChart3 class="nav-icon small-icon" />
                <span class="nav-label">Overview</span>
              </router-link>
              <router-link to="/admin/transport/requests" class="nav-item child-item" @click="closeSidebarOnMobile">
                <FileText class="nav-icon small-icon" />
                <span class="nav-label">Requests</span>
              </router-link>
              <router-link to="/admin/transport/routes" class="nav-item child-item" @click="closeSidebarOnMobile">
                <Map class="nav-icon small-icon" />
                <span class="nav-label">Routes</span>
              </router-link>
              <router-link to="/admin/transport/slots" class="nav-item child-item" @click="closeSidebarOnMobile">
                <Calendar class="nav-icon small-icon" />
                <span class="nav-label">Slots</span>
              </router-link>
              <router-link to="/admin/transport/stops" class="nav-item child-item" @click="closeSidebarOnMobile">
                <MapPin class="nav-icon small-icon" />
                <span class="nav-label">Stops</span>
              </router-link>
              <router-link to="/admin/transport/manifest" class="nav-item child-item" @click="closeSidebarOnMobile">
                <Users class="nav-icon small-icon" />
                <span class="nav-label">Manifest</span>
              </router-link>
              <router-link to="/admin/transport/reports" class="nav-item child-item" @click="closeSidebarOnMobile">
                <FileSpreadsheet class="nav-icon small-icon" />
                <span class="nav-label">Reports</span>
              </router-link>
              <router-link to="/admin/transport/settings" class="nav-item child-item" @click="closeSidebarOnMobile">
                <Settings class="nav-icon small-icon" />
                <span class="nav-label">Settings</span>
              </router-link>
            </div>
          </div>

          <!-- Admin ID Card Management Group -->
          <div class="nav-group">
            <div class="nav-group-header" @click="toggleGroup('adminIdCard')">
              <CreditCard class="nav-icon" />
              <span class="nav-label">ID CARD MANAGEMENT</span>
              <ChevronDown v-if="expandedGroups.adminIdCard" class="group-arrow" />
              <ChevronRight v-else class="group-arrow" />
            </div>
            <div v-show="expandedGroups.adminIdCard" class="nav-group-children">
              <router-link to="/admin/id-card" class="nav-item child-item" @click="closeSidebarOnMobile">
                <BarChart3 class="nav-icon small-icon" />
                <span class="nav-label">Dashboard</span>
              </router-link>
              <router-link to="/admin/id-card/requests" class="nav-item child-item" @click="closeSidebarOnMobile">
                <FileText class="nav-icon small-icon" />
                <span class="nav-label">Requests</span>
              </router-link>
              <router-link to="/admin/id-card/types" class="nav-item child-item" @click="closeSidebarOnMobile">
                <CreditCard class="nav-icon small-icon" />
                <span class="nav-label">Types</span>
              </router-link>
              <router-link to="/admin/id-card/settings" class="nav-item child-item" @click="closeSidebarOnMobile">
                <Settings class="nav-icon small-icon" />
                <span class="nav-label">Settings</span>
              </router-link>
            </div>
          </div>
        </div>

        <!-- Back to LMS (Replaces Logout) -->
        <div class="nav-section mt-auto">
          <a href="https://batechu.com/lms/dashboard" class="nav-item logout-item">
            <ArrowLeft class="nav-icon" />
            <span class="nav-label">Back to LMS</span>
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
import { ref, computed, reactive } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import NotificationBell from '@/features/notifications/components/NotificationBell.vue';
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
  Menu,
  FileSpreadsheet,
  ChevronDown,
  ChevronRight,
  CreditCard,
  ArrowLeft
} from 'lucide-vue-next';

const authStore = useAuthStore();
const router = useRouter();
const sidebarOpen = ref(false);

// State for collapsible groups
const expandedGroups = reactive({
  studentTransport: true, // Default open for better discovery
  studentIdCard: true,
  adminTransport: true,
  adminIdCard: true
});

const toggleGroup = (group) => {
  expandedGroups[group] = !expandedGroups[group];
};

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
  /* Removed simple border for a more elevated look, or kept it very subtle */
  border-bottom: 1px solid transparent;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 var(--spacing-xl);
  z-index: 1000;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
  /* Professional elevation */
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

.hamburger::before {
  top: -8px;
}

.hamburger::after {
  top: 8px;
}

.portal-logo {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.header-logo {
  height: 32px;
  width: auto;
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
  gap: var(--spacing-lg);
}

.user-avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: var(--color-surfaceHighlight);
  color: var(--color-primary);
  border: none;
  /* Removed border */
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

.avatar-image {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.user-avatar-link {
  text-decoration: none;
}

/* Sidebar */
.portal-sidebar {
  position: fixed;
  top: 64px;
  left: 0;
  width: 260px;
  height: calc(100vh - 64px);
  background: var(--color-surface);
  border-right: 1px solid rgba(0, 0, 0, 0.05);
  /* Very subtle border */
  overflow-y: auto;
  z-index: 900;
  transition: transform var(--transition-normal);
  display: flex;
  flex-direction: column;
}

.sidebar-nav {
  padding: var(--spacing-xl) 0;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.nav-section {
  margin-bottom: var(--spacing-xl);
}

.mt-auto {
  margin-top: auto;
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
  position: relative;
  /* Context for pseudo-elements */
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  padding: 10px var(--spacing-lg);
  color: var(--color-textMain);
  text-decoration: none;
  transition: all 0.2s ease;
  border-radius: var(--radius-md);
  /* Apply radius to item */
  cursor: pointer;
  font-size: 14px;
  font-weight: var(--fw-medium);
  margin-right: var(--spacing-sm);
  z-index: 1;
  /* Text above bg */
}

/* Hover State */
.nav-item:hover {
  color: var(--color-primary);
}

.nav-item:hover::before {
  content: '';
  position: absolute;
  inset: 0;
  background-color: var(--color-primary);
  opacity: 0.05;
  /* Subtle hover tint */
  border-radius: var(--radius-md);
  z-index: -1;
}

/* Active State */
.nav-item.router-link-active {
  background-color: transparent;
  /* Handled by pseudo */
  color: var(--color-primary);
  font-weight: 700;
  box-shadow: none;
}

/* Faint Background Tint (Active) */
.nav-item.router-link-active::before {
  content: '';
  position: absolute;
  inset: 0;
  background-color: var(--color-primary);
  opacity: 0.08;
  /* Very Faint Tint (Requested) */
  border-radius: var(--radius-md);
  z-index: -1;
}

/* Active Dot Indicator */
.nav-item.router-link-active::after {
  content: '';
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background-color: var(--color-primary);
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.5);
  /* Crisp edge */
}

.nav-item.router-link-active .nav-icon {
  opacity: 1;
  color: var(--color-primary);
}

.small-icon {
  width: 18px;
  height: 18px;
}

.router-link-active .nav-icon {
  opacity: 1;
}

.nav-label {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Collapsible Groups */
.nav-group-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  padding: 12px var(--spacing-lg);
  color: var(--color-textMain);
  cursor: pointer;
  font-size: 14px;
  font-weight: var(--fw-semibold);
  /* Slightly bolder than items */
  transition: background-color 0.2s ease;
  user-select: none;
}

.nav-group-header:hover {
  background-color: var(--color-surfaceHighlight);
  color: var(--color-primary);
}

.group-arrow {
  width: 16px;
  height: 16px;
  opacity: 0.5;
  transition: transform 0.2s ease;
}

.nav-group-children {
  background-color: transparent;
  /* No distinct background, keeping it clean */
}

.child-item {
  padding-left: calc(var(--spacing-lg) + 28px);
  /* Indent children */
  font-size: 13.5px;
  padding-top: 10px;
  padding-bottom: 10px;
}

.child-item:hover {
  background-color: var(--color-surfaceHighlight);
}

.logout-item {
  color: var(--color-danger);
  margin-top: var(--spacing-lg);
  border-top: 1px solid var(--color-border);
  padding-top: var(--spacing-lg);
}

.logout-item:hover {
  background: var(--color-dangerBg);
  color: var(--color-dangerText);
}

/* Main Content */
.portal-main {
  margin-left: 260px;
  min-height: 100vh;
  background: var(--color-background);
  /* Increased top padding significantly as requested for "distance" */
  padding: calc(64px + 32px) 32px 32px 32px;
  transition: margin-left var(--transition-normal);
}

.content-container {
  max-width: 1400px;
  /* Wider container to breathe */
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
    top: 64px;
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
