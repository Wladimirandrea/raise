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
        // Crear roles
        $roles = [
            ['name' => 'admin',       'description' => 'Administrador del sistema'],
            ['name' => 'casemanager', 'description' => 'case manager / Manejador de casos'],
            ['name' => 'client',      'description' => 'Cliente'],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(['name' => $roleData['name']], $roleData);
        }

        // Avatares por defecto según rol
        $defaultAvatars = [
            'admin'       => 'avatars/admin.png',
            'casemanager' => 'avatars/casemanager.png',
            'client'      => 'avatars/client.png',
        ];

        // Crear usuarios de prueba
        $users = [
            [
                'name'     => 'Admin Principal',
                'email'    => 'admin@raise.com',
                'password' => Hash::make('123456789'),
                'phone'    => '555-0001',
                'role'     => 'admin',
            ],
            [
                'name'     => 'Juan',
                'email'    => 'juan-casemanager@raise.com',
                'password' => Hash::make('123456789'),
                'phone'    => '555-0002',
                'role'     => 'casemanager',
            ],
            [
                'name'     => 'Cliente María',
                'email'    => 'client@raise.com',
                'password' => Hash::make('123456789'),
                'phone'    => '555-0003',
                'role'     => 'client',
            ],
        ];

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
        }

        $this->command->info('Roles y usuarios de prueba creados exitosamente.');
        $this->command->info('Admin: admin@raise.com / 123456789');
        $this->command->info('Juan: juan-casemanager@raise.com / 123456789');
        $this->command->info('Client: client@raise.com / 123456789');
    }
}