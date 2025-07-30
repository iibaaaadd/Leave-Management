<?php

// Update LeaveRequestController.php - Tambahkan method ini

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Models\Audit;

class LeaveRequestController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'List Leave Requests',
            'data' => LeaveRequest::with('user')->latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'jenis_cuti'       => 'required|string|max:255',
            'tanggal_mulai'    => 'required|date',
            'tanggal_selesai'  => 'required|date|after_or_equal:tanggal_mulai',
            'alasan'           => 'required|string',
            'file_lampiran'    => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:2048',
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

        return response()->json(['message' => 'Cuti berhasil diajukan', 'data' => $leave]);
    }

    public function update(Request $request, $id)
    {
        $leave = LeaveRequest::findOrFail($id);

        // Batasi hanya pemilik data yang bisa update
        if (Auth::id() !== $leave->user_id) {
            return response()->json(['message' => 'Tidak diizinkan mengubah data ini'], 403);
        }

        $request->validate([
            'jenis_cuti'       => 'sometimes|required|string|max:255',
            'tanggal_mulai'    => 'sometimes|required|date',
            'tanggal_selesai'  => 'sometimes|required|date|after_or_equal:tanggal_mulai',
            'alasan'           => 'sometimes|required|string',
            'file_lampiran'    => 'sometimes|file|mimes:pdf,jpg,png,jpeg|max:2048',
        ]);

        if ($request->hasFile('file_lampiran')) {
            if ($leave->file_lampiran && Storage::disk('public')->exists($leave->file_lampiran)) {
                Storage::disk('public')->delete($leave->file_lampiran);
            }

            $leave->file_lampiran = $request->file('file_lampiran')->store('lampiran_cuti', 'public');
        }

        // Update hanya field yang ada
        if ($request->filled('jenis_cuti')) {
            $leave->jenis_cuti = $request->jenis_cuti;
        }

        if ($request->filled('tanggal_mulai')) {
            $leave->tanggal_mulai = $request->tanggal_mulai;
        }

        if ($request->filled('tanggal_selesai')) {
            $leave->tanggal_selesai = $request->tanggal_selesai;
        }

        if ($request->filled('alasan')) {
            $leave->alasan = $request->alasan;
        }

        $leave->save();

        return response()->json([
            'message' => 'Pengajuan cuti berhasil diperbarui',
            'data' => $leave,
        ]);
    }

    public function show($id)
    {
        $leave = LeaveRequest::with(['user', 'approval'])->findOrFail($id);
        return response()->json($leave);
    }

    /**
     * Get audit trail for leave requests
     */
    public function auditTrail(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);
            $page = $request->get('page', 1);

            // Get audits for LeaveRequest model only
            $audits = Audit::where('auditable_type', LeaveRequest::class)
                ->with('user') // Include user relationship if available
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

            // Create response with pagination meta
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
            return response()->json([
                'message' => 'Error fetching audit trail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get audit trail for a specific leave request
     */
    public function specificAuditTrail($id)
    {
        try {
            $leave = LeaveRequest::findOrFail($id);
            
            $audits = Audit::where('auditable_type', LeaveRequest::class)
                ->where('auditable_id', $id)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            // Transform the data
            $transformedAudits = $audits->map(function ($audit) {
                return [
                    'id' => $audit->id,
                    'user_name' => $audit->user ? $audit->user->name : 'System',
                    'event' => $audit->event,
                    'old_values' => $audit->old_values,
                    'new_values' => $audit->new_values,
                    'ip_address' => $audit->ip_address,
                    'user_agent' => $audit->user_agent,
                    'created_at' => $audit->created_at,
                ];
            });

            return response()->json([
                'message' => 'Audit trail for leave request',
                'leave_request' => $leave,
                'audit_trail' => $transformedAudits
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching specific audit trail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}