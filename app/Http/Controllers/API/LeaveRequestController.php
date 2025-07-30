<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Models\Audit;

class LeaveRequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Admin bisa melihat semua data, user biasa hanya data miliknya
        if ($user->role === 'admin') {
            $leaveRequests = LeaveRequest::with('user')->latest()->get();
        } else {
            $leaveRequests = LeaveRequest::where('user_id', $user->id)
                ->with('user')
                ->latest()
                ->get();
        }

        return response()->json([
            'message' => 'List Leave Requests',
            'data' => $leaveRequests
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'jenis_cuti'       => 'required|string|max:255',
            'tanggal_mulai'    => 'required|date',
            'tanggal_selesai'  => 'required|date|after_or_equal:tanggal_mulai',
            'alasan'           => 'required|string',
            'file_lampiran'    => 'required|file|mimes:pdf|between:100,500', // 100KB - 500KB
        ]);

        $filePath = null;
        if ($request->hasFile('file_lampiran')) {
            $filePath = $request->file('file_lampiran')->store('lampiran_cuti', 'public');
        }

        $leave = LeaveRequest::create([
            'id'                  => Str::uuid(),
            'user_id'             => Auth::id(),
            'jenis_cuti'          => $request->jenis_cuti,
            'tanggal_mulai'       => $request->tanggal_mulai,
            'tanggal_selesai'     => $request->tanggal_selesai,
            'alasan'              => $request->alasan,
            'file_lampiran'       => $filePath,
            'nama_user_snapshot'  => Auth::user()->name,
            'submitted_at'        => now(),
        ]);

        return response()->json([
            'message' => 'Leave request submitted successfully',
            'data' => $leave
        ]);
    }

    public function show($id)
    {
        $leave = LeaveRequest::with(['user'])->findOrFail($id);
        $user = Auth::user();
        
        // Batasi akses hanya pemilik data atau admin
        if ($user->role !== 'admin' && $user->id !== $leave->user_id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        return response()->json([
            'message' => 'Leave request details',
            'data' => $leave
        ]);
    }

    public function update(Request $request, $id)
    {
        $leave = LeaveRequest::findOrFail($id);
        $user = Auth::user();
        
        // Batasi hanya pemilik data atau admin yang bisa update
        if ($user->role !== 'admin' && $user->id !== $leave->user_id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $request->validate([
            'jenis_cuti'       => 'sometimes|required|string|max:255',
            'tanggal_mulai'    => 'sometimes|required|date',
            'tanggal_selesai'  => 'sometimes|required|date|after_or_equal:tanggal_mulai',
            'alasan'           => 'sometimes|required|string',
            'file_lampiran'    => 'sometimes|file|mimes:pdf|between:100,500',
        ]);

        if ($request->hasFile('file_lampiran')) {
            // Hapus file lama jika ada
            if ($leave->file_lampiran && Storage::disk('public')->exists($leave->file_lampiran)) {
                Storage::disk('public')->delete($leave->file_lampiran);
            }
            $leave->file_lampiran = $request->file('file_lampiran')->store('lampiran_cuti', 'public');
        }

        // Update field yang ada
        $fillableFields = ['jenis_cuti', 'tanggal_mulai', 'tanggal_selesai', 'alasan'];
        foreach ($fillableFields as $field) {
            if ($request->filled($field)) {
                $leave->$field = $request->$field;
            }
        }

        $leave->save();

        return response()->json([
            'message' => 'Leave request updated successfully',
            'data' => $leave,
        ]);
    }

    public function destroy($id)
    {
        try {
            $leave = LeaveRequest::findOrFail($id);
            $user = Auth::user();
            
            // Check permission - hanya admin atau pemilik data yang bisa menghapus
            if ($user->role !== 'admin' && $user->id !== $leave->user_id) {
                return response()->json(['message' => 'Access denied'], 403);
            }

            // Delete file attachment if exists
            if ($leave->file_lampiran && Storage::disk('public')->exists($leave->file_lampiran)) {
                Storage::disk('public')->delete($leave->file_lampiran);
            }

            // Delete the leave request record
            $leave->delete();

            return response()->json([
                'message' => 'Leave request deleted successfully',
                'success' => true
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Leave request not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting leave request: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error deleting leave request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * View PDF attachment - Fixed untuk new tab opening
     */
    public function viewAttachment($id)
    {
        try {
            $leave = LeaveRequest::findOrFail($id);
            $user = Auth::user();
            
            // Check access permission
            if ($user->role !== 'admin' && $user->id !== $leave->user_id) {
                return response()->json(['message' => 'Access denied'], 403);
            }

            // Check if file exists
            if (!$leave->file_lampiran) {
                return response()->json(['message' => 'No attachment found'], 404);
            }

            // Check if file exists in storage
            if (!Storage::disk('public')->exists($leave->file_lampiran)) {
                return response()->json(['message' => 'File not found in storage'], 404);
            }

            // Get file path and content
            $filePath = Storage::disk('public')->path($leave->file_lampiran);
            $fileContent = Storage::disk('public')->get($leave->file_lampiran);
            $mimeType = Storage::disk('public')->mimeType($leave->file_lampiran);

            // Return file with proper headers for PDF viewing
            return response($fileContent, 200, [
                'Content-Type' => $mimeType ?: 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($leave->file_lampiran) . '"',
                'Content-Length' => strlen($fileContent),
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Leave request not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error viewing attachment: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error loading attachment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get audit trail for leave requests (Admin only)
     */
    public function auditTrail(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Hanya admin yang bisa mengakses audit trail
            if ($user->role !== 'admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $perPage = $request->get('per_page', 10);
            $page = $request->get('page', 1);

            // Get audits for LeaveRequest model only
            $audits = Audit::where('auditable_type', LeaveRequest::class)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            // Transform the data to include user name
            $transformedAudits = $audits->getCollection()->map(function ($audit) {
                return [
                    'id' => $audit->id,
                    'auditable_type' => $audit->auditable_type,
                    'auditable_id' => $audit->auditable_id,
                    'user_id' => $audit->user_id,
                    'user_name' => $audit->user ? $audit->user->name : ($audit->user_id ? 'User #' . $audit->user_id : null),
                    'event' => $audit->event,
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
                'data' => $transformedAudits,
                'current_page' => $audits->currentPage(),
                'last_page' => $audits->lastPage(),
                'per_page' => $audits->perPage(),
                'total' => $audits->total(),
                'from' => $audits->firstItem(),
                'to' => $audits->lastItem(),
                'has_more_pages' => $audits->hasMorePages(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching audit trail: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching audit trail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
