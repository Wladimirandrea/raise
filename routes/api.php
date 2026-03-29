<?php

use App\Http\Controllers\Api\Admin\AppointmentController;
use App\Http\Controllers\Api\Admin\CaseManagerController;
use App\Http\Controllers\Api\CaseManager\CaseManagerDashboardController;
use App\Http\Controllers\Api\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\ScheduleController;
use App\Http\Controllers\Api\Admin\DayOffController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

// ─── Broadcasting ──────────────────────────────────────────
Route::post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:sanctum');

// ─── Auth pública ──────────────────────────────────────────
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login',    [LoginController::class, 'login']);

// ─── Admin routes ──────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {

    // USUARIOS → /api/admin/users
    Route::prefix('users')->group(function () {
        Route::get('/',          [UserController::class, 'index']);
        Route::post('/',         [UserController::class, 'store']);
        Route::get('/{user}',    [UserController::class, 'show']);
        Route::put('/{user}',    [UserController::class, 'update']);
        Route::post('/{user}',   [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
    });

    // SCHEDULES → /api/admin/schedules
    Route::prefix('schedules')->group(function () {
        Route::get('/',                    [ScheduleController::class, 'index']);
        Route::post('/upsert',             [ScheduleController::class, 'upsert']);
        Route::patch('/{schedule}/toggle', [ScheduleController::class, 'toggle']);
        Route::delete('/{schedule}',       [ScheduleController::class, 'destroy']);
    });

    // DAYS OFF → /api/admin/days-off
    Route::apiResource('days-off', DayOffController::class)->only(['index', 'store', 'destroy']);

    // APPOINTMENTS → /api/admin/appointments
    Route::prefix('appointments')->group(function () {
        Route::get('/form-data',              [AppointmentController::class, 'formData']);
        Route::get('/clients-by-manager',     [AppointmentController::class, 'clientsByManager']);
        Route::get('/available-slots',        [AppointmentController::class, 'availableSlots']);
        Route::get('/',                       [AppointmentController::class, 'index']);
        Route::post('/',                      [AppointmentController::class, 'store']);
        Route::get('/{appointment}',          [AppointmentController::class, 'show']);
        Route::put('/{appointment}',          [AppointmentController::class, 'update']);
        Route::patch('/{appointment}/status', [AppointmentController::class, 'updateStatus']);
        Route::delete('/{appointment}',       [AppointmentController::class, 'destroy']);
    });

    // CASE MANAGERS → /api/admin/case-managers
    Route::prefix('case-managers')->group(function () {
        Route::get('/',                   [CaseManagerController::class, 'index']);
        Route::get('/unassigned-clients', [CaseManagerController::class, 'unassignedClients']);
        Route::patch('/reassign',         [CaseManagerController::class, 'reassignClient']);
        Route::get('/{user}/clients',     [CaseManagerController::class, 'clients']);
        Route::post('/{user}/assign',     [CaseManagerController::class, 'assignClients']);
        Route::delete('/{user}/unassign', [CaseManagerController::class, 'unassignClient']);
    });
});

// ─── Case Manager routes ───────────────────────────────────
Route::middleware(['auth:sanctum', 'role:case_manager'])->prefix('case-manager')->group(function () {
    Route::get('/dashboard',    [CaseManagerDashboardController::class, 'dashboard']);
    Route::get('/appointments', [CaseManagerDashboardController::class, 'appointments']);
    Route::get('/clients',      [CaseManagerDashboardController::class, 'clients']);
    Route::patch('/appointments/{appointment}/status', [CaseManagerDashboardController::class, 'updateAppointmentStatus']);
});