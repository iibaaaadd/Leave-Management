<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
}
