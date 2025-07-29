<template>
  <div class="sidebar">
    <div class="sidebar-content">
      <ul class="nav flex-column">
        <li class="nav-item">
          <a
            class="nav-link"
            href="/dashboard"
            @click="setActiveMenu('dashboard')"
            :class="{ active: activeMenu === 'dashboard' }"
          >
            <span class="nav-icon">📊</span>
            <span class="nav-text">Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a
            class="nav-link"
            href="/user"
            @click="setActiveMenu('user')"
            :class="{ active: activeMenu === 'user' }"
          >
            <span class="nav-icon">👥</span>
            <span class="nav-text">Users</span>
          </a>
        </li>
        <li class="nav-item">
          <a
            class="nav-link"
            href="/request"
            @click="setActiveMenu('request')"
            :class="{ active: activeMenu === 'request' }"
          >
            <span class="nav-icon">📝</span>
            <span class="nav-text">Request</span>
          </a>
        </li>
        <li class="nav-item">
          <a
            class="nav-link"
            href="/approve"
            @click="setActiveMenu('approve')"
            :class="{ active: activeMenu === 'approve' }"
          >
            <span class="nav-icon">✅</span>
            <span class="nav-text">Approve</span>
          </a>
        </li>
        <li class="nav-item">
          <a
            class="nav-link"
            href="#"
            @click="setActiveMenu('products')"
            :class="{ active: activeMenu === 'products' }"
          >
            <span class="nav-icon">📦</span>
            <span class="nav-text">Products</span>
          </a>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  name: "Sidebar",
  props: {
    activeMenu: {
      type: String,
      default: "dashboard"
    }
  },
  methods: {
    setActiveMenu(menu) {
      this.$emit('menu-change', menu);
    }
  }
};
</script>

<style scoped>
/* Sidebar */
.sidebar {
  width: 250px;
  background: linear-gradient(180deg, #343a40 0%, #495057 100%);
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
  position: fixed;
  height: calc(100vh - 56px);
  overflow-y: auto;
}

.sidebar-content {
  padding: 1rem 0;
}

.sidebar .nav-link {
  color: rgba(255, 255, 255, 0.8);
  padding: 0.75rem 1.5rem;
  border-radius: 0;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
}

.sidebar .nav-link:hover,
.sidebar .nav-link.active {
  color: white;
  background: linear-gradient(90deg, rgba(0, 123, 255, 0.2), transparent);
  border-left: 3px solid #007bff;
}

.nav-icon {
  font-size: 1.2rem;
  margin-right: 0.75rem;
  width: 20px;
  text-align: center;
}

.nav-text {
  font-weight: 500;
}

/* Scrollbar styling */
.sidebar::-webkit-scrollbar {
  width: 6px;
}

.sidebar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
}

.sidebar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5);
}
</style>