<template>
  <div class="dashboard-wrapper">
    <!-- Navbar Component -->
    <Navbar
      :currentUser="currentUser"
      :isLoggingOut="isLoggingOut"
      @logout="logout"
    />
    <div class="dashboard-container">
      <!-- Sidebar Component -->
      <Sidebar :activeMenu="activeMenu" @menu-change="setActiveMenu" />
      <!-- Main Content Component -->
      <div class="main-content">
        <div class="container-fluid p-4">
          <!-- Dashboard Header -->
          <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
              <h1 class="page-title">
                <span class="title-icon">👥</span>
                Users
              </h1>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-outline-primary btn-sm" @click="exportUsers">
                <span class="me-1">📥</span>
                Export
              </button>
              <button class="btn btn-primary btn-sm" @click="openCreateModal">
                <span class="me-1">➕</span>
                Add New
              </button>
            </div>
          </div>
          <!-- Custom Filter Controls -->
          <div class="card mb-4">
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Search</label>
                  <input
                    type="text"
                    class="form-control"
                    placeholder="Search users..."
                    v-model="searchQuery"
                    @input="handleSearch"
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Filter by Role</label>
                  <select class="form-select" v-model="filterRole" @change="handleRoleFilter">
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="user">User</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Filter by Jabatan</label>
                  <select class="form-select" v-model="filterJabatan" @change="handleJabatanFilter">
                    <option value="">All Jabatan</option>
                    <option v-for="jabatan in uniqueJabatan" :key="jabatan" :value="jabatan">
                      {{ jabatan }}
                    </option>
                  </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                  <button class="btn btn-outline-secondary w-100" @click="clearFilters">
                    Clear Filters
                  </button>
                </div>
              </div>
            </div>
          </div>
          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading users...</p>
          </div>
          <!-- Error State -->
          <div v-else-if="error" class="alert alert-danger">
            {{ error }}
            <button class="btn btn-sm btn-outline-danger ms-2" @click="loadUsers">
              Try Again
            </button>
          </div>
          <!-- Users DataTable -->
          <div v-else class="card">
            <div class="card-body">
              <table id="usersTable" class="table table-striped table-hover" style="width:100%">
                <thead>
                  <tr>
                    <th>NIP</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Jabatan</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="user in users" :key="user.id">
                    <td>{{ user.nip }}</td>
                    <td>{{ user.name }}</td>
                    <td>{{ user.email }}</td>
                    <td>
                      <span
                         :class="[
                          'badge',
                           user.role === 'admin' ? 'bg-danger' : 'bg-primary'
                        ]"
                      >
                        {{ user.role }}
                      </span>
                    </td>
                    <td>{{ user.jabatan }}</td>
                    <td>
                      <div class="btn-group">
                        <button
                           class="btn btn-sm btn-outline-info"
                           @click="viewUser(user)"
                          title="View Details"
                        >
                          <i class="fas fa-eye"></i>
                        </button>
                        <button
                           class="btn btn-sm btn-outline-primary"
                           @click="openEditModal(user)"
                          title="Edit User"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button
                           class="btn btn-sm btn-outline-danger"
                           @click="confirmDelete(user)"
                          title="Delete User"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Create/Edit User Modal -->
    <div
       class="modal fade"
       id="userModal"
       tabindex="-1"
       ref="userModal"
      data-bs-backdrop="static"
      data-bs-keyboard="false"
    >
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ isEditing ? 'Edit User' : 'Create New User' }}</h5>
            <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitUserForm">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="name" class="form-label">Name *</label>
                    <input
                      type="text"
                      class="form-control"
                      id="name"
                      v-model="userForm.name"
                      required
                    />
                    <div v-if="formErrors.name" class="text-danger mt-1">
                      {{ formErrors.name[0] }}
                    </div>
                  </div>
                </div>
                                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input
                      type="email"
                      class="form-control"
                      id="email"
                      v-model="userForm.email"
                      required
                    />
                    <div v-if="formErrors.email" class="text-danger mt-1">
                      {{ formErrors.email[0] }}
                    </div>
                  </div>
                </div>
              </div>
                            <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="password" class="form-label">
                      {{ isEditing ? 'Password (leave blank to keep current)' : 'Password *' }}
                    </label>
                    <input
                      type="password"
                      class="form-control"
                      id="password"
                      v-model="userForm.password"
                      :required="!isEditing"
                    />
                    <div v-if="formErrors.password" class="text-danger mt-1">
                      {{ formErrors.password[0] }}
                    </div>
                  </div>
                </div>
                                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="role" class="form-label">Role *</label>
                    <select class="form-select" id="role" v-model="userForm.role" required>
                      <option value="user">User</option>
                      <option value="admin">Admin</option>
                    </select>
                    <div v-if="formErrors.role" class="text-danger mt-1">
                      {{ formErrors.role[0] }}
                    </div>
                  </div>
                </div>
              </div>
                            <div class="mb-3">
                <label for="jabatan" class="form-label">Jabatan *</label>
                <input
                  type="text"
                  class="form-control"
                  id="jabatan"
                  v-model="userForm.jabatan"
                  required
                />
                <div v-if="formErrors.jabatan" class="text-danger mt-1">
                  {{ formErrors.jabatan[0] }}
                </div>
              </div>
                            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="closeModal">
                  Cancel
                </button>
                <button
                   type="submit"
                   class="btn btn-primary"
                   :disabled="formSubmitting"
                >
                  <span v-if="formSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                  {{ isEditing ? 'Update User' : 'Create User' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <!-- View User Modal -->
    <div
       class="modal fade"
       id="viewModal"
       tabindex="-1"
       ref="viewModal"
      data-bs-backdrop="static"
      data-bs-keyboard="false"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">User Details</h5>
            <button type="button" class="btn-close" @click="closeViewModal" aria-label="Close"></button>
          </div>
          <div class="modal-body" v-if="selectedUser">
            <div class="row mb-3">
              <div class="col-sm-4"><strong>NIP:</strong></div>
              <div class="col-sm-8">{{ selectedUser.nip }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-4"><strong>Name:</strong></div>
              <div class="col-sm-8">{{ selectedUser.name }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-4"><strong>Email:</strong></div>
              <div class="col-sm-8">{{ selectedUser.email }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-4"><strong>Role:</strong></div>
              <div class="col-sm-8">
                <span
                   :class="[
                    'badge',
                     selectedUser.role === 'admin' ? 'bg-danger' : 'bg-primary'
                  ]"
                >
                  {{ selectedUser.role }}
                </span>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-4"><strong>Jabatan:</strong></div>
              <div class="col-sm-8">{{ selectedUser.jabatan }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-4"><strong>Email Verified:</strong></div>
              <div class="col-sm-8">
                <span :class="selectedUser.email_verified_at ? 'text-success' : 'text-warning'">
                  {{ selectedUser.email_verified_at ? 'Yes' : 'No' }}
                </span>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-4"><strong>Created At:</strong></div>
              <div class="col-sm-8">{{ formatDate(selectedUser.created_at) }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-4"><strong>Updated At:</strong></div>
              <div class="col-sm-8">{{ formatDate(selectedUser.updated_at) }}</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeViewModal">
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
    <!-- Delete Confirmation Modal -->
    <div
       class="modal fade"
       id="deleteModal"
       tabindex="-1"
       ref="deleteModal"
      data-bs-backdrop="static"
      data-bs-keyboard="false"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Delete</h5>
            <button type="button" class="btn-close" @click="closeDeleteModal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete user <strong>{{ userToDelete?.name }}</strong>?</p>
            <p class="text-danger">This action cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeDeleteModal">
              Cancel
            </button>
            <button
               type="button"
               class="btn btn-danger"
               @click="deleteUser"
               :disabled="deleteSubmitting"
            >
              <span v-if="deleteSubmitting" class="spinner-border spinner-border-sm me-1"></span>
              Delete User
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import Navbar from "../components/Navbar.vue";
import Sidebar from "../components/Sidebar.vue";

export default {
  name: "UserManagementDataTablesFixed",
  components: {
    Navbar,
    Sidebar,
  },
  data() {
    return {
      activeMenu: "user",
      isLoggingOut: false,
      currentUser: {
        name: "John Doe",
        email: "john@example.com",
      },
      
      // Users data
      users: [],
      loading: true,
      error: null,
      searchQuery: "",
      filterRole: "",
      filterJabatan: "",
      
      // DataTable instance
      dataTable: null,
      
      // Modal instances
      userModalInstance: null,
      viewModalInstance: null,
      deleteModalInstance: null,
      
      // Modal state
      isEditing: false,
      selectedUser: null,
      userForm: {
        name: "",
        email: "",
        password: "",
        role: "user",
        jabatan: "",
      },
      formErrors: {},
      formSubmitting: false,
      
      // Delete modal
      userToDelete: null,
      deleteSubmitting: false,
      
      // Libraries loaded status
      librariesLoaded: false,
    };
  },
  computed: {
    uniqueJabatan() {
      const jabatanSet = new Set(this.users.map(user => user.jabatan));
      return Array.from(jabatanSet).sort();
    }
  },
  async mounted() {
    this.setupAxiosDefaults();
    await this.loadUserData();
    await this.loadUsers();
    
    // Load external libraries
    this.loadExternalLibraries();
  },
  methods: {
    loadExternalLibraries() {
      // Check if jQuery is already loaded
      if (typeof window.$ === 'undefined') {
        this.loadScript('https://code.jquery.com/jquery-3.7.1.min.js')
          .then(() => {
            return this.loadBootstrap();
          })
          .then(() => {
            return this.loadDataTables();
          })
          .then(() => {
            this.loadFontAwesome();
            this.librariesLoaded = true;
            this.initializeComponents();
          })
          .catch(error => {
            console.error('Error loading libraries:', error);
          });
      } else {
        this.loadBootstrap()
          .then(() => {
            return this.loadDataTables();
          })
          .then(() => {
            this.loadFontAwesome();
            this.librariesLoaded = true;
            this.initializeComponents();
          })
          .catch(error => {
            console.error('Error loading libraries:', error);
          });
      }
    },
    
    loadScript(src) {
      return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
      });
    },
    
    loadCSS(href) {
      return new Promise((resolve) => {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        link.onload = resolve;
        document.head.appendChild(link);
        // CSS loading doesn't need to block, so resolve immediately
        resolve();
      });
    },
    
    async loadBootstrap() {
      // Load Bootstrap CSS
      await this.loadCSS('https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css');
      
      // Load Bootstrap JS
      await this.loadScript('https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js');
    },
    
    async loadDataTables() {
      // Load DataTables CSS
      await this.loadCSS('https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css');
      
      // Load DataTables JS
      await this.loadScript('https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js');
      await this.loadScript('https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js');
    },
    
    loadFontAwesome() {
      this.loadCSS('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
    },
    
    initializeComponents() {
      this.$nextTick(() => {
        this.initializeDataTable();
        this.initializeModals();
      });
    },
    
    initializeDataTable() {
      if (this.dataTable) {
        this.dataTable.destroy();
      }
      
      this.dataTable = window.$('#usersTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[1, 'asc']], // Sort by name by default
        columnDefs: [
          {
            targets: [5], // Actions column (updated from 6 to 5)
            orderable: false,
            searchable: false
          }
        ],
        language: {
          search: "Search in table:",
          lengthMenu: "Show _MENU_ entries per page",
          info: "Showing _START_ to _END_ of _TOTAL_ entries",
          infoEmpty: "Showing 0 to 0 of 0 entries",
          infoFiltered: "(filtered from _MAX_ total entries)",
          paginate: {
            first: "First",
            last: "Last",
            next: "Next",
            previous: "Previous"
          }
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
      });
      
      // Hide the default search box since we have custom filters
      window.$('.dataTables_filter').hide();
    },
    
    initializeModals() {
      // Initialize Bootstrap modals
      if (window.bootstrap) {
        this.userModalInstance = new window.bootstrap.Modal(this.$refs.userModal);
        this.viewModalInstance = new window.bootstrap.Modal(this.$refs.viewModal);
        this.deleteModalInstance = new window.bootstrap.Modal(this.$refs.deleteModal);
      }
    },
    
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
    
    async loadUserData() {
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
    
    async loadUsers() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/api/users');
        this.users = response.data.data;
        
        // Reinitialize DataTable after data load
        this.$nextTick(() => {
          if (this.dataTable && this.librariesLoaded) {
            this.dataTable.clear().rows.add(window.$('#usersTable tbody tr')).draw();
          } else if (this.librariesLoaded) {
            this.initializeDataTable();
          }
        });
      } catch (error) {
        console.error("Error loading users:", error);
        this.error = "Failed to load users. Please try again.";
      } finally {
        this.loading = false;
      }
    },
    
    handleSearch() {
      if (this.dataTable) {
        this.dataTable.search(this.searchQuery).draw();
      }
    },
    
    handleRoleFilter() {
      if (this.dataTable) {
        this.dataTable.column(3).search(this.filterRole).draw();
      }
    },
    
    handleJabatanFilter() {
      if (this.dataTable) {
        this.dataTable.column(4).search(this.filterJabatan).draw();
      }
    },
    
    clearFilters() {
      this.searchQuery = "";
      this.filterRole = "";
      this.filterJabatan = "";
      
      if (this.dataTable) {
        this.dataTable.search('').columns().search('').draw();
      }
    },
    
    setActiveMenu(menu) {
      this.activeMenu = menu;
    },
    
    // Modal handling
    openCreateModal() {
      this.isEditing = false;
      this.resetForm();
      
      if (this.userModalInstance) {
        this.userModalInstance.show();
      } else {
        // Fallback for manual modal opening
        this.$refs.userModal.style.display = 'block';
        this.$refs.userModal.classList.add('show');
        document.body.classList.add('modal-open');
      }
    },
    
    openEditModal(user) {
      this.isEditing = true;
      this.userForm = {
        id: user.id,
        name: user.name,
        email: user.email,
        password: "",
        role: user.role,
        jabatan: user.jabatan,
      };
      
      if (this.userModalInstance) {
        this.userModalInstance.show();
      } else {
        // Fallback for manual modal opening
        this.$refs.userModal.style.display = 'block';
        this.$refs.userModal.classList.add('show');
        document.body.classList.add('modal-open');
      }
    },
    
    viewUser(user) {
      this.selectedUser = user;
      
      if (this.viewModalInstance) {
        this.viewModalInstance.show();
      } else {
        // Fallback for manual modal opening
        this.$refs.viewModal.style.display = 'block';
        this.$refs.viewModal.classList.add('show');
        document.body.classList.add('modal-open');
      }
    },
    
    confirmDelete(user) {
      this.userToDelete = user;
      
      if (this.deleteModalInstance) {
        this.deleteModalInstance.show();
      } else {
        // Fallback for manual modal opening
        this.$refs.deleteModal.style.display = 'block';
        this.$refs.deleteModal.classList.add('show');
        document.body.classList.add('modal-open');
      }
    },
    
    closeModal() {
      if (this.userModalInstance) {
        this.userModalInstance.hide();
      } else {
        // Fallback for manual modal closing
        this.$refs.userModal.style.display = 'none';
        this.$refs.userModal.classList.remove('show');
        document.body.classList.remove('modal-open');
      }
      this.resetForm();
    },
    
    closeViewModal() {
      if (this.viewModalInstance) {
        this.viewModalInstance.hide();
      } else {
        // Fallback for manual modal closing
        this.$refs.viewModal.style.display = 'none';
        this.$refs.viewModal.classList.remove('show');
        document.body.classList.remove('modal-open');
      }
      this.selectedUser = null;
    },
    
    closeDeleteModal() {
      if (this.deleteModalInstance) {
        this.deleteModalInstance.hide();
      } else {
        // Fallback for manual modal closing
        this.$refs.deleteModal.style.display = 'none';
        this.$refs.deleteModal.classList.remove('show');
        document.body.classList.remove('modal-open');
      }
      this.userToDelete = null;
    },
    
    resetForm() {
      this.userForm = {
        name: "",
        email: "",
        password: "",
        role: "user",
        jabatan: "",
      };
      this.formErrors = {};
    },
    
    // CRUD operations
    async submitUserForm() {
      this.formSubmitting = true;
      this.formErrors = {};
      
      try {
        let response;
        
        if (this.isEditing) {
          response = await axios.put(`/api/users/${this.userForm.id}`, this.userForm);
          
          const index = this.users.findIndex(u => u.id === this.userForm.id);
          if (index !== -1) {
            this.users[index] = response.data.data;
          }
          
          this.showToast("User updated successfully", "success");
        } else {
          response = await axios.post('/api/users', this.userForm);
          this.users.push(response.data.data);
          this.showToast("User created successfully", "success");
        }
        
        // Refresh DataTable
        this.$nextTick(() => {
          if (this.dataTable) {
            this.dataTable.clear().rows.add(window.$('#usersTable tbody tr')).draw();
          }
        });
        
        this.closeModal();
      } catch (error) {
        console.error("Form submission error:", error);
        
        if (error.response && error.response.data && error.response.data.errors) {
          this.formErrors = error.response.data.errors;
        } else {
          this.showToast("An error occurred. Please try again.", "error");
        }
      } finally {
        this.formSubmitting = false;
      }
    },
    
    async deleteUser() {
      if (!this.userToDelete) return;
      
      this.deleteSubmitting = true;
      
      try {
        await axios.delete(`/api/users/${this.userToDelete.id}`);
        
        this.users = this.users.filter(u => u.id !== this.userToDelete.id);
        
        // Refresh DataTable
        this.$nextTick(() => {
          if (this.dataTable) {
            this.dataTable.clear().rows.add(window.$('#usersTable tbody tr')).draw();
          }
        });
        
        this.showToast("User deleted successfully", "success");
        this.closeDeleteModal();
      } catch (error) {
        console.error("Delete error:", error);
        this.showToast("Failed to delete user. Please try again.", "error");
      } finally {
        this.deleteSubmitting = false;
      }
    },
    
    exportUsers() {
      if (this.dataTable) {
        // Get filtered data from DataTable
        const data = this.dataTable.rows({ search: 'applied' }).data().toArray();
        
        // Create CSV content
        const headers = ['NIP', 'Name', 'Email', 'Role', 'Jabatan'];
        const csvContent = [
          headers.join(','),
          ...this.users
            .filter((user, index) => data.some(row => row.includes(user.nip)))
            .map(user => [
              user.nip,
              `"${user.name}"`,
              user.email,
              user.role,
              `"${user.jabatan}"`
            ].join(','))
        ].join('\n');
        
        // Download CSV
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `users_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
      }
    },
    
    formatDate(dateString) {
      if (!dateString) return '-';
      return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },
    
    showToast(message, type = 'info') {
      // Simple toast implementation
      const toast = document.createElement('div');
      toast.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} position-fixed`;
      toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
      toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
      `;
      document.body.appendChild(toast);
      
      setTimeout(() => {
        if (toast.parentElement) {
          toast.remove();
        }
      }, 5000);
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
      window.location.replace("/login");
    },
  },
  
  beforeUnmount() {
    // Cleanup DataTable instance
    if (this.dataTable) {
      this.dataTable.destroy();
    }
    
    // Cleanup modal instances
    if (this.userModalInstance) {
      this.userModalInstance.dispose();
    }
    if (this.viewModalInstance) {
      this.viewModalInstance.dispose();
    }
    if (this.deleteModalInstance) {
      this.deleteModalInstance.dispose();
    }
  }
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
  margin-top: 56px;
  min-height: calc(100vh - 56px);
}

/* Main Content */
.main-content {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
}

/* Page Title */
.page-title {
  font-size: 1.75rem;
  font-weight: 600;
  margin-bottom: 0;
  display: flex;
  align-items: center;
}

.title-icon {
  margin-right: 10px;
  font-size: 1.5rem;
}

/* Custom DataTable Styling */
.dataTables_wrapper .dataTables_length select {
  padding: 4px 8px;
  border: 1px solid #dee2e6;
  border-radius: 4px;
}

.dataTables_wrapper .dataTables_info {
  padding-top: 8px;
}

/* Action buttons styling */
.btn-group .btn {
  margin-right: 2px;
}

.btn-group .btn:last-child {
  margin-right: 0;
}

/* Badge styling */
.badge {
  font-size: 0.75em;
}

/* Modal improvements */
.modal-lg {
  max-width: 800px;
}

/* Manual modal styling for fallback */
.modal.show {
  display: block !important;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1040;
  width: 100vw;
  height: 100vh;
  background-color: #000;
  opacity: 0.5;
}

/* Responsive Design */
@media (max-width: 768px) {
  .dashboard-container {
    flex-direction: column;
  }
  
  .main-content {
    padding: 15px;
  }
  
  .btn-group {
    flex-direction: column;
  }
  
  .btn-group .btn {
    margin-bottom: 2px;
    margin-right: 0;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .dashboard-wrapper {
    background-color: #1a1a1a;
  }
}

/* Loading states */
.loading {
  opacity: 0.6;
}

/* Toast positioning */
.position-fixed {
  position: fixed !important;
}
</style>
