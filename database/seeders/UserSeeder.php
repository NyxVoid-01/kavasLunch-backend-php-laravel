<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed users table with initial data
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'id' => 'd2636b72-5fc8-437e-a4fe-da007091e4bf',
                'email' => 'ruben.rojas@upch.pe',
                'password' => Hash::make('password'),
                'full_name' => 'Ruben Anderson Rojas Ramos',
                'role' => 'user',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '71baea35-4d10-4700-9321-7d03e3fe7e3e',
                'email' => 'admin@upch.pe',
                'password' => Hash::make('password'),
                'full_name' => 'Administrador',
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'e6b1f610-d6fb-4260-a8ac-f66b8474aa2a',
                'email' => 'exidax.666@upch.pe',
                'password' => Hash::make('password'),
                'full_name' => 'Diego Thiago Rojas Ramos',
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'cf178675-601c-443a-b25e-80f63a314c49',
                'email' => 'juanito@upch.pe',
                'password' => Hash::make('password'),
                'full_name' => 'Juanito Pérez',
                'role' => 'user',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '70252a50-5c39-429f-8a60-3eed453a0df1',
                'email' => 'demo@upch.pe',
                'password' => Hash::make('password'),
                'full_name' => 'Usuario Demo',
                'role' => 'user',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '47d7762e-14c0-475b-b5c9-e486718b34d8',
                'email' => 'cristiano@upch.pe',
                'password' => Hash::make('password'),
                'full_name' => 'Cristiano Ronaldo',
                'role' => 'user',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '40164859-c59a-4db9-b1cb-6651a3c162b2',
                'email' => 'maria.lopez@upch.pe',
                'password' => Hash::make('password'),
                'full_name' => 'María López',
                'role' => 'user',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '76eb1f7e-4b76-4a3c-804a-dc5bb969d823',
                'email' => 'carlos.garcia@upch.pe',
                'password' => Hash::make('password'),
                'full_name' => 'Carlos García',
                'role' => 'user',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->command->info('✅ 8 usuarios creados (password: password)');
    }
}
