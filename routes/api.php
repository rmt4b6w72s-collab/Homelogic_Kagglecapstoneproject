<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ResidentController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\VitalSignController;
use App\Http\Controllers\Api\MedicationController;
use App\Http\Controllers\Api\FacilityController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\VitalRangeController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ChartController;
use App\Http\Controllers\Api\MedicationAdministrationController;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\AssessmentQuestionController;
use App\Http\Controllers\Api\SleepRecordController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DrugController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\EmployeeDocumentController;
use App\Http\Controllers\Api\NotificationController;

Route::prefix('v1')->group(function () {
    // Auth routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->middleware('auth:sanctum');
    Route::get('/dashboard/resident-vitals/{residentId}', [DashboardController::class, 'residentVitalsTrend'])->middleware('auth:sanctum');

    // Residents
    Route::apiResource('residents', ResidentController::class)->middleware('auth:sanctum');
    Route::get('/residents/{id}/appointments', [ResidentController::class, 'appointments'])->middleware('auth:sanctum');
    Route::get('/residents/{id}/vitals', [ResidentController::class, 'vitals'])->middleware('auth:sanctum');

    // Appointments
    Route::apiResource('appointments', AppointmentController::class)->middleware('auth:sanctum');
    Route::patch('/appointments/{id}/status', [AppointmentController::class, 'updateStatus'])->middleware('auth:sanctum');

    // Vital Signs
    Route::apiResource('vitals', VitalSignController::class)->middleware('auth:sanctum');

    // Assessments
    Route::apiResource('assessments', AssessmentController::class)->middleware('auth:sanctum');
    Route::patch('/assessments/{id}/status', [AssessmentController::class, 'updateStatus'])->middleware('auth:sanctum');
    Route::patch('/assessments/{assessment}/questions/{question}', [AssessmentQuestionController::class, 'update'])->middleware('auth:sanctum');

    // Medications
    Route::apiResource('medications', MedicationController::class)->middleware('auth:sanctum');
    Route::get('/medications/administrations', [MedicationController::class, 'administrations'])->middleware('auth:sanctum');
    
    // Drugs
    Route::apiResource('drugs', DrugController::class)->middleware('auth:sanctum');

    // Medication Administrations
    Route::apiResource('medication-administrations', MedicationAdministrationController::class)->middleware('auth:sanctum');

    // Sleep Records
    Route::apiResource('sleep-records', SleepRecordController::class)->middleware('auth:sanctum');

    // Facilities & Branches
    Route::apiResource('facilities', FacilityController::class)->middleware('auth:sanctum');
    Route::apiResource('branches', BranchController::class)->middleware('auth:sanctum');

    // Vital ranges
    Route::apiResource('vital-ranges', VitalRangeController::class)->middleware('auth:sanctum');

    // Leave Requests
    Route::apiResource('leave-requests', LeaveRequestController::class)->middleware('auth:sanctum');

    // Roles & permissions
    Route::apiResource('roles', RoleController::class)->middleware('auth:sanctum');
    Route::get('/permissions', [RoleController::class, 'permissions'])->middleware('auth:sanctum');

    // Users
    Route::apiResource('users', UserController::class)->middleware('auth:sanctum');

    // Employee Documents
    Route::apiResource('employee-documents', EmployeeDocumentController::class)->middleware('auth:sanctum');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->middleware('auth:sanctum');
    Route::get('/notifications/count', [NotificationController::class, 'count'])->middleware('auth:sanctum');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->middleware('auth:sanctum');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->middleware('auth:sanctum');

    // Charts
    Route::prefix('charts')->middleware('auth:sanctum')->group(function () {
        Route::get('/residents', [ChartController::class, 'residentStats']);
        Route::get('/vitals', [ChartController::class, 'vitalsStats']);
        Route::get('/assessments', [ChartController::class, 'assessmentStats']);
        Route::get('/appointments', [ChartController::class, 'appointmentStats']);
        Route::get('/sleep', [ChartController::class, 'sleepStats']);
        Route::get('/staff', [ChartController::class, 'staffStats']);
    });
});

