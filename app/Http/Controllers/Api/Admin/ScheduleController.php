<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    // Devuelve todos los días (0-6) con su horario si existe
    public function index()
    {
        $schedules = Schedule::orderBy('day_of_week')->get()->keyBy('day_of_week');

        $days = collect([1, 2, 3, 4, 5, 6, 0])->map(function ($day) use ($schedules) {
            if ($schedules->has($day)) {
                return $schedules[$day];
            }
            return [
                'id'          => null,
                'day_of_week' => $day,
                'day_name'    => Schedule::dayName($day),
                'start_time'  => null,
                'end_time'    => null,
                'is_active'   => false,
            ];
        });

        return response()->json($days);
    }

    // Crear o actualizar el horario de un día
    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'is_active'   => 'boolean',
        ]);

        $schedule = Schedule::updateOrCreate(
            ['day_of_week' => $validated['day_of_week']],
            [
                'start_time' => $validated['start_time'],
                'end_time'   => $validated['end_time'],
                'is_active'  => $validated['is_active'] ?? true,
            ]
        );

        return response()->json($schedule->fresh(), 200);
    }

    // Activar / desactivar un día sin cambiar el horario
    public function toggle(Request $request, Schedule $schedule)
    {
        $schedule->update(['is_active' => !$schedule->is_active]);
        return response()->json($schedule->fresh());
    }

    // Eliminar el horario de un día
    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return response()->json(null, 204);
    }
}