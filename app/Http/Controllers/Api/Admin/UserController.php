<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->get();
        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone'    => 'nullable|string|max:15',
            'role'     => 'required|exists:roles,name',
            'avatar'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Avatar por defecto según rol
        $defaultAvatars = [
            'admin'       => 'avatars/admin.png',
            'case_manager' => 'avatars/casemanager.png',
            'client'      => 'avatars/client.png',
        ];

        $data = [
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone'    => $validated['phone'] ?? null,
            'avatar'   => $defaultAvatars[$validated['role']] ?? 'avatars/default.png',
        ];

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create($data);

        $role = Role::where('name', $validated['role'])->first();
        $user->roles()->attach($role->id);

        return response()->json($user->load('roles'), 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'phone'    => 'nullable|string|max:15',
            'role'     => 'sometimes|exists:roles,name',
            'avatar'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = [];

        if (isset($validated['name']))  $data['name']  = $validated['name'];
        if (isset($validated['email'])) $data['email'] = $validated['email'];
        if (isset($validated['phone'])) $data['phone'] = $validated['phone'];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            \Log::info('Avatar recibido', [
                'name'  => $request->file('avatar')->getClientOriginalName(),
                'size'  => $request->file('avatar')->getSize(),
                'valid' => $request->file('avatar')->isValid(),
            ]);

            // Eliminar avatar anterior si es uno subido por el usuario (no default)
            $defaultAvatars = ['avatars/admin.png', 'avatars/casemanager.png', 'avatars/client.png', 'avatars/default.png'];
            if ($user->avatar && !in_array($user->avatar, $defaultAvatars)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            \Log::info('Avatar guardado en: ' . $path);
            $data['avatar'] = $path;
        }

        $user->update($data);

        \Log::info('Data enviada al update:', $data);
        \Log::info('Avatar en DB después del update:', ['avatar' => $user->fresh()->avatar]);

        if (isset($validated['role'])) {
            $role = Role::where('name', $validated['role'])->first();
            $user->roles()->sync([$role->id]);
        }

        return response()->json($user->load('roles'));
    }

    public function show(User $user)
    {
        return response()->json($user->load('roles'));
    }

    public function destroy(User $user)
    {
        // Evitar eliminar al propio admin o usuarios críticos
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'No puedes eliminarte a ti mismo'], 403);
        }

        $user->delete();
        return response()->json(null, 204);
    }
}