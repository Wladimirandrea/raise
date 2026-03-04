<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Schedule;
use App\Models\User;
use App\Notifications\AppointmentNotification;
use Carbon\Carbon;
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
            'title' => $a->title . ' (' . $a->caseManager->name . ')',
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
            'notes'            => 'nullable|string',
        ]);

        // Calcular end_time automáticamente: start + 30 min
        $validated['end_time'] = Carbon::parse($validated['start_time'])
            ->addMinutes(30)
            ->format('H:i');

        // Verificar solapamiento
        $conflict = Appointment::where('case_manager_id', $validated['case_manager_id'])
            ->where('appointment_date', $validated['appointment_date'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($validated) {
                $q->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })->exists();

        if ($conflict) {
            return response()->json([
                'message' => 'El case manager ya tiene una cita en ese horario.'
            ], 422);
        }

        $appointment = Appointment::create($validated);
        $appointment->load(['caseManager', 'client']);

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
            'title'  => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|in:pending,confirmed,cancelled,completed,no_show',
            'notes'  => 'nullable|string',
        ]);

        $appointment->update($validated);
        $appointment->load(['caseManager', 'client']);

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

    // ─── GET /admin/appointments/clients-by-manager ───────────
    public function clientsByManager(Request $request): JsonResponse
    {
        $request->validate(['case_manager_id' => 'required|exists:users,id']);

        $clients = User::where('case_manager_id', $request->case_manager_id)
            ->where('is_active', true)
            ->select('id', 'name', 'avatar')
            ->orderBy('name')
            ->get();

        return response()->json(['clients' => $clients]);
    }

    // ─── GET /admin/appointments/available-slots ──────────────
    public function availableSlots(Request $request): JsonResponse
    {
        $request->validate([
            'case_manager_id' => 'required|exists:users,id',
            'date'            => 'required|date|after_or_equal:today',
        ]);

        $date      = Carbon::parse($request->date);
        $dayOfWeek = $date->dayOfWeek;

        $schedule = Schedule::where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return response()->json([
                'available' => false,
                'message'   => 'No hay horario disponible para ese día.',
                'slots'     => [],
            ]);
        }

        $slots   = [];
        $current = Carbon::parse($request->date . ' ' . $schedule->start_time);
        $end     = Carbon::parse($request->date . ' ' . $schedule->end_time);

        while ($current->copy()->addMinutes(30)->lte($end)) {
            $slots[] = [
                'start' => $current->format('H:i'),
                'end'   => $current->copy()->addMinutes(30)->format('H:i'),
                'label' => $current->format('H:i') . ' - ' . $current->copy()->addMinutes(30)->format('H:i'),
            ];
            $current->addMinutes(30);
        }

        $bookedSlots = Appointment::where('case_manager_id', $request->case_manager_id)
            ->where('appointment_date', $request->date)
            ->whereNotIn('status', ['cancelled'])
            ->get(['start_time', 'end_time']);

        $availableSlots = array_values(array_filter($slots, function ($slot) use ($bookedSlots) {
            foreach ($bookedSlots as $booked) {
                $slotStart   = Carbon::parse($slot['start']);
                $slotEnd     = Carbon::parse($slot['end']);
                $bookedStart = Carbon::parse($booked->start_time);
                $bookedEnd   = Carbon::parse($booked->end_time);
                if ($slotStart->lt($bookedEnd) && $slotEnd->gt($bookedStart)) {
                    return false;
                }
            }
            return true;
        }));

        return response()->json([
            'available' => true,
            'schedule'  => ['start' => $schedule->start_time, 'end' => $schedule->end_time],
            'slots'     => $availableSlots,
            'booked'    => $bookedSlots->map(fn($b) => [
                'start' => substr($b->start_time, 0, 5),
                'end'   => substr($b->end_time, 0, 5),
            ]),
        ]);
    }
}
