<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{
    // ─── GET /api/admin/appointments ──────────────────────────
    // Retorna todas las citas (con filtros opcionales)
    public function index(Request $request): JsonResponse
    {
        $query = Appointment::with(['caseManager', 'client']);

        if ($request->filled('case_manager_id')) {
            $query->forCaseManager($request->case_manager_id);
        }

        if ($request->filled('client_id')) {
            $query->forClient($request->client_id);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('appointment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('appointment_date', '<=', $request->date_to);
        }

        $appointments = $query->orderBy('appointment_date')->orderBy('start_time')->get();

        // Formato para FullCalendar
        $events = $appointments->map(fn($a) => [
            'id'               => $a->id,
            'title'            => $a->title,
            'start'            => $a->appointment_date->format('Y-m-d') . 'T' . $a->start_time,
            'end'              => $a->appointment_date->format('Y-m-d') . 'T' . $a->end_time,
            'backgroundColor'  => $a->status_color,
            'borderColor'      => $a->status_color,
            'extendedProps'    => [
                'status'           => $a->status,
                'status_label'     => $a->status_label,
                'case_manager'     => $a->caseManager->name,
                'case_manager_id'  => $a->case_manager_id,
                'client'           => $a->client->name,
                'client_id'        => $a->client_id,
                'notes'            => $a->notes,
                'start_time'       => $a->start_time,
                'end_time'         => $a->end_time,
            ],
        ]);

        return response()->json([
            'events'       => $events,
            'appointments' => $appointments, // para la tabla lista
        ]);
    }

    // ─── POST /api/admin/appointments ─────────────────────────
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'case_manager_id'  => 'required|exists:users,id',
            'client_id'        => 'required|exists:users,id',
            'title'            => 'required|string|max:255',
            'appointment_date' => 'required|date|after_or_equal:today',
            'start_time'       => 'required|date_format:H:i',
            'end_time'         => 'required|date_format:H:i|after:start_time',
            'notes'            => 'nullable|string',
        ]);

        // Verificar que no haya solapamiento para el case manager
        $conflict = Appointment::where('case_manager_id', $validated['case_manager_id'])
            ->where('appointment_date', $validated['appointment_date'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($validated) {
                $q->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                  ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']]);
            })->exists();

        if ($conflict) {
            return response()->json([
                'message' => 'El case manager ya tiene una cita en ese horario.'
            ], 422);
        }

        $appointment = Appointment::create($validated);
        $appointment->load(['caseManager', 'client']);

        // Notificar por email al case manager y al cliente
        $appointment->caseManager->notify(new AppointmentNotification($appointment, 'created'));
        $appointment->client->notify(new AppointmentNotification($appointment, 'created'));

        return response()->json($appointment, 201);
    }

    // ─── GET /api/admin/appointments/{id} ─────────────────────
    public function show(Appointment $appointment): JsonResponse
    {
        return response()->json($appointment->load(['caseManager', 'client']));
    }

    // ─── PUT /api/admin/appointments/{id} ─────────────────────
    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'case_manager_id'  => 'required|exists:users,id',
            'client_id'        => 'required|exists:users,id',
            'title'            => 'required|string|max:255',
            'appointment_date' => 'required|date',
            'start_time'       => 'required|date_format:H:i',
            'end_time'         => 'required|date_format:H:i|after:start_time',
            'notes'            => 'nullable|string',
        ]);

        $appointment->update($validated);
        $appointment->load(['caseManager', 'client']);

        // Notificar cambio
        $appointment->caseManager->notify(new AppointmentNotification($appointment, 'updated'));
        $appointment->client->notify(new AppointmentNotification($appointment, 'updated'));

        return response()->json($appointment);
    }

    // ─── PATCH /api/admin/appointments/{id}/status ────────────
    public function updateStatus(Request $request, Appointment $appointment): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed,no_show',
        ]);

        $appointment->update(['status' => $request->status]);
        $appointment->load(['caseManager', 'client']);

        // Notificar cambio de estado
        $appointment->caseManager->notify(new AppointmentNotification($appointment, 'status_changed'));
        $appointment->client->notify(new AppointmentNotification($appointment, 'status_changed'));

        return response()->json($appointment);
    }

    // ─── DELETE /api/admin/appointments/{id} ──────────────────
    public function destroy(Appointment $appointment): JsonResponse
    {
        $appointment->load(['caseManager', 'client']);

        // Notificar cancelación antes de borrar
        $appointment->caseManager->notify(new AppointmentNotification($appointment, 'cancelled'));
        $appointment->client->notify(new AppointmentNotification($appointment, 'cancelled'));

        $appointment->delete();

        return response()->json(['message' => 'Cita eliminada correctamente.']);
    }

    // ─── GET /api/admin/appointments/form-data ────────────────
    // Retorna case managers y clientes para los selects del formulario
    public function formData(): JsonResponse
    {
        // Case managers = usuarios con rol 'case_manager'
        $caseManagers = User::whereHas('roles', fn($q) => $q->where('name', 'case_manager'))
            ->where('is_active', true)
            ->select('id', 'name', 'email', 'avatar')
            ->get();

        // Clientes = usuarios con rol 'client'
        $clients = User::whereHas('roles', fn($q) => $q->where('name', 'client'))
            ->where('is_active', true)
            ->select('id', 'name', 'email', 'avatar')
            ->get();

        return response()->json([
            'case_managers' => $caseManagers,
            'clients'       => $clients,
        ]);
    }
}