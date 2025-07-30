// resources/js/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import LoginForm from '../pages/LoginForm.vue'
import Approve from '../pages/LeaveApprove.vue'
import Request from '../pages/LeaveRequest.vue'
import User from '../pages/UserManagement.vue'
import Dashboard from '../pages/DashboardContent.vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: LoginForm
  },
  {
    path: '/',
    component: DashboardLayout, 
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: Dashboard
      },
      {
        path: 'dashboard',
        redirect: '/'
      },
      {
        path: 'users',
        name: 'Users',
        component: User,
        meta: { requiresAdmin: true } 
      },
      {
        path: 'leave-requests',
        name: 'LeaveRequests',
        component: Request
      },
      {
        path: 'leave-approvals',
        name: 'LeaveApprovals',
        component: Approve
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const isAuthenticated = !!localStorage.getItem('auth_token') 
  const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
  const userRole = userData.role || 'user'

  // Check authentication
  if (to.meta.requiresAuth && !isAuthenticated) {
    next({ name: 'Login' }) 
    return
  }

  // Check admin access
  if (to.meta.requiresAdmin && userRole !== 'admin') {
    // Redirect non-admin users ke dashboard dengan pesan error
    next({ name: 'Dashboard' })
    // Optional: Tampilkan notifikasi error
    // alert('Access denied. Admin role required.')
    return
  }

  // Redirect ke dashboard jika sudah login dan mengakses login page
  if (to.name === 'Login' && isAuthenticated) {
    next({ name: 'Dashboard' }) 
  } else {
    next() 
  }
})

export default router