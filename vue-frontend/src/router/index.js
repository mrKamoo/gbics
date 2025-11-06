import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/Login.vue'),
    meta: { requiresAuth: false, guestOnly: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/auth/Register.vue'),
    meta: { requiresAuth: false, guestOnly: true }
  },
  {
    path: '/pending-approval',
    name: 'PendingApproval',
    component: () => import('@/views/auth/PendingApproval.vue'),
    meta: { requiresAuth: true, requiresApproval: false }
  },
  {
    path: '/',
    component: () => import('@/components/layout/AppLayout.vue'),
    meta: { requiresAuth: true, requiresApproval: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/views/Dashboard.vue')
      },
      {
        path: '/gbics',
        name: 'Gbics',
        component: () => import('@/views/assets/Gbics.vue')
      },
      {
        path: '/switches',
        name: 'Switches',
        component: () => import('@/views/assets/Switches.vue')
      },
      {
        path: '/patch-cords',
        name: 'PatchCords',
        component: () => import('@/views/assets/PatchCords.vue')
      },
      {
        path: '/inventory/stock',
        name: 'Stock',
        component: () => import('@/views/inventory/Stock.vue')
      },
      {
        path: '/inventory/movements',
        name: 'Movements',
        component: () => import('@/views/inventory/Movements.vue')
      },
      {
        path: '/inventory/assignments',
        name: 'Assignments',
        component: () => import('@/views/inventory/Assignments.vue')
      },
      {
        path: '/locations',
        name: 'Locations',
        component: () => import('@/views/locations/LocationManagement.vue')
      },
      {
        path: '/admin/users',
        name: 'AdminUsers',
        component: () => import('@/views/admin/Users.vue'),
        meta: { requiresRole: ['super_admin', 'admin'] }
      },
      {
        path: '/admin/settings',
        name: 'Settings',
        component: () => import('@/views/admin/Settings.vue'),
        meta: { requiresRole: ['super_admin', 'admin'] }
      },
      {
        path: '/admin/fscom-catalog',
        name: 'FsComCatalog',
        component: () => import('@/views/admin/FsComCatalog.vue'),
        meta: { requiresRole: ['super_admin', 'admin'] }
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  // Check if route requires authentication
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Login', query: { redirect: to.fullPath } })
    return
  }

  // Redirect authenticated users from guest-only pages
  if (to.meta.guestOnly && authStore.isAuthenticated) {
    next({ name: 'Dashboard' })
    return
  }

  // Check if user is approved
  if (to.meta.requiresApproval && authStore.isAuthenticated && !authStore.isApproved) {
    next({ name: 'PendingApproval' })
    return
  }

  // Check role requirements
  if (to.meta.requiresRole && authStore.isAuthenticated) {
    const hasRole = authStore.hasAnyRole(to.meta.requiresRole)
    if (!hasRole) {
      next({ name: 'Dashboard' })
      return
    }
  }

  next()
})

export default router
