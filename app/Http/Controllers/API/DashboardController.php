<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\LeaveApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function getStats(Request $request)
    {
        try {
            $currentUser = Auth::user();
            $today = Carbon::today();
            $currentMonth = Carbon::now()->startOfMonth();

            $isAdmin = $currentUser->role === 'admin';

            // Total Users - hanya untuk admin
            $totalUsers = $isAdmin ? User::count() : 0;

            // Pending Requests - tidak ada record di approval
            if ($isAdmin) {
                $pendingRequests = LeaveRequest::whereDoesntHave('approval')->count();
            } else {
                $pendingRequests = LeaveRequest::where('user_id', $currentUser->id)
                    ->whereDoesntHave('approval')
                    ->count();
            }

            // Approved Today - Perbaikan: gunakan boolean true, bukan string
            if ($isAdmin) {
                $approvedToday = LeaveApproval::whereDate('created_at', $today)
                    ->where('status_approve', true)
                    ->count();
            } else {
                $approvedToday = LeaveApproval::whereDate('created_at', $today)
                    ->where('status_approve', true)
                    ->whereHas('leaveRequest', function ($query) use ($currentUser) {
                        $query->where('user_id', $currentUser->id);
                    })->count();
            }

            // Total Leave Requests This Month
            if ($isAdmin) {
                $totalThisMonth = LeaveRequest::where('created_at', '>=', $currentMonth)->count();
            } else {
                $totalThisMonth = LeaveRequest::where('user_id', $currentUser->id)
                    ->where('created_at', '>=', $currentMonth)
                    ->count();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'totalUsers' => $totalUsers,
                    'pendingRequests' => $pendingRequests,
                    'approvedToday' => $approvedToday,
                    'totalThisMonth' => $totalThisMonth,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading dashboard stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMonthlyChartStats(Request $request)
    {
        try {
            $currentUser = Auth::user();
            $year = $request->input('year', now()->year);
            $isAdmin = $currentUser->role === 'admin';

            // Query statistik bulanan dengan perbaikan untuk boolean status
            $monthlyStatsQuery = LeaveRequest::selectRaw('EXTRACT(MONTH FROM leave_requests.created_at) as month')
                ->selectRaw("SUM(CASE WHEN approvals.status_approve = true THEN 1 ELSE 0 END) as approved")
                ->selectRaw("SUM(CASE WHEN approvals.status_approve = false THEN 1 ELSE 0 END) as rejected")
                ->selectRaw("SUM(CASE WHEN approvals.id IS NULL THEN 1 ELSE 0 END) as pending")
                ->leftJoin('leave_approvals as approvals', 'leave_requests.id', '=', 'approvals.leave_request_id')
                ->whereRaw('EXTRACT(YEAR FROM leave_requests.created_at) = ?', [$year])
                ->groupByRaw('EXTRACT(MONTH FROM leave_requests.created_at)')
                ->orderByRaw('EXTRACT(MONTH FROM leave_requests.created_at)');

            if (!$isAdmin) {
                $monthlyStatsQuery->where('leave_requests.user_id', $currentUser->id);
            }

            $monthlyStatsRaw = $monthlyStatsQuery->get()->keyBy('month');

            // Pastikan semua 12 bulan ada, walaupun 0, dan dalam urutan yang benar
            $monthlyStats = collect(range(1, 12))->map(function ($month) use ($monthlyStatsRaw) {
                $item = $monthlyStatsRaw->get($month);
                return [
                    'month' => $month,
                    'approved' => $item ? (int)$item->approved : 0,
                    'rejected' => $item ? (int)$item->rejected : 0,
                    'pending' => $item ? (int)$item->pending : 0,
                ];
            })->values(); // Pastikan array numerik

            return response()->json([
                'success' => true,
                'data' => [
                    'monthlyStats' => $monthlyStats,
                    'year' => $year, // Kirim tahun untuk konfirmasi
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading monthly stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities(Request $request)
    {
        try {
            $currentUser = Auth::user();
            $isAdmin = $currentUser->role === 'admin';
            $limit = $request->get('limit', 10);

            $activities = collect();

            if ($isAdmin) {
                // Admin bisa lihat semua aktivitas
                $recentApprovals = LeaveApproval::with(['leaveRequest.user'])
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get()
                    ->map(function ($approval) {
                        // Perbaikan: gunakan boolean untuk status
                        $status = $approval->status_approve === true ? 'approved' : ($approval->status_approve === false ? 'rejected' : 'pending');
                        $icon = $status === 'approved' ? '✅' : ($status === 'rejected' ? '❌' : '⏳');

                        $userName = $approval->leaveRequest->user->name ??
                            $approval->leaveRequest->nama_user_snapshot ??
                            'Unknown User';

                        return [
                            'id' => 'approval_' . $approval->id,
                            'icon' => $icon,
                            'message' => "Leave request {$status} for {$userName}",
                            'time' => $approval->created_at->diffForHumans(),
                            'type' => 'approval',
                            'created_at' => $approval->created_at
                        ];
                    });

                $recentRequests = LeaveRequest::with('user')
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get()
                    ->map(function ($request) {
                        $userName = $request->user->name ??
                            $request->nama_user_snapshot ??
                            'Unknown User';

                        return [
                            'id' => 'request_' . $request->id,
                            'icon' => '📝',
                            'message' => "New leave request from {$userName}",
                            'time' => $request->created_at->diffForHumans(),
                            'type' => 'request',
                            'created_at' => $request->created_at
                        ];
                    });

                $activities = $recentApprovals->concat($recentRequests);
            } else {
                // User biasa hanya bisa lihat aktivitas mereka sendiri
                $userApprovals = LeaveApproval::whereHas('leaveRequest', function ($query) use ($currentUser) {
                    $query->where('user_id', $currentUser->id);
                })
                    ->with(['approver'])
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get()
                    ->map(function ($approval) {
                        // Perbaikan: gunakan boolean untuk status
                        $status = $approval->status_approve === true ? 'approved' : ($approval->status_approve === false ? 'rejected' : 'pending');
                        $icon = $status === 'approved' ? '✅' : ($status === 'rejected' ? '❌' : '⏳');

                        $approverName = $approval->approver->name ??
                            $approval->nama_approver_snapshot ??
                            'System';

                        return [
                            'id' => 'approval_' . $approval->id,
                            'icon' => $icon,
                            'message' => "Your leave request was {$status} by {$approverName}",
                            'time' => $approval->created_at->diffForHumans(),
                            'type' => 'approval',
                            'created_at' => $approval->created_at
                        ];
                    });

                $userRequests = LeaveRequest::where('user_id', $currentUser->id)
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get()
                    ->map(function ($request) {
                        return [
                            'id' => 'request_' . $request->id,
                            'icon' => '📝',
                            'message' => "You submitted a leave request for {$request->jenis_cuti}",
                            'time' => $request->created_at->diffForHumans(),
                            'type' => 'request',
                            'created_at' => $request->created_at
                        ];
                    });

                $activities = $userApprovals->concat($userRequests);
            }

            // Sort by created_at and limit
            $activities = $activities->sortByDesc('created_at')->take($limit)->values();

            return response()->json([
                'success' => true,
                'data' => $activities->toArray(), // Pastikan dalam format array
                'count' => $activities->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading recent activities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user profile data
     */
    public function getUserProfile(Request $request)
    {
        try {
            $user = Auth::user();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'nip' => $user->nip,
                    'jabatan' => $user->jabatan,
                    'role' => $user->role,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading user profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
