<?php

namespace Database\Seeders;

use App\Models\Dish;
use Illuminate\Database\Seeder;

class CompleteDishSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar tabla primero
        Dish::truncate();

        // ==========================================
        // ENTRADAS (Appetizers)
        // ==========================================
        $appetizers = [
            [
                'name' => 'Ensalada César',
                'description' => 'Lechuga romana, crutones, parmesano y aderezo César casero',
                'price' => 3.50,
                'image_url' => 'https://images.unsplash.com/photo-1546793665-c74683f339c1',
            ],
            [
                'name' => 'Sopa de Pollo',
                'description' => 'Sopa casera con trozos de pollo, fideos y vegetales',
                'price' => 3.00,
                'image_url' => 'https://images.unsplash.com/photo-1547592166-23ac45744acd',
            ],
            [
                'name' => 'Crema de Zapallo',
                'description' => 'Crema suave de zapallo con crutones y hierbas',
                'price' => 3.00,
                'image_url' => 'https://images.unsplash.com/photo-1476718406336-bb5a9690ee2a',
            ],
            [
                'name' => 'Ensalada Mixta',
                'description' => 'Lechuga, tomate, pepino, zanahoria y vinagreta',
                'price' => 2.50,
                'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd',
            ],
            [
                'name' => 'Causa Limeña',
                'description' => 'Papa amarilla, atún, palta y mayonesa',
                'price' => 4.00,
                'image_url' => 'https://images.unsplash.com/photo-1626645738196-c2a7c87a8f58',
            ],
            [
                'name' => 'Sopa Criolla',
                'description' => 'Sopa tradicional peruana con carne, fideos y huevo',
                'price' => 3.50,
                'image_url' => 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d',
            ],
        ];

        foreach ($appetizers as $appetizer) {
            Dish::create(array_merge($appetizer, [
                'category' => 'appetizer',
                'is_active' => true,
            ]));
        }

        // ==========================================
        // PLATOS PRINCIPALES (Main Dishes)
        // ==========================================
        $mainDishes = [
            [
                'name' => 'Pollo a la Plancha',
                'description' => 'Pechuga de pollo grillada con arroz blanco y ensalada fresca',
                'price' => 8.50,
                'image_url' => 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6',
            ],
            [
                'name' => 'Lomo Saltado',
                'description' => 'Carne de res salteada con cebolla, tomate, papas fritas y arroz',
                'price' => 10.00,
                'image_url' => 'https://images.unsplash.com/photo-1626645738196-c2a7c87a8f58',
            ],
            [
                'name' => 'Ají de Gallina',
                'description' => 'Pollo deshilachado en crema de ají amarillo con arroz y papa',
                'price' => 9.00,
                'image_url' => 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d',
            ],
            [
                'name' => 'Arroz con Pollo',
                'description' => 'Arroz verde con pollo, zanahoria y alverjitas',
                'price' => 8.00,
                'image_url' => 'https://images.unsplash.com/photo-1645112411341-6c4fd023714a',
            ],
            [
                'name' => 'Tallarines Rojos con Pollo',
                'description' => 'Pasta en salsa de tomate con pollo apanado',
                'price' => 8.50,
                'image_url' => 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9',
            ],
            [
                'name' => 'Milanesa de Pollo',
                'description' => 'Pollo apanado con arroz, papas fritas y ensalada',
                'price' => 9.50,
                'image_url' => 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d',
            ],
            [
                'name' => 'Seco de Res',
                'description' => 'Carne de res guisada con cilantro, frejoles y arroz',
                'price' => 10.50,
                'image_url' => 'https://images.unsplash.com/photo-1529042410759-befb1204b468',
            ],
            [
                'name' => 'Estofado de Carne',
                'description' => 'Carne de res en salsa con papas y zanahorias',
                'price' => 9.50,
                'image_url' => 'https://images.unsplash.com/photo-1529042410759-befb1204b468',
            ],
            [
                'name' => 'Chaufa de Pollo',
                'description' => 'Arroz chaufa con pollo, huevo y cebollita china',
                'price' => 8.50,
                'image_url' => 'https://images.unsplash.com/photo-1617093727343-374698b1b08d',
            ],
            [
                'name' => 'Pescado Frito',
                'description' => 'Filete de pescado frito con arroz y ensalada',
                'price' => 11.00,
                'image_url' => 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2',
            ],
            [
                'name' => 'Tallarines Verdes con Bistec',
                'description' => 'Pasta al pesto con bistec apanado',
                'price' => 10.00,
                'image_url' => 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9',
            ],
            [
                'name' => 'Carapulcra con Sopa Seca',
                'description' => 'Papa seca guisada con sopa seca y carne de cerdo',
                'price' => 10.50,
                'image_url' => 'https://images.unsplash.com/photo-1626645738196-c2a7c87a8f58',
            ],
        ];

        foreach ($mainDishes as $dish) {
            Dish::create(array_merge($dish, [
                'category' => 'main',
                'is_active' => true,
            ]));
        }

        // ==========================================
        // BEBIDAS (Drinks)
        // ==========================================
        $drinks = [
            [
                'name' => 'Chicha Morada',
                'description' => 'Bebida tradicional peruana de maíz morado',
                'price' => 2.00,
                'image_url' => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc',
            ],
            [
                'name' => 'Limonada Natural',
                'description' => 'Refrescante limonada preparada al momento',
                'price' => 2.50,
                'image_url' => 'https://images.unsplash.com/photo-1523677011781-c91d1bbe2f9d',
            ],
            [
                'name' => 'Refresco de Maracuyá',
                'description' => 'Jugo natural de maracuyá',
                'price' => 2.50,
                'image_url' => 'https://images.unsplash.com/photo-1546548970-71785318a17b',
            ],
            [
                'name' => 'Emoliente',
                'description' => 'Bebida caliente tradicional de hierbas',
                'price' => 2.00,
                'image_url' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e',
            ],
            [
                'name' => 'Inca Kola',
                'description' => 'Gaseosa peruana (500ml)',
                'price' => 2.50,
                'image_url' => 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97',
            ],
            [
                'name' => 'Coca Cola',
                'description' => 'Gaseosa (500ml)',
                'price' => 2.50,
                'image_url' => 'https://images.unsplash.com/photo-1554866585-cd94860890b7',
            ],
            [
                'name' => 'Agua Mineral',
                'description' => 'Agua sin gas (500ml)',
                'price' => 1.50,
                'image_url' => 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d',
            ],
            [
                'name' => 'Jugo de Papaya',
                'description' => 'Jugo natural de papaya con leche',
                'price' => 3.00,
                'image_url' => 'https://images.unsplash.com/photo-1600271886742-f049cd451bba',
            ],
        ];

        foreach ($drinks as $drink) {
            Dish::create(array_merge($drink, [
                'category' => 'drink',
                'is_active' => true,
            ]));
        }

        $this->command->info('✅ ' . Dish::count() . ' platos creados exitosamente!');
    }
}