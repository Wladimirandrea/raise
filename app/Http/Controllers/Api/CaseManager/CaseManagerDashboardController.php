<?php

namespace App\Http\Controllers\Api\CaseManager;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\AppointmentStatusUpdated;
use App\Mail\AppointmentStatusMail;
use Illuminate\Support\Facades\Mail;

class CaseManagerDashboardController extends Controller
{
    // ─── GET /api/case-manager/dashboard ──────────────────────
    public function dashboard(): JsonResponse
    {
        $userId = Auth::id();
        $today  = Carbon::today();

        // Estadísticas generales
        $total     = Appointment::where('case_manager_id', $userId)->count();
        $pending   = Appointment::where('case_manager_id', $userId)->where('status', 'pending')->count();
        $confirmed = Appointment::where('case_manager_id', $userId)->where('status', 'confirmed')->count();
        $completed = Appointment::where('case_manager_id', $userId)->where('status', 'completed')->count();
        $cancelled = Appointment::where('case_manager_id', $userId)->where('status', 'cancelled')->count();
        $clients   = User::where('case_manager_id', $userId)->where('is_active', true)->count();

        // Citas de hoy
        $todayAppointments = Appointment::with(['client'])
            ->where('case_manager_id', $userId)
            ->whereDate('appointment_date', $today)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_time')
            ->get();

        // Próximas citas (los próximos 7 días, excluyendo hoy)
        $upcomingAppointments = Appointment::with(['client'])
            ->where('case_manager_id', $userId)
            ->whereDate('appointment_date', '>', $today)
            ->whereDate('appointment_date', '<=', $today->copy()->addDays(7))
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->take(5)
            ->get();

        return response()->json([
            'stats' => [
                'total'     => $total,
                'pending'   => $pending,
                'confirmed' => $confirmed,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'clients'   => $clients,
            ],
            'today_appointments'    => $todayAppointments,
            'upcoming_appointments' => $upcomingAppointments,
        ]);
    }

    // ─── GET /api/case-manager/appointments ───────────────────
    public function appointments(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $query = Appointment::with(['client'])
            ->where('case_manager_id', $userId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('appointment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('appointment_date', '<=', $request->date_to);
        }

        $appointments = $query->orderBy('appointment_date')->orderBy('start_time')->get();

        $events = $appointments->map(fn($a) => [
            'id'              => $a->id,
            'title'           => $a->title . ' - ' . $a->client->name,
            'start'           => $a->appointment_date->format('Y-m-d') . 'T' . $a->start_time,
            'end'             => $a->appointment_date->format('Y-m-d') . 'T' . $a->end_time,
            'backgroundColor' => $a->status_color,
            'borderColor'     => $a->status_color,
            'extendedProps'   => [
                'status'     => $a->status,
                'client'     => $a->client->name,
                'client_id'  => $a->client_id,
                'notes'      => $a->notes,
                'start_time' => $a->start_time,
                'end_time'   => $a->end_time,
            ],
        ]);

        return response()->json([
            'appointments' => $appointments,
            'events'       => $events,
        ]);
    }

    // ─── GET /api/case-manager/clients ────────────────────────
    public function clients(): JsonResponse
    {
        $userId = Auth::id();

        $clients = User::where('case_manager_id', $userId)
            ->where('is_active', true)
            ->withCount([
                'appointments as total_appointments' => fn($q) => $q->where('case_manager_id', $userId),
                'appointments as pending_appointments' => fn($q) => $q->where('case_manager_id', $userId)->where('status', 'pending'),
            ])
            ->with([
                'appointments' => fn($q) => $q
                    ->where('case_manager_id', $userId)
                    ->whereDate('appointment_date', '>=', Carbon::today())
                    ->whereNotIn('status', ['cancelled'])
                    ->orderBy('appointment_date')
                    ->orderBy('start_time')
                    ->take(1)
            ])
            ->orderBy('name')
            ->get();

        return response()->json(['clients' => $clients]);
    }

    public function updateAppointmentStatus(Request $request, Appointment $appointment): JsonResponse
    {
        if ($appointment->case_manager_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $request->validate([
            'status' => 'required|in:confirmed,completed,cancelled,no_show',
        ]);

        $appointment->update(['status' => $request->status]);
        $appointment->load(['caseManager', 'client']);

        event(new AppointmentStatusUpdated($appointment));

        $lang = $request->header('X-Locale', app()->getLocale() ?? 'es');

        // 👇 DEBUG — verifica que llega hasta aquí
        \Log::info('=== updateAppointmentStatus ===', [
            'appointment_id' => $appointment->id,
            'status'         => $appointment->status,
            'lang'           => $lang,
            'cm_id'          => $appointment->case_manager_id,
            'cm_email'       => $appointment->caseManager?->email,
            'client_id'      => $appointment->client_id ?? null,
            'client_email'   => $appointment->client?->email,
        ]);

        // Email al case manager
        try {
            $cm = User::find($appointment->case_manager_id);
            \Log::info('CM encontrado:', ['cm' => $cm?->email]);
            if ($cm?->email) {
                Mail::to($cm->email)->send(new AppointmentStatusMail($appointment, $lang, 'case_manager'));
                \Log::info('Email CM enviado OK');
            } else {
                \Log::warning('CM no tiene email');
            }
        } catch (\Exception $e) {
            \Log::error('Error email CM: ' . $e->getMessage());
        }

        // Email al cliente
        try {
            $client = $appointment->client;
            \Log::info('Cliente encontrado:', ['client' => $client?->email]);
            if ($client?->email) {
                Mail::to($client->email)->send(new AppointmentStatusMail($appointment, $lang, 'client'));
                \Log::info('Email cliente enviado OK');
            } else {
                \Log::warning('Cliente no tiene email o no está relacionado');
            }
        } catch (\Exception $e) {
            \Log::error('Error email cliente: ' . $e->getMessage());
        }

        return response()->json($appointment);
    }

    public function store(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'client_id'        => 'required|exists:users,id',
            'title'            => 'required|string|max:255',
            'appointment_date' => 'required|date|after_or_equal:today',
            'start_time'       => 'required|date_format:H:i',
            'notes'            => 'nullable|string',
        ]);

        // Verificar que el cliente pertenece a este case manager
        $client = User::find($validated['client_id']);
        if ($client->case_manager_id !== $userId) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $validated['case_manager_id'] = $userId;
        $validated['end_time'] = \Carbon\Carbon::parse($validated['start_time'])
            ->addMinutes(30)->format('H:i');

        // Verificar solapamiento
        $conflict = Appointment::where('case_manager_id', $userId)
            ->where('appointment_date', $validated['appointment_date'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($validated) {
                $q->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })->exists();

        if ($conflict) {
            return response()->json(['message' => 'Ya tienes una cita en ese horario.'], 422);
        }

        $appointment = Appointment::create($validated);
        $appointment->load(['caseManager', 'client']);

        $lang = $request->header('X-Locale', app()->getLocale() ?? 'es');

        // Emails
        try {
            if ($appointment->caseManager?->email) {
                Mail::to($appointment->caseManager->email)
                    ->send(new \App\Mail\AppointmentConfirmation($appointment, 'case_manager', $lang));
            }
            if ($appointment->client?->email) {
                Mail::to($appointment->client->email)
                    ->send(new \App\Mail\AppointmentConfirmation($appointment, 'client', $lang));
            }
        } catch (\Exception $e) {
            \Log::error('Error email nueva cita CM: ' . $e->getMessage());
        }

        return response()->json($appointment, 201);
    }

    public function availableSlots(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $request->validate([
            'date' => 'required|date|after_or_equal:today',
        ]);

        $date      = \Carbon\Carbon::parse($request->date);
        $dayOfWeek = $date->dayOfWeek;

        $schedule = \App\Models\Schedule::where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return response()->json(['available' => false, 'slots' => []]);
        }

        $slots   = [];
        $current = \Carbon\Carbon::parse($request->date . ' ' . $schedule->start_time);
        $end     = \Carbon\Carbon::parse($request->date . ' ' . $schedule->end_time);

        while ($current->copy()->addMinutes(30)->lte($end)) {
            $slots[] = [
                'start' => $current->format('H:i'),
                'end'   => $current->copy()->addMinutes(30)->format('H:i'),
                'label' => $current->format('H:i') . ' - ' . $current->copy()->addMinutes(30)->format('H:i'),
            ];
            $current->addMinutes(30);
        }

        $booked = Appointment::where('case_manager_id', $userId)
            ->where('appointment_date', $request->date)
            ->whereNotIn('status', ['cancelled'])
            ->get(['start_time', 'end_time']);

        $available = array_values(array_filter($slots, function ($slot) use ($booked) {
            foreach ($booked as $b) {
                if (
                    \Carbon\Carbon::parse($slot['start'])->lt(\Carbon\Carbon::parse($b->end_time)) &&
                    \Carbon\Carbon::parse($slot['end'])->gt(\Carbon\Carbon::parse($b->start_time))
                ) {
                    return false;
                }
            }
            return true;
        }));

        return response()->json(['available' => true, 'slots' => $available]);
    }
}
