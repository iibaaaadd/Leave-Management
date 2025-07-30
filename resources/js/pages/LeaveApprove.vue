<template>
    <div class="approve-content">
        <div class="container-fluid">
            <!-- Dashboard Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="page-title">
                        <span class="title-icon">✅</span>
                        {{ isAdmin ? "Leave Approvals" : "My Leave Requests" }}
                    </h1>
                    <p class="text-muted mb-0">
                        {{
                            isAdmin
                                ? "Review and manage employee leave requests"
                                : "View your leave request status"
                        }}
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button
                        class="btn btn-outline-primary btn-sm"
                        @click="exportRequests"
                    >
                        <span class="me-1">📥</span>
                        Export
                    </button>
                    <button
                        class="btn btn-outline-secondary btn-sm"
                        @click="refreshRequests"
                        :disabled="loading"
                    >
                        <span class="me-1">🔄</span>
                        {{ loading ? "Loading..." : "Refresh" }}
                    </button>
                </div>
            </div>

            <!-- Alert Messages -->
            <div
                v-if="alertMessage"
                :class="`alert alert-${alertType} alert-dismissible fade show`"
                role="alert"
            >
                {{ alertMessage }}
                <button
                    type="button"
                    class="btn-close"
                    @click="clearAlert"
                ></button>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon">👥</div>
                            <div class="stat-info">
                                <h3>{{ stats.pending_approvals || 0 }}</h3>
                                <p>
                                    {{
                                        isAdmin
                                            ? "Pending Approvals"
                                            : "Pending Requests"
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon">✅</div>
                            <div class="stat-info">
                                <h3>{{ stats.approved_today || 0 }}</h3>
                                <p>
                                    {{
                                        isAdmin
                                            ? "Approved Today"
                                            : "Approved Today"
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon">❌</div>
                            <div class="stat-info">
                                <h3>{{ stats.rejected_today || 0 }}</h3>
                                <p>
                                    {{
                                        isAdmin
                                            ? "Rejected Today"
                                            : "Rejected Today"
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon">📊</div>
                            <div class="stat-info">
                                <h3>{{ stats.total_requests || 0 }}</h3>
                                <p>
                                    {{
                                        isAdmin
                                            ? "Total Requests"
                                            : "My Total Requests"
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select
                            class="form-select"
                            v-model="filterStatus"
                            @change="filterRequests"
                        >
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select
                            class="form-select"
                            v-model="filterType"
                            @change="filterRequests"
                        >
                            <option value="">All Leave Types</option>
                            <option value="Annual Leave">Annual Leave</option>
                            <option value="Sick Leave">Sick Leave</option>
                            <option value="Personal Leave">
                                Personal Leave
                            </option>
                            <option value="Emergency Leave">
                                Emergency Leave
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Search employee..."
                            v-model="searchEmployee"
                            @input="filterRequests"
                        />
                    </div>
                </div>

                <!-- Approval Table -->
                <div class="card">
                    <div
                        class="card-header d-flex justify-content-between align-items-center"
                    >
                        <h5 class="mb-0">Leave Approval Requests</h5>
                        <small class="text-muted"
                            >{{ filteredRequests.length }} requests</small
                        >
                    </div>
                    <div class="card-body">
                        <div v-if="loading" class="text-center py-4">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading requests...</p>
                        </div>
                        <div v-else class="table-responsive">
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
                                        <th v-if="isAdmin">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="request in filteredRequests"
                                        :key="request.id"
                                    >
                                        <td>
                                            <div
                                                class="d-flex align-items-center"
                                            >
                                                <div>
                                                    <div class="fw-medium">
                                                        {{
                                                            request.employee_name
                                                        }}
                                                    </div>
                                                    <small class="text-muted">{{
                                                        request.jabatan || "N/A"
                                                    }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span
                                                class="badge"
                                                :class="
                                                    getLeaveTypeBadge(
                                                        request.jenis_cuti
                                                    )
                                                "
                                            >
                                                {{ request.jenis_cuti }}
                                            </span>
                                        </td>
                                        <td>
                                            {{
                                                formatDate(
                                                    request.tanggal_mulai
                                                )
                                            }}
                                        </td>
                                        <td>
                                            {{
                                                formatDate(
                                                    request.tanggal_selesai
                                                )
                                            }}
                                        </td>
                                        <td>
                                            {{
                                                calculateDays(
                                                    request.tanggal_mulai,
                                                    request.tanggal_selesai
                                                )
                                            }}
                                        </td>
                                        <td>
                                            <span
                                                :title="request.alasan"
                                                class="reason-text"
                                            >
                                                {{
                                                    request.alasan ||
                                                    "No reason provided"
                                                }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge"
                                                :class="
                                                    getStatusBadge(
                                                        request.approval_status
                                                    )
                                                "
                                            >
                                                {{
                                                    formatStatus(
                                                        request.approval_status
                                                    )
                                                }}
                                            </span>
                                        </td>
                                        <td v-if="isAdmin">
                                            <div class="btn-group btn-group-sm">
                                                <button
                                                    class="btn btn-success btn-sm"
                                                    @click="
                                                        showApprovalModal(
                                                            request,
                                                            true
                                                        )
                                                    "
                                                    :disabled="
                                                        request.approval_status !==
                                                            'pending' ||
                                                        isProcessing
                                                    "
                                                    title="Approve Request"
                                                >
                                                    ✅ Approve
                                                </button>
                                                <button
                                                    class="btn btn-danger btn-sm"
                                                    @click="
                                                        showApprovalModal(
                                                            request,
                                                            false
                                                        )
                                                    "
                                                    :disabled="
                                                        request.approval_status !==
                                                            'pending' ||
                                                        isProcessing
                                                    "
                                                    title="Reject Request"
                                                >
                                                    ❌ Reject
                                                </button>
                                                <button
                                                    class="btn btn-outline-info btn-sm"
                                                    @click="
                                                        viewDetails(request)
                                                    "
                                                    title="View Details"
                                                >
                                                    👁️
                                                </button>
                                                <button
                                                    v-if="request.file_lampiran"
                                                    class="btn btn-outline-secondary btn-sm"
                                                    @click="
                                                        viewAttachment(request)
                                                    "
                                                    title="View Attachment"
                                                >
                                                    📎
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="
                                            !loading &&
                                            filteredRequests.length === 0
                                        "
                                    >
                                        <td
                                            colspan="8"
                                            class="text-center text-muted py-4"
                                        >
                                            No leave requests found
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="row mt-4" v-if="isAdmin">
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
                                        :disabled="
                                            pendingCount === 0 || isProcessing
                                        "
                                    >
                                        ✅ Approve All Pending ({{
                                            pendingCount
                                        }})
                                    </button>
                                    <button
                                        class="btn btn-outline-primary"
                                        @click="exportRequests"
                                    >
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
                                    <span class="fw-bold">{{
                                        (stats.approved_today || 0) +
                                        (stats.rejected_today || 0)
                                    }}</span>
                                </div>
                                <div class="summary-item">
                                    <span>Approval Rate:</span>
                                    <span class="fw-bold"
                                        >{{ approvalRate }}%</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Audit Logs DataTable -->
                <div class="row mt-4" v-if="isAdmin">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">
                                        <span class="me-2">📋</span>
                                        Audit Logs
                                    </h5>
                                    <small class="text-muted">
                                        Track all approval activities and changes
                                    </small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button
                                        class="btn btn-outline-secondary btn-sm"
                                        @click="toggleAuditTable"
                                    >
                                        <span class="me-1">
                                            {{ showAuditTable ? '🔼' : '🔽' }}
                                        </span>
                                        {{ showAuditTable ? 'Hide' : 'Show' }}
                                    </button>
                                    <button
                                        class="btn btn-outline-primary btn-sm"
                                        @click="fetchAuditLogs(1)"
                                        :disabled="auditLoading"
                                    >
                                        <span class="me-1">🔄</span>
                                        {{ auditLoading ? 'Loading...' : 'Refresh' }}
                                    </button>
                                </div>
                            </div>
                            <div class="card-body" v-show="showAuditTable">
                                <!-- Audit Filters -->
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <select class="form-select form-select-sm" v-model="auditEventFilter" @change="filterAuditLogs">
                                            <option value="">All Events</option>
                                            <option value="created">Created</option>
                                            <option value="updated">Updated</option>
                                            <option value="deleted">Deleted</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input
                                            type="text"
                                            class="form-control form-control-sm"
                                            placeholder="Search user..."
                                            v-model="auditUserFilter"
                                            @input="filterAuditLogs"
                                        />
                                    </div>
                                    <div class="col-md-3">
                                        <input
                                            type="text"
                                            class="form-control form-control-sm"
                                            placeholder="Search employee..."
                                            v-model="auditEmployeeFilter"
                                            @input="filterAuditLogs"
                                        />
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-select form-select-sm" v-model="auditStatusFilter" @change="filterAuditLogs">
                                            <option value="">All Status</option>
                                            <option value="approved">Approved</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Loading State -->
                                <div v-if="auditLoading" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading audit logs...</span>
                                    </div>
                                    <p class="mt-2 mb-0">Loading audit logs...</p>
                                </div>

                                <!-- Audit Table -->
                                <div v-else class="table-responsive">
                                    <table class="table table-sm table-hover audit-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 80px;">Event</th>
                                                <th style="width: 120px;">User</th>
                                                <th style="width: 150px;">Employee</th>
                                                <th style="width: 100px;">Leave Type</th>
                                                <th style="width: 80px;">Status</th>
                                                <th style="width: 100px;">IP Address</th>
                                                <th style="width: 80px;">Browser</th>
                                                <th style="width: 120px;">Date</th>
                                                <th style="width: 60px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="audit in filteredAuditLogs" :key="audit.id" class="audit-row">
                                                <td>
                                                    <span class="badge" :class="getEventBadge(audit.event)">
                                                        {{ formatAuditEvent(audit.event) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="user-info">
                                                        <div class="fw-medium">{{ audit.user_name }}</div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="employee-info">
                                                        <div class="fw-medium">{{ audit.employee_name }}</div>
                                                        <small class="text-muted">{{ audit.leave_type }}</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge" :class="getLeaveTypeBadge(audit.leave_type)">
                                                        {{ audit.leave_type }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge" :class="getStatusBadge(audit.approval_status)" v-if="audit.approval_status !== 'N/A'">
                                                        {{ audit.approval_status }}
                                                    </span>
                                                    <span v-else class="text-muted">-</span>
                                                </td>
                                                <td>
                                                    <small class="text-muted font-monospace">
                                                        {{ audit.ip_address || 'N/A' }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ formatUserAgent(audit.user_agent) }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="date-info">
                                                        <div class="fw-medium">{{ formatDate(audit.created_at) }}</div>
                                                        <small class="text-muted">{{ formatTime(audit.created_at) }}</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <button
                                                        class="btn btn-outline-info btn-sm"
                                                        @click="viewAuditDetail(audit)"
                                                        title="View Details"
                                                    >
                                                        👁️
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="!auditLoading && filteredAuditLogs.length === 0">
                                                <td colspan="9" class="text-center text-muted py-4">
                                                    <div class="empty-state">
                                                        <span class="display-6">📋</span>
                                                        <p class="mt-2 mb-0">No audit logs found</p>
                                                        <small class="text-muted">Audit logs will appear here when approval activities occur</small>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <div v-if="auditPagination.last_page > 1" class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="pagination-info">
                                        <small class="text-muted">
                                            Showing {{ auditPagination.from || 0 }} to {{ auditPagination.to || 0 }} 
                                            of {{ auditPagination.total || 0 }} entries
                                        </small>
                                    </div>
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0">
                                            <li class="page-item" :class="{ disabled: auditPagination.current_page <= 1 }">
                                                <button class="page-link" @click="changeAuditPage(auditPagination.current_page - 1)">
                                                    Previous
                                                </button>
                                            </li>
                                            <li 
                                                v-for="page in getVisiblePages(auditPagination.current_page, auditPagination.last_page)" 
                                                :key="page"
                                                class="page-item" 
                                                :class="{ active: page === auditPagination.current_page }"
                                            >
                                                <button class="page-link" @click="changeAuditPage(page)">
                                                    {{ page }}
                                                </button>
                                            </li>
                                            <li class="page-item" :class="{ disabled: auditPagination.current_page >= auditPagination.last_page }">
                                                <button class="page-link" @click="changeAuditPage(auditPagination.current_page + 1)">
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
            </div>
        </div>

        <!-- Approval Modal -->
        <div class="modal fade" id="approvalModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{
                                currentApproval.isApprove ? "Approve" : "Reject"
                            }}
                            Leave Request
                        </h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="currentApproval.request">
                            <div class="mb-3">
                                <strong>Employee:</strong>
                                {{ currentApproval.request.employee_name }}
                            </div>
                            <div class="mb-3">
                                <strong>Jabatan:</strong>
                                {{ currentApproval.request.jabatan }}
                            </div>
                            <div class="mb-3">
                                <strong>Leave Type:</strong>
                                {{ currentApproval.request.jenis_cuti }}
                            </div>
                            <div class="mb-3">
                                <strong>Duration:</strong>
                                {{
                                    formatDate(
                                        currentApproval.request.tanggal_mulai
                                    )
                                }}
                                -
                                {{
                                    formatDate(
                                        currentApproval.request.tanggal_selesai
                                    )
                                }}
                                ({{
                                    calculateDays(
                                        currentApproval.request.tanggal_mulai,
                                        currentApproval.request.tanggal_selesai
                                    )
                                }}
                                days)
                            </div>
                            <div class="mb-3">
                                <strong>Reason:</strong>
                                {{
                                    currentApproval.request.alasan ||
                                    "No reason provided"
                                }}
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    {{
                                        currentApproval.isApprove
                                            ? "Approval Notes (Optional)"
                                            : "Rejection Reason"
                                    }}
                                    {{ !currentApproval.isApprove ? "*" : "" }}
                                </label>
                                <textarea
                                    class="form-control"
                                    v-model="currentApproval.notes"
                                    rows="3"
                                    :placeholder="
                                        currentApproval.isApprove
                                            ? 'Add any notes for approval...'
                                            : 'Please provide reason for rejection...'
                                    "
                                    :required="!currentApproval.isApprove"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :class="`btn ${
                                currentApproval.isApprove
                                    ? 'btn-success'
                                    : 'btn-danger'
                            }`"
                            @click="processApproval"
                            :disabled="
                                isProcessing ||
                                (!currentApproval.isApprove &&
                                    !currentApproval.notes.trim())
                            "
                        >
                            <span
                                v-if="isProcessing"
                                class="spinner-border spinner-border-sm me-2"
                            ></span>
                            {{
                                isProcessing
                                    ? "Processing..."
                                    : currentApproval.isApprove
                                    ? "Approve"
                                    : "Reject"
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Modal -->
        <div class="modal fade" id="detailsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Leave Request Details</h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="selectedRequest" class="row">
                            <div class="col-md-6">
                                <h6>Employee Information</h6>
                                <table class="table table-sm">
                                    <tbody>
                                        <tr>
                                            <td><strong>Name:</strong></td>
                                            <td>
                                                {{
                                                    selectedRequest.employee_name
                                                }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Jabatan:</strong></td>
                                            <td>
                                                {{
                                                    selectedRequest.jabatan ||
                                                    "N/A"
                                                }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Submitted:</strong></td>
                                            <td>
                                                {{
                                                    formatDateTime(
                                                        selectedRequest.submitted_at ||
                                                            selectedRequest.created_at
                                                    )
                                                }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Leave Information</h6>
                                <table class="table table-sm">
                                    <tbody>
                                        <tr>
                                            <td><strong>Type:</strong></td>
                                            <td>
                                                <span
                                                    class="badge"
                                                    :class="
                                                        getLeaveTypeBadge(
                                                            selectedRequest.jenis_cuti
                                                        )
                                                    "
                                                >
                                                    {{
                                                        selectedRequest.jenis_cuti
                                                    }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <strong>Start Date:</strong>
                                            </td>
                                            <td>
                                                {{
                                                    formatDate(
                                                        selectedRequest.tanggal_mulai
                                                    )
                                                }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>End Date:</strong></td>
                                            <td>
                                                {{
                                                    formatDate(
                                                        selectedRequest.tanggal_selesai
                                                    )
                                                }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Duration:</strong></td>
                                            <td>
                                                {{
                                                    calculateDays(
                                                        selectedRequest.tanggal_mulai,
                                                        selectedRequest.tanggal_selesai
                                                    )
                                                }}
                                                days
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <span
                                                    class="badge"
                                                    :class="
                                                        getStatusBadge(
                                                            selectedRequest.approval_status
                                                        )
                                                    "
                                                >
                                                    {{
                                                        formatStatus(
                                                            selectedRequest.approval_status
                                                        )
                                                    }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-12 mt-3">
                                <h6>Reason</h6>
                                <p class="border p-2 rounded">
                                    {{
                                        selectedRequest.alasan ||
                                        "No reason provided"
                                    }}
                                </p>
                            </div>
                            <div
                                v-if="selectedRequest.approval_notes"
                                class="col-12 mt-3"
                            >
                                <h6>Approval Notes</h6>
                                <p class="border p-2 rounded">
                                    {{ selectedRequest.approval_notes }}
                                </p>
                            </div>
                            <div
                                v-if="selectedRequest.approved_by"
                                class="col-12 mt-3"
                            >
                                <h6>Approval Information</h6>
                                <p>
                                    <strong>Approved by:</strong>
                                    {{ selectedRequest.approved_by }}
                                </p>
                                <p>
                                    <strong>Date:</strong>
                                    {{
                                        formatDateTime(
                                            selectedRequest.approval_date
                                        )
                                    }}
                                </p>
                            </div>
                            <div
                                v-if="selectedRequest.file_lampiran"
                                class="col-12 mt-3"
                            >
                                <h6>Attachment</h6>
                                <button
                                    class="btn btn-outline-primary btn-sm"
                                    @click="viewAttachment(selectedRequest)"
                                >
                                    📎 View PDF Attachment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audit Detail Modal -->
        <div class="modal fade" id="auditDetailModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <span class="me-2">📋</span>
                            Audit Log Details
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="selectedAudit" class="row">
                            <div class="col-md-6">
                                <h6>Audit Information</h6>
                                <table class="table table-sm">
                                    <tbody>
                                        <tr>
                                            <td><strong>Event:</strong></td>
                                            <td>
                                                <span class="badge" :class="getEventBadge(selectedAudit.event)">
                                                    {{ formatAuditEvent(selectedAudit.event) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>User:</strong></td>
                                            <td>{{ selectedAudit.user_name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Date:</strong></td>
                                            <td>{{ formatDateTime(selectedAudit.created_at) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>IP Address:</strong></td>
                                            <td class="font-monospace">{{ selectedAudit.ip_address || 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Browser:</strong></td>
                                            <td>{{ formatUserAgent(selectedAudit.user_agent) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Leave Request Information</h6>
                                <table class="table table-sm">
                                    <tbody>
                                        <tr>
                                            <td><strong>Employee:</strong></td>
                                            <td>{{ selectedAudit.employee_name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Leave Type:</strong></td>
                                            <td>
                                                <span class="badge" :class="getLeaveTypeBadge(selectedAudit.leave_type)">
                                                    {{ selectedAudit.leave_type }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <span class="badge" :class="getStatusBadge(selectedAudit.approval_status)" v-if="selectedAudit.approval_status !== 'N/A'">
                                                    {{ selectedAudit.approval_status }}
                                                </span>
                                                <span v-else class="text-muted">N/A</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-12 mt-3" v-if="selectedAudit.old_values && Object.keys(selectedAudit.old_values).length > 0">
                                <h6>Previous Values</h6>
                                <div class="border p-2 rounded bg-light">
                                    <pre class="mb-0 small">{{ JSON.stringify(selectedAudit.old_values, null, 2) }}</pre>
                                </div>
                            </div>
                            <div class="col-12 mt-3" v-if="selectedAudit.new_values && Object.keys(selectedAudit.new_values).length > 0">
                                <h6>New Values</h6>
                                <div class="border p-2 rounded bg-light">
                                    <pre class="mb-0 small">{{ JSON.stringify(selectedAudit.new_values, null, 2) }}</pre>
                                </div>
                            </div>
                            <div class="col-12 mt-3" v-if="selectedAudit.approval_data">
                                <h6>Approval Details</h6>
                                <div class="border p-2 rounded">
                                    <p><strong>Approval ID:</strong> {{ selectedAudit.approval_data.id }}</p>
                                    <p><strong>Status:</strong> {{ selectedAudit.approval_data.status_approve ? 'Approved' : 'Rejected' }}</p>
                                    <p><strong>Notes:</strong> {{ selectedAudit.approval_data.catatan || 'No notes' }}</p>
                                    <p><strong>Approver:</strong> {{ selectedAudit.approval_data.nama_approver_snapshot }}</p>
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
import axios from "axios";
import { Modal } from "bootstrap";

export default {
    name: "LeaveApprovalContent",
    data() {
        return {
            loading: true,
            isProcessing: false,
            alertMessage: "",
            alertType: "success",
            filterStatus: "",
            filterType: "",
            searchEmployee: "",
            stats: {
                pending_approvals: 0,
                approved_today: 0,
                rejected_today: 0,
                total_requests: 0,
            },
            leaveRequests: [],
            filteredRequests: [],
            selectedRequest: null,
            currentApproval: {
                request: null,
                isApprove: true,
                notes: "",
            },
            // Audit logs data
            auditLogs: [],
            filteredAuditLogs: [],
            auditLoading: false,
            auditPagination: {
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0,
            },
            selectedAudit: null,
            showAuditTable: true,
            // Audit filters
            auditEventFilter: "",
            auditUserFilter: "",
            auditEmployeeFilter: "",
            auditStatusFilter: "",
        };
    },
    computed: {
        userRole() {
            const userData = JSON.parse(
                localStorage.getItem("user_data") || "{}"
            );
            return userData.role || "user";
        },
        isAdmin() {
            return this.userRole === "admin";
        },
        currentUserId() {
            const userData = JSON.parse(
                localStorage.getItem("user_data") || "{}"
            );
            return userData.id;
        },
        pendingCount() {
            return this.leaveRequests.filter(
                (r) => r.approval_status === "pending"
            ).length;
        },
        approvalRate() {
            const total =
                (this.stats.approved_today || 0) +
                (this.stats.rejected_today || 0);
            return total === 0
                ? 0
                : Math.round(((this.stats.approved_today || 0) / total) * 100);
        },
    },
    async mounted() {
        await this.fetchLeaveRequests();
        await this.fetchStats();
        await this.fetchAuditLogs();
    },
    methods: {
        async fetchAuditLogs(page = 1) {
            try {
                this.auditLoading = true;
                const response = await axios.get(
                    "/api/leave-approvals/audit-logs",
                    {
                        params: {
                            page: page,
                            per_page: this.auditPagination.per_page,
                        },
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "auth_token"
                            )}`,
                            Accept: "application/json",
                        },
                    }
                );
                this.auditLogs = response.data.data || [];
                this.filteredAuditLogs = [...this.auditLogs];
                this.auditPagination =
                    response.data.pagination || this.auditPagination;
            } catch (error) {
                console.error("Error fetching audit logs:", error);
                this.showAlert(
                    "Error loading audit logs: " +
                        (error.response?.data?.message || error.message),
                    "warning"
                );
            } finally {
                this.auditLoading = false;
            }
        },

        async viewAuditDetail(audit) {
            try {
                const response = await axios.get(
                    `/api/leave-approvals/audit-logs/${audit.id}`,
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "auth_token"
                            )}`,
                            Accept: "application/json",
                        },
                    }
                );
                this.selectedAudit = response.data.data;
                const modal = new Modal(
                    document.getElementById("auditDetailModal")
                );
                modal.show();
            } catch (error) {
                console.error("Error fetching audit detail:", error);
                this.showAlert(
                    "Error loading audit detail: " +
                        (error.response?.data?.message || error.message),
                    "danger"
                );
            }
        },

        filterAuditLogs() {
            let filtered = [...this.auditLogs];

            if (this.auditEventFilter) {
                filtered = filtered.filter(
                    (audit) => audit.event === this.auditEventFilter
                );
            }

            if (this.auditUserFilter) {
                filtered = filtered.filter((audit) =>
                    audit.user_name
                        .toLowerCase()
                        .includes(this.auditUserFilter.toLowerCase())
                );
            }

            if (this.auditEmployeeFilter) {
                filtered = filtered.filter((audit) =>
                    audit.employee_name
                        .toLowerCase()
                        .includes(this.auditEmployeeFilter.toLowerCase())
                );
            }

            if (this.auditStatusFilter) {
                filtered = filtered.filter(
                    (audit) => audit.approval_status === this.auditStatusFilter
                );
            }

            this.filteredAuditLogs = filtered;
        },

        getEventBadge(event) {
            const badges = {
                created: "bg-success",
                updated: "bg-warning text-dark",
                deleted: "bg-danger",
            };
            return badges[event] || "bg-secondary";
        },

        formatAuditEvent(event) {
            const eventMap = {
                created: "Created",
                updated: "Updated",
                deleted: "Deleted",
            };
            return eventMap[event] || event;
        },

        formatUserAgent(userAgent) {
            if (!userAgent) return "N/A";
            if (userAgent.includes("Chrome")) return "Chrome";
            if (userAgent.includes("Firefox")) return "Firefox";
            if (userAgent.includes("Safari")) return "Safari";
            if (userAgent.includes("Edge")) return "Edge";
            return "Other";
        },

        formatTime(dateString) {
            if (!dateString) return "-";
            return new Date(dateString).toLocaleTimeString("en-US", {
                hour: "2-digit",
                minute: "2-digit",
            });
        },

        changeAuditPage(page) {
            if (page >= 1 && page <= this.auditPagination.last_page) {
                this.fetchAuditLogs(page);
            }
        },

        getVisiblePages(currentPage, lastPage) {
            const pages = [];
            const maxVisible = 5;
            let start = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let end = Math.min(lastPage, start + maxVisible - 1);

            if (end - start + 1 < maxVisible) {
                start = Math.max(1, end - maxVisible + 1);
            }

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            return pages;
        },

        toggleAuditTable() {
            this.showAuditTable = !this.showAuditTable;
            if (this.showAuditTable && this.auditLogs.length === 0) {
                this.fetchAuditLogs();
            }
        },

        async fetchLeaveRequests() {
            try {
                this.loading = true;
                const response = await axios.get("/api/leave-approvals", {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                        Accept: "application/json",
                    },
                });
                this.leaveRequests = response.data.data || [];
                this.filteredRequests = [...this.leaveRequests];
            } catch (error) {
                console.error("Error fetching leave requests:", error);
                this.showAlert(
                    "Error loading leave requests: " +
                        (error.response?.data?.message || error.message),
                    "danger"
                );
            } finally {
                this.loading = false;
            }
        },

        async fetchStats() {
            try {
                const response = await axios.get("/api/leave-approvals/stats", {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                        Accept: "application/json",
                    },
                });
                this.stats = response.data.data || {
                    pending_approvals: 0,
                    approved_today: 0,
                    rejected_today: 0,
                    total_requests: 0,
                };
            } catch (error) {
                console.error("Error fetching stats:", error);
                this.showAlert(
                    "Error loading statistics: " +
                        (error.response?.data?.message || error.message),
                    "warning"
                );
            }
        },

        async refreshRequests() {
            await this.fetchLeaveRequests();
            await this.fetchStats();
            await this.fetchAuditLogs(1);
            this.showAlert("Data refreshed successfully", "success");
        },

        formatDate(dateString) {
            if (!dateString) return "-";
            return new Date(dateString).toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
            });
        },

        formatDateTime(dateString) {
            if (!dateString) return "-";
            return new Date(dateString).toLocaleString("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
                hour: "2-digit",
                minute: "2-digit",
            });
        },

        calculateDays(startDate, endDate) {
            if (!startDate || !endDate) return 0;
            const start = new Date(startDate);
            const end = new Date(endDate);
            return Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
        },

        getInitials(name) {
            if (!name) return "?";
            return name
                .split(" ")
                .map((n) => n[0])
                .join("")
                .toUpperCase()
                .slice(0, 2);
        },

        getLeaveTypeBadge(type) {
            const badges = {
                "Annual Leave": "bg-primary",
                "Sick Leave": "bg-warning text-dark",
                "Personal Leave": "bg-info",
                "Emergency Leave": "bg-danger",
            };
            return badges[type] || "bg-secondary";
        },

        getStatusBadge(status) {
            const badges = {
                pending: "bg-warning text-dark",
                approved: "bg-success",
                rejected: "bg-danger",
            };
            return badges[status] || "bg-secondary";
        },

        formatStatus(status) {
            const statusMap = {
                pending: "Pending",
                approved: "Approved",
                rejected: "Rejected",
            };
            return statusMap[status] || status;
        },

        filterRequests() {
            let filtered = [...this.leaveRequests];
            if (this.filterStatus) {
                filtered = filtered.filter(
                    (r) => r.approval_status === this.filterStatus
                );
            }
            if (this.filterType) {
                filtered = filtered.filter(
                    (r) => r.jenis_cuti === this.filterType
                );
            }
            if (this.searchEmployee) {
                filtered = filtered.filter(
                    (r) =>
                        r.employee_name
                            .toLowerCase()
                            .includes(this.searchEmployee.toLowerCase()) ||
                        (r.jabatan &&
                            r.jabatan
                                .toLowerCase()
                                .includes(this.searchEmployee.toLowerCase()))
                );
            }
            this.filteredRequests = filtered;
        },

        showApprovalModal(request, isApprove) {
            this.currentApproval = {
                request: request,
                isApprove: isApprove,
                notes: "",
            };
            const modal = new Modal(document.getElementById("approvalModal"));
            modal.show();
        },

        async processApproval() {
            if (
                !this.currentApproval.isApprove &&
                !this.currentApproval.notes.trim()
            ) {
                this.showAlert(
                    "Please provide a reason for rejection",
                    "danger"
                );
                return;
            }

            this.isProcessing = true;
            try {
                const response = await axios.post(
                    `/api/leave-approvals/${this.currentApproval.request.leave_request_id}/approve`,
                    {
                        status_approve: this.currentApproval.isApprove,
                        catatan: this.currentApproval.notes.trim() || null,
                    },
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "auth_token"
                            )}`,
                            Accept: "application/json",
                        },
                    }
                );

                // Update local data
                const requestIndex = this.leaveRequests.findIndex(
                    (r) => r.id === this.currentApproval.request.id
                );
                if (requestIndex !== -1) {
                    this.leaveRequests[requestIndex].approval_status = this
                        .currentApproval.isApprove
                        ? "approved"
                        : "rejected";
                    this.leaveRequests[requestIndex].approval_notes =
                        this.currentApproval.notes;
                    this.leaveRequests[requestIndex].updated_at =
                        new Date().toISOString();
                }

                // Refresh stats and filter
                await this.fetchStats();
                await this.fetchAuditLogs(1); // Refresh audit logs
                this.filterRequests();

                // Hide modal
                const modal = Modal.getInstance(
                    document.getElementById("approvalModal")
                );
                modal.hide();

                this.showAlert(
                    `Leave request ${
                        this.currentApproval.isApprove ? "approved" : "rejected"
                    } successfully!`,
                    "success"
                );
            } catch (error) {
                console.error("Error processing approval:", error);
                this.showAlert(
                    error.response?.data?.message ||
                        "Error processing request. Please try again.",
                    "danger"
                );
            } finally {
                this.isProcessing = false;
            }
        },

        viewDetails(request) {
            this.selectedRequest = request;
            const modal = new Modal(document.getElementById("detailsModal"));
            modal.show();
        },

        async viewAttachment(request) {
            if (!request.file_lampiran) {
                this.showAlert("No attachment available", "warning");
                return;
            }

            try {
                const attachmentUrl = `/api/leave-approvals/${request.leave_request_id}/attachment`;
                const response = await axios.get(attachmentUrl, {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                        Accept: "application/pdf",
                    },
                    responseType: "blob",
                });

                const blob = new Blob([response.data], {
                    type: "application/pdf",
                });
                const url = window.URL.createObjectURL(blob);
                window.open(url, "_blank");

                setTimeout(() => {
                    window.URL.revokeObjectURL(url);
                }, 1000);
            } catch (error) {
                console.error("Error viewing attachment:", error);
                this.showAlert(
                    "Error loading attachment: " +
                        (error.response?.data?.message || error.message),
                    "danger"
                );
            }
        },

        async approveAllPending() {
            const pendingRequests = this.leaveRequests.filter(
                (r) => r.approval_status === "pending"
            );
            if (pendingRequests.length === 0) {
                this.showAlert("No pending requests to approve", "warning");
                return;
            }

            if (
                !confirm(
                    `Are you sure you want to approve all ${pendingRequests.length} pending requests?`
                )
            ) {
                return;
            }

            this.isProcessing = true;
            try {
                const response = await axios.post(
                    "/api/leave-approvals/bulk-approve",
                    {
                        leave_request_ids: pendingRequests.map(
                            (r) => r.leave_request_id
                        ),
                        catatan: "Bulk approval",
                    },
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "auth_token"
                            )}`,
                            Accept: "application/json",
                        },
                    }
                );

                // Update local data
                pendingRequests.forEach((request) => {
                    const index = this.leaveRequests.findIndex(
                        (r) => r.id === request.id
                    );
                    if (index !== -1) {
                        this.leaveRequests[index].approval_status = "approved";
                        this.leaveRequests[index].approval_notes =
                            "Bulk approval";
                        this.leaveRequests[index].updated_at =
                            new Date().toISOString();
                    }
                });

                await this.fetchStats();
                await this.fetchAuditLogs(1); // Refresh audit logs
                this.filterRequests();

                this.showAlert(
                    `Successfully approved ${response.data.approved_count} requests!`,
                    "success"
                );
            } catch (error) {
                console.error("Error approving all requests:", error);
                this.showAlert(
                    "Error approving requests. Please try again.",
                    "danger"
                );
            } finally {
                this.isProcessing = false;
            }
        },

        exportRequests() {
            const headers = [
                "Employee",
                "Jabatan",
                "Leave Type",
                "Start Date",
                "End Date",
                "Days",
                "Reason",
                "Status",
                "Approved By",
                "Notes",
            ];

            const csvContent = [
                headers.join(","),
                ...this.filteredRequests.map((request) =>
                    [
                        `"${request.employee_name}"`,
                        `"${request.jabatan || "N/A"}"`,
                        request.jenis_cuti,
                        request.tanggal_mulai,
                        request.tanggal_selesai,
                        this.calculateDays(
                            request.tanggal_mulai,
                            request.tanggal_selesai
                        ),
                        `"${request.alasan || "No reason provided"}"`,
                        this.formatStatus(request.approval_status),
                        `"${request.approved_by || "N/A"}"`,
                        `"${request.approval_notes || "N/A"}"`,
                    ].join(",")
                ),
            ].join("\n");

            const blob = new Blob([csvContent], { type: "text/csv" });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `leave_approvals_${
                new Date().toISOString().split("T")[0]
            }.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        },

        showAlert(message, type = "success") {
            this.alertMessage = message;
            this.alertType = type;
            setTimeout(() => {
                this.clearAlert();
            }, 5000);
        },

        clearAlert() {
            this.alertMessage = "";
            this.alertType = "success";
        },
    },
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
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

/* Audit Table Styles */
.audit-table {
    font-size: 0.875rem;
}

.audit-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.audit-row:hover {
    background-color: #f8f9fa;
}

.user-info,
.employee-info,
.date-info {
    line-height: 1.2;
}

.user-info .fw-medium,
.employee-info .fw-medium,
.date-info .fw-medium {
    font-size: 0.875rem;
}

.user-info small,
.employee-info small,
.date-info small {
    font-size: 0.75rem;
    display: block;
}

.empty-state {
    text-align: center;
    padding: 2rem 1rem;
}

.empty-state .display-6 {
    font-size: 3rem;
    opacity: 0.5;
}

.pagination-info {
    font-size: 0.875rem;
}

.font-monospace {
    font-family: 'Courier New', Courier, monospace;
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

    .audit-table {
        font-size: 0.8rem;
    }

    .audit-table th {
        font-size: 0.7rem;
    }

    .user-info,
    .employee-info,
    .date-info {
        font-size: 0.8rem;
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

    .audit-table th {
        background-color: #2c3034;
        color: #e9ecef;
    }

    .audit-row:hover {
        background-color: #495057;
    }
}
</style>
