// resources/js/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import LoginForm from '../pages/LoginForm.vue'
import Approve from '../pages/LeaveApprove.vue'
import Request from '../pages/LeaveRequest.vue'
import User from '../pages/UserManagement.vue'
import Dashboard from '../Dashboard.vue'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: LoginForm
  },
  {
    path: '/user',
    name: 'user',
    component: User
  },
  {
    path: '/request',
    name: 'request',
    component: Request
  },
  {
    path: '/approve',
    name: 'approve',
    component: Approve
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const isAuthenticated = !!localStorage.getItem('auth_token') 

  if (to.meta.requiresAuth && !isAuthenticated) {
    next({ name: 'Login' }) // redirect ke login jika belum login
  } else if (to.name === 'Login' && isAuthenticated) {
    next({ name: 'Dashboard' }) // jika sudah login dan buka login lagi, arahkan ke dashboard
  } else {
    next() // izinkan navigasi
  }
})

export default router
