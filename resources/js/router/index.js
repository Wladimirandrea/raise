// resources/js/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  { path: '/', name: 'home', component: () => import('@/views/HomeView.vue') },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/auth/RegisterView.vue'),
    meta: { guestOnly: true }
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { guestOnly: true }
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/views/DashboardView.vue'),
    meta: { requiresAuth: true }
  },

  // ─── Admin ────────────────────────────────────────────────
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

  // ─── Case Manager ─────────────────────────────────────────
  {
    path: '/case-manager/dashboard',
    name: 'case-manager-dashboard',
    component: () => import('@/views/case-manager/DashboardCaseManager.vue'),
    meta: { requiresAuth: true, roles: ['case_manager'] }
  },
  {
    path: '/case-manager/citas',
    name: 'case-manager-citas',
    component: () => import('@/views/case-manager/MisCitas.vue'),
    meta: { requiresAuth: true, roles: ['case_manager'] }
  },
  {
    path: '/case-manager/clientes',
    name: 'case-manager-clientes',
    component: () => import('@/views/case-manager/MisClientes.vue'),
    meta: { requiresAuth: true, roles: ['case_manager'] }
  },
  {
    path: '/case-manager/perfil',
    name: 'case-manager-perfil',
    component: () => import('@/views/case-manager/PerfilCaseManager.vue'),
    meta: { requiresAuth: true, roles: ['case_manager'] }
  },
  {
    path: '/case-manager/estado-citas',
    name: 'case-manager-estado-citas',
    component: () => import('@/views/case-manager/EstadoCitas.vue'),
    meta: { requiresAuth: true, roles: ['case_manager'] }
  },

  // ─── Client ───────────────────────────────────────────────
  {
    path: '/client/dashboard',
    name: 'client-dashboard',
    component: () => import('@/views/client/DashboardClient.vue'),
    meta: { requiresAuth: true, roles: ['client'] }
  },

  // ─── 404 ──────────────────────────────────────────────────
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

router.beforeEach((to, from, next) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return next('/login')
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return next('/dashboard')
  }

  // Redirección automática desde /dashboard
  if (auth.isAuthenticated && to.path === '/dashboard') {
    const role = auth.user?.roles?.[0]?.name
    if (role === 'admin') return next('/admin/dashboard')
    if (role === 'case_manager') return next('/case-manager/dashboard')
    if (role === 'client') return next('/client/dashboard')
  }

  // Protección por rol
  if (to.meta.roles && auth.isAuthenticated) {
    const userRoles = auth.user?.roles?.map(r => r.name) || []
    const hasRole = to.meta.roles.some(role => userRoles.includes(role))
    if (!hasRole) return next('/')
  }

  next()
})

export default router