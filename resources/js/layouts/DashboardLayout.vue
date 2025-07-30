<template>
  <div class="dashboard-wrapper">
    <!-- Navbar Component - Tetap -->
    <Navbar
      :currentUser="currentUser"
      :isLoggingOut="isLoggingOut"
      @logout="logout"
    />
    
    <div class="dashboard-container">
      <!-- Sidebar Component - Tetap -->
      <Sidebar :activeMenu="activeMenu" @menu-change="setActiveMenu" />
      
      <!-- Content Area - Yang Berubah -->
      <div class="main-content">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import Navbar from "../components/Navbar.vue";
import Sidebar from "../components/Sidebar.vue";

export default {
  name: "DashboardLayout",
  components: {
    Navbar,
    Sidebar,
  },
  data() {
    return {
      activeMenu: "dashboard",
      isLoggingOut: false,
      currentUser: {
        name: "John Doe",
        email: "john@example.com",
      },
    };
  },
  mounted() {
    this.setupAxiosDefaults();
    this.loadUserData();
    this.setActiveMenuFromRoute();
  },
  watch: {
    '$route'(to) {
      this.setActiveMenuFromRoute();
    }
  },
  methods: {
    setupAxiosDefaults() {
      const token = localStorage.getItem("auth_token");
      if (token) {
        axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;
      }
      const csrfToken = document.querySelector('meta[name="csrf-token"]');
      if (csrfToken) {
        axios.defaults.headers.common["X-CSRF-TOKEN"] = csrfToken.getAttribute("content");
      }
    },
    
    loadUserData() {
      const userData = localStorage.getItem("user_data");
      if (userData) {
        try {
          this.currentUser = JSON.parse(userData);
        } catch (error) {
          this.currentUser = {
            name: "User",
            email: "user@example.com",
          };
        }
      }
    },
    
    setActiveMenuFromRoute() {
      const routeName = this.$route.name;
      const routePath = this.$route.path;
      
      // Set active menu berdasarkan route name atau path
      switch(routeName) {
        case 'Users':
        case 'UserManagement':
          this.activeMenu = 'users';
          break;
        case 'LeaveRequests':
          this.activeMenu = 'leave-requests';
          break;
        case 'LeaveApprovals':
          this.activeMenu = 'leave-approvals';
          break;
        case 'Settings':
          this.activeMenu = 'settings';
          break;
        case 'Dashboard':
        default:
          this.activeMenu = 'dashboard';
          break;
      }
    },
    
    setActiveMenu(menu) {
      this.activeMenu = menu;
      
      // Navigate ke route yang sesuai
      const routeMap = {
        'dashboard': '/',
        'users': '/users',
        'leave-requests': '/leave-requests',
        'leave-approvals': '/leave-approvals',
        'settings': '/settings'
      };
      
      const targetRoute = routeMap[menu];
      if (targetRoute && this.$route.path !== targetRoute) {
        this.$router.push(targetRoute);
      }
    },
    
    async logout() {
      if (this.isLoggingOut) return;
      this.isLoggingOut = true;
      
      try {
        const token = localStorage.getItem("auth_token");
        if (!token) {
          console.warn("No auth token found");
          this.forceLogout();
          return;
        }
        
        const response = await axios.post(
          "/api/logout",
          {},
          {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
              "Content-Type": "application/json",
            },
            timeout: 10000,
          }
        );
        
        if (response.status === 200) {
          console.log("Logout berhasil");
          this.forceLogout();
        }
      } catch (error) {
        console.error("Logout error:", error);
        this.forceLogout();
      } finally {
        this.isLoggingOut = false;
      }
    },
    
    forceLogout() {
      localStorage.removeItem("auth_token");
      localStorage.removeItem("user_data");
      delete axios.defaults.headers.common["Authorization"];
      this.$router.push('/login');
    },
  },
};
</script>

<style scoped>
/* Dashboard Layout */
.dashboard-wrapper {
  min-height: 100vh;
  background-color: #f8f9fa;
}

/* Dashboard Container */
.dashboard-container {
  display: flex;
  margin-top: 56px; /* Navbar height */
  min-height: calc(100vh - 56px);
}

/* Main Content */
.main-content {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
}

/* Responsive Design */
@media (max-width: 768px) {
  .dashboard-container {
    flex-direction: column;
  }
  
  .main-content {
    padding: 15px;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .dashboard-wrapper {
    background-color: #1a1a1a;
  }
}
</style>