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
                <div
                    v-if="loading"
                    class="spinner-border text-primary"
                    role="status"
                >
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-info">
                            <h3>{{ formatNumber(stats.totalUsers) }}</h3>
                            <p>Total Users</p>
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

            <!-- Filter Tahun -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="yearFilter" class="form-label"
                        >Filter Tahun</label
                    >
                    <select
                        class="form-select"
                        v-model="selectedYear"
                        @change="loadMonthlyChartData"
                    >
                        <option
                            v-for="year in yearOptions"
                            :key="year"
                            :value="year"
                        >
                            {{ year }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Chart -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <apexchart
                                type="bar"
                                height="400"
                                :options="monthlyChartOptions"
                                :series="monthlySeries"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Recent Activities -->
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
                                <button
                                    v-if="userRole === 'admin'"
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
                        <div
                            class="card-header d-flex justify-content-between align-items-center"
                        >
                            <h5 class="mb-0">Recent Activities</h5>
                            <button
                                class="btn btn-sm btn-outline-secondary"
                                @click="refreshActivities"
                                :disabled="loadingActivities"
                            >
                                <span v-if="!loadingActivities">🔄</span>
                                <span
                                    v-else
                                    class="spinner-border spinner-border-sm"
                                    role="status"
                                ></span>
                            </button>
                        </div>
                        <div class="card-body">
                            <div
                                class="activity-list"
                                v-if="recentActivities.length > 0"
                            >
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
                            <div
                                v-else-if="loadingActivities"
                                class="text-center py-3"
                            >
                                <div
                                    class="spinner-border text-primary"
                                    role="status"
                                >
                                    <span class="visually-hidden"
                                        >Loading activities...</span
                                    >
                                </div>
                            </div>
                            <div v-else class="text-center py-3 text-muted">
                                <p class="mb-0">No recent activities</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Alert -->
            <div
                v-if="error"
                class="alert alert-danger alert-dismissible fade show"
                role="alert"
            >
                <strong>Error!</strong> {{ error }}
                <button
                    type="button"
                    class="btn-close"
                    @click="error = null"
                ></button>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import VueApexCharts from "vue3-apexcharts";

export default {
    components: {
        apexchart: VueApexCharts,
    },
    name: "DashboardContent",
    data() {
        return {
            selectedYear: new Date().getFullYear(),
            yearOptions: this.generateYearOptions(),
            monthlyChartOptions: {
                chart: {
                    type: "bar",
                    height: 350,
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: "50%",
                    },
                },
                dataLabels: {
                    enabled: true,
                },
                xaxis: {
                    categories: [
                        "Jan",
                        "Feb",
                        "Mar",
                        "Apr",
                        "May",
                        "Jun",
                        "Jul",
                        "Aug",
                        "Sep",
                        "Oct",
                        "Nov",
                        "Dec",
                    ],
                },
                title: {
                    text: "Leave Request Status by Month",
                    align: "center",
                    style: {
                        fontSize: "18px",
                    },
                },
            },
            monthlySeries: [
                {
                    name: "Approved",
                    data: Array(12).fill(0),
                },
                {
                    name: "Rejected",
                    data: Array(12).fill(0),
                },
                {
                    name: "Pending",
                    data: Array(12).fill(0),
                },
            ],
            loading: false,
            loadingActivities: false,
            error: null,
            currentUser: {
                name: "Loading...",
                email: "",
                role: "user",
            },
            stats: {
                totalUsers: 0,
                pendingRequests: 0,
                approvedToday: 0,
                totalThisMonth: 0,
            },
            recentActivities: [],
        };
    },
    async mounted() {
        await this.loadDashboardData();
        await this.loadMonthlyChartData();

        // Auto refresh setiap 5 menit
        this.refreshInterval = setInterval(() => {
            this.loadDashboardData(false);
            this.loadMonthlyChartData();
        }, 300000); // 5 minutes
    },
    beforeUnmount() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
    },
    computed: {
        userRole() {
            return this.currentUser.role || "user";
        },
    },
    methods: {
        generateYearOptions() {
            const currentYear = new Date().getFullYear();
            const years = [];
            for (let i = 0; i < 5; i++) {
                years.push(currentYear - i);
            }
            return years;
        },
        formatNumber(num) {
            return new Intl.NumberFormat().format(num || 0);
        },

        navigateTo(path) {
            this.$router.push(path);
        },

        async loadDashboardData(showLoading = true) {
            if (showLoading) {
                this.loading = true;
            }
            this.error = null;

            try {
                // Load semua data secara parallel
                const [userResponse, statsResponse, activitiesResponse] =
                    await Promise.all([
                        this.loadUserProfile(),
                        this.loadDashboardStats(),
                        this.loadRecentActivities(),
                    ]);

                // User Profile
                if (userResponse && userResponse.success) {
                    this.currentUser = userResponse.data;
                }

                // Stats
                if (statsResponse && statsResponse.success) {
                    this.stats = statsResponse.data;
                }

                // Activities - Perbaikan untuk memastikan data langsung tampil
                if (activitiesResponse && activitiesResponse.success) {
                    this.recentActivities = activitiesResponse.data || [];
                }
            } catch (error) {
                console.error("Error loading dashboard data:", error);
                this.error =
                    "Failed to load dashboard data. Please try refreshing the page.";
            } finally {
                this.loading = false;
            }
        },

        async loadMonthlyChartData() {
            try {
                const token = localStorage.getItem("auth_token");
                const response = await axios.get(
                    "/api/dashboard/monthly-stats",
                    {
                        headers: {
                            Authorization: `Bearer ${token}`,
                            Accept: "application/json",
                        },
                        params: {
                            year: this.selectedYear, // Perbaikan: gunakan selectedYear, bukan hardcode
                        },
                    }
                );

                if (response.data.success) {
                    const stats = response.data.data.monthlyStats;

                    // Perbaikan: pastikan data dalam urutan yang benar (Jan-Dec)
                    this.monthlySeries = [
                        {
                            name: "Approved",
                            data: stats.map((s) => parseInt(s.approved) || 0),
                        },
                        {
                            name: "Rejected",
                            data: stats.map((s) => parseInt(s.rejected) || 0),
                        },
                        {
                            name: "Pending",
                            data: stats.map((s) => parseInt(s.pending) || 0),
                        },
                    ];

                    // Update chart title dengan tahun yang dipilih
                    this.monthlyChartOptions = {
                        ...this.monthlyChartOptions,
                        title: {
                            ...this.monthlyChartOptions.title,
                            text: `Leave Request Status by Month (${this.selectedYear})`,
                        },
                    };
                }
            } catch (error) {
                console.error("Error loading monthly chart data:", error);
                this.error = "Failed to load chart data.";
            }
        },

        async loadUserProfile() {
            try {
                const token = localStorage.getItem("auth_token");
                if (!token) {
                    this.$router.push("/login");
                    return { success: false };
                }

                const response = await axios.get("/api/dashboard/profile", {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        Accept: "application/json",
                    },
                });

                return response.data;
            } catch (error) {
                console.error("Error loading user profile:", error);
                if (error.response?.status === 401) {
                    localStorage.removeItem("auth_token");
                    this.$router.push("/login");
                }
                return { success: false, error: error.message };
            }
        },

        async loadDashboardStats() {
            try {
                const token = localStorage.getItem("auth_token");
                const response = await axios.get("/api/dashboard/stats", {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        Accept: "application/json",
                    },
                });

                return response.data;
            } catch (error) {
                console.error("Error loading dashboard stats:", error);
                return { success: false, error: error.message };
            }
        },

        async loadRecentActivities() {
            try {
                const token = localStorage.getItem("auth_token");
                const response = await axios.get("/api/dashboard/activities", {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        Accept: "application/json",
                    },
                    params: {
                        limit: 5,
                    },
                });

                return response.data;
            } catch (error) {
                console.error("Error loading recent activities:", error);
                return { success: false, error: error.message };
            }
        },

        async refreshActivities() {
            this.loadingActivities = true;

            try {
                const response = await this.loadRecentActivities();
                if (response && response.success) {
                    this.recentActivities = response.data || [];
                } else {
                    this.error = "Failed to refresh activities.";
                }
            } catch (error) {
                console.error("Error refreshing activities:", error);
                this.error = "Failed to refresh activities.";
            } finally {
                this.loadingActivities = false;
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

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
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
