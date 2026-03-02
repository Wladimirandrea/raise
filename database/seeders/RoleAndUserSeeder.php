<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\User;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Roles ─────────────────────────────────────────────
        $roles = [
            ['name' => 'admin',        'description' => 'Administrador del sistema'],
            ['name' => 'case_manager', 'description' => 'Case Manager / Manejador de casos'],
            ['name' => 'client',       'description' => 'Cliente'],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(['name' => $roleData['name']], $roleData);
        }

        // ─── Avatares por defecto ──────────────────────────────
        $defaultAvatars = [
            'admin'        => 'avatars/admin.png',
            'case_manager' => 'avatars/casemanager.png',
            'client'       => 'avatars/client.png',
        ];

        // ─── Usuarios ──────────────────────────────────────────
        $users = [
            // Admin
            [
                'name'     => 'Admin Principal',
                'email'    => 'admin@raise.com',
                'password' => Hash::make('123456789'),
                'phone'    => '555-0001',
                'role'     => 'admin',
            ],

            // Case Managers
            [
                'name'     => 'Juan García',
                'email'    => 'juan-casemanager@raise.com',
                'password' => Hash::make('123456789'),
                'phone'    => '555-0002',
                'role'     => 'case_manager',
            ],
            [
                'name'     => 'María López',
                'email'    => 'maria-casemanager@raise.com',
                'password' => Hash::make('123456789'),
                'phone'    => '555-0003',
                'role'     => 'case_manager',
            ],
            [
                'name'     => 'Carlos Pérez',
                'email'    => 'carlos-casemanager@raise.com',
                'password' => Hash::make('123456789'),
                'phone'    => '555-0004',
                'role'     => 'case_manager',
            ],

            // Clientes
            [
                'name'     => 'Cliente María',
                'email'    => 'client@raise.com',
                'password' => Hash::make('123456789'),
                'phone'    => '555-0010',
                'role'     => 'client',
            ],
            [
                'name'     => 'Pedro Ramírez',
                'email'    => 'pedro@raise.com',
                'password' => Hash::make('123456789'),
                'phone'    => '555-0011',
                'role'     => 'client',
            ],
            [
                'name'     => 'Sofía Torres',
                'email'    => 'sofia@raise.com',
                'password' => Hash::make('123456789'),
                'phone'    => '555-0012',
                'role'     => 'client',
            ],
            [
                'name'     => 'Diego Morales',
                'email'    => 'diego@raise.com',
                'password' => Hash::make('123456789'),
                'phone'    => '555-0013',
                'role'     => 'client',
            ],
            [
                'name'     => 'Valentina Cruz',
                'email'    => 'valentina@raise.com',
                'password' => Hash::make('123456789'),
                'phone'    => '555-0014',
                'role'     => 'client',
            ],
            [
                'name'     => 'Andrés Vargas',
                'email'    => 'andres@raise.com',
                'password' => Hash::make('123456789'),
                'phone'    => '555-0015',
                'role'     => 'client',
            ],
            // 2 clientes SIN case manager asignado (para probar el tab "Sin asignar")
            [
                'name'     => 'Laura Sin Asignar',
                'email'    => 'laura@raise.com',
                'password' => Hash::make('123456789'),
                'phone'    => '555-0016',
                'role'     => 'client',
            ],
            [
                'name'     => 'Roberto Sin Asignar',
                'email'    => 'roberto@raise.com',
                'password' => Hash::make('123456789'),
                'phone'    => '555-0017',
                'role'     => 'client',
            ],
        ];

        // ─── Crear usuarios y asignar roles ───────────────────
        $createdUsers = [];
        foreach ($users as $userData) {
            $roleName = $userData['role'];
            unset($userData['role']);

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData + [
                    'is_active' => true,
                    'avatar'    => $defaultAvatars[$roleName] ?? 'avatars/default.png',
                ]
            );

            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching($role->id);
            }

            $createdUsers[$roleName][] = $user;
        }

        // ─── Asignar clientes a case managers ─────────────────
        // Los primeros 6 clientes se reparten entre los 3 case managers
        // Los últimos 2 quedan sin asignar (para probar el módulo)
        $caseManagers = $createdUsers['case_manager'] ?? [];
        $clients      = $createdUsers['client'] ?? [];

        foreach ($clients as $index => $client) {
            if ($index < 6 && count($caseManagers) > 0) {
                $cm = $caseManagers[$index % count($caseManagers)];
                $client->update(['case_manager_id' => $cm->id]);
            }
            // Los últimos 2 quedan con case_manager_id = null
        }

        // ─── Output ───────────────────────────────────────────
        $this->command->info('✅ Roles y usuarios creados correctamente.');
        $this->command->newLine();
        $this->command->info('🔐 Credenciales (password: 123456789)');
        $this->command->info('   Admin:        admin@raise.com');
        $this->command->info('   Case Manager: juan-casemanager@raise.com');
        $this->command->info('   Case Manager: maria-casemanager@raise.com');
        $this->command->info('   Case Manager: carlos-casemanager@raise.com');
        $this->command->info('   Clientes:     client@raise.com ... roberto@raise.com');
        $this->command->newLine();
        $this->command->info('📊 6 clientes asignados | 2 clientes sin asignar');
    }
}