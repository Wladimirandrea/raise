<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CaseManagerController extends Controller
{
    // ─── GET /api/admin/case-managers ─────────────────────────
    // Lista todos los case managers con conteo de clientes
    public function index(): JsonResponse
    {
        $caseManagers = User::whereHas('roles', fn($q) => $q->where('name', 'case_manager'))
            ->withCount('clients')
            ->with(['clients' => fn($q) => $q->select('id', 'name', 'email', 'avatar', 'is_active', 'case_manager_id')])
            ->orderBy('name')
            ->get()
            ->map(fn($cm) => [
                'id'            => $cm->id,
                'name'          => $cm->name,
                'email'         => $cm->email,
                'avatar'        => $cm->avatar,
                'avatar_url'    => $cm->avatar_url,
                'is_active'     => $cm->is_active,
                'clients_count' => $cm->clients_count,
                'clients'       => $cm->clients,
            ]);

        return response()->json($caseManagers);
    }

    // ─── GET /api/admin/case-managers/{id}/clients ────────────
    // Clientes asignados a un case manager específico
    public function clients(User $user): JsonResponse
    {
        abort_unless($user->isCaseManager(), 403, 'El usuario no es un case manager.');

        $clients = $user->clients()
            ->select('id', 'name', 'email', 'avatar', 'phone', 'is_active', 'case_manager_id')
            ->orderBy('name')
            ->get();

        return response()->json([
            'case_manager' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'avatar_url' => $user->avatar_url,
            ],
            'clients' => $clients,
        ]);
    }

    // ─── GET /api/admin/case-managers/unassigned-clients ──────
    // Clientes sin case manager asignado
    public function unassignedClients(): JsonResponse
    {
        $clients = User::whereHas('roles', fn($q) => $q->where('name', 'client'))
            ->whereNull('case_manager_id')
            ->select('id', 'name', 'email', 'avatar', 'phone', 'is_active')
            ->orderBy('name')
            ->get();

        return response()->json($clients);
    }

    // ─── POST /api/admin/case-managers/{id}/assign ────────────
    // Asignar uno o varios clientes a un case manager
    public function assignClients(Request $request, User $user): JsonResponse
    {
        abort_unless($user->isCaseManager(), 403, 'El usuario no es un case manager.');

        $request->validate([
            'client_ids'   => 'required|array|min:1',
            'client_ids.*' => 'exists:users,id',
        ]);

        // Actualizar case_manager_id en cada cliente seleccionado
        User::whereIn('id', $request->client_ids)
            ->whereHas('roles', fn($q) => $q->where('name', 'client'))
            ->update(['case_manager_id' => $user->id]);

        return response()->json([
            'message'       => count($request->client_ids) . ' cliente(s) asignado(s) correctamente.',
            'clients_count' => $user->clients()->count(),
        ]);
    }

    // ─── DELETE /api/admin/case-managers/{id}/unassign ────────
    // Desasignar un cliente de su case manager
    public function unassignClient(Request $request, User $user): JsonResponse
    {
        abort_unless($user->isCaseManager(), 403, 'El usuario no es un case manager.');

        $request->validate([
            'client_id' => 'required|exists:users,id',
        ]);

        User::where('id', $request->client_id)
            ->where('case_manager_id', $user->id)
            ->update(['case_manager_id' => null]);

        return response()->json([
            'message'       => 'Cliente desasignado correctamente.',
            'clients_count' => $user->clients()->count(),
        ]);
    }

    // ─── PATCH /api/admin/case-managers/reassign ──────────────
    // Reasignar un cliente a otro case manager
    public function reassignClient(Request $request): JsonResponse
    {
        $request->validate([
            'client_id'          => 'required|exists:users,id',
            'new_case_manager_id' => 'required|exists:users,id',
        ]);

        User::where('id', $request->client_id)
            ->update(['case_manager_id' => $request->new_case_manager_id]);

        return response()->json(['message' => 'Cliente reasignado correctamente.']);
    }
}