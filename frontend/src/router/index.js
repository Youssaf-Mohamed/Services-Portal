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
      return next('/student/transport');
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
        return next('/student/transport');
      } else {
        return next('/login');
      }
    }
  }

  next();
});

export default router;
