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
    component: DashboardLayout, // Layout sebagai parent
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: Dashboard
      },
      {
        path: 'dashboard',
        redirect: '/' // Redirect dashboard ke root
      },
      {
        path: 'users',
        name: 'Users',
        component: User
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

  if (to.meta.requiresAuth && !isAuthenticated) {
    next({ name: 'Login' }) 
  } else if (to.name === 'Login' && isAuthenticated) {
    next({ name: 'Dashboard' }) 
  } else {
    next() 
  }
})

export default router