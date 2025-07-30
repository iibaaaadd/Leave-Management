<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveApproval;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LeaveApprovalController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
            
            // Base query for leave requests with approval status
            $query = LeaveRequest::with([
                'user',
                'approval' => function ($query) {
                    $query->latest();
                }
            ]);

            // Filter berdasarkan role user
            if ($user->role !== 'admin') {
                // Jika bukan admin, hanya tampilkan data milik user sendiri
                $query->where('user_id', $user->id);
            }
            // Jika admin, tampilkan semua data (tidak perlu filter tambahan)

            $leaveRequests = $query->latest()
                ->get()
                ->map(function ($request) {
                    $latestApproval = $request->approval->first();

                    return [
                        'id' => $request->id,
                        'leave_request_id' => $request->id,
                        'employee_name' => $request->user->name ?? $request->nama_user_snapshot ?? 'Unknown',
                        'jabatan' => $request->user->jabatan ?? 'N/A',
                        'jenis_cuti' => $request->jenis_cuti,
                        'tanggal_mulai' => $request->tanggal_mulai,
                        'tanggal_selesai' => $request->tanggal_selesai,
                        'alasan' => $request->alasan,
                        'file_lampiran' => $request->file_lampiran,
                        'submitted_at' => $request->submitted_at ?? $request->created_at,
                        'approval_status' => $latestApproval ?
                            ($latestApproval->status_approve ? 'approved' : 'rejected') : 'pending',
                        'approval_notes' => $latestApproval->catatan ?? null,
                        'approved_by' => $latestApproval->nama_approver_snapshot ?? null,
                        'approval_date' => $latestApproval->created_at ?? null,
                        'created_at' => $request->created_at,
                        'updated_at' => $latestApproval->updated_at ?? $request->updated_at,
                    ];
                });

            return response()->json([
                'message' => 'List Leave Requests for Approval',
                'data' => $leaveRequests,
                'user_role' => $user->role // Tambahkan info role untuk debugging
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching leave requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function approve(Request $request, $leaveRequestId)
    {
        $request->validate([
            'status_approve' => 'required|boolean',
            'catatan' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $user = Auth::user();
            $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);

            // Jika bukan admin, pastikan user hanya bisa approve request milik sendiri
            if ($user->role !== 'admin' && $leaveRequest->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized to approve this request'
                ], 403);
            }

            // Check if already approved/rejected
            $existingApproval = LeaveApproval::where('leave_request_id', $leaveRequest->id)->first();
            if ($existingApproval) {
                return response()->json([
                    'message' => 'This request has already been processed'
                ], 400);
            }

            // Create new approval record
            $approval = LeaveApproval::create([
                'id' => Str::uuid(),
                'leave_request_id' => $leaveRequest->id,
                'approved_by' => Auth::id(),
                'status_approve' => $request->status_approve,
                'catatan' => $request->catatan,
                'nama_approver_snapshot' => Auth::user()->name,
            ]);

            DB::commit();

            return response()->json([
                'message' => $request->status_approve
                    ? 'Leave request approved successfully'
                    : 'Leave request rejected successfully',
                'data' => $approval->load(['leaveRequest.user', 'approver'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'Error processing approval',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $leaveRequest = LeaveRequest::with(['user', 'approval.approver'])->findOrFail($id);
            
            // Jika bukan admin, pastikan user hanya bisa melihat request milik sendiri
            if ($user->role !== 'admin' && $leaveRequest->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized to view this request'
                ], 403);
            }

            $latestApproval = $leaveRequest->approval->first();

            $data = [
                'id' => $leaveRequest->id,
                'employee_name' => $leaveRequest->user->name ?? $leaveRequest->nama_user_snapshot ?? 'Unknown',
                'jabatan' => $leaveRequest->user->jabatan ?? 'N/A',
                'jenis_cuti' => $leaveRequest->jenis_cuti,
                'tanggal_mulai' => $leaveRequest->tanggal_mulai,
                'tanggal_selesai' => $leaveRequest->tanggal_selesai,
                'alasan' => $leaveRequest->alasan,
                'file_lampiran' => $leaveRequest->file_lampiran,
                'submitted_at' => $leaveRequest->submitted_at ?? $leaveRequest->created_at,
                'approval_status' => $latestApproval ?
                    ($latestApproval->status_approve ? 'approved' : 'rejected') : 'pending',
                'approval_notes' => $latestApproval->catatan ?? null,
                'approved_by' => $latestApproval->nama_approver_snapshot ?? null,
                'approval_date' => $latestApproval->created_at ?? null,
            ];

            return response()->json([
                'message' => 'Leave request details',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Leave request not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function stats()
    {
        try {
            $user = Auth::user();
            $today = now()->startOfDay();

            // Base queries
            $pendingQuery = LeaveRequest::whereDoesntHave('approval');
            $totalQuery = LeaveRequest::query();
            $approvedQuery = LeaveApproval::where('status_approve', true)->whereDate('created_at', $today);
            $rejectedQuery = LeaveApproval::where('status_approve', false)->whereDate('created_at', $today);

            // Filter berdasarkan role user
            if ($user->role !== 'admin') {
                // Jika bukan admin, hanya hitung data milik user sendiri
                $pendingQuery->where('user_id', $user->id);
                $totalQuery->where('user_id', $user->id);
                
                // Untuk approval stats, filter berdasarkan leave requests milik user
                $userLeaveRequestIds = LeaveRequest::where('user_id', $user->id)->pluck('id');
                $approvedQuery->whereIn('leave_request_id', $userLeaveRequestIds);
                $rejectedQuery->whereIn('leave_request_id', $userLeaveRequestIds);
            }

            $stats = [
                'pending_approvals' => $pendingQuery->count(),
                'approved_today' => $approvedQuery->count(),
                'rejected_today' => $rejectedQuery->count(),
                'total_requests' => $totalQuery->count(),
            ];

            return response()->json([
                'message' => 'Approval statistics',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'leave_request_ids' => 'required|array',
            'leave_request_ids.*' => 'exists:leave_requests,id',
            'catatan' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $user = Auth::user();
            $approvedCount = 0;

            foreach ($request->leave_request_ids as $leaveRequestId) {
                $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);
                
                // Jika bukan admin, pastikan user hanya bisa approve request milik sendiri
                if ($user->role !== 'admin' && $leaveRequest->user_id !== $user->id) {
                    continue; // Skip request yang bukan milik user
                }

                // Check if not already approved
                $existingApproval = LeaveApproval::where('leave_request_id', $leaveRequestId)->first();

                if (!$existingApproval) {
                    LeaveApproval::create([
                        'id' => Str::uuid(),
                        'leave_request_id' => $leaveRequestId,
                        'approved_by' => Auth::id(),
                        'status_approve' => true,
                        'catatan' => $request->catatan ?: 'Bulk approval',
                        'nama_approver_snapshot' => Auth::user()->name,
                    ]);

                    $approvedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'message' => "Successfully approved {$approvedCount} leave requests",
                'approved_count' => $approvedCount
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'Error processing bulk approval',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function pendingRequests()
    {
        try {
            $user = Auth::user();
            
            // Base query for pending requests
            $query = LeaveRequest::with('user')->whereDoesntHave('approval');

            // Filter berdasarkan role user
            if ($user->role !== 'admin') {
                $query->where('user_id', $user->id);
            }

            $pendingRequests = $query->latest()
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'employee_name' => $request->user->name ?? $request->nama_user_snapshot ?? 'Unknown',
                        'jabatan' => $request->user->jabatan ?? 'N/A',
                        'jenis_cuti' => $request->jenis_cuti,
                        'tanggal_mulai' => $request->tanggal_mulai,
                        'tanggal_selesai' => $request->tanggal_selesai,
                        'alasan' => $request->alasan,
                        'file_lampiran' => $request->file_lampiran,
                        'submitted_at' => $request->submitted_at ?? $request->created_at,
                        'approval_status' => 'pending',
                        'created_at' => $request->created_at,
                    ];
                });

            return response()->json([
                'message' => 'Pending leave requests',
                'data' => $pendingRequests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching pending requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function viewAttachment($leaveRequestId)
    {
        try {
            $user = Auth::user();
            $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);

            // Jika bukan admin, pastikan user hanya bisa melihat attachment milik sendiri
            if ($user->role !== 'admin' && $leaveRequest->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized to view this attachment'
                ], 403);
            }

            if (!$leaveRequest->file_lampiran) {
                return response()->json([
                    'message' => 'No attachment found for this request'
                ], 404);
            }

            // Clean the file path - remove escaped slashes
            $filePath = str_replace('\/', '/', $leaveRequest->file_lampiran);

            // Try different approaches to find and serve the file
            $possiblePaths = [
                $filePath,
                'public/' . $filePath,
                'app/' . $filePath,
            ];

            foreach ($possiblePaths as $path) {
                try {
                    // Check if file exists using different methods
                    if (Storage::exists($path)) {
                        // Method 1: Direct file response (most reliable)
                        $fullPath = Storage::path($path);

                        if (file_exists($fullPath) && is_readable($fullPath)) {
                            return response()->file($fullPath, [
                                'Content-Type' => 'application/pdf',
                                'Content-Disposition' => 'inline; filename="attachment.pdf"'
                            ]);
                        }
                    }

                    // Method 2: Try with public disk
                    if (Storage::disk('public')->exists($path)) {
                        $fullPath = Storage::disk('public')->path($path);

                        if (file_exists($fullPath) && is_readable($fullPath)) {
                            return response()->file($fullPath, [
                                'Content-Type' => 'application/pdf',
                                'Content-Disposition' => 'inline; filename="attachment.pdf"'
                            ]);
                        }
                    }

                    // Method 3: Stream file contents directly
                    if (Storage::exists($path)) {
                        $contents = Storage::get($path);

                        return response($contents, 200, [
                            'Content-Type' => 'application/pdf',
                            'Content-Disposition' => 'inline; filename="attachment.pdf"',
                            'Content-Length' => strlen($contents)
                        ]);
                    }
                } catch (\Exception $e) {
                    // Continue to next path if this one fails
                    Log::warning("Failed to access file at path: $path - " . $e->getMessage());
                    continue;
                }
            }

            // If all methods fail, return detailed debug info
            return response()->json([
                'message' => 'File not accessible',
                'debug_info' => [
                    'original_path' => $leaveRequest->file_lampiran,
                    'cleaned_path' => $filePath,
                    'checked_paths' => $possiblePaths,
                    'storage_path' => storage_path('app'),
                    'public_storage_path' => storage_path('app/public'),
                    'file_exists_checks' => [
                        'default_disk' => Storage::exists($filePath),
                        'public_disk' => Storage::disk('public')->exists($filePath),
                        'filesystem_check' => file_exists(storage_path('app/' . $filePath)),
                        'public_filesystem_check' => file_exists(storage_path('app/public/' . $filePath)),
                    ]
                ]
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in viewAttachment: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error loading attachment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Alternative simple method that bypasses Laravel Storage methods
    public function viewAttachmentDirect($leaveRequestId)
    {
        try {
            $user = Auth::user();
            $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);

            // Check authorization
            if ($user->role !== 'admin' && $leaveRequest->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (!$leaveRequest->file_lampiran) {
                return response()->json(['message' => 'No attachment found'], 404);
            }

            // Clean path and build full file path
            $filePath = str_replace('\/', '/', $leaveRequest->file_lampiran);

            $possibleFullPaths = [
                storage_path('app/' . $filePath),
                storage_path('app/public/' . $filePath),
                public_path('storage/' . $filePath),
            ];

            foreach ($possibleFullPaths as $fullPath) {
                if (file_exists($fullPath) && is_readable($fullPath)) {
                    $mimeType = mime_content_type($fullPath) ?: 'application/pdf';
                    $fileSize = filesize($fullPath);

                    return response()->file($fullPath, [
                        'Content-Type' => $mimeType,
                        'Content-Length' => $fileSize,
                        'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"'
                    ]);
                }
            }

            return response()->json([
                'message' => 'File not found',
                'checked_paths' => $possibleFullPaths
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error loading attachment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Stream method for large files
    public function streamAttachment($leaveRequestId)
    {
        try {
            $user = Auth::user();
            $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);

            // Check authorization
            if ($user->role !== 'admin' && $leaveRequest->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (!$leaveRequest->file_lampiran) {
                return response()->json(['message' => 'No attachment found'], 404);
            }

            $filePath = str_replace('\/', '/', $leaveRequest->file_lampiran);

            // Try to get file using Storage::get() which is more reliable for reading
            if (Storage::exists($filePath)) {
                $fileContent = Storage::get($filePath);

                return response()->streamDownload(function () use ($fileContent) {
                    echo $fileContent;
                }, 'attachment.pdf', [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline'
                ]);
            }

            return response()->json(['message' => 'File not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error streaming attachment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function auditLogs(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 10);
            $page = $request->get('page', 1);
            
            // Base query untuk audit logs dari LeaveApproval
            $query = \OwenIt\Auditing\Models\Audit::where('auditable_type', LeaveApproval::class)
                ->with(['auditable' => function($q) {
                    $q->with(['leaveRequest.user', 'approver']);
                }]);

            // Filter berdasarkan role user
            if ($user->role !== 'admin') {
                // Untuk non-admin, hanya tampilkan audit logs dari approval milik user sendiri
                $userLeaveRequestIds = LeaveRequest::where('user_id', $user->id)->pluck('id');
                $userApprovalIds = LeaveApproval::whereIn('leave_request_id', $userLeaveRequestIds)->pluck('id');
                $query->whereIn('auditable_id', $userApprovalIds);
            }

            $audits = $query->latest()->paginate($perPage, ['*'], 'page', $page);

            $auditData = $audits->getCollection()->map(function ($audit) {
                $approval = $audit->auditable;
                $leaveRequest = $approval ? $approval->leaveRequest : null;
                $employee = $leaveRequest ? $leaveRequest->user : null;

                return [
                    'id' => $audit->id,
                    'event' => $audit->event,
                    'auditable_type' => $audit->auditable_type,
                    'auditable_id' => $audit->auditable_id,
                    'user_id' => $audit->user_id,
                    'user_name' => $audit->user ? $audit->user->name : 'System',
                    'employee_name' => $employee ? $employee->name : ($leaveRequest ? $leaveRequest->nama_user_snapshot : 'N/A'),
                    'leave_type' => $leaveRequest ? $leaveRequest->jenis_cuti : 'N/A',
                    'approval_status' => $approval ? ($approval->status_approve ? 'approved' : 'rejected') : 'N/A',
                    'old_values' => $audit->old_values,
                    'new_values' => $audit->new_values,
                    'url' => $audit->url,
                    'ip_address' => $audit->ip_address,
                    'user_agent' => $audit->user_agent,
                    'tags' => $audit->tags,
                    'created_at' => $audit->created_at,
                    'updated_at' => $audit->updated_at,
                ];
            });

            return response()->json([
                'message' => 'Audit logs retrieved successfully',
                'data' => $auditData,
                'pagination' => [
                    'current_page' => $audits->currentPage(),
                    'last_page' => $audits->lastPage(),
                    'per_page' => $audits->perPage(),
                    'total' => $audits->total(),
                    'from' => $audits->firstItem(),
                    'to' => $audits->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching audit logs: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error fetching audit logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function auditDetail($auditId)
    {
        try {
            $user = Auth::user();
            $audit = \OwenIt\Auditing\Models\Audit::with(['auditable.leaveRequest.user', 'auditable.approver'])
                ->findOrFail($auditId);

            // Check authorization untuk non-admin
            if ($user->role !== 'admin') {
                $approval = $audit->auditable;
                if ($approval && $approval->leaveRequest) {
                    if ($approval->leaveRequest->user_id !== $user->id) {
                        return response()->json([
                            'message' => 'Unauthorized to view this audit log'
                        ], 403);
                    }
                }
            }

            $approval = $audit->auditable;
            $leaveRequest = $approval ? $approval->leaveRequest : null;
            $employee = $leaveRequest ? $leaveRequest->user : null;

            $auditDetail = [
                'id' => $audit->id,
                'event' => $audit->event,
                'auditable_type' => $audit->auditable_type,
                'auditable_id' => $audit->auditable_id,
                'user_id' => $audit->user_id,
                'user_name' => $audit->user ? $audit->user->name : 'System',
                'employee_name' => $employee ? $employee->name : ($leaveRequest ? $leaveRequest->nama_user_snapshot : 'N/A'),
                'leave_type' => $leaveRequest ? $leaveRequest->jenis_cuti : 'N/A',
                'approval_status' => $approval ? ($approval->status_approve ? 'approved' : 'rejected') : 'N/A',
                'old_values' => $audit->old_values,
                'new_values' => $audit->new_values,
                'url' => $audit->url,
                'ip_address' => $audit->ip_address,
                'user_agent' => $audit->user_agent,
                'tags' => $audit->tags,
                'created_at' => $audit->created_at,
                'updated_at' => $audit->updated_at,
                'approval_data' => $approval ? [
                    'id' => $approval->id,
                    'leave_request_id' => $approval->leave_request_id,
                    'approved_by' => $approval->approved_by,
                    'status_approve' => $approval->status_approve,
                    'catatan' => $approval->catatan,
                    'nama_approver_snapshot' => $approval->nama_approver_snapshot,
                ] : null,
                'leave_request_data' => $leaveRequest ? [
                    'id' => $leaveRequest->id,
                    'jenis_cuti' => $leaveRequest->jenis_cuti,
                    'tanggal_mulai' => $leaveRequest->tanggal_mulai,
                    'tanggal_selesai' => $leaveRequest->tanggal_selesai,
                    'alasan' => $leaveRequest->alasan,
                ] : null,
            ];

            return response()->json([
                'message' => 'Audit log detail retrieved successfully',
                'data' => $auditDetail
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Audit log not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}