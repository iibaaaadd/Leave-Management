<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\LeaveRequestController;
use App\Http\Controllers\API\LeaveApprovalController;
use App\Models\LeaveRequest;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('users', UserController::class);
    Route::get('users/{id}/audits', [UserController::class, 'audits']);
    Route::apiResource('leave-requests', LeaveRequestController::class);
    Route::prefix('audit/leave-requests')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'auditTrail']);
        Route::get('{id}', [LeaveRequestController::class, 'specificAuditTrail']);
    });
    Route::apiResource('leave-approvals', LeaveApprovalController::class);
    Route::post('/leave-approvals/{leaveRequestId}/approve', [LeaveApprovalController::class, 'approve']);
});
