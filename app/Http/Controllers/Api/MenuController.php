<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyMenu;
use App\Models\Dish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    // ==========================================
    // MÉTODOS PÚBLICOS (Sin autenticación)
    // ==========================================
    
    /**
     * Listar menús disponibles (público)
     * GET /api/public/menus
     */
    public function publicIndex(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|date',
            'available' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = DailyMenu::with(['appetizer', 'mainDish', 'drink'])
            ->active()
            ->where('date', '>=', now()->toDateString());

        if ($request->has('date')) {
            $query->forDate($request->date);
        }

        if ($request->boolean('available')) {
            $query->available();
        }

        $menus = $query->orderBy('date')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'menus' => $menus->map(fn($menu) => $this->formatMenuResponse($menu))
            ]
        ]);
    }

    /**
     * Menú de hoy (público)
     * GET /api/public/menus/today
     */
    public function publicToday()
    {
        $menu = DailyMenu::with(['appetizer', 'mainDish', 'drink'])
            ->active()
            ->today()
            ->first();

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'No hay menú disponible para hoy'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'menu' => $this->formatMenuResponse($menu)
            ]
        ]);
    }

    /**
     * Ver detalle de menú (público)
     * GET /api/public/menus/{id}
     */
    public function publicShow($id)
    {
        $menu = DailyMenu::with(['appetizer', 'mainDish', 'drink'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'menu' => $this->formatMenuResponse($menu)
            ]
        ]);
    }

    /**
     * Listar platos (público)
     * GET /api/public/dishes
     */
    public function publicDishes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'sometimes|in:appetizer,main,drink',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Dish::active();

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        $dishes = $query->orderBy('category')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'dishes' => $dishes
            ]
        ]);
    }

    // ==========================================
    // MÉTODOS PROTEGIDOS (Requieren autenticación)
    // ==========================================
    
    /**
     * Listar menús (usuario autenticado)
     * GET /api/user/menus
     */
    public function index(Request $request)
    {
        return $this->publicIndex($request);
    }

    /**
     * Menú de hoy (usuario autenticado)
     * GET /api/user/menus/today
     */
    public function today()
    {
        return $this->publicToday();
    }

    /**
     * Ver detalle de menú (usuario autenticado)
     * GET /api/user/menus/{id}
     */
    public function show($id)
    {
        return $this->publicShow($id);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================
    
    /**
     * Formatear respuesta de menú
     */
    private function formatMenuResponse($menu)
    {
        return [
            'id' => $menu->id,
            'date' => $menu->date->format('Y-m-d'),
            'price' => (string) $menu->price,
            'stock' => $menu->stock,
            'available_stock' => $menu->available_stock,
            'is_available' => $menu->isAvailable(),
            'appetizer' => $menu->appetizer ? [
                'id' => $menu->appetizer->id,
                'name' => $menu->appetizer->name,
                'description' => $menu->appetizer->description,
                'price' => (string) $menu->appetizer->price,
                'image_url' => $menu->appetizer->image_url,
            ] : null,
            'main_dish' => $menu->mainDish ? [
                'id' => $menu->mainDish->id,
                'name' => $menu->mainDish->name,
                'description' => $menu->mainDish->description,
                'price' => (string) $menu->mainDish->price,
                'image_url' => $menu->mainDish->image_url,
            ] : null,
            'drink' => $menu->drink ? [
                'id' => $menu->drink->id,
                'name' => $menu->drink->name,
                'description' => $menu->drink->description,
                'price' => (string) $menu->drink->price,
                'image_url' => $menu->drink->image_url,
            ] : null,
        ];
    }
}