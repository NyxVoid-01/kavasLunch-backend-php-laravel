<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyMenuSeeder extends Seeder
{
    /**
     * Seed daily_menus table with sample weekly menus
     * Primera semana de diciembre 2025 (Lunes 1 - Viernes 5)
     */
    public function run(): void
    {
        // Primera semana de diciembre 2025: Lunes 1 de diciembre
        $startDate = Carbon::create(2025, 12, 1);

        DB::table('daily_menus')->insert([
            // Lunes
            [
                'id' => 'b1beac44-f1ee-4539-b5a4-0b3f58f12ca0',
                'date' => $startDate->copy()->toDateString(),
                'appetizer_id' => '3434ffe5-b747-42e9-a1b4-41f25446c45c', // Ensalada César
                'main_dish_id' => 'b8b5b75c-c495-4ec6-ae4f-aebfedbd65f3', // Pollo a la Plancha
                'drink_id' => '316bd56c-1d17-4221-98a5-ff9c49ba6b7a', // Chicha Morada
                'price' => 12.00,
                'stock' => 50,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '7ce5470c-69ab-4dcb-962c-a5d52d75837a',
                'date' => $startDate->copy()->toDateString(),
                'appetizer_id' => '414b4dca-a0cb-44e7-bc8a-faa26b959667', // Sopa de Pollo
                'main_dish_id' => 'a06de96f-499f-4929-bc5b-d526bf55a7e6', // Ají de Gallina
                'drink_id' => '2127e6c9-9ca7-4fda-8c97-9077efd10c7b', // Emoliente
                'price' => 12.50,
                'stock' => 50,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Martes
            [
                'id' => '623d4c0b-7f36-4e3e-9e76-fc3a74db1b41',
                'date' => $startDate->copy()->addDay()->toDateString(),
                'appetizer_id' => '414b4dca-a0cb-44e7-bc8a-faa26b959667', // Sopa de Pollo
                'main_dish_id' => '4d7fde66-7e24-4288-bcfd-43dd63017731', // Lomo Saltado
                'drink_id' => '27effa86-852c-47cc-9b70-a2a1f6c36f3d', // Limonada
                'price' => 13.50,
                'stock' => 50,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '2206920c-f2ce-4a97-ba41-f17f766f1a95',
                'date' => $startDate->copy()->addDay()->toDateString(),
                'appetizer_id' => 'b0c496ec-7537-4b2a-955c-b3b095daafbe', // Causa
                'main_dish_id' => '2d05f77a-cc75-454e-94e8-cdfe6ace2ebd', // Arroz con Pollo
                'drink_id' => '316bd56c-1d17-4221-98a5-ff9c49ba6b7a', // Chicha Morada
                'price' => 12.00,
                'stock' => 50,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Miércoles
            [
                'id' => '17fb5e26-9838-4f67-99f7-7d6ac496fb51',
                'date' => $startDate->copy()->addDays(2)->toDateString(),
                'appetizer_id' => '4ee49309-bdd2-434e-9fa0-90c4f32a7301', // Crema de Zapallo
                'main_dish_id' => 'a06de96f-499f-4929-bc5b-d526bf55a7e6', // Ají de Gallina
                'drink_id' => '12dc09f7-5795-4214-8cf8-f4b6d5876c70', // Maracuyá
                'price' => 12.50,
                'stock' => 50,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'c6f6551b-ad6d-4729-8418-cbeb0a629856',
                'date' => $startDate->copy()->addDays(2)->toDateString(),
                'appetizer_id' => '3434ffe5-b747-42e9-a1b4-41f25446c45c', // Ensalada César
                'main_dish_id' => '5d8a7645-5d3c-4479-a91d-37a0d8dd8699', // Seco de Carne
                'drink_id' => '27effa86-852c-47cc-9b70-a2a1f6c36f3d', // Limonada
                'price' => 13.00,
                'stock' => 50,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Jueves
            [
                'id' => '86302645-5081-4076-bf6f-fdf74bbe9aba',
                'date' => $startDate->copy()->addDays(3)->toDateString(),
                'appetizer_id' => 'b0c496ec-7537-4b2a-955c-b3b095daafbe', // Causa
                'main_dish_id' => '2d05f77a-cc75-454e-94e8-cdfe6ace2ebd', // Arroz con Pollo
                'drink_id' => '316bd56c-1d17-4221-98a5-ff9c49ba6b7a', // Chicha Morada
                'price' => 12.00,
                'stock' => 50,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '6881d5aa-7a0f-4b24-97d2-4ccf7857197b',
                'date' => $startDate->copy()->addDays(3)->toDateString(),
                'appetizer_id' => '4ee49309-bdd2-434e-9fa0-90c4f32a7301', // Crema de Zapallo
                'main_dish_id' => '4d7fde66-7e24-4288-bcfd-43dd63017731', // Lomo Saltado
                'drink_id' => '12dc09f7-5795-4214-8cf8-f4b6d5876c70', // Maracuyá
                'price' => 13.50,
                'stock' => 50,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Viernes
            [
                'id' => '635044fc-eee5-4532-8104-d11705d0b8dd',
                'date' => $startDate->copy()->addDays(4)->toDateString(),
                'appetizer_id' => '414b4dca-a0cb-44e7-bc8a-faa26b959667', // Sopa de Pollo
                'main_dish_id' => 'b8b5b75c-c495-4ec6-ae4f-aebfedbd65f3', // Pollo a la Plancha
                'drink_id' => '27effa86-852c-47cc-9b70-a2a1f6c36f3d', // Limonada
                'price' => 11.50,
                'stock' => 50,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '233ce5b6-f79f-4c0b-b4d7-703e72f7377c',
                'date' => $startDate->copy()->addDays(4)->toDateString(),
                'appetizer_id' => 'b0c496ec-7537-4b2a-955c-b3b095daafbe', // Causa
                'main_dish_id' => 'a06de96f-499f-4929-bc5b-d526bf55a7e6', // Ají de Gallina
                'drink_id' => '2127e6c9-9ca7-4fda-8c97-9077efd10c7b', // Emoliente
                'price' => 13.00,
                'stock' => 50,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->command->info('✅ 10 menús diarios creados (semana actual)');
    }
}
