<template>
  <div class="leave-request-content">
    <div class="container-fluid">
      <!-- Dashboard Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h1 class="page-title">
            <span class="title-icon">📝</span>
            Leave Request
          </h1>
          <p class="text-muted mb-0">
            Manage your leave requests and track status
          </p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-primary btn-sm" @click="exportData">
            <span class="me-1">📥</span>
            Export
          </button>
          <button class="btn btn-primary btn-sm" @click="showRequestModal = true">
            <span class="me-1">➕</span>
            New Request
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="row mb-4">
        <div class="col-md-3 mb-3">
          <div class="stat-card">
            <div class="stat-icon">📝</div>
            <div class="stat-info">
              <h3>{{ stats.totalRequests }}</h3>
              <p>Total Requests</p>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-info">
              <h3>{{ stats.pendingRequests }}</h3>
              <p>Pending</p>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
              <h3>{{ stats.approvedRequests }}</h3>
              <p>Approved</p>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-info">
              <h3>{{ stats.remainingDays }}</h3>
              <p>Days Remaining</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Request Form Card -->
      <div class="row mb-4">
        <div class="col-md-6 mb-3">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">Quick Request</h5>
            </div>
            <div class="card-body">
              <form @submit.prevent="submitLeaveRequest">
                <div class="mb-3">
                  <label class="form-label">Leave Type</label>
                  <select class="form-select" v-model="newRequest.jenis_cuti" required>
                    <option value="">Select leave type</option>
                    <option value="Annual Leave">Annual Leave</option>
                    <option value="Sick Leave">Sick Leave</option>
                    <option value="Personal Leave">Personal Leave</option>
                    <option value="Emergency Leave">Emergency Leave</option>
                  </select>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" v-model="newRequest.tanggal_mulai" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" v-model="newRequest.tanggal_selesai" required>
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Reason</label>
                  <textarea class="form-control" rows="3" v-model="newRequest.alasan" placeholder="Brief reason for leave..." required></textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label">Attachment (Optional)</label>
                  <input type="file" class="form-control" @change="handleFileUpload" accept=".pdf,.jpg,.png,.jpeg">
                  <small class="text-muted">Max 2MB. Supported: PDF, JPG, PNG, JPEG</small>
                </div>
                <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                  <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                  {{ isSubmitting ? 'Submitting...' : 'Submit Request' }}
                </button>
              </form>
            </div>
          </div>
        </div>
        <div class="col-md-6 mb-3">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">Leave Balance</h5>
            </div>
            <div class="card-body">
              <div class="balance-item">
                <div class="d-flex justify-content-between mb-2">
                  <span>Annual Leave</span>
                  <span class="fw-bold">{{ leaveBalance.annual }} days</span>
                </div>
                <div class="progress mb-3">
                  <div class="progress-bar bg-primary" :style="`width: ${(leaveBalance.annual/25)*100}%`"></div>
                </div>
              </div>
              <div class="balance-item">
                <div class="d-flex justify-content-between mb-2">
                  <span>Sick Leave</span>
                  <span class="fw-bold">{{ leaveBalance.sick }} days</span>
                </div>
                <div class="progress mb-3">
                  <div class="progress-bar bg-warning" :style="`width: ${(leaveBalance.sick/12)*100}%`"></div>
                </div>
              </div>
              <div class="balance-item">
                <div class="d-flex justify-content-between mb-2">
                  <span>Personal Leave</span>
                  <span class="fw-bold">{{ leaveBalance.personal }} days</span>
                </div>
                <div class="progress">
                  <div class="progress-bar bg-info" :style="`width: ${(leaveBalance.personal/5)*100}%`"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- My Requests Table -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">My Leave Requests</h5>
        </div>
        <div class="card-body">
          <div v-if="loading" class="text-center py-4">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
          <div v-else-if="myRequests.length === 0" class="text-center py-4 text-muted">
            No leave requests found
          </div>
          <div v-else class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Request Date</th>
                  <th>Leave Type</th>
                  <th>Start Date</th>
                  <th>End Date</th>
                  <th>Days</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="request in myRequests" :key="request.id">
                  <td>{{ formatDate(request.submitted_at) }}</td>
                  <td>
                    <span class="badge" :class="getLeaveTypeBadge(request.jenis_cuti)">
                      {{ request.jenis_cuti }}
                    </span>
                  </td>
                  <td>{{ formatDate(request.tanggal_mulai) }}</td>
                  <td>{{ formatDate(request.tanggal_selesai) }}</td>
                  <td>{{ calculateDays(request.tanggal_mulai, request.tanggal_selesai) }}</td>
                  <td>
                    <span class="badge" :class="getStatusBadge(request.status || 'Pending')">
                      {{ request.status || 'Pending' }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <button
                        class="btn btn-outline-primary btn-sm"
                        @click="viewRequest(request)"
                        title="View Details"
                      >
                        👁️
                      </button>
                      <button
                        class="btn btn-outline-warning btn-sm"
                        @click="editRequest(request)"
                        :disabled="request.status !== 'Pending' && !request.status"
                        title="Edit Request"
                      >
                        ✏️
                      </button>
                      <button
                        class="btn btn-outline-danger btn-sm"
                        @click="cancelRequest(request.id)"
                        :disabled="request.status !== 'Pending' && !request.status"
                        title="Cancel Request"
                      >
                        ❌
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Audit Trail Table -->
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <span class="me-2">🔍</span>
            Leave Request Audit Trail
          </h5>
          <button class="btn btn-outline-secondary btn-sm" @click="refreshAuditTrail">
            <span class="me-1">🔄</span>
            Refresh
          </button>
        </div>
        <div class="card-body">
          <div v-if="auditLoading" class="text-center py-4">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Loading audit trail...</span>
            </div>
          </div>
          <div v-else-if="auditTrail.length === 0" class="text-center py-4 text-muted">
            No audit records found
          </div>
          <div v-else>
            <div class="table-responsive">
              <table class="table table-striped table-sm">
                <thead>
                  <tr>
                    <th>Date & Time</th>
                    <th>User</th>
                    <th>Event</th>
                    <th>Leave Type</th>
                    <th>Changes</th>
                    <th>IP Address</th>
                    <th>User Agent</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="audit in auditTrail" :key="audit.id">
                    <td>
                      <small class="text-muted">
                        {{ formatDateTime(audit.created_at) }}
                      </small>
                    </td>
                    <td>
                      <span class="badge bg-info">{{ audit.user_name || 'System' }}</span>
                    </td>
                    <td>
                      <span class="badge" :class="getEventBadge(audit.event)">
                        {{ formatEvent(audit.event) }}
                      </span>
                    </td>
                    <td>
                      <small>{{ getAuditLeaveType(audit) }}</small>
                    </td>
                    <td>
                      <div v-if="audit.old_values && audit.new_values" class="audit-changes">
                        <small v-for="(change, key) in getChanges(audit)" :key="key" class="d-block">
                          <strong>{{ formatFieldName(key) }}:</strong>
                          <span class="text-danger">{{ change.old }}</span>
                          →
                          <span class="text-success">{{ change.new }}</span>
                        </small>
                      </div>
                      <small v-else-if="audit.event === 'created'" class="text-muted">
                        New record created
                      </small>
                      <small v-else-if="audit.event === 'deleted'" class="text-muted">
                        Record deleted
                      </small>
                      <small v-else class="text-muted">No changes</small>
                    </td>
                    <td>
                      <small class="text-muted">{{ audit.ip_address || '-' }}</small>
                    </td>
                    <td>
                      <small class="text-muted" :title="audit.user_agent">
                        {{ formatUserAgent(audit.user_agent) }}
                      </small>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
              <div class="text-muted">
                Showing {{ auditMeta.from }} to {{ auditMeta.to }} of {{ auditMeta.total }} entries
              </div>
              <nav>
                <ul class="pagination pagination-sm mb-0">
                  <li class="page-item" :class="{ disabled: auditMeta.current_page === 1 }">
                    <button class="page-link" @click="fetchAuditTrail(auditMeta.current_page - 1)" :disabled="auditMeta.current_page === 1">
                      Previous
                    </button>
                  </li>
                  <li 
                    v-for="page in getPaginationPages()" 
                    :key="page" 
                    class="page-item" 
                    :class="{ active: page === auditMeta.current_page }"
                  >
                    <button class="page-link" @click="fetchAuditTrail(page)">{{ page }}</button>
                  </li>
                  <li class="page-item" :class="{ disabled: auditMeta.current_page === auditMeta.last_page }">
                    <button class="page-link" @click="fetchAuditTrail(auditMeta.current_page + 1)" :disabled="auditMeta.current_page === auditMeta.last_page">
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

    <!-- Alert Messages -->
    <div v-if="alertMessage" :class="`alert alert-${alertType} alert-dismissible fade show position-fixed`" style="top: 20px; right: 20px; z-index: 1050;">
      {{ alertMessage }}
      <button type="button" class="btn-close" @click="clearAlert"></button>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: "LeaveRequestContent",
  data() {
    return {
      showRequestModal: false,
      isSubmitting: false,
      loading: true,
      auditLoading: true,
      alertMessage: '',
      alertType: 'success',
      stats: {
        totalRequests: 0,
        pendingRequests: 0,
        approvedRequests: 0,
        remainingDays: 18
      },
      leaveBalance: {
        annual: 18,
        sick: 8,
        personal: 3
      },
      newRequest: {
        jenis_cuti: '',
        tanggal_mulai: '',
        tanggal_selesai: '',
        alasan: '',
        file_lampiran: null
      },
      editingRequest: null,
      myRequests: [],
      auditTrail: [],
      auditMeta: {
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0,
        total: 0,
        per_page: 10
      }
    };
  },
  
  async mounted() {
    await this.fetchLeaveRequests();
    await this.fetchAuditTrail();
  },

  methods: {
    async fetchLeaveRequests() {
      try {
        this.loading = true;
        const response = await axios.get('/api/leave-requests', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json'
          }
        });
        
        this.myRequests = response.data.data || [];
        this.calculateStats();
      } catch (error) {
        console.error('Error fetching leave requests:', error);
        this.showAlert('Error loading leave requests', 'danger');
      } finally {
        this.loading = false;
      }
    },

    async fetchAuditTrail(page = 1) {
      try {
        this.auditLoading = true;
        const response = await axios.get(`/api/audit/leave-requests?page=${page}&per_page=10`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json'
          }
        });
        
        this.auditTrail = response.data.data || [];
        this.auditMeta = {
          current_page: response.data.current_page || 1,
          last_page: response.data.last_page || 1,
          from: response.data.from || 0,
          to: response.data.to || 0,
          total: response.data.total || 0,
          per_page: response.data.per_page || 10
        };
      } catch (error) {
        console.error('Error fetching audit trail:', error);
        this.showAlert('Error loading audit trail', 'danger');
      } finally {
        this.auditLoading = false;
      }
    },

    refreshAuditTrail() {
      this.fetchAuditTrail(this.auditMeta.current_page);
    },

    getPaginationPages() {
      const current = this.auditMeta.current_page;
      const last = this.auditMeta.last_page;
      const delta = 2;
      const pages = [];
      
      for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
        pages.push(i);
      }
      
      return pages;
    },

    calculateStats() {
      this.stats.totalRequests = this.myRequests.length;
      this.stats.pendingRequests = this.myRequests.filter(r => !r.status || r.status === 'Pending').length;
      this.stats.approvedRequests = this.myRequests.filter(r => r.status === 'Approved').length;
    },

    handleFileUpload(event) {
      const file = event.target.files[0];
      if (file) {
        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
          this.showAlert('File size must be less than 2MB', 'danger');
          event.target.value = '';
          return;
        }
        this.newRequest.file_lampiran = file;
      }
    },

    async submitLeaveRequest() {
      if (!this.newRequest.jenis_cuti || !this.newRequest.tanggal_mulai || !this.newRequest.tanggal_selesai || !this.newRequest.alasan) {
        this.showAlert('Please fill in all required fields', 'danger');
        return;
      }

      this.isSubmitting = true;

      try {
        const formData = new FormData();
        formData.append('jenis_cuti', this.newRequest.jenis_cuti);
        formData.append('tanggal_mulai', this.newRequest.tanggal_mulai);
        formData.append('tanggal_selesai', this.newRequest.tanggal_selesai);
        formData.append('alasan', this.newRequest.alasan);
        
        if (this.newRequest.file_lampiran) {
          formData.append('file_lampiran', this.newRequest.file_lampiran);
        }

        const url = this.editingRequest 
          ? `/api/leave-requests/${this.editingRequest.id}`
          : '/api/leave-requests';
        
        const method = this.editingRequest ? 'put' : 'post';

        const response = await axios({
          method,
          url,
          data: formData,
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Content-Type': 'multipart/form-data',
            'Accept': 'application/json'
          }
        });

        this.showAlert(response.data.message || 'Leave request submitted successfully!', 'success');
        
        // Reset form
        this.resetForm();
        
        // Refresh data
        await this.fetchLeaveRequests();
        await this.fetchAuditTrail();

      } catch (error) {
        console.error('Error submitting request:', error);
        const errorMessage = error.response?.data?.message || 'Error submitting request. Please try again.';
        this.showAlert(errorMessage, 'danger');
      } finally {
        this.isSubmitting = false;
      }
    },

    resetForm() {
      this.newRequest = {
        jenis_cuti: '',
        tanggal_mulai: '',
        tanggal_selesai: '',
        alasan: '',
        file_lampiran: null
      };
      this.editingRequest = null;
      
      // Reset file input
      const fileInput = document.querySelector('input[type="file"]');
      if (fileInput) fileInput.value = '';
    },

    editRequest(request) {
      this.editingRequest = request;
      this.newRequest = {
        jenis_cuti: request.jenis_cuti,
        tanggal_mulai: request.tanggal_mulai,
        tanggal_selesai: request.tanggal_selesai,
        alasan: request.alasan,
        file_lampiran: null
      };
    },

    async viewRequest(request) {
      try {
        const response = await axios.get(`/api/leave-requests/${request.id}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json'
          }
        });
        
        const details = response.data;
        const attachmentInfo = details.file_lampiran ? `\nAttachment: ${details.file_lampiran}` : '';
        
        alert(`Request Details:\n\nType: ${details.jenis_cuti}\nDates: ${details.tanggal_mulai} to ${details.tanggal_selesai}\nDays: ${this.calculateDays(details.tanggal_mulai, details.tanggal_selesai)}\nStatus: ${details.status || 'Pending'}\nReason: ${details.alasan}${attachmentInfo}`);
      } catch (error) {
        console.error('Error fetching request details:', error);
        this.showAlert('Error loading request details', 'danger');
      }
    },

    async cancelRequest(id) {
      if (!confirm('Are you sure you want to cancel this request?')) {
        return;
      }

      try {
        // Since there's no delete endpoint, we'll update the status
        await axios.put(`/api/leave-requests/${id}`, 
          { status: 'Cancelled' },
          {
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
              'Accept': 'application/json',
              'Content-Type': 'application/json'
            }
          }
        );

        this.showAlert('Request cancelled successfully', 'success');
        await this.fetchLeaveRequests();
        await this.fetchAuditTrail();
      } catch (error) {
        console.error('Error cancelling request:', error);
        this.showAlert('Error cancelling request', 'danger');
      }
    },

    exportData() {
      // Simple CSV export
      const csvContent = this.myRequests.map(request => 
        `${request.submitted_at},${request.jenis_cuti},${request.tanggal_mulai},${request.tanggal_selesai},${this.calculateDays(request.tanggal_mulai, request.tanggal_selesai)},${request.status || 'Pending'}`
      ).join('\n');
      
      const blob = new Blob([`Request Date,Leave Type,Start Date,End Date,Days,Status\n${csvContent}`], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'leave_requests.csv';
      a.click();
      window.URL.revokeObjectURL(url);
    },

    // Audit Trail Helper Methods
    formatDateTime(dateString) {
      if (!dateString) return '-';
      return new Date(dateString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    formatEvent(event) {
      const eventMap = {
        'created': 'Created',
        'updated': 'Updated',
        'deleted': 'Deleted',
        'restored': 'Restored'
      };
      return eventMap[event] || event;
    },

    getEventBadge(event) {
      const badges = {
        'created': 'bg-success',
        'updated': 'bg-warning',
        'deleted': 'bg-danger',
        'restored': 'bg-info'
      };
      return badges[event] || 'bg-secondary';
    },

    getAuditLeaveType(audit) {
      if (audit.new_values && audit.new_values.jenis_cuti) {
        return audit.new_values.jenis_cuti;
      }
      if (audit.old_values && audit.old_values.jenis_cuti) {
        return audit.old_values.jenis_cuti;
      }
      return '-';
    },

    getChanges(audit) {
      if (!audit.old_values || !audit.new_values) return {};
      
      const changes = {};
      const oldValues = audit.old_values;
      const newValues = audit.new_values;
      
      Object.keys(newValues).forEach(key => {
        if (oldValues[key] !== newValues[key]) {
          changes[key] = {
            old: oldValues[key] || '-',
            new: newValues[key] || '-'
          };
        }
      });
      
      return changes;
    },

    formatFieldName(fieldName) {
      const fieldMap = {
        'jenis_cuti': 'Leave Type',
        'tanggal_mulai': 'Start Date',
        'tanggal_selesai': 'End Date',
        'alasan': 'Reason',
        'file_lampiran': 'Attachment',
        'status': 'Status',
        'updated_at': 'Updated At'
      };
      return fieldMap[fieldName] || fieldName;
    },

    formatUserAgent(userAgent) {
      if (!userAgent) return '-';
      
      // Extract browser name from user agent
      if (userAgent.includes('Chrome')) return 'Chrome';
      if (userAgent.includes('Firefox')) return 'Firefox';
      if (userAgent.includes('Safari')) return 'Safari';
      if (userAgent.includes('Edge')) return 'Edge';
      
      return 'Other';
    },

    formatDate(dateString) {
      if (!dateString) return '-';
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    calculateDays(startDate, endDate) {
      if (!startDate || !endDate) return 0;
      const start = new Date(startDate);
      const end = new Date(endDate);
      return Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
    },

    getLeaveTypeBadge(type) {
      const badges = {
        'Annual Leave': 'bg-primary',
        'Sick Leave': 'bg-warning',
        'Personal Leave': 'bg-info',
        'Emergency Leave': 'bg-danger'
      };
      return badges[type] || 'bg-secondary';
    },

    getStatusBadge(status) {
      const badges = {
        'Pending': 'bg-warning',
        'Approved': 'bg-success',
        'Rejected': 'bg-danger',
        'Cancelled': 'bg-secondary'
      };
      return badges[status] || 'bg-secondary';
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
  }
};
</script>

<style scoped>
.leave-request-content {
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

.balance-item {
  margin-bottom: 1rem;
}

.balance-item:last-child {
  margin-bottom: 0;
}

.progress {
  height: 8px;
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

.audit-changes {
  max-width: 200px;
}

.audit-changes small {
  font-size: 0.75rem;
  line-height: 1.2;
}

.pagination {
  --bs-pagination-padding-x: 0.5rem;
  --bs-pagination-padding-y: 0.25rem;
  --bs-pagination-font-size: 0.875rem;
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
  
  .audit-changes {
    max-width: 150px;
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