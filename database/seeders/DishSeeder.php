<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DishSeeder extends Seeder
{
    /**
     * Seed dishes table with initial menu items
     */
    public function run(): void
    {
        DB::table('dishes')->insert([
            // Entradas / Appetizers
            [
                'id' => '3434ffe5-b747-42e9-a1b4-41f25446c45c',
                'name' => 'Ensalada César',
                'description' => 'Lechuga romana, crutones, parmesano y aderezo César casero',
                'category' => 'appetizer',
                'price' => 3.50,
                'image_url' => 'https://images.unsplash.com/photo-1546793665-c74683f339c1?w=800',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '414b4dca-a0cb-44e7-bc8a-faa26b959667',
                'name' => 'Sopa de Pollo',
                'description' => 'Sopa casera con trozos de pollo, fideos y vegetales',
                'category' => 'appetizer',
                'price' => 3.00,
                'image_url' => 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=800',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '4ee49309-bdd2-434e-9fa0-90c4f32a7301',
                'name' => 'Crema de Zapallo',
                'description' => 'Crema suave de zapallo con crutones y hierbas',
                'category' => 'appetizer',
                'price' => 3.00,
                'image_url' => 'https://images.unsplash.com/photo-1476718406336-bb5a9690ee2a?w=800',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'b0c496ec-7537-4b2a-955c-b3b095daafbe',
                'name' => 'Causa',
                'description' => 'Papa amarilla, atún, palta y mayonesa',
                'category' => 'appetizer',
                'price' => 4.00,
                'image_url' => 'https://images.unsplash.com/photo-1626645738196-c2a7c87a8f58?w=800',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Platos Principales / Main Dishes
            [
                'id' => 'b8b5b75c-c495-4ec6-ae4f-aebfedbd65f3',
                'name' => 'Pollo a la Plancha',
                'description' => 'Pechuga de pollo grillada con arroz blanco y ensalada fresca',
                'category' => 'main',
                'price' => 8.50,
                'image_url' => 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=800',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '4d7fde66-7e24-4288-bcfd-43dd63017731',
                'name' => 'Lomo Saltado',
                'description' => 'Carne de res salteada con cebolla, tomate, papas fritas y arroz',
                'category' => 'main',
                'price' => 10.00,
                'image_url' => 'https://images.unsplash.com/photo-1626645738196-c2a7c87a8f58?w=800',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'a06de96f-499f-4929-bc5b-d526bf55a7e6',
                'name' => 'Ají de Gallina',
                'description' => 'Pollo deshilachado en crema de ají amarillo con arroz y papa',
                'category' => 'main',
                'price' => 9.00,
                'image_url' => 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?w=800',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '2d05f77a-cc75-454e-94e8-cdfe6ace2ebd',
                'name' => 'Arroz con Pollo',
                'description' => 'Arroz verde con pollo, zanahoria y alverjitas',
                'category' => 'main',
                'price' => 8.00,
                'image_url' => 'https://images.unsplash.com/photo-1645112411341-6c4fd023714a?w=800',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '5d8a7645-5d3c-4479-a91d-37a0d8dd8699',
                'name' => 'Seco de Carne',
                'description' => 'Carne de res en salsa de cilantro con frejoles y arroz',
                'category' => 'main',
                'price' => 9.50,
                'image_url' => 'https://peru.info/archivos/publicacion/publicacion_20250324_100228.jpg',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Bebidas / Drinks
            [
                'id' => '316bd56c-1d17-4221-98a5-ff9c49ba6b7a',
                'name' => 'Chicha Morada',
                'description' => 'Bebida tradicional peruana de maíz morado',
                'category' => 'drink',
                'price' => 2.00,
                'image_url' => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=800',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '27effa86-852c-47cc-9b70-a2a1f6c36f3d',
                'name' => 'Limonada Natural',
                'description' => 'Refrescante limonada preparada al momento',
                'category' => 'drink',
                'price' => 2.50,
                'image_url' => 'https://images.unsplash.com/photo-1523677011781-c91d1bbe2f9d?w=800',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '12dc09f7-5795-4214-8cf8-f4b6d5876c70',
                'name' => 'Refresco de Maracuyá',
                'description' => 'Jugo natural de maracuyá',
                'category' => 'drink',
                'price' => 2.50,
                'image_url' => 'https://images.unsplash.com/photo-1546548970-71785318a17b?w=800',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '2127e6c9-9ca7-4fda-8c97-9077efd10c7b',
                'name' => 'Emoliente',
                'description' => 'Bebida caliente tradicional de hierbas',
                'category' => 'drink',
                'price' => 2.00,
                'image_url' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=800',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->command->info('✅ 13 platos con imágenes creados');
    }
}
