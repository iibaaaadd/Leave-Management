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
                    <p class="text-muted mb-0">Manage your leave requests</p>
                </div>
                <div class="d-flex gap-2">
                    <button
                        class="btn btn-outline-primary btn-sm"
                        @click="exportData"
                    >
                        <span class="me-1">📥</span>
                        Export
                    </button>
                </div>
            </div>

            <!-- Alert Messages -->
            <div
                v-if="alertMessage"
                :class="`alert alert-${alertType} alert-dismissible fade show position-fixed`"
                style="top: 20px; right: 20px; z-index: 1050"
            >
                {{ alertMessage }}
                <button
                    type="button"
                    class="btn-close"
                    @click="clearAlert"
                ></button>
            </div>

            <!-- Request Form Card -->
            <div class="row mb-4">
                <div class="col-md-12 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                {{
                                    editingRequest
                                        ? "Edit Request"
                                        : "Quick Request"
                                }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <form @submit.prevent="submitLeaveRequest">
                                <div class="mb-3">
                                    <label class="form-label">Leave Type</label>
                                    <select
                                        class="form-select"
                                        v-model="newRequest.jenis_cuti"
                                        required
                                    >
                                        <option value="">
                                            Select leave type
                                        </option>
                                        <option value="Annual Leave">
                                            Annual Leave
                                        </option>
                                        <option value="Sick Leave">
                                            Sick Leave
                                        </option>
                                        <option value="Personal Leave">
                                            Personal Leave
                                        </option>
                                        <option value="Emergency Leave">
                                            Emergency Leave
                                        </option>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"
                                            >Start Date</label
                                        >
                                        <input
                                            type="date"
                                            class="form-control"
                                            v-model="newRequest.tanggal_mulai"
                                            required
                                        />
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"
                                            >End Date</label
                                        >
                                        <input
                                            type="date"
                                            class="form-control"
                                            v-model="newRequest.tanggal_selesai"
                                            required
                                        />
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reason</label>
                                    <textarea
                                        class="form-control"
                                        rows="3"
                                        v-model="newRequest.alasan"
                                        placeholder="Brief reason for leave..."
                                        required
                                    ></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"
                                        >Attachment (Required PDF)</label
                                    >
                                    <input
                                        type="file"
                                        class="form-control"
                                        @change="handleFileUpload"
                                        accept=".pdf"
                                        :required="!editingRequest"
                                    />
                                    <small class="text-muted">
                                        Required: PDF only, file size between
                                        100KB - 500KB
                                    </small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        :disabled="isSubmitting"
                                    >
                                        <span
                                            v-if="isSubmitting"
                                            class="spinner-border spinner-border-sm me-2"
                                        ></span>
                                        {{
                                            isSubmitting
                                                ? "Submitting..."
                                                : editingRequest
                                                ? "Update Request"
                                                : "Submit Request"
                                        }}
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-secondary"
                                        @click="resetForm"
                                        v-if="editingRequest"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Requests Table -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        {{
                            userRole === "admin"
                                ? "All Leave Requests"
                                : "My Leave Requests"
                        }}
                    </h5>
                </div>
                <div class="card-body">
                    <div v-if="loading" class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div
                        v-else-if="myRequests.length === 0"
                        class="text-center py-4 text-muted"
                    >
                        No leave requests found
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th v-if="userRole === 'admin'">
                                        Employee
                                    </th>
                                    <th>Request Date</th>
                                    <th>Leave Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Days</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="request in myRequests"
                                    :key="request.id"
                                >
                                    <td v-if="userRole === 'admin'">
                                        <span class="badge bg-secondary">
                                            {{
                                                request.user?.name ||
                                                request.nama_user_snapshot ||
                                                "Unknown"
                                            }}
                                        </span>
                                    </td>
                                    <td>
                                        {{
                                            formatDate(
                                                request.submitted_at ||
                                                    request.created_at
                                            )
                                        }}
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
                                        {{ formatDate(request.tanggal_mulai) }}
                                    </td>
                                    <td>
                                        {{
                                            formatDate(request.tanggal_selesai)
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
                                        <div class="btn-group btn-group-sm">
                                            <button
                                                class="btn btn-outline-primary btn-sm"
                                                @click="
                                                    viewPdfAttachment(request)
                                                "
                                                title="View PDF Attachment"
                                                :disabled="
                                                    !request.file_lampiran
                                                "
                                            >
                                                📄
                                            </button>
                                            <button
                                                class="btn btn-outline-warning btn-sm"
                                                @click="editRequest(request)"
                                                :disabled="
                                                    !canEditRequest(request)
                                                "
                                                title="Edit Request"
                                            >
                                                ✏️
                                            </button>
                                            <button
                                                class="btn btn-outline-danger btn-sm"
                                                @click="
                                                    showDeleteModal(request)
                                                "
                                                :disabled="
                                                    !canEditRequest(request)
                                                "
                                                title="Delete Request"
                                            >
                                                🗑️
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Audit Trail Table - Only for Admin -->
            <div class="card" v-if="userRole === 'admin'">
                <div
                    class="card-header d-flex justify-content-between align-items-center"
                >
                    <h5 class="mb-0">
                        <span class="me-2">🔍</span>
                        Leave Request Audit Trail
                    </h5>
                    <button
                        class="btn btn-outline-secondary btn-sm"
                        @click="refreshAuditTrail"
                    >
                        <span class="me-1">🔄</span>
                        Refresh
                    </button>
                </div>
                <div class="card-body">
                    <div v-if="auditLoading" class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden"
                                >Loading audit trail...</span
                            >
                        </div>
                    </div>
                    <div
                        v-else-if="auditTrail.length === 0"
                        class="text-center py-4 text-muted"
                    >
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
                                    <tr
                                        v-for="audit in auditTrail"
                                        :key="audit.id"
                                    >
                                        <td>
                                            <small class="text-muted">
                                                {{
                                                    formatDateTime(
                                                        audit.created_at
                                                    )
                                                }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                {{
                                                    audit.user_name || "System"
                                                }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge"
                                                :class="
                                                    getEventBadge(audit.event)
                                                "
                                            >
                                                {{ formatEvent(audit.event) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small>{{
                                                getAuditLeaveType(audit)
                                            }}</small>
                                        </td>
                                        <td>
                                            <div
                                                v-if="hasChanges(audit)"
                                                class="audit-changes"
                                            >
                                                <small
                                                    v-for="(
                                                        change, key
                                                    ) in getChanges(audit)"
                                                    :key="key"
                                                    class="d-block"
                                                >
                                                    <strong
                                                        >{{
                                                            formatFieldName(
                                                                key
                                                            )
                                                        }}:</strong
                                                    >
                                                    <span
                                                        class="text-danger"
                                                        v-html="
                                                            formatChangeValue(
                                                                change.old
                                                            )
                                                        "
                                                    ></span>
                                                    →
                                                    <span
                                                        class="text-success"
                                                        v-html="
                                                            formatChangeValue(
                                                                change.new
                                                            )
                                                        "
                                                    ></span>
                                                </small>
                                            </div>
                                            <small v-else class="text-muted">
                                                No detailed changes
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ audit.ip_address || "-" }}
                                            </small>
                                        </td>
                                        <td>
                                            <small
                                                class="text-muted"
                                                :title="audit.user_agent"
                                            >
                                                {{
                                                    formatUserAgent(
                                                        audit.user_agent
                                                    )
                                                }}
                                            </small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination -->
                        <div
                            class="d-flex justify-content-between align-items-center mt-3"
                        >
                            <div class="text-muted">
                                Showing {{ auditMeta.from }} to
                                {{ auditMeta.to }} of
                                {{ auditMeta.total }} entries
                            </div>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li
                                        class="page-item"
                                        :class="{
                                            disabled:
                                                auditMeta.current_page === 1,
                                        }"
                                    >
                                        <button
                                            class="page-link"
                                            @click="
                                                fetchAuditTrail(
                                                    auditMeta.current_page - 1
                                                )
                                            "
                                            :disabled="
                                                auditMeta.current_page === 1
                                            "
                                        >
                                            Previous
                                        </button>
                                    </li>
                                    <li
                                        v-for="page in getPaginationPages()"
                                        :key="page"
                                        class="page-item"
                                        :class="{
                                            active:
                                                page === auditMeta.current_page,
                                        }"
                                    >
                                        <button
                                            class="page-link"
                                            @click="fetchAuditTrail(page)"
                                        >
                                            {{ page }}
                                        </button>
                                    </li>
                                    <li
                                        class="page-item"
                                        :class="{
                                            disabled:
                                                auditMeta.current_page ===
                                                auditMeta.last_page,
                                        }"
                                    >
                                        <button
                                            class="page-link"
                                            @click="
                                                fetchAuditTrail(
                                                    auditMeta.current_page + 1
                                                )
                                            "
                                            :disabled="
                                                auditMeta.current_page ===
                                                auditMeta.last_page
                                            "
                                        >
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

        <!-- Delete Confirmation Modal -->
        <div
            class="modal fade"
            id="deleteModal"
            tabindex="-1"
            aria-labelledby="deleteModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">
                            Confirm Delete
                        </h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <p>
                            Are you sure you want to delete this leave request
                            for
                            <strong>{{ requestToDelete?.jenis_cuti }}</strong>
                            from
                            {{ formatDate(requestToDelete?.tanggal_mulai) }} to
                            {{ formatDate(requestToDelete?.tanggal_selesai) }}?
                        </p>
                        <p class="text-danger">This action cannot be undone.</p>
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
                            class="btn btn-danger"
                            @click="confirmDeleteRequest"
                            :disabled="isDeleting"
                        >
                            <span
                                v-if="isDeleting"
                                class="spinner-border spinner-border-sm me-2"
                            ></span>
                            {{
                                isDeleting
                                    ? "Deleting..."
                                    : "Yes, Delete Request"
                            }}
                        </button>
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
    name: "LeaveRequestContent",
    data() {
        return {
            isSubmitting: false,
            loading: true,
            auditLoading: true,
            alertMessage: "",
            alertType: "success",
            newRequest: {
                jenis_cuti: "",
                tanggal_mulai: "",
                tanggal_selesai: "",
                alasan: "",
                file_lampiran: null,
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
                per_page: 10,
            },
            requestToDelete: null,
            isDeleting: false,
        };
    },
    async mounted() {
        await this.fetchLeaveRequests();
        if (this.userRole === "admin") {
            await this.fetchAuditTrail();
        }
    },
    computed: {
        userRole() {
            const userData = JSON.parse(
                localStorage.getItem("user_data") || "{}"
            );
            return userData.role || "user";
        },
        currentUserId() {
            const userData = JSON.parse(
                localStorage.getItem("user_data") || "{}"
            );
            return userData.id;
        },
    },
    methods: {
        async fetchLeaveRequests() {
            try {
                this.loading = true;
                const response = await axios.get("/api/leave-requests", {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                        Accept: "application/json",
                    },
                });
                this.myRequests = response.data.data || [];
            } catch (error) {
                console.error("Error fetching leave requests:", error);
                this.showAlert("Error loading leave requests", "danger");
            } finally {
                this.loading = false;
            }
        },

        async fetchAuditTrail(page = 1) {
            if (this.userRole !== "admin") return;
            try {
                this.auditLoading = true;
                const response = await axios.get(
                    `/api/audit/leave-requests?page=${page}&per_page=10`,
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "auth_token"
                            )}`,
                            Accept: "application/json",
                        },
                    }
                );
                this.auditTrail = response.data.data || [];
                this.auditMeta = {
                    current_page: response.data.current_page || 1,
                    last_page: response.data.last_page || 1,
                    from: response.data.from || 0,
                    to: response.data.to || 0,
                    total: response.data.total || 0,
                    per_page: response.data.per_page || 10,
                };
            } catch (error) {
                console.error("Error fetching audit trail:", error);
                this.showAlert("Error loading audit trail", "danger");
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
            for (
                let i = Math.max(1, current - delta);
                i <= Math.min(last, current + delta);
                i++
            ) {
                pages.push(i);
            }
            return pages;
        },

        canEditRequest(request) {
            // Admin bisa edit semua, user biasa hanya bisa edit miliknya
            if (this.userRole === "admin") {
                return true;
            }
            return request.user_id === this.currentUserId;
        },

        handleFileUpload(event) {
            const file = event.target.files[0];
            if (file) {
                // Validate file type
                if (file.type !== "application/pdf") {
                    this.showAlert("Only PDF files are allowed", "danger");
                    event.target.value = "";
                    return;
                }
                // Validate file size (100KB - 500KB)
                const fileSizeKB = file.size / 1024;
                if (fileSizeKB < 100) {
                    this.showAlert(
                        "File size must be at least 100KB",
                        "danger"
                    );
                    event.target.value = "";
                    return;
                }
                if (fileSizeKB > 500) {
                    this.showAlert("File size must not exceed 500KB", "danger");
                    event.target.value = "";
                    return;
                }
                this.newRequest.file_lampiran = file;
                this.showAlert(
                    `PDF file selected: ${file.name} (${Math.round(
                        fileSizeKB
                    )}KB)`,
                    "success"
                );
            }
        },

        async submitLeaveRequest() {
            if (
                !this.newRequest.jenis_cuti ||
                !this.newRequest.tanggal_mulai ||
                !this.newRequest.tanggal_selesai ||
                !this.newRequest.alasan ||
                (!this.editingRequest && !this.newRequest.file_lampiran)
            ) {
                this.showAlert(
                    "Please fill in all required fields including PDF attachment",
                    "danger"
                );
                return;
            }
            this.isSubmitting = true;
            try {
                const formData = new FormData();
                formData.append("jenis_cuti", this.newRequest.jenis_cuti);
                formData.append("tanggal_mulai", this.newRequest.tanggal_mulai);
                formData.append(
                    "tanggal_selesai",
                    this.newRequest.tanggal_selesai
                );
                formData.append("alasan", this.newRequest.alasan);
                if (this.newRequest.file_lampiran) {
                    formData.append(
                        "file_lampiran",
                        this.newRequest.file_lampiran
                    );
                }
                const url = this.editingRequest
                    ? `/api/leave-requests/${this.editingRequest.id}`
                    : "/api/leave-requests";
                const method = this.editingRequest ? "put" : "post";
                const response = await axios({
                    method,
                    url,
                    data: formData,
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                        "Content-Type": "multipart/form-data",
                        Accept: "application/json",
                    },
                });
                this.showAlert(
                    response.data.message ||
                        "Leave request submitted successfully!",
                    "success"
                );
                // Reset form
                this.resetForm();
                // Refresh data
                await this.fetchLeaveRequests();
                if (this.userRole === "admin") {
                    await this.fetchAuditTrail();
                }
            } catch (error) {
                console.error("Error submitting request:", error);
                const errorMessage =
                    error.response?.data?.message ||
                    "Error submitting request. Please try again.";
                this.showAlert(errorMessage, "danger");
            } finally {
                this.isSubmitting = false;
            }
        },

        resetForm() {
            this.newRequest = {
                jenis_cuti: "",
                tanggal_mulai: "",
                tanggal_selesai: "",
                alasan: "",
                file_lampiran: null,
            };
            this.editingRequest = null;
            // Reset file input
            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput) fileInput.value = "";
        },

        editRequest(request) {
            // Cek izin edit
            if (!this.canEditRequest(request)) {
                this.showAlert(
                    "You don't have permission to edit this request",
                    "danger"
                );
                return;
            }
            this.editingRequest = request;
            this.newRequest = {
                jenis_cuti: request.jenis_cuti,
                tanggal_mulai: request.tanggal_mulai,
                tanggal_selesai: request.tanggal_selesai,
                alasan: request.alasan,
                file_lampiran: null,
            };
        },

        // Fixed PDF viewing method
        async viewPdfAttachment(request) {
            if (!request.file_lampiran) {
                this.showAlert("No PDF attachment available", "warning");
                return;
            }

            try {
                // Create direct URL to attachment endpoint
                const attachmentUrl = `/api/leave-requests/${request.id}/attachment`;

                // Open in new tab with proper authorization
                const newTab = window.open("about:blank", "_blank");

                // Get the PDF with authorization
                const response = await axios.get(attachmentUrl, {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                        Accept: "application/pdf",
                    },
                    responseType: "blob",
                });

                // Create blob URL and set to new tab
                const blob = new Blob([response.data], {
                    type: "application/pdf",
                });
                const url = URL.createObjectURL(blob);

                if (newTab) {
                    newTab.location.href = url;

                    // Clean up blob URL after tab loads
                    newTab.onload = () => {
                        setTimeout(() => {
                            URL.revokeObjectURL(url);
                        }, 1000);
                    };
                }

                this.showAlert("PDF opened in new tab", "success");
            } catch (error) {
                console.error("Error loading PDF:", error);
                this.showAlert(
                    "Error loading PDF attachment: " +
                        (error.response?.data?.message || error.message),
                    "danger"
                );
            }
        },

        showDeleteModal(request) {
            this.requestToDelete = request;
            const modal = new Modal(document.getElementById("deleteModal"));
            modal.show();
        },

        async confirmDeleteRequest() {
            if (!this.requestToDelete) return;
            this.isDeleting = true;
            try {
                await axios.delete(
                    `/api/leave-requests/${this.requestToDelete.id}`,
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "auth_token"
                            )}`,
                            Accept: "application/json",
                        },
                    }
                );
                this.showAlert("Request deleted successfully", "success");
                // Hide modal
                const modal = Modal.getInstance(
                    document.getElementById("deleteModal")
                );
                modal.hide();
                // Reset
                this.requestToDelete = null;
                // Refresh data
                await this.fetchLeaveRequests();
                if (this.userRole === "admin") {
                    await this.fetchAuditTrail();
                }
            } catch (error) {
                console.error("Error deleting request:", error);
                this.showAlert("Error deleting request", "danger");
            } finally {
                this.isDeleting = false;
            }
        },

        exportData() {
            // Simple CSV export - no status column
            const headers =
                this.userRole === "admin"
                    ? "Employee,Request Date,Leave Type,Start Date,End Date,Days"
                    : "Request Date,Leave Type,Start Date,End Date,Days";
            const csvContent = this.myRequests
                .map((request) => {
                    const baseData = `${
                        request.submitted_at || request.created_at
                    },${request.jenis_cuti},${request.tanggal_mulai},${
                        request.tanggal_selesai
                    },${this.calculateDays(
                        request.tanggal_mulai,
                        request.tanggal_selesai
                    )}`;
                    return this.userRole === "admin"
                        ? `${
                              request.user?.name ||
                              request.nama_user_snapshot ||
                              "Unknown"
                          },${baseData}`
                        : baseData;
                })
                .join("\n");
            const blob = new Blob([`${headers}\n${csvContent}`], {
                type: "text/csv",
            });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = "leave_requests.csv";
            a.click();
            window.URL.revokeObjectURL(url);
        },

        // Audit Trail Helper Methods
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

        formatEvent(event) {
            const eventMap = {
                created: "Created",
                updated: "Updated",
                deleted: "Deleted",
                restored: "Restored",
            };
            return eventMap[event] || event;
        },

        getEventBadge(event) {
            const badges = {
                created: "bg-success",
                updated: "bg-warning",
                deleted: "bg-danger",
                restored: "bg-info",
            };
            return badges[event] || "bg-secondary";
        },

        getAuditLeaveType(audit) {
            if (audit.new_values && audit.new_values.jenis_cuti) {
                return audit.new_values.jenis_cuti;
            }
            if (audit.old_values && audit.old_values.jenis_cuti) {
                return audit.old_values.jenis_cuti;
            }
            return "-";
        },

        // Method yang diperbaiki untuk menangani berbagai event audit trail

        hasChanges(audit) {
            // Selalu return true karena setiap audit event memiliki informasi yang berguna
            return true;
        },

        getChanges(audit) {
            const changes = {};

            // Fields yang ingin ditampilkan untuk setiap event
            const displayFields = [
                "jenis_cuti",
                "tanggal_mulai",
                "tanggal_selesai",
                "alasan",
                "file_lampiran",
            ];

            if (audit.event === "created") {
                // Untuk event created, tampilkan semua data baru yang dibuat
                if (audit.new_values) {
                    displayFields.forEach((field) => {
                        if (audit.new_values[field]) {
                            changes[field] = {
                                old: null,
                                new: audit.new_values[field],
                                type: "created",
                            };
                        }
                    });
                }
            } else if (audit.event === "deleted") {
                // Untuk event deleted, tampilkan data yang telah dihapus
                if (audit.old_values) {
                    displayFields.forEach((field) => {
                        if (audit.old_values[field]) {
                            changes[field] = {
                                old: audit.old_values[field],
                                new: null,
                                type: "deleted",
                            };
                        }
                    });
                }
            } else if (audit.event === "updated") {
                // Untuk event updated, tampilkan hanya field yang benar-benar berubah
                if (audit.old_values && audit.new_values) {
                    displayFields.forEach((field) => {
                        const oldValue = audit.old_values[field];
                        const newValue = audit.new_values[field];

                        // Cek apakah value benar-benar berubah
                        if (oldValue !== newValue) {
                            changes[field] = {
                                old: oldValue,
                                new: newValue,
                                type: "updated",
                            };
                        }
                    });
                }
            } else if (audit.event === "restored") {
                // Untuk event restored, tampilkan data yang dikembalikan
                if (audit.new_values) {
                    displayFields.forEach((field) => {
                        if (audit.new_values[field]) {
                            changes[field] = {
                                old: null,
                                new: audit.new_values[field],
                                type: "restored",
                            };
                        }
                    });
                }
            }

            return changes;
        },

        // Method helper untuk format tampilan berdasarkan tipe perubahan
        formatChangeDisplay(change, fieldName) {
            const { old, new: newValue, type } = change;

            switch (type) {
                case "created":
                    return `
                <div class="change-item created">
                    <strong>${this.formatFieldName(fieldName)}:</strong>
                    <span class="badge bg-success me-1">NEW</span>
                    <span class="new-value">${newValue || "-"}</span>
                </div>
            `;

                case "deleted":
                    return `
                <div class="change-item deleted">
                    <strong>${this.formatFieldName(fieldName)}:</strong>
                    <span class="badge bg-danger me-1">DELETED</span>
                    <span class="old-value text-decoration-line-through">${
                        old || "-"
                    }</span>
                </div>
            `;

                case "updated":
                    return `
                <div class="change-item updated">
                    <strong>${this.formatFieldName(fieldName)}:</strong>
                    <span class="badge bg-warning me-1">CHANGED</span>
                    <div class="change-detail mt-1">
                        <small class="text-muted">From:</small> 
                        <span class="old-value text-decoration-line-through">${
                            old || "-"
                        }</span>
                        <br>
                        <small class="text-muted">To:</small> 
                        <span class="new-value fw-bold">${
                            newValue || "-"
                        }</span>
                    </div>
                </div>
            `;

                case "restored":
                    return `
                <div class="change-item restored">
                    <strong>${this.formatFieldName(fieldName)}:</strong>
                    <span class="badge bg-info me-1">RESTORED</span>
                    <span class="new-value">${newValue || "-"}</span>
                </div>
            `;

                default:
                    return `
                <div class="change-item">
                    <strong>${this.formatFieldName(fieldName)}:</strong>
                    ${old || "-"} → ${newValue || "-"}
                </div>
            `;
            }
        },

        // Method untuk mendapatkan summary message berdasarkan event
        getAuditSummary(audit) {
            const changes = this.getChanges(audit);
            const changeCount = Object.keys(changes).length;

            switch (audit.event) {
                case "created":
                    return `Data cuti baru telah dibuat dengan ${changeCount} field`;

                case "deleted":
                    return `Data cuti telah dihapus (${changeCount} field)`;

                case "updated":
                    if (changeCount === 0) {
                        return "Data diupdate tanpa perubahan field utama";
                    }
                    return `${changeCount} field telah diupdate`;

                case "restored":
                    return `Data cuti telah dipulihkan dengan ${changeCount} field`;

                default:
                    return `Event: ${audit.event}`;
            }
        },

        formatChangeValue(value) {
            if (value === "[DELETED]") {
                return '<span class="badge bg-danger">DELETED</span>';
            } else if (value === "[NEW]") {
                return '<span class="badge bg-success">NEW</span>';
            }
            return value || "-";
        },

        formatFieldName(fieldName) {
            const fieldMap = {
                jenis_cuti: "Leave Type",
                tanggal_mulai: "Start Date",
                tanggal_selesai: "End Date",
                alasan: "Reason",
                file_lampiran: "Attachment",
                updated_at: "Updated At",
            };
            return fieldMap[fieldName] || fieldName;
        },

        formatUserAgent(userAgent) {
            if (!userAgent) return "-";
            // Extract browser name from user agent
            if (userAgent.includes("Chrome")) return "Chrome";
            if (userAgent.includes("Firefox")) return "Firefox";
            if (userAgent.includes("Safari")) return "Safari";
            if (userAgent.includes("Edge")) return "Edge";
            return "Other";
        },

        formatDate(dateString) {
            if (!dateString) return "-";
            return new Date(dateString).toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
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
                "Annual Leave": "bg-primary",
                "Sick Leave": "bg-warning",
                "Personal Leave": "bg-info",
                "Emergency Leave": "bg-danger",
            };
            return badges[type] || "bg-secondary";
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
