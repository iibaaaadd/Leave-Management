<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveApproval;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LeaveApprovalController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'List Leave Approvals',
            'data' => LeaveApproval::with(['leaveRequest', 'approver'])->latest()->get()
        ]);
    }

    public function approve(Request $request, $leaveRequestId)
    {
        $request->validate([
            'status_approve' => 'required|boolean',
            'catatan'        => 'nullable|string',
        ]);

        $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);

        $approval = LeaveApproval::updateOrCreate(
            ['leave_request_id' => $leaveRequest->id],
            [
                'id'                      => Str::uuid(),
                'approved_by'            => Auth::id(),
                'status_approve'         => $request->status_approve,
                'catatan'                => $request->catatan,
                'nama_approver_snapshot' => Auth::user()->name,
            ]
        );

        return response()->json([
            'message' => 'Persetujuan cuti berhasil diproses',
            'data' => $approval
        ]);
    }

    public function show($id)
    {
        $approval = LeaveApproval::with(['leaveRequest', 'approver'])->findOrFail($id);
        return response()->json($approval);
    }
}
