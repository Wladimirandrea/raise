<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DayOff;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DayOffController extends Controller
{
    // GET /admin/days-off
    public function index(): JsonResponse
    {
        $daysOff = DayOff::orderBy('date')->orderBy('start_time')->get();
        return response()->json($daysOff);
    }

    // POST /admin/days-off
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date'       => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
            'reason'     => 'nullable|string|max:255',
        ]);

        // Verificar solapamiento con otro day off el mismo día
        $conflict = DayOff::where('date', $validated['date'])
            ->where(function ($q) use ($validated) {
                $q->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })->exists();

        if ($conflict) {
            return response()->json([
                'message' => 'Ya existe un bloqueo que se superpone con ese horario.'
            ], 422);
        }

        $dayOff = DayOff::create($validated);
        return response()->json($dayOff, 201);
    }

    // DELETE /admin/days-off/{id}
    public function destroy(DayOff $days_off): JsonResponse
    {
        $days_off->delete();
        return response()->json(['message' => 'Día off eliminado correctamente.']);
    }
}
