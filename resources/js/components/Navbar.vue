<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="#">
        <span class="brand-icon">🚀</span>
        LEAVE MANAGEMENT
      </a>
      <div class="navbar-nav ms-auto">
        <div class="nav-item dropdown">
          <a
            class="nav-link dropdown-toggle d-flex align-items-center"
            href="#"
            role="button"
            data-bs-toggle="dropdown"
          >
            <div class="user-avatar me-2">
              <span>{{ userInitials }}</span>
            </div>
            {{ currentUser.name }}
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <a class="dropdown-item" href="#">
                <span class="me-2">👤</span>Profile
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="#">
                <span class="me-2">⚙️</span>Settings
              </a>
            </li>
            <li><hr class="dropdown-divider" /></li>
            <li>
              <button
                class="dropdown-item text-danger w-100 text-start border-0 bg-transparent"
                @click="handleLogout"
                :disabled="isLoggingOut"
              >
                <span v-if="isLoggingOut" class="spinner-border spinner-border-sm me-2"></span>
                <span v-else class="me-2">🚪</span>
                {{ isLoggingOut ? 'Logging out...' : 'Logout' }}
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </nav>
</template>

<script>
export default {
  name: "Navbar",
  props: {
    currentUser: {
      type: Object,
      required: true,
      default: () => ({
        name: "User",
        email: "user@example.com"
      })
    },
    isLoggingOut: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    userInitials() {
      return this.currentUser.name
        .split(" ")
        .map((name) => name.charAt(0))
        .join("")
        .toUpperCase();
    }
  },
  methods: {
    handleLogout() {
      this.$emit('logout');
    }
  }
};
</script>

<style scoped>
/* Navbar */
.navbar {
  z-index: 1030;
  backdrop-filter: blur(10px);
}

.brand-icon {
  font-size: 1.5rem;
  margin-right: 0.5rem;
}

.user-avatar {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #007bff, #0056b3);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.8rem;
  font-weight: bold;
}

.dropdown-item:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner-border-sm {
  width: 0.875rem;
  height: 0.875rem;
}
</style>
