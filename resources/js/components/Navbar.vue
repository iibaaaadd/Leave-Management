<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="#">
        <span class="brand-icon">🚀</span>
        LEAVE MANAGEMENT
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
              data-bs-target="#navbarNav" aria-controls="navbarNav" 
              aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <div class="navbar-nav ms-auto">
          <div class="nav-item dropdown" ref="dropdownContainer">
            <a
                class="nav-link dropdown-toggle d-flex align-items-center"
                href="#"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                ref="dropdownToggle"
                @click.prevent="toggleDropdown"
            >
              <div class="user-avatar me-2">
                <span>{{ userInitials }}</span>
              </div>
              {{ currentUser.name }}
            </a>
            <ul class="dropdown-menu dropdown-menu-end" ref="dropdownMenu">
              <li>
                <a class="dropdown-item" href="#" @click.prevent="viewProfile">
                  <span class="me-2">👤</span>Profile
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="#" @click.prevent="viewSettings">
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
                  <span
                      v-if="isLoggingOut"
                      class="spinner-border spinner-border-sm me-2"
                  ></span>
                  <span v-else class="me-2">🚪</span>
                  {{ isLoggingOut ? "Logging out..." : "Logout" }}
                </button>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script>
import { Collapse, Dropdown } from 'bootstrap'

export default {
  name: 'Navbar',
  props: {
    currentUser: {
      type: Object,
      required: true,
      default: () => ({
        name: "User",
        email: "user@example.com",
      }),
    },
    isLoggingOut: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      userName: 'John Doe',
      dropdownInstance: null,
      isDropdownOpen: false
    }
  },
  async mounted() {
    // Tunggu sampai DOM fully loaded
    await this.$nextTick();
    
    // Delay untuk memastikan Bootstrap sudah loaded
    setTimeout(() => {
      this.initializeDropdown();
    }, 100);
  },
  beforeUnmount() {
    // Cleanup dropdown instance
    if (this.dropdownInstance) {
      this.dropdownInstance.dispose();
    }
  },
  computed: {
    userInitials() {
      return this.currentUser.name
        .split(" ")
        .map((name) => name.charAt(0))
        .join("")
        .toUpperCase();
    },
  },
  methods: {
    initializeDropdown() {
      const dropdownElement = this.$refs.dropdownToggle;
      if (dropdownElement) {
        this.dropdownInstance = new Dropdown(dropdownElement);
      }
    },
    
    toggleDropdown() {
      if (this.dropdownInstance) {
        this.dropdownInstance.toggle();
      } else {
        this.isDropdownOpen = !this.isDropdownOpen;
        const menu = this.$refs.dropdownMenu;
        if (menu) {
          if (this.isDropdownOpen) {
            menu.classList.add('show');
          } else {
            menu.classList.remove('show');
          }
        }
      }
    },
    
    handleLogout() {
      if (this.dropdownInstance) {
        this.dropdownInstance.hide();
      } else {
        this.isDropdownOpen = false;
        const menu = this.$refs.dropdownMenu;
        if (menu) {
          menu.classList.remove('show');
        }
      }
      
      this.$emit("logout");
    },
    initializeBootstrap() {
      // Inisialisasi collapse untuk mobile menu
      const collapseElementList = document.querySelectorAll('.collapse')
      collapseElementList.forEach(collapseEl => {
        new Collapse(collapseEl, { toggle: false })
      })
    },
    viewProfile() {
      console.log('View Profile clicked')
    },
    viewSettings() {
      console.log('Settings clicked')
    },
    logout() {
      console.log('Logout clicked')
      // Implement logout logic
    }
  }
}
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

/* Manual dropdown styles */
.dropdown-menu.show {
  display: block;
}
</style>
