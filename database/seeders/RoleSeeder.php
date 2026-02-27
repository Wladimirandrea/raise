<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
     public function run(): void
    {
        Role::create(['name' => 'admin', 'description' => 'Administrador']);
        Role::create(['name' => 'casemanager', 'description' => 'Case Manager']);
        Role::create(['name' => 'client', 'description' => 'Cliente']);
    }
}