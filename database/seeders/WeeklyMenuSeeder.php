<?php

namespace Database\Seeders;

use App\Models\DailyMenu;
use App\Models\Dish;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class WeeklyMenuSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar tabla primero
        DailyMenu::truncate();

        // Obtener platos por categoría
        $appetizers = Dish::where('category', 'appetizer')->where('is_active', true)->get();
        $mainDishes = Dish::where('category', 'main')->where('is_active', true)->get();
        $drinks = Dish::where('category', 'drink')->where('is_active', true)->get();

        if ($appetizers->isEmpty() || $mainDishes->isEmpty() || $drinks->isEmpty()) {
            $this->command->error('❌ No hay platos disponibles. Ejecuta primero CompleteDishSeeder');
            return;
        }

        // Configuración de menús por día de la semana
        $weeklyMenus = [
            // Lunes
            [
                'day_offset' => 0, // Hoy
                'appetizer' => 'Ensalada César',
                'main' => 'Pollo a la Plancha',
                'drink' => 'Chicha Morada',
                'price' => 12.00,
                'stock' => 50,
            ],
            // Martes
            [
                'day_offset' => 1,
                'appetizer' => 'Sopa de Pollo',
                'main' => 'Lomo Saltado',
                'drink' => 'Limonada Natural',
                'price' => 13.50,
                'stock' => 45,
            ],
            // Miércoles
            [
                'day_offset' => 2,
                'appetizer' => 'Crema de Zapallo',
                'main' => 'Ají de Gallina',
                'drink' => 'Refresco de Maracuyá',
                'price' => 12.50,
                'stock' => 50,
            ],
            // Jueves
            [
                'day_offset' => 3,
                'appetizer' => 'Causa Limeña',
                'main' => 'Tallarines Rojos con Pollo',
                'drink' => 'Chicha Morada',
                'price' => 12.50,
                'stock' => 55,
            ],
            // Viernes
            [
                'day_offset' => 4,
                'appetizer' => 'Ensalada Mixta',
                'main' => 'Pescado Frito',
                'drink' => 'Limonada Natural',
                'price' => 13.50,
                'stock' => 40,
            ],
            // Semana siguiente - Lunes
            [
                'day_offset' => 7,
                'appetizer' => 'Sopa Criolla',
                'main' => 'Milanesa de Pollo',
                'drink' => 'Inca Kola',
                'price' => 12.00,
                'stock' => 50,
            ],
            // Semana siguiente - Martes
            [
                'day_offset' => 8,
                'appetizer' => 'Ensalada César',
                'main' => 'Seco de Res',
                'drink' => 'Jugo de Papaya',
                'price' => 14.00,
                'stock' => 45,
            ],
            // Semana siguiente - Miércoles
            [
                'day_offset' => 9,
                'appetizer' => 'Crema de Zapallo',
                'main' => 'Chaufa de Pollo',
                'drink' => 'Chicha Morada',
                'price' => 11.50,
                'stock' => 55,
            ],
            // Semana siguiente - Jueves
            [
                'day_offset' => 10,
                'appetizer' => 'Causa Limeña',
                'main' => 'Tallarines Verdes con Bistec',
                'drink' => 'Refresco de Maracuyá',
                'price' => 14.00,
                'stock' => 50,
            ],
            // Semana siguiente - Viernes
            [
                'day_offset' => 11,
                'appetizer' => 'Ensalada Mixta',
                'main' => 'Carapulcra con Sopa Seca',
                'drink' => 'Emoliente',
                'price' => 13.50,
                'stock' => 40,
            ],
        ];

        $menusCreated = 0;

        foreach ($weeklyMenus as $menuConfig) {
            $date = Carbon::today()->addDays($menuConfig['day_offset']);

            // Buscar los platos
            $appetizer = $appetizers->where('name', $menuConfig['appetizer'])->first();
            $mainDish = $mainDishes->where('name', $menuConfig['main'])->first();
            $drink = $drinks->where('name', $menuConfig['drink'])->first();

            if (!$appetizer || !$mainDish || !$drink) {
                $this->command->warn("⚠️  Saltando menú para {$date->format('Y-m-d')} - platos no encontrados");
                continue;
            }

            DailyMenu::create([
                'date' => $date,
                'appetizer_id' => $appetizer->id,
                'main_dish_id' => $mainDish->id,
                'drink_id' => $drink->id,
                'price' => $menuConfig['price'],
                'stock' => $menuConfig['stock'],
                'is_active' => true,
            ]);

            $menusCreated++;
            $this->command->info("✅ Menú creado para {$date->format('l, d/m/Y')}");
        }

        $this->command->info("🎉 Total: {$menusCreated} menús creados exitosamente!");
    }
}