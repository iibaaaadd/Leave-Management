<template>
  <div class="approve-content">
    <div class="container-fluid">
      <!-- Dashboard Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h1 class="page-title">
            <span class="title-icon">✅</span>
            Leave Approvals
          </h1>
          <p class="text-muted mb-0">
            Review and manage employee leave requests
          </p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-primary btn-sm">
            <span class="me-1">📥</span>
            Export
          </button>
          <button class="btn btn-outline-secondary btn-sm" @click="refreshRequests">
            <span class="me-1">🔄</span>
            Refresh
          </button>
        </div>
      </div>
      
      <!-- Content Area -->
      <div class="content-area">
        <!-- Stats Cards -->
        <div class="row mb-4">
          <div class="col-md-3 mb-3">
            <div class="stat-card">
              <div class="stat-icon">👥</div>
              <div class="stat-info">
                <h3>{{ stats.pendingApprovals }}</h3>
                <p>Pending Approvals</p>
              </div>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="stat-card">
              <div class="stat-icon">✅</div>
              <div class="stat-info">
                <h3>{{ stats.approvedToday }}</h3>
                <p>Approved Today</p>
              </div>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="stat-card">
              <div class="stat-icon">❌</div>
              <div class="stat-info">
                <h3>{{ stats.rejectedToday }}</h3>
                <p>Rejected Today</p>
              </div>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="stat-card">
              <div class="stat-icon">📊</div>
              <div class="stat-info">
                <h3>{{ stats.totalRequests }}</h3>
                <p>Total Requests</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Filter Section -->
        <div class="row mb-3">
          <div class="col-md-4">
            <select class="form-select" v-model="filterStatus" @change="filterRequests">
              <option value="">All Status</option>
              <option value="Pending">Pending</option>
              <option value="Approved">Approved</option>
              <option value="Rejected">Rejected</option>
            </select>
          </div>
          <div class="col-md-4">
            <select class="form-select" v-model="filterType" @change="filterRequests">
              <option value="">All Leave Types</option>
              <option value="Annual Leave">Annual Leave</option>
              <option value="Sick Leave">Sick Leave</option>
              <option value="Personal Leave">Personal Leave</option>
              <option value="Emergency Leave">Emergency Leave</option>
            </select>
          </div>
          <div class="col-md-4">
            <input 
              type="text" 
              class="form-control" 
              placeholder="Search employee..." 
              v-model="searchEmployee"
              @input="filterRequests"
            >
          </div>
        </div>
        
        <!-- Approval Table -->
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Leave Approval Requests</h5>
            <small class="text-muted">{{ filteredRequests.length }} requests</small>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Days</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="request in filteredRequests" :key="request.id">
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar me-2">{{ request.employee.charAt(0) }}</div>
                        <div>
                          <div class="fw-medium">{{ request.employee }}</div>
                          <small class="text-muted">{{ request.department }}</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge" :class="getLeaveTypeBadge(request.type)">
                        {{ request.type }}
                      </span>
                    </td>
                    <td>{{ formatDate(request.startDate) }}</td>
                    <td>{{ formatDate(request.endDate) }}</td>
                    <td>{{ request.days }}</td>
                    <td>
                      <span 
                        :title="request.reason" 
                        class="reason-text"
                      >
                        {{ request.reason || 'No reason provided' }}
                      </span>
                    </td>
                    <td>
                      <span class="badge" :class="getStatusBadge(request.status)">
                        {{ request.status }}
                      </span>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm">
                        <button 
                          class="btn btn-success btn-sm"
                          @click="approveRequest(request.id)"
                          :disabled="request.status !== 'Pending' || isProcessing"
                          title="Approve Request"
                        >
                          ✅ Approve
                        </button>
                        <button 
                          class="btn btn-danger btn-sm"
                          @click="rejectRequest(request.id)"
                          :disabled="request.status !== 'Pending' || isProcessing"
                          title="Reject Request"
                        >
                          ❌ Reject
                        </button>
                        <button 
                          class="btn btn-outline-info btn-sm"
                          @click="viewDetails(request)"
                          title="View Details"
                        >
                          👁️
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="filteredRequests.length === 0">
                    <td colspan="8" class="text-center text-muted py-4">
                      No leave requests found
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="row mt-4">
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
              </div>
              <div class="card-body">
                <div class="d-grid gap-2">
                  <button 
                    class="btn btn-success"
                    @click="approveAllPending"
                    :disabled="pendingCount === 0 || isProcessing"
                  >
                    ✅ Approve All Pending ({{ pendingCount }})
                  </button>
                  <button class="btn btn-outline-primary" @click="exportRequests">
                    📊 Export Report
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Today's Summary</h5>
              </div>
              <div class="card-body">
                <div class="summary-item">
                  <span>Requests Reviewed:</span>
                  <span class="fw-bold">{{ stats.approvedToday + stats.rejectedToday }}</span>
                </div>
                <div class="summary-item">
                  <span>Approval Rate:</span>
                  <span class="fw-bold">{{ approvalRate }}%</span>
                </div>
                <div class="summary-item">
                  <span>Average Response Time:</span>
                  <span class="fw-bold">2.5 hours</span>
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
  name: "LeaveApprovalContent",
  data() {
    return {
      isProcessing: false,
      filterStatus: '',
      filterType: '',
      searchEmployee: '',
      stats: {
        pendingApprovals: 12,
        approvedToday: 8,
        rejectedToday: 2,
        totalRequests: 156,
      },
      leaveRequests: [
        {
          id: 1,
          employee: "John Smith",
          department: "Engineering",
          type: "Annual Leave",
          startDate: "2024-08-15",
          endDate: "2024-08-20",
          days: 5,
          reason: "Family vacation to Bali",
          status: "Pending"
        },
        {
          id: 2,
          employee: "Sarah Johnson",
          department: "Marketing",
          type: "Sick Leave",
          startDate: "2024-08-18",
          endDate: "2024-08-19",
          days: 2,
          reason: "Medical checkup",
          status: "Pending"
        },
        {
          id: 3,
          employee: "Mike Davis",
          department: "Sales",
          type: "Personal Leave",
          startDate: "2024-08-22",
          endDate: "2024-08-23",
          days: 2,
          reason: "Personal matters",
          status: "Approved"
        },
        {
          id: 4,
          employee: "Emily Brown",
          department: "HR",
          type: "Emergency Leave",
          startDate: "2024-08-10",
          endDate: "2024-08-11",
          days: 2,
          reason: "Family emergency",
          status: "Rejected"
        },
        {
          id: 5,
          employee: "David Wilson",
          department: "Engineering",
          type: "Annual Leave",
          startDate: "2024-09-01",
          endDate: "2024-09-05",
          days: 5,
          reason: "Wedding ceremony",
          status: "Pending"
        }
      ],
      filteredRequests: []
    };
  },
  computed: {
    pendingCount() {
      return this.leaveRequests.filter(r => r.status === 'Pending').length;
    },
    approvalRate() {
      const total = this.stats.approvedToday + this.stats.rejectedToday;
      return total === 0 ? 0 : Math.round((this.stats.approvedToday / total) * 100);
    }
  },
  mounted() {
    this.filteredRequests = [...this.leaveRequests];
  },
  methods: {
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },
    
    getLeaveTypeBadge(type) {
      const badges = {
        'Annual Leave': 'bg-primary',
        'Sick Leave': 'bg-warning text-dark',
        'Personal Leave': 'bg-info',
        'Emergency Leave': 'bg-danger'
      };
      return badges[type] || 'bg-secondary';
    },
    
    getStatusBadge(status) {
      const badges = {
        'Pending': 'bg-warning text-dark',
        'Approved': 'bg-success',
        'Rejected': 'bg-danger'
      };
      return badges[status] || 'bg-secondary';
    },
    
    filterRequests() {
      let filtered = [...this.leaveRequests];
      
      if (this.filterStatus) {
        filtered = filtered.filter(r => r.status === this.filterStatus);
      }
      
      if (this.filterType) {
        filtered = filtered.filter(r => r.type === this.filterType);
      }
      
      if (this.searchEmployee) {
        filtered = filtered.filter(r => 
          r.employee.toLowerCase().includes(this.searchEmployee.toLowerCase()) ||
          r.department.toLowerCase().includes(this.searchEmployee.toLowerCase())
        );
      }
      
      this.filteredRequests = filtered;
    },
    
    async approveRequest(id) {
      if (!confirm('Are you sure you want to approve this request?')) return;
      
      this.isProcessing = true;
      
      try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        const request = this.leaveRequests.find(r => r.id === id);
        if (request && request.status === 'Pending') {
          request.status = 'Approved';
          this.stats.pendingApprovals--;
          this.stats.approvedToday++;
          this.filterRequests(); // Refresh filtered list
        }
        
        alert('Leave request approved successfully!');
        
      } catch (error) {
        console.error('Error approving request:', error);
        alert('Error approving request. Please try again.');
      } finally {
        this.isProcessing = false;
      }
    },
    
    async rejectRequest(id) {
      if (!confirm('Are you sure you want to reject this request?')) return;
      
      this.isProcessing = true;
      
      try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        const request = this.leaveRequests.find(r => r.id === id);
        if (request && request.status === 'Pending') {
          request.status = 'Rejected';
          this.stats.pendingApprovals--;
          this.stats.rejectedToday++;
          this.filterRequests(); // Refresh filtered list
        }
        
        alert('Leave request rejected successfully!');
        
      } catch (error) {
        console.error('Error rejecting request:', error);
        alert('Error rejecting request. Please try again.');
      } finally {
        this.isProcessing = false;
      }
    },
    
    viewDetails(request) {
      const details = `
Request Details:

Employee: ${request.employee}
Department: ${request.department}
Leave Type: ${request.type}
Start Date: ${this.formatDate(request.startDate)}
End Date: ${this.formatDate(request.endDate)}
Duration: ${request.days} days
Reason: ${request.reason || 'No reason provided'}
Status: ${request.status}
      `;
      alert(details);
    },
    
    async approveAllPending() {
      if (!confirm(`Are you sure you want to approve all ${this.pendingCount} pending requests?`)) return;
      
      this.isProcessing = true;
      
      try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        const pendingRequests = this.leaveRequests.filter(r => r.status === 'Pending');
        pendingRequests.forEach(request => {
          request.status = 'Approved';
          this.stats.approvedToday++;
        });
        
        this.stats.pendingApprovals = 0;
        this.filterRequests(); // Refresh filtered list
        
        alert(`Successfully approved ${pendingRequests.length} requests!`);
        
      } catch (error) {
        console.error('Error approving all requests:', error);
        alert('Error approving requests. Please try again.');
      } finally {
        this.isProcessing = false;
      }
    },
    
    exportRequests() {
      // Create CSV content
      const headers = ['Employee', 'Department', 'Leave Type', 'Start Date', 'End Date', 'Days', 'Reason', 'Status'];
      const csvContent = [
        headers.join(','),
        ...this.filteredRequests.map(request => [
          `"${request.employee}"`,
          `"${request.department}"`,
          request.type,
          request.startDate,
          request.endDate,
          request.days,
          `"${request.reason || 'No reason provided'}"`,
          request.status
        ].join(','))
      ].join('\n');
      
      // Download CSV
      const blob = new Blob([csvContent], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `leave_requests_${new Date().toISOString().split('T')[0]}.csv`;
      a.click();
      window.URL.revokeObjectURL(url);
    },
    
  }
};
</script>

<style scoped>
.approve-content {
  padding: 0;
  animation: fadeIn 0.5s ease-out;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 600;
  color: #2c3e50;
  margin: 0;
}

.title-icon {
  margin-right: 0.5rem;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  display: flex;
  align-items: center;
  transition: transform 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.stat-icon {
  font-size: 2rem;
  margin-right: 1rem;
}

.stat-info h3 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
  color: #2c3e50;
}

.stat-info p {
  margin: 0;
  color: #6c757d;
  font-size: 0.875rem;
}

.card {
  border: none;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  border-radius: 8px;
}

.card-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  border-radius: 8px 8px 0 0 !important;
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background-color: #007bff;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.875rem;
}

.table th {
  border-top: none;
  font-weight: 600;
  color: #495057;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

.reason-text {
  max-width: 150px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  cursor: help;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  padding: 0.25rem 0;
}

.summary-item:last-child {
  margin-bottom: 0;
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
  .d-flex.justify-content-between {
    flex-direction: column;
    gap: 1rem;
  }
  
  .stat-card {
    padding: 1rem;
  }
  
  .table-responsive {
    font-size: 0.875rem;
  }
  
  .reason-text {
    max-width: 100px;
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