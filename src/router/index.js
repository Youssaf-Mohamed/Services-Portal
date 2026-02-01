import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/LoginView.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/token/verify',
    name: 'SSOCallback',
    component: () => import('../views/SSOCallback.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/student',
    name: 'StudentHome',
    component: () => import('../views/student/StudentHome.vue'),
    meta: { requiresAuth: true, requiresRole: 'student' },
  },
  {
    path: '/student/profile',
    name: 'StudentProfileEmbed',
    component: () => import('../views/student/ProfileEmbed.vue'),
    meta: { requiresAuth: true, requiresRole: 'student' },
  },
  {
    path: '/student/transport',
    name: 'StudentTransport',
    component: () => import('../features/transport/pages/TransportHome.vue'),
    meta: { requiresAuth: true, requiresRole: 'student' },
  },
  {
    path: '/student/transport/my-requests',
    name: 'StudentTransportRequests',
    component: () => import('../features/transport/pages/MyRequests.vue'),
    meta: { requiresAuth: true, requiresRole: 'student' },
  },
  {
    path: '/student/transport/my-subscription',
    name: 'StudentTransportSubscription',
    component: () => import('../features/transport/pages/MySubscription.vue'),
    meta: { requiresAuth: true, requiresRole: 'student' },
  },
  {
    path: '/admin',
    name: 'AdminDashboard',
    component: () => import('../views/admin/DashboardView.vue'),
    meta: { requiresAuth: true, requiresRole: 'admin' },
  },
  // Admin Transport Management Routes
  {
    path: '/admin/transport',
    name: 'AdminTransportDashboard',
    component: () => import('../features/adminTransport/pages/Dashboard.vue'),
    meta: { requiresAuth: true, requiresRole: 'admin' },
  },
  {
    path: '/admin/transport/requests',
    name: 'AdminTransportRequests',
    component: () => import('../features/adminTransport/pages/RequestsList.vue'),
    meta: { requiresAuth: true, requiresRole: 'admin' },
  },
  {
    path: '/admin/transport/requests/:id',
    name: 'AdminTransportRequestDetails',
    component: () => import('../features/adminTransport/pages/RequestDetails.vue'),
    meta: { requiresAuth: true, requiresRole: 'admin' },
  },
  {
    path: '/admin/transport/routes',
    name: 'AdminTransportRoutes',
    component: () => import('../features/adminTransport/pages/RoutesList.vue'),
    meta: { requiresAuth: true, requiresRole: 'admin' },
  },
  {
    path: '/admin/transport/slots',
    name: 'AdminTransportSlots',
    component: () => import('../features/adminTransport/pages/SlotsList.vue'),
    meta: { requiresAuth: true, requiresRole: 'admin' },
  },
  {
    path: '/admin/transport/stops',
    name: 'AdminTransportStops',
    component: () => import('../features/adminTransport/pages/StopsList.vue'),
    meta: { requiresAuth: true, requiresRole: 'admin' },
  },
  {
    path: '/admin/transport/settings',
    name: 'admin-transport-settings',
    component: () => import('@/features/adminTransport/pages/Settings.vue'),
    meta: { requiresAuth: true, role: 'admin' },
  },
  {
    path: '/admin/transport/manifest',
    name: 'AdminTransportManifest',
    component: () => import('../features/adminTransport/pages/Manifest.vue'),
    meta: { requiresAuth: true, requiresRole: 'admin' },
  },
  {
    path: '/admin/transport/reports',
    name: 'AdminTransportReports',
    component: () => import('@/features/adminTransport/pages/Reports.vue'),
    meta: { requiresAuth: true, requiresRole: 'admin' },
  },
  {
    path: '/notifications',
    name: 'Notifications',
    component: () => import('@/features/notifications/pages/NotificationsPage.vue'),
    meta: { requiresAuth: true },
  },
  // Unified My Requests (all modules)
  {
    path: '/student/my-requests',
    name: 'UnifiedMyRequests',
    component: () => import('../features/myRequests/pages/UnifiedRequests.vue'),
    meta: { requiresAuth: true, requiresRole: 'student' },
  },
  // ID Card Student Routes
  {
    path: '/student/id-card',
    name: 'StudentIdCard',
    component: () => import('../features/idCard/pages/IdCardHome.vue'),
    meta: { requiresAuth: true, requiresRole: 'student' },
  },
  // NOTE: NewIdCardRequest route removed - now using modal-based submission
  // {
  //   path: '/student/id-card/request/:typeCode',
  //   name: 'NewIdCardRequest',
  //   component: () => import('../features/idCard/pages/NewIdCardRequest.vue'),
  //   meta: { requiresAuth: true, requiresRole: 'student' },
  // },
  {
    path: '/student/id-card/my-requests',
    name: 'StudentIdCardRequests',
    component: () => import('../features/idCard/pages/MyIdCardRequests.vue'),
    meta: { requiresAuth: true, requiresRole: 'student' },
  },
  // Admin ID Card Routes
  {
    path: '/admin/id-card',
    name: 'AdminIdCardDashboard',
    component: () => import('../features/adminIdCard/pages/Dashboard.vue'),
    meta: { requiresAuth: true, requiresRole: 'admin' },
  },
  {
    path: '/admin/id-card/requests',
    name: 'AdminIdCardRequests',
    component: () => import('../features/adminIdCard/pages/RequestsList.vue'),
    meta: { requiresAuth: true, requiresRole: 'admin' },
  },
  {
    path: '/admin/id-card/requests/:id',
    name: 'AdminIdCardRequestDetails',
    component: () => import('../features/adminIdCard/pages/RequestDetails.vue'),
    meta: { requiresAuth: true, requiresRole: 'admin' },
  },
  {
    path: '/admin/id-card/settings',
    name: 'AdminIdCardSettings',
    component: () => import('../features/adminIdCard/pages/Settings.vue'),
    meta: { requiresAuth: true, requiresRole: 'admin' },
  },
  {
    path: '/admin/id-card/types',
    name: 'AdminIdCardTypes',
    component: () => import('../features/adminIdCard/pages/IdCardTypes.vue'),
    meta: { requiresAuth: true, requiresRole: 'admin' },
  },
  {
    path: '/',
    redirect: '/login',
  },
];


const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // Try to fetch user if not already loaded (with try/catch for stability)
  if (!authStore.user && !authStore.loading) {
    try {
      await authStore.fetchMe();
    } catch (e) {
      // Silently fail - user will be null
    }
  }

  // Guest-only routes (login page)
  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    const role = authStore.userRole;
    if (role === 'admin') {
      return next('/admin');
    } else if (role === 'student') {
      return next('/student');
    }
  }

  // Protected routes
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next('/login');
  }

  // Role-based access
  if (to.meta.requiresRole) {
    if (!authStore.hasRole(to.meta.requiresRole)) {
      // Redirect to appropriate dashboard
      const role = authStore.userRole;
      if (role === 'admin') {
        return next('/admin');
      } else if (role === 'student') {
        return next('/student');
      } else {
        return next('/login');
      }
    }
  }

  next();
});

export default router;
