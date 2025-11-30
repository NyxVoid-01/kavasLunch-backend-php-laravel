<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Iniciando seeders de KAVAS-LUNCH...');
        $this->command->line('');

        $this->call([
            CompleteUserSeeder::class,
            CompleteDishSeeder::class,
            WeeklyMenuSeeder::class,
        ]);

        $this->command->line('');
        $this->command->info('✅ ¡Todos los seeders ejecutados exitosamente!');
        $this->command->line('');
        $this->command->line('🎯 Resumen:');
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->line('👥 Usuarios: ' . \App\Models\User::count());
        $this->command->line('🍽️  Platos: ' . \App\Models\Dish::count());
        $this->command->line('📅 Menús: ' . \App\Models\DailyMenu::count());
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}