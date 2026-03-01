// resources/js/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'   // ← CORREGIDO con alias @

// Lazy loading de vistas
const RegisterView = () => import('@/views/auth/RegisterView.vue')
const LoginView = () => import('@/views/auth/LoginView.vue')
const DashboardView = () => import('@/views/DashboardView.vue')
const HomeView = () => import('@/views/HomeView.vue')

const routes = [
  { path: '/', name: 'home', component: HomeView },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: { guestOnly: true }
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { guestOnly: true }
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: DashboardView,
    meta: { requiresAuth: true }
  },


  {
    path: '/admin/dashboard',
    name: 'admin-dashboard',
    component: () => import('@/views/admin/DashboardAdmin.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/users',
    name: 'admin-users',
    component: () => import('@/views/admin/UsersCrud.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },

  {
    path: '/admin/schedules',
    name: 'admin-schedules',
    component: () => import('@/views/admin/SchedulesAdmin.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },







  {
    path: '/admin/appointments',
    name: 'admin-appointments',
    component: () => import('@/views/admin/AppointmentsAdmin.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/case-managers',
    name: 'admin-case-managers',
    component: () => import('@/views/admin/CaseManagersAdmin.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/reports',
    name: 'admin-reports',
    component: () => import('@/views/admin/ReportsAdmin.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/settings',
    name: 'admin-settings',
    component: () => import('@/views/admin/SettingsAdmin.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },


  {
    path: '/case-manager/dashboard',
    name: 'case-manager-dashboard',
    component: () => import('@/views/case-manager/DashboardCaseManager.vue'),
    meta: { requiresAuth: true, roles: ['casemanager'] }
  },
  {
    path: '/client/dashboard',
    name: 'client-dashboard',
    component: () => import('@/views/client/DashboardClient.vue'),
    meta: { requiresAuth: true, roles: ['client'] }
  },


  // Ruta 404 (opcional pero útil)
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: { template: '<div class="text-center mt-20"><h1>404 - Página no encontrada</h1></div>' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Middleware global
router.beforeEach((to, from, next) => {
  const auth = useAuthStore();

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return next('/login');
  }

  // Redirección automática si va a una ruta genérica /dashboard
  if (auth.isAuthenticated && to.path === '/dashboard') {
    const role = auth.user?.roles?.[0]?.name;

    if (role === 'admin') return next('/admin/dashboard');
    if (role === 'casemanager') return next('/case-manager/dashboard');
    if (role === 'client') return next('/client/dashboard');
  }

  // Protección adicional por rol específico
  if (to.meta.roles && auth.isAuthenticated) {
    const userRoles = auth.user?.roles?.map(r => r.name) || [];
    const hasRole = to.meta.roles.some(role => userRoles.includes(role));

    if (!hasRole) {
      return next('/'); // o mostrar mensaje de acceso denegado
    }
  }

  next();
});

export default router