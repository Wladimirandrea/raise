<?php

use App\Http\Controllers\Api\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\ScheduleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

// ✅ Ruta de autenticación de canales privados de Reverb
Route::post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:sanctum');


Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);

// ─── USUARIOS ─────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin/users')->group(function () {
    Route::get('/',        [UserController::class, 'index']);
    Route::post('/',       [UserController::class, 'store']);
    Route::get('/{user}',  [UserController::class, 'show']);
    Route::put('/{user}',  [UserController::class, 'update']);
    Route::post('/{user}', [UserController::class, 'update']);
    Route::delete('/{user}', [UserController::class, 'destroy']);
});

// ─── SCHEDULES ────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('schedules',                          [ScheduleController::class, 'index']);
    Route::post('schedules/upsert',                  [ScheduleController::class, 'upsert']);
    Route::patch('schedules/{schedule}/toggle',      [ScheduleController::class, 'toggle']);
    Route::delete('schedules/{schedule}',            [ScheduleController::class, 'destroy']);
});