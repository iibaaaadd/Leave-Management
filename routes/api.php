<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\LeaveRequestController;
use App\Http\Controllers\API\LeaveApprovalController;
use App\Http\Controllers\API\DashboardController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getStats']);
        Route::get('/activities', [DashboardController::class, 'getRecentActivities']);
        Route::get('/profile', [DashboardController::class, 'getUserProfile']);
        Route::get('/monthly-stats', [DashboardController::class, 'getMonthlyChartStats']);
    });
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('users', UserController::class);
    Route::get('users/{id}/audits', [UserController::class, 'audits']);
    Route::apiResource('leave-requests', LeaveRequestController::class);
    Route::get('/leave-requests/{id}/attachment', [LeaveRequestController::class, 'viewAttachment']);
    Route::prefix('audit')->group(function () {
        Route::get('/leave-requests', [LeaveRequestController::class, 'auditTrail']);
    });
    Route::prefix('leave-approvals')->group(function () {
        Route::get('stats', [LeaveApprovalController::class, 'stats']);
        Route::get('pending', [LeaveApprovalController::class, 'pendingRequests']);
        Route::get('audit-logs', [LeaveApprovalController::class, 'auditLogs']);
        Route::get('audit-logs/{auditId}', [LeaveApprovalController::class, 'auditDetail']);
        Route::post('bulk-approve', [LeaveApprovalController::class, 'bulkApprove']);
        Route::post('{leaveRequestId}/approve', [LeaveApprovalController::class, 'approve']);
        Route::get('{leaveRequestId}/attachment', [LeaveApprovalController::class, 'viewAttachment']);
        Route::get('{leaveRequestId}/attachment-direct', [LeaveApprovalController::class, 'viewAttachmentDirect']);
        Route::get('{leaveRequestId}/stream', [LeaveApprovalController::class, 'streamAttachment']);
        Route::get('{leaveRequestId}/download', [LeaveApprovalController::class, 'downloadAttachment'])->name('leave-approval.download-attachment');
    });
    Route::apiResource('leave-approvals', LeaveApprovalController::class);
});
