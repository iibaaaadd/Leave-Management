<template>
    <div class="user-management-content">
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
                    <button
                        class="btn btn-outline-primary btn-sm"
                        @click="exportUsers"
                    >
                        <span class="me-1">📥</span>
                        Export
                    </button>
                    <button
                        class="btn btn-primary btn-sm"
                        @click="openCreateModal"
                    >
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
                            <select
                                class="form-select"
                                v-model="filterRole"
                                @change="handleRoleFilter"
                            >
                                <option value="">All Roles</option>
                                <option value="admin">Admin</option>
                                <option value="user">User</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Filter by Jabatan</label>
                            <select
                                class="form-select"
                                v-model="filterJabatan"
                                @change="handleJabatanFilter"
                            >
                                <option value="">All Jabatan</option>
                                <option
                                    v-for="jabatan in uniqueJabatan"
                                    :key="jabatan"
                                    :value="jabatan"
                                >
                                    {{ jabatan }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button
                                class="btn btn-outline-secondary w-100"
                                @click="clearFilters"
                            >
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
                <button
                    class="btn btn-sm btn-outline-danger ms-2"
                    @click="loadUsers"
                >
                    Try Again
                </button>
            </div>

            <!-- Users Table -->
            <div v-else class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Users Management</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
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
                                <tr v-for="user in paginatedUsers" :key="user.id">
                                    <td>{{ user.nip }}</td>
                                    <td>{{ user.name }}</td>
                                    <td>{{ user.email }}</td>
                                    <td>
                                        <span
                                            :class="[
                                                'badge',
                                                user.role === 'admin'
                                                    ? 'bg-danger'
                                                    : 'bg-primary',
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
                                                👁️
                                            </button>
                                            <button
                                                class="btn btn-sm btn-outline-warning"
                                                @click="loadUserAuditHistory(user)"
                                                title="View Audit History"
                                            >
                                                📋
                                            </button>
                                            <button
                                                class="btn btn-sm btn-outline-primary"
                                                @click="openEditModal(user)"
                                                title="Edit User"
                                            >
                                                ✏️
                                            </button>
                                            <button
                                                class="btn btn-sm btn-outline-danger"
                                                @click="confirmDelete(user)"
                                                title="Delete User"
                                            >
                                                🗑️
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <small class="text-muted">
                                Showing {{ startIndex + 1 }} to {{ endIndex }} of {{ filteredUsers.length }} entries
                            </small>
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                    <button class="page-link" @click="goToPage(currentPage - 1)" :disabled="currentPage === 1">
                                        Previous
                                    </button>
                                </li>
                                <li 
                                    v-for="page in visiblePages" 
                                    :key="page" 
                                    class="page-item" 
                                    :class="{ active: page === currentPage }"
                                >
                                    <button class="page-link" @click="goToPage(page)">
                                        {{ page }}
                                    </button>
                                </li>
                                <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                                    <button class="page-link" @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages">
                                        Next
                                    </button>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Audit History Table -->
            <div class="card" v-if="selectedUserForAudit">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <span class="me-2">📋</span>
                        Audit History - {{ selectedUserForAudit.name }}
                    </h5>
                    <button 
                        class="btn btn-sm btn-outline-secondary"
                        @click="clearAuditHistory"
                    >
                        <span class="me-1">✖️</span>
                        Close
                    </button>
                </div>
                <div class="card-body">
                    <!-- Audit Loading State -->
                    <div v-if="loadingAudit" class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading audit history...</span>
                        </div>
                        <p class="mt-2">Loading audit history...</p>
                    </div>
                    
                    <!-- No Audit Data -->
                    <div v-else-if="auditHistory.length === 0" class="text-center py-4 text-muted">
                        <div class="mb-3">
                            <span style="font-size: 3rem;">📝</span>
                        </div>
                        <h6>No audit history found</h6>
                        <p class="mb-0">There are no recorded activities for this user.</p>
                    </div>
                    
                    <!-- Audit Table -->
                    <div v-else class="table-responsive">
                        <table class="table table-sm table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Event</th>
                                    <th>User</th>
                                    <th>Changes</th>
                                    <th>IP Address</th>
                                    <th>User Agent</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="audit in paginatedAuditHistory" :key="audit.id">
                                    <td>
                                        <span 
                                            :class="[
                                                'badge',
                                                audit.event === 'created' ? 'bg-success' :
                                                audit.event === 'updated' ? 'bg-warning text-dark' :
                                                audit.event === 'deleted' ? 'bg-danger' : 'bg-info'
                                            ]"
                                        >
                                            {{ audit.event }}
                                        </span>
                                    </td>
                                    <td>{{ audit.user_name || 'System' }}</td>
                                    <td>
                                        <div v-if="audit.old_values || audit.new_values" class="small">
                                            <div v-if="audit.event === 'created'" class="text-success">
                                                <strong>Created:</strong>
                                                <div v-for="(value, key) in audit.new_values" :key="key" class="mt-1">
                                                    <span class="text-muted fw-bold">{{ key }}:</span> 
                                                    <span class="ms-1">{{ formatAuditValue(value) }}</span>
                                                </div>
                                            </div>
                                            <div v-else-if="audit.event === 'updated'">
                                                <div v-for="(value, key) in audit.new_values" :key="key" class="mt-1">
                                                    <span class="text-muted fw-bold">{{ key }}:</span><br>
                                                    <span class="text-danger small">{{ formatAuditValue(audit.old_values[key]) }}</span> 
                                                    <span class="mx-1">→</span> 
                                                    <span class="text-success small">{{ formatAuditValue(value) }}</span>
                                                </div>
                                            </div>
                                            <div v-else-if="audit.event === 'deleted'" class="text-danger">
                                                <strong>Deleted:</strong>
                                                <div v-for="(value, key) in audit.old_values" :key="key" class="mt-1">
                                                    <span class="text-muted fw-bold">{{ key }}:</span> 
                                                    <span class="ms-1">{{ formatAuditValue(value) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td>
                                        <code class="small">{{ audit.ip_address || '-' }}</code>
                                    </td>
                                    <td>
                                        <span 
                                            :title="audit.user_agent" 
                                            class="text-truncate d-inline-block small" 
                                            style="max-width: 200px;"
                                        >
                                            {{ formatUserAgent(audit.user_agent) }}
                                        </span>
                                    </td>
                                    <td>{{ formatDate(audit.created_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Audit Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3" v-if="totalAuditPages > 1">
                            <div>
                                <small class="text-muted">
                                    Showing {{ auditStartIndex + 1 }} to {{ auditEndIndex }} of {{ auditHistory.length }} audit entries
                                </small>
                            </div>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item" :class="{ disabled: currentAuditPage === 1 }">
                                        <button class="page-link" @click="goToAuditPage(currentAuditPage - 1)" :disabled="currentAuditPage === 1">
                                            Previous
                                        </button>
                                    </li>
                                    <li 
                                        v-for="page in visibleAuditPages" 
                                        :key="page" 
                                        class="page-item" 
                                        :class="{ active: page === currentAuditPage }"
                                    >
                                        <button class="page-link" @click="goToAuditPage(page)">
                                            {{ page }}
                                        </button>
                                    </li>
                                    <li class="page-item" :class="{ disabled: currentAuditPage === totalAuditPages }">
                                        <button class="page-link" @click="goToAuditPage(currentAuditPage + 1)" :disabled="currentAuditPage === totalAuditPages">
                                            Next
                                        </button>
                                    </li>
                                </ul>
                            </nav>
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
            data-bs-backdrop="true"
            data-bs-keyboard="true"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ isEditing ? "Edit User" : "Create New User" }}
                        </h5>
                        <button
                            type="button"
                            class="btn-close"
                            @click="closeModal"
                            aria-label="Close"
                        ></button>
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
                                        <div
                                            v-if="formErrors.name"
                                            class="text-danger mt-1"
                                        >
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
                                        <div
                                            v-if="formErrors.email"
                                            class="text-danger mt-1"
                                        >
                                            {{ formErrors.email[0] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nip" class="form-label">NIP *</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="nip"
                                            v-model="userForm.nip"
                                            required
                                        />
                                        <div
                                            v-if="formErrors.nip"
                                            class="text-danger mt-1"
                                        >
                                            {{ formErrors.nip[0] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">
                                            {{
                                                isEditing
                                                    ? "Password (leave blank to keep current)"
                                                    : "Password *"
                                            }}
                                        </label>
                                        <input
                                            type="password"
                                            class="form-control"
                                            id="password"
                                            v-model="userForm.password"
                                            :required="!isEditing"
                                        />
                                        <div
                                            v-if="formErrors.password"
                                            class="text-danger mt-1"
                                        >
                                            {{ formErrors.password[0] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Role *</label>
                                        <select
                                            class="form-select"
                                            id="role"
                                            v-model="userForm.role"
                                            required
                                        >
                                            <option value="user">User</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                        <div
                                            v-if="formErrors.role"
                                            class="text-danger mt-1"
                                        >
                                            {{ formErrors.role[0] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="jabatan" class="form-label">Jabatan *</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="jabatan"
                                            v-model="userForm.jabatan"
                                            required
                                        />
                                        <div
                                            v-if="formErrors.jabatan"
                                            class="text-danger mt-1"
                                        >
                                            {{ formErrors.jabatan[0] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    @click="closeModal"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    :disabled="formSubmitting"
                                >
                                    <span
                                        v-if="formSubmitting"
                                        class="spinner-border spinner-border-sm me-1"
                                    ></span>
                                    {{
                                        isEditing
                                            ? "Update User"
                                            : "Create User"
                                    }}
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
            data-bs-backdrop="true"
            data-bs-keyboard="true"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">User Details</h5>
                        <button
                            type="button"
                            class="btn-close"
                            @click="closeViewModal"
                            aria-label="Close"
                        ></button>
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
                                        selectedUser.role === 'admin'
                                            ? 'bg-danger'
                                            : 'bg-primary',
                                    ]"
                                >
                                    {{ selectedUser.role }}
                                </span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Jabatan:</strong>
                            </div>
                            <div class="col-sm-8">
                                {{ selectedUser.jabatan }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Email Verified:</strong>
                            </div>
                            <div class="col-sm-8">
                                <span
                                    :class="
                                        selectedUser.email_verified_at
                                            ? 'text-success'
                                            : 'text-warning'
                                    "
                                >
                                    {{
                                        selectedUser.email_verified_at
                                            ? "Yes"
                                            : "No"
                                    }}
                                </span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Created At:</strong>
                            </div>
                            <div class="col-sm-8">
                                {{ formatDate(selectedUser.created_at) }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Updated At:</strong>
                            </div>
                            <div class="col-sm-8">
                                {{ formatDate(selectedUser.updated_at) }}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            @click="closeViewModal"
                        >
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
            data-bs-backdrop="true"
            data-bs-keyboard="true"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button
                            type="button"
                            class="btn-close"
                            @click="closeDeleteModal"
                            aria-label="Close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <p>
                            Are you sure you want to delete user
                            <strong>{{ userToDelete?.name }}</strong>?
                        </p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            @click="closeDeleteModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger"
                            @click="deleteUser"
                            :disabled="deleteSubmitting"
                        >
                            <span
                                v-if="deleteSubmitting"
                                class="spinner-border spinner-border-sm me-1"
                            ></span>
                            Delete User
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <div v-if="alertMessage" :class="`alert alert-${alertType} alert-dismissible fade show position-fixed`" style="top: 20px; right: 20px; z-index: 1050;">
            {{ alertMessage }}
            <button type="button" class="btn-close" @click="clearAlert"></button>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "UserManagementContent",
    data() {
        return {
            // Users data
            users: [],
            filteredUsers: [],
            loading: true,
            error: null,
            searchQuery: "",
            filterRole: "",
            filterJabatan: "",
            alertMessage: '',
            alertType: 'success',

            // Pagination
            currentPage: 1,
            itemsPerPage: 10,

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
                nip: "",
                role: "user",
                jabatan: "",
            },
            formErrors: {},
            formSubmitting: false,

            // Delete modal
            userToDelete: null,
            deleteSubmitting: false,

            // Audit history
            selectedUserForAudit: null,
            auditHistory: [],
            loadingAudit: false,
            currentAuditPage: 1,
            auditItemsPerPage: 10,

            // Libraries loaded status
            librariesLoaded: false,
        };
    },

    computed: {
        uniqueJabatan() {
            const jabatanSet = new Set(this.users.map((user) => user.jabatan));
            return Array.from(jabatanSet).sort();
        },

        totalPages() {
            return Math.ceil(this.filteredUsers.length / this.itemsPerPage);
        },

        startIndex() {
            return (this.currentPage - 1) * this.itemsPerPage;
        },

        endIndex() {
            return Math.min(this.startIndex + this.itemsPerPage, this.filteredUsers.length);
        },

        paginatedUsers() {
            return this.filteredUsers.slice(this.startIndex, this.endIndex);
        },

        visiblePages() {
            const pages = [];
            const start = Math.max(1, this.currentPage - 2);
            const end = Math.min(this.totalPages, this.currentPage + 2);
            
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        },

        // Audit pagination
        totalAuditPages() {
            return Math.ceil(this.auditHistory.length / this.auditItemsPerPage);
        },

        auditStartIndex() {
            return (this.currentAuditPage - 1) * this.auditItemsPerPage;
        },

        auditEndIndex() {
            return Math.min(this.auditStartIndex + this.auditItemsPerPage, this.auditHistory.length);
        },

        paginatedAuditHistory() {
            return this.auditHistory.slice(this.auditStartIndex, this.auditEndIndex);
        },

        visibleAuditPages() {
            const pages = [];
            const start = Math.max(1, this.currentAuditPage - 2);
            const end = Math.min(this.totalAuditPages, this.currentAuditPage + 2);
            
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        }
    },

    async mounted() {
        this.setupAxiosDefaults();
        
        // Load external libraries first
        await this.loadExternalLibraries();
        
        await this.loadUsers();
    },

    methods: {
        async loadExternalLibraries() {
            try {
                // Load Bootstrap
                await this.loadBootstrap();
                
                // Load Font Awesome for icons
                this.loadFontAwesome();
                
                this.librariesLoaded = true;
                
                // Initialize components after libraries are loaded
                this.$nextTick(() => {
                    this.initializeModals();
                });
            } catch (error) {
                console.error("Error loading external libraries:", error);
                this.librariesLoaded = false;
            }
        },

        loadScript(src) {
            return new Promise((resolve, reject) => {
                // Check if script already exists
                if (document.querySelector(`script[src="${src}"]`)) {
                    resolve();
                    return;
                }

                const script = document.createElement("script");
                script.src = src;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        },

        loadCSS(href) {
            return new Promise((resolve) => {
                // Check if CSS already exists
                if (document.querySelector(`link[href="${href}"]`)) {
                    resolve();
                    return;
                }

                const link = document.createElement("link");
                link.rel = "stylesheet";
                link.href = href;
                link.onload = resolve;
                document.head.appendChild(link);
                // CSS loading doesn't need to block, so resolve immediately
                resolve();
            });
        },

        async loadBootstrap() {
            // Load Bootstrap CSS
            await this.loadCSS(
                "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
            );

            // Load Bootstrap JS
            await this.loadScript(
                "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
            );

            // Wait a bit to ensure Bootstrap is fully initialized
            return new Promise(resolve => {
                setTimeout(resolve, 100);
            });
        },

        loadFontAwesome() {
            this.loadCSS(
                "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
            );
        },

        initializeModals() {
            if (window.bootstrap && this.$refs.userModal) {
                this.userModalInstance = new window.bootstrap.Modal(this.$refs.userModal, {
                    backdrop: true,
                    keyboard: true
                });
                
                if (this.$refs.viewModal) {
                    this.viewModalInstance = new window.bootstrap.Modal(this.$refs.viewModal, {
                        backdrop: true,
                        keyboard: true
                    });
                }
                
                if (this.$refs.deleteModal) {
                    this.deleteModalInstance = new window.bootstrap.Modal(this.$refs.deleteModal, {
                        backdrop: true,
                        keyboard: true
                    });
                }
            }
        },

        setupAxiosDefaults() {
            const token = localStorage.getItem("auth_token");
            if (token) {
                axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;
            }
        },

        async loadUsers() {
            this.loading = true;
            this.error = null;
            try {
                const response = await axios.get("/api/users", {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                        'Accept': 'application/json'
                    }
                });
                this.users = response.data.data || response.data;
                this.filteredUsers = [...this.users];
                this.currentPage = 1;
            } catch (error) {
                console.error("Error loading users:", error);
                this.error = "Failed to load users. Please try again.";
                this.showAlert('Error loading users', 'danger');
            } finally {
                this.loading = false;
            }
        },

        handleSearch() {
            this.filterUsers();
        },

        handleRoleFilter() {
            this.filterUsers();
        },

        handleJabatanFilter() {
            this.filterUsers();
        },

        filterUsers() {
            let filtered = [...this.users];

            if (this.searchQuery) {
                filtered = filtered.filter(user => 
                    user.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                    user.email.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                    user.nip.toLowerCase().includes(this.searchQuery.toLowerCase())
                );
            }

            if (this.filterRole) {
                filtered = filtered.filter(user => user.role === this.filterRole);
            }

            if (this.filterJabatan) {
                filtered = filtered.filter(user => user.jabatan === this.filterJabatan);
            }

            this.filteredUsers = filtered;
            this.currentPage = 1;
        },

        clearFilters() {
            this.searchQuery = "";
            this.filterRole = "";
            this.filterJabatan = "";
            this.filteredUsers = [...this.users];
            this.currentPage = 1;
        },

        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },

        goToAuditPage(page) {
            if (page >= 1 && page <= this.totalAuditPages) {
                this.currentAuditPage = page;
            }
        },

        // Modal handling with improved reliability
        openCreateModal() {
            this.isEditing = false;
            this.resetForm();
            
            if (this.userModalInstance) {
                this.userModalInstance.show();
            } else {
                // Fallback for manual modal opening
                this.$refs.userModal.style.display = "block";
                this.$refs.userModal.classList.add("show");
                document.body.classList.add("modal-open");
                
                // Add backdrop
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.setAttribute('data-fallback-backdrop', 'true');
                document.body.appendChild(backdrop);
            }
        },

        openEditModal(user) {
            this.isEditing = true;
            this.userForm = {
                id: user.id,
                name: user.name,
                email: user.email,
                password: "",
                nip: user.nip,
                role: user.role,
                jabatan: user.jabatan,
            };
            
            if (this.userModalInstance) {
                this.userModalInstance.show();
            } else {
                // Fallback for manual modal opening
                this.$refs.userModal.style.display = "block";
                this.$refs.userModal.classList.add("show");
                document.body.classList.add("modal-open");
                
                // Add backdrop
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.setAttribute('data-fallback-backdrop', 'true');
                document.body.appendChild(backdrop);
            }
        },

        viewUser(user) {
            this.selectedUser = user;
            
            if (this.viewModalInstance) {
                this.viewModalInstance.show();
            } else {
                // Fallback for manual modal opening
                this.$refs.viewModal.style.display = "block";
                this.$refs.viewModal.classList.add("show");
                document.body.classList.add("modal-open");
                
                // Add backdrop
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.setAttribute('data-fallback-backdrop', 'true');
                document.body.appendChild(backdrop);
            }
        },

        async loadUserAuditHistory(user) {
            this.selectedUserForAudit = user;
            this.loadingAudit = true;
            this.currentAuditPage = 1;
            this.auditHistory = [];

            try {
                const response = await axios.get(`/api/users/${user.id}/audits`, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                        'Accept': 'application/json'
                    }
                });
                this.auditHistory = response.data.data || response.data;
                
                // Scroll to audit section
                setTimeout(() => {
                    const auditSection = document.querySelector('.card:last-of-type');
                    if (auditSection) {
                        auditSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 100);
            } catch (error) {
                console.error("Error loading audit history:", error);
                this.showAlert('Error loading audit history', 'danger');
                this.auditHistory = [];
            } finally {
                this.loadingAudit = false;
            }
        },

        clearAuditHistory() {
            this.selectedUserForAudit = null;
            this.auditHistory = [];
            this.currentAuditPage = 1;
        },

        confirmDelete(user) {
            this.userToDelete = user;
            
            if (this.deleteModalInstance) {
                this.deleteModalInstance.show();
            } else {
                // Fallback for manual modal opening
                this.$refs.deleteModal.style.display = "block";
                this.$refs.deleteModal.classList.add("show");
                document.body.classList.add("modal-open");
                
                // Add backdrop
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.setAttribute('data-fallback-backdrop', 'true');
                document.body.appendChild(backdrop);
            }
        },

        closeModal() {
            if (this.userModalInstance) {
                this.userModalInstance.hide();
            } else {
                // Fallback for manual modal closing
                this.$refs.userModal.style.display = "none";
                this.$refs.userModal.classList.remove("show");
                document.body.classList.remove("modal-open");
                
                // Remove fallback backdrop
                const backdrop = document.querySelector('[data-fallback-backdrop="true"]');
                if (backdrop) {
                    backdrop.remove();
                }
            }
            this.resetForm();
        },

        closeViewModal() {
            if (this.viewModalInstance) {
                this.viewModalInstance.hide();
            } else {
                // Fallback for manual modal closing
                this.$refs.viewModal.style.display = "none";
                this.$refs.viewModal.classList.remove("show");
                document.body.classList.remove("modal-open");
                
                // Remove fallback backdrop
                const backdrop = document.querySelector('[data-fallback-backdrop="true"]');
                if (backdrop) {
                    backdrop.remove();
                }
            }
            this.selectedUser = null;
        },

        closeDeleteModal() {
            if (this.deleteModalInstance) {
                this.deleteModalInstance.hide();
            } else {
                // Fallback for manual modal closing
                this.$refs.deleteModal.style.display = "none";
                this.$refs.deleteModal.classList.remove("show");
                document.body.classList.remove("modal-open");
                
                // Remove fallback backdrop
                const backdrop = document.querySelector('[data-fallback-backdrop="true"]');
                if (backdrop) {
                    backdrop.remove();
                }
            }
            this.userToDelete = null;
        },

        resetForm() {
            this.userForm = {
                name: "",
                email: "",
                password: "",
                nip: "",
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
                    response = await axios.put(`/api/users/${this.userForm.id}`, this.userForm, {
                        headers: {
                            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    const index = this.users.findIndex(u => u.id === this.userForm.id);
                    if (index !== -1) {
                        this.users[index] = response.data.data || response.data;
                    }
                    this.showAlert("User updated successfully", "success");
                } else {
                    response = await axios.post("/api/users", this.userForm, {
                        headers: {
                            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    this.users.push(response.data.data || response.data);
                    this.showAlert("User created successfully", "success");
                }

                this.filterUsers();
                this.closeModal();
            } catch (error) {
                console.error("Form submission error:", error);
                if (error.response?.data?.errors) {
                    this.formErrors = error.response.data.errors;
                } else {
                    this.showAlert("An error occurred. Please try again.", "danger");
                }
            } finally {
                this.formSubmitting = false;
            }
        },

        async deleteUser() {
            if (!this.userToDelete) return;
            
            this.deleteSubmitting = true;
            try {
                await axios.delete(`/api/users/${this.userToDelete.id}`, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                        'Accept': 'application/json'
                    }
                });
                
                this.users = this.users.filter(u => u.id !== this.userToDelete.id);
                this.filterUsers();
                
                // Clear audit history if deleted user was selected
                if (this.selectedUserForAudit && this.selectedUserForAudit.id === this.userToDelete.id) {
                    this.clearAuditHistory();
                }
                
                this.showAlert("User deleted successfully", "success");
                this.closeDeleteModal();
            } catch (error) {
                console.error("Delete error:", error);
                this.showAlert("Failed to delete user. Please try again.", "danger");
            } finally {
                this.deleteSubmitting = false;
            }
        },

        exportUsers() {
            const headers = ["NIP", "Name", "Email", "Role", "Jabatan"];
            const csvContent = [
                headers.join(","),
                ...this.filteredUsers.map((user) =>
                    [
                        user.nip,
                        `"${user.name}"`,
                        user.email,
                        user.role,
                        `"${user.jabatan}"`,
                    ].join(",")
                ),
            ].join("\n");

            const blob = new Blob([csvContent], { type: "text/csv" });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `users_${new Date().toISOString().split("T")[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        },

        formatDate(dateString) {
            if (!dateString) return "-";
            return new Date(dateString).toLocaleDateString("id-ID", {
                year: "numeric",
                month: "short",
                day: "numeric",
                hour: "2-digit",
                minute: "2-digit",
            });
        },

        formatAuditValue(value) {
            if (value === null || value === undefined) return '-';
            if (typeof value === 'boolean') return value ? 'Yes' : 'No';
            if (typeof value === 'string' && value.includes('T') && value.includes('Z')) {
                // Probably a date string
                return this.formatDate(value);
            }
            return value;
        },

        formatUserAgent(userAgent) {
            if (!userAgent) return '-';
            
            // Extract browser info from user agent
            if (userAgent.includes('Chrome')) return 'Chrome';
            if (userAgent.includes('Firefox')) return 'Firefox';
            if (userAgent.includes('Safari')) return 'Safari';
            if (userAgent.includes('Edge')) return 'Edge';
            if (userAgent.includes('Opera')) return 'Opera';
            
            return userAgent.length > 20 ? userAgent.substring(0, 20) + '...' : userAgent;
        },

        showAlert(message, type = 'success') {
            this.alertMessage = message;
            this.alertType = type;
            setTimeout(() => {
                this.clearAlert();
            }, 5000);
        },

        clearAlert() {
            this.alertMessage = '';
            this.alertType = 'success';
        }
    },

    beforeUnmount() {
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

        // Remove any fallback backdrops
        const backdrops = document.querySelectorAll('[data-fallback-backdrop="true"]');
        backdrops.forEach(backdrop => backdrop.remove());
        
        // Clean up body classes
        document.body.classList.remove("modal-open");
    },
};
</script>

<style scoped>
.user-management-content {
    padding: 0;
    animation: fadeIn 0.5s ease-out;
}

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

.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    margin-bottom: 1rem;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 8px 8px 0 0 !important;
    font-weight: 600;
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.badge {
    font-size: 0.75em;
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Audit table specific styles */
.table-responsive {
    border-radius: 0.375rem;
}

.table-dark th {
    background-color: #343a40;
    border-color: #495057;
}

.table-striped > tbody > tr:nth-of-type(odd) > td {
    background-color: rgba(0, 0, 0, 0.025);
}

.table-hover > tbody > tr:hover > td {
    background-color: rgba(0, 0, 0, 0.075);
}

/* Enhanced modal styles */
.modal-content {
    border-radius: 0.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.modal-header {
    border-bottom: 1px solid #e9ecef;
    padding: 1.25rem;
}

.modal-body {
    padding: 1.25rem;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 1rem 1.25rem;
}

/* Alert positioning */
.alert.position-fixed {
    top: 20px;
    right: 20px;
    z-index: 1060;
    min-width: 300px;
    max-width: 500px;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    .btn-group .btn {
        margin-bottom: 2px;
        margin-right: 0;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .modal-dialog {
        margin: 0.5rem;
    }
}

@media (prefers-color-scheme: dark) {
    .user-management-content {
        color: #e9ecef;
    }
    .card {
        background: #343a40;
        color: #e9ecef;
    }
    .card-header {
        background-color: #2c3034;
        border-color: #495057;
    }
    .table-striped > tbody > tr:nth-of-type(odd) > td {
        background-color: rgba(255, 255, 255, 0.05);
    }
    .table-hover > tbody > tr:hover > td {
        background-color: rgba(255, 255, 255, 0.075);
    }
}
</style>