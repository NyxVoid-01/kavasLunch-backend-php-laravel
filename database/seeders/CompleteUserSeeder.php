<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompleteUserSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar tabla primero
        User::truncate();

        $users = [
            // Administrador
            [
                'full_name' => 'Administrador KAVAS',
                'email' => 'admin@upch.pe',
                'password' => Hash::make('Admin123!'),
                'role' => 'admin',
            ],
            // Usuario demo
            [
                'full_name' => 'Usuario Demo',
                'email' => 'demo@upch.pe',
                'password' => Hash::make('Demo123!'),
                'role' => 'user',
            ],
            // Estudiantes de ejemplo
            [
                'full_name' => 'María García López',
                'email' => 'maria.garcia@upch.pe',
                'password' => Hash::make('Maria123!'),
                'role' => 'user',
            ],
            [
                'full_name' => 'Juan Carlos Pérez',
                'email' => 'juan.perez@upch.pe',
                'password' => Hash::make('Juan123!'),
                'role' => 'user',
            ],
            [
                'full_name' => 'Ana Sofía Rodríguez',
                'email' => 'ana.rodriguez@upch.pe',
                'password' => Hash::make('Ana123!'),
                'role' => 'user',
            ],
            // Docente
            [
                'full_name' => 'Dr. Luis Fernando Torres',
                'email' => 'luis.torres@upch.pe',
                'password' => Hash::make('Luis123!'),
                'role' => 'user',
            ],
            // Personal administrativo
            [
                'full_name' => 'Carmen Rosa Flores',
                'email' => 'carmen.flores@upch.pe',
                'password' => Hash::make('Carmen123!'),
                'role' => 'user',
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
            $this->command->info("✅ Usuario creado: {$userData['email']} ({$userData['role']})");
        }

        $this->command->info('🎉 Total: ' . User::count() . ' usuarios creados!');
        $this->command->line('');
        $this->command->line('📝 Credenciales de acceso:');
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->line('👑 Admin:  admin@upch.pe / Admin123!');
        $this->command->line('👤 Demo:   demo@upch.pe  / Demo123!');
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}