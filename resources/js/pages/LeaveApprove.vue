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
                <div class="container-fluid">
                    <!-- Dashboard Header -->
                    <div
                        class="d-flex justify-content-between align-items-center mb-4"
                    >
                        <div>
                            <h1 class="page-title">
                                <span class="title-icon">📊</span>
                                Leave Approve
                            </h1>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm">
                                <span class="me-1">📥</span>
                                Export
                            </button>
                            <button class="btn btn-primary btn-sm">
                                <span class="me-1">➕</span>
                                Add New
                            </button>
                        </div>
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
    name: "approve",
    components: {
        Navbar,
        Sidebar,
    },
    data() {
        return {
            activeMenu: "approve",
            isLoggingOut: false,
            currentUser: {
                name: "John Doe",
                email: "john@example.com",
            },
            stats: {
                users: 1250,
                products: 89,
                orders: 456,
                revenue: 12500,
            },
        };
    },
    async mounted() {
        this.setupAxiosDefaults();
        await this.loadStats();
        this.loadUserData();
    },
    methods: {
        setupAxiosDefaults() {
            const token = localStorage.getItem("auth_token");
            if (token) {
                axios.defaults.headers.common[
                    "Authorization"
                ] = `Bearer ${token}`;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                axios.defaults.headers.common["X-CSRF-TOKEN"] =
                    csrfToken.getAttribute("content");
            }
        },
        async loadStats() {
            try {
                await new Promise((resolve) => setTimeout(resolve, 1000));

                this.stats = {
                    users: 1250,
                    products: 89,
                    orders: 456,
                    revenue: 12500,
                };
            } catch (error) {
                console.error("Error loading stats:", error);
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
        setActiveMenu(menu) {
            this.activeMenu = menu;
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

                if (error.response) {
                    console.error("Response status:", error.response.status);
                    console.error("Response data:", error.response.data);

                    if (error.response.status === 401) {
                        console.log("Token sudah tidak valid, logout paksa");
                    } else if (error.response.status === 404) {
                        console.error("Logout endpoint tidak ditemukan");
                    } else {
                        console.error(
                            "Server error:",
                            error.response.data?.message
                        );
                    }
                } else if (error.request) {
                    console.error("No response received:", error.request);
                } else {
                    console.error("Request setup error:", error.message);
                }

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

/* Responsive Design */
@media (max-width: 768px) {
    .dashboard-container {
        flex-direction: column;
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
    pointer-events: none;
}
</style>
