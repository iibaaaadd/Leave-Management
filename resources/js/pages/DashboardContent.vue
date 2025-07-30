<template>
    <div class="dashboard-content">
        <div class="container-fluid">
            <!-- Dashboard Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="page-title">
                        <span class="title-icon">📊</span>
                        Dashboard
                    </h1>
                    <p class="text-muted mb-0">
                        Welcome back, {{ currentUser.name }}!
                    </p>
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

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-info">
                            <h3>{{ formatNumber(stats.totalEmployees) }}</h3>
                            <p>Total Employees</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon">📝</div>
                        <div class="stat-info">
                            <h3>{{ formatNumber(stats.pendingRequests) }}</h3>
                            <p>Pending Requests</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <h3>{{ formatNumber(stats.approvedToday) }}</h3>
                            <p>Approved Today</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-info">
                            <h3>{{ formatNumber(stats.totalThisMonth) }}</h3>
                            <p>Total This Month</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button
                                    class="btn btn-outline-primary"
                                    @click="navigateTo('/leave-requests')"
                                >
                                    <span class="me-2">📝</span>
                                    Submit Leave Request
                                </button>
                                <button
                                    class="btn btn-outline-success"
                                    @click="navigateTo('/leave-approvals')"
                                >
                                    <span class="me-2">✅</span>
                                    Review Approvals
                                </button>
                                <button v-if="userRole === 'admin'"
                                    class="btn btn-outline-info"
                                    @click="navigateTo('/users')"
                                >
                                    <span class="me-2">👥</span>
                                    Manage Users
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            <div class="activity-list">
                                <div
                                    v-for="activity in recentActivities"
                                    :key="activity.id"
                                    class="activity-item"
                                >
                                    <div class="activity-icon">
                                        {{ activity.icon }}
                                    </div>
                                    <div class="activity-content">
                                        <p class="mb-1">
                                            {{ activity.message }}
                                        </p>
                                        <small class="text-muted">{{
                                            activity.time
                                        }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "DashboardContent",
    data() {
        return {
            currentUser: {
                name: "John Doe",
                email: "john@example.com",
            },
            stats: {
                totalEmployees: 156,
                pendingRequests: 12,
                approvedToday: 8,
                totalThisMonth: 45,
            },
            recentActivities: [
                {
                    id: 1,
                    icon: "✅",
                    message: "Leave request approved for Sarah Johnson",
                    time: "2 hours ago",
                },
                {
                    id: 2,
                    icon: "📝",
                    message: "New leave request from Mike Davis",
                    time: "4 hours ago",
                },
                {
                    id: 3,
                    icon: "👥",
                    message: "New employee John Smith added",
                    time: "1 day ago",
                },
                {
                    id: 4,
                    icon: "❌",
                    message: "Leave request rejected for Alex Brown",
                    time: "2 days ago",
                },
            ],
        };
    },
    mounted() {
        this.loadUserData();
        this.loadDashboardStats();
    },
    computed: {
        userRole() {
            const userData = JSON.parse(
                localStorage.getItem("user_data") || "{}"
            );
            return userData.role || "user";
        },
    },
    methods: {
        formatNumber(num) {
            return new Intl.NumberFormat().format(num);
        },

        navigateTo(path) {
            this.$router.push(path);
        },

        loadUserData() {
            const userData = localStorage.getItem("user_data");
            if (userData) {
                try {
                    this.currentUser = JSON.parse(userData);
                } catch (error) {
                    console.error("Error parsing user data:", error);
                }
            }
        },

        async loadDashboardStats() {
            try {
                console.log("Dashboard stats loaded");
            } catch (error) {
                console.error("Error loading dashboard stats:", error);
            }
        },
    },
};
</script>

<style scoped>
.dashboard-content {
    padding: 0;
    animation: fadeIn 0.5s ease-out;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #343a40;
    margin-bottom: 0.5rem;
}

.title-icon {
    font-size: 1.8rem;
    margin-right: 0.5rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    font-size: 2.5rem;
    margin-right: 1rem;
    opacity: 0.8;
}

.stat-info h3 {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
    color: #343a40;
}

.stat-info p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 500;
}

.card {
    border: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-radius: 12px;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    border-radius: 12px 12px 0 0 !important;
    padding: 1rem 1.5rem;
}

.card-body {
    padding: 1.5rem;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    padding: 0.75rem 1rem;
}

.btn:hover {
    transform: translateY(-2px);
}

.activity-list {
    max-height: 300px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f3f4;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    font-size: 1.25rem;
    margin-right: 0.75rem;
    margin-top: 0.125rem;
}

.activity-content {
    flex: 1;
}

.activity-content p {
    font-size: 0.9rem;
    line-height: 1.4;
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

/* Responsive Design */
@media (max-width: 768px) {
    .page-title {
        font-size: 1.5rem;
    }

    .stat-card {
        padding: 1rem;
    }

    .stat-icon {
        font-size: 2rem;
    }

    .stat-info h3 {
        font-size: 1.5rem;
    }

    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
}

@media (max-width: 576px) {
    .dashboard-content {
        padding: 0;
    }

    .container-fluid {
        padding: 0.5rem;
    }

    .stat-card {
        flex-direction: column;
        text-align: center;
        padding: 1rem;
    }

    .stat-icon {
        margin-right: 0;
        margin-bottom: 0.5rem;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .page-title {
        color: #e9ecef;
    }

    .stat-card {
        background: #343a40;
        color: #e9ecef;
        border-color: #495057;
    }

    .stat-info h3 {
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
}
</style>
