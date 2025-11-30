<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyMenu;
use App\Models\Dish;
use App\Models\Reservation;
use App\Models\StockAudit;
use App\Models\Payment;
use App\Services\TimeSlotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    protected $timeSlotService;

    public function __construct(TimeSlotService $timeSlotService)
    {
        $this->timeSlotService = $timeSlotService;
    }
    // ==========================================
    // DASHBOARD
    // ==========================================
    
    public function dashboardStats()
    {
        $today = now()->toDateString();
        
        $stats = [
            'today_reservations' => Reservation::whereDate('reservation_date', $today)->count(),
            'today_revenue' => Reservation::whereDate('reservation_date', $today)
                ->with('menu')
                ->get()
                ->sum(fn($r) => $r->menu->price),
            'pending_reservations' => Reservation::whereDate('reservation_date', $today)
                ->where('status', 'pending')
                ->count(),
            'delivered_reservations' => Reservation::whereDate('reservation_date', $today)
                ->where('status', 'delivered')
                ->count(),
            'active_menus' => DailyMenu::active()->where('date', '>=', $today)->count(),
            'low_stock_menus' => DailyMenu::active()
                ->where('date', '>=', $today)
                ->where('stock', '<=', 5)
                ->count(),
            'total_dishes' => Dish::active()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    // ==========================================
    // GESTIÓN DE PLATOS
    // ==========================================
    
    public function getDishes(Request $request)
    {
        $query = Dish::query();

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $dishes = $query->orderBy('category')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => ['dishes' => $dishes]
        ]);
    }

    public function showDish($id)
    {
        $dish = Dish::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => ['dish' => $dish]
        ]);
    }

    public function storeDish(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:appetizer,main,drink',
            'price' => 'required|numeric|min:0',
            'image_url' => 'nullable|url',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $dish = Dish::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Plato creado exitosamente',
            'data' => ['dish' => $dish]
        ], 201);
    }

    public function updateDish(Request $request, $id)
    {
        $dish = Dish::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|required|in:appetizer,main,drink',
            'price' => 'sometimes|required|numeric|min:0',
            'image_url' => 'nullable|url',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $dish->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Plato actualizado exitosamente',
            'data' => ['dish' => $dish]
        ]);
    }

    public function destroyDish($id)
    {
        $dish = Dish::findOrFail($id);
        
        // Verificar que no esté en uso en menús activos
        $inUse = DailyMenu::where('date', '>=', now()->toDateString())
            ->where(function($query) use ($id) {
                $query->where('appetizer_id', $id)
                    ->orWhere('main_dish_id', $id)
                    ->orWhere('drink_id', $id);
            })
            ->exists();

        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar. El plato está en uso en menús activos.'
            ], 400);
        }

        $dish->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plato eliminado exitosamente'
        ]);
    }

    // ==========================================
    // GESTIÓN DE MENÚS
    // ==========================================
    
    public function getMenus(Request $request)
    {
        $query = DailyMenu::with(['appetizer', 'mainDish', 'drink']);

        if ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $menus = $query->orderBy('date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => ['menus' => $menus]
        ]);
    }

    public function showMenu($id)
    {
        $menu = DailyMenu::with(['appetizer', 'mainDish', 'drink', 'reservations'])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => ['menu' => $menu]
        ]);
    }

    public function storeMenu(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
            'appetizer_id' => 'nullable|uuid|exists:dishes,id',
            'main_dish_id' => 'nullable|uuid|exists:dishes,id',
            'drink_id' => 'nullable|uuid|exists:dishes,id',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validar que no exista menú para esa fecha
        if (DailyMenu::whereDate('date', $request->date)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un menú para esta fecha'
            ], 400);
        }

        $menu = DailyMenu::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Menú creado exitosamente',
            'data' => ['menu' => $menu->load(['appetizer', 'mainDish', 'drink'])]
        ], 201);
    }

    public function updateMenu(Request $request, $id)
    {
        $menu = DailyMenu::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'appetizer_id' => 'nullable|uuid|exists:dishes,id',
            'main_dish_id' => 'nullable|uuid|exists:dishes,id',
            'drink_id' => 'nullable|uuid|exists:dishes,id',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldStock = $menu->stock;
        $menu->update($request->all());

        // Auditoría de cambio de stock
        if ($request->has('stock') && $oldStock != $menu->stock) {
            StockAudit::create([
                'menu_id' => $menu->id,
                'old_stock' => $oldStock,
                'new_stock' => $menu->stock,
                'reason' => $request->get('reason', 'Actualización manual'),
                'created_by' => auth('api')->id(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Menú actualizado exitosamente',
            'data' => ['menu' => $menu->load(['appetizer', 'mainDish', 'drink'])]
        ]);
    }

    public function destroyMenu($id)
    {
        $menu = DailyMenu::findOrFail($id);

        // Verificar que no tenga reservas activas
        if ($menu->reservations()->whereIn('status', ['pending', 'delivered'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar. El menú tiene reservas activas.'
            ], 400);
        }

        $menu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menú eliminado exitosamente'
        ]);
    }

    public function updateStock(Request $request, $id)
    {
        $menu = DailyMenu::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'stock' => 'required|integer|min:0',
            'reason' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldStock = $menu->stock;
        $menu->update(['stock' => $request->stock]);

        // Crear auditoría
        StockAudit::create([
            'menu_id' => $menu->id,
            'old_stock' => $oldStock,
            'new_stock' => $request->stock,
            'reason' => $request->reason,
            'created_by' => auth('api')->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stock actualizado exitosamente',
            'data' => [
                'menu' => $menu,
                'audit' => [
                    'old_stock' => $oldStock,
                    'new_stock' => $request->stock,
                    'change' => $request->stock - $oldStock,
                ]
            ]
        ]);
    }

    // ==========================================
    // GESTIÓN DE RESERVAS
    // ==========================================
    
    public function getAllReservations(Request $request)
    {
        $query = Reservation::with(['user', 'menu.appetizer', 'menu.mainDish', 'menu.drink', 'payment']);

        if ($request->has('date_from')) {
            $query->whereDate('reservation_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('reservation_date', '<=', $request->date_to);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $reservations = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $reservations
        ]);
    }

    public function todayReservations()
    {
        $reservations = Reservation::with(['user', 'menu.appetizer', 'menu.mainDish', 'menu.drink', 'payment'])
            ->today()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'reservations' => $reservations,
                'summary' => [
                    'total' => $reservations->count(),
                    'pending' => $reservations->where('status', 'pending')->count(),
                    'delivered' => $reservations->where('status', 'delivered')->count(),
                    'cancelled' => $reservations->where('status', 'cancelled')->count(),
                ]
            ]
        ]);
    }

    public function showReservation($id)
    {
        $reservation = Reservation::with(['user', 'menu.appetizer', 'menu.mainDish', 'menu.drink', 'payment'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => ['reservation' => $reservation]
        ]);
    }

    public function markAsDelivered($id)
    {
        $reservation = Reservation::findOrFail($id);

        if ($reservation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'La reserva no está en estado pendiente'
            ], 400);
        }

        $reservation->markAsDelivered();

        return response()->json([
            'success' => true,
            'message' => 'Reserva marcada como entregada',
            'data' => ['reservation' => $reservation->fresh()]
        ]);
    }

    public function adminCancelReservation($id)
    {
        $reservation = Reservation::findOrFail($id);

        if ($reservation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden cancelar reservas pendientes'
            ], 400);
        }

        DB::transaction(function () use ($reservation) {
            $reservation->status = 'cancelled';
            $reservation->save();
            
            // Devolver stock
            $reservation->menu->increment('stock');
        });

        return response()->json([
            'success' => true,
            'message' => 'Reserva cancelada por administrador',
            'data' => ['reservation' => $reservation->fresh()]
        ]);
    }

    // ==========================================
    // REPORTES
    // ==========================================
    
    public function reservationsReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Reservation::with(['user', 'menu'])
            ->whereDate('reservation_date', '>=', $request->date_from)
            ->whereDate('reservation_date', '<=', $request->date_to);

        $reservations = $query->get();

        $totalReservations = $reservations->count();
        $totalRevenue = $reservations->sum(fn($r) => $r->menu->price);

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'from' => $request->date_from,
                    'to' => $request->date_to,
                ],
                'summary' => [
                    'total_reservations' => $totalReservations,
                    'total_revenue' => $totalRevenue,
                    'average_reservation_value' => $totalReservations > 0 ? $totalRevenue / $totalReservations : 0,
                ],
                'status_breakdown' => [
                    'pending' => $reservations->where('status', 'pending')->count(),
                    'delivered' => $reservations->where('status', 'delivered')->count(),
                    'cancelled' => $reservations->where('status', 'cancelled')->count(),
                ],
                'payment_methods' => [
                    'cash' => $reservations->where('payment_method', 'cash')->count(),
                    'yape' => $reservations->where('payment_method', 'yape')->count(),
                    'plin' => $reservations->where('payment_method', 'plin')->count(),
                    'credit_card' => $reservations->where('payment_method', 'credit_card')->count(),
                ],
                'reservations' => $reservations,
            ]
        ]);
    }

    public function salesReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $reservations = Reservation::with(['menu'])
            ->whereDate('reservation_date', '>=', $request->date_from)
            ->whereDate('reservation_date', '<=', $request->date_to)
            ->where('status', '!=', 'cancelled')
            ->get();

        $dailySales = $reservations->groupBy(function($r) {
            return $r->reservation_date->format('Y-m-d');
        })->map(function($dayReservations) {
            return [
                'date' => $dayReservations->first()->reservation_date->format('Y-m-d'),
                'total_reservations' => $dayReservations->count(),
                'total_revenue' => $dayReservations->sum(fn($r) => $r->menu->price),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'from' => $request->date_from,
                    'to' => $request->date_to,
                ],
                'daily_sales' => $dailySales,
                'total_revenue' => $reservations->sum(fn($r) => $r->menu->price),
                'total_reservations' => $reservations->count(),
            ]
        ]);
    }

    public function stockAudits(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'menu_id' => 'sometimes|uuid|exists:daily_menus,id',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = StockAudit::with(['menu', 'createdBy']);

        if ($request->has('menu_id')) {
            $query->byMenu($request->menu_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $audits = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => ['audits' => $audits]
        ]);
    }

    // ==========================================
    // TIME SLOT MANAGEMENT
    // ==========================================

    /**
     * Ver reservas agrupadas por time slot
     * GET /api/admin/reservations/by-timeslot
     */
    public function reservationsByTimeSlot(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $date = $request->has('date') ? Carbon::parse($request->date) : today();
        
        $timeSlots = $this->timeSlotService->getReservationsByTimeSlot($date);

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date->format('Y-m-d'),
                'time_slots' => $timeSlots,
                'summary' => [
                    'total_slots' => $timeSlots->count(),
                    'occupied_slots' => $timeSlots->filter(fn($slot) => $slot['capacity_used'] > 0)->count(),
                    'full_slots' => $timeSlots->filter(fn($slot) => $slot['is_full'])->count(),
                    'total_reservations' => $timeSlots->sum('capacity_used'),
                ]
            ]
        ]);
    }

    /**
     * Escanear QR y marcar como entregado
     * POST /api/admin/reservations/scan-qr
     */
    public function scanQrCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reservation = Reservation::with(['user', 'menu.appetizer', 'menu.mainDish', 'menu.drink'])
                ->where('code', $request->code)
                ->firstOrFail();

            if ($reservation->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'La reserva no está en estado pendiente',
                    'data' => [
                        'reservation' => $reservation,
                        'current_status' => $reservation->status
                    ]
                ], 400);
            }

            $reservation->markAsDelivered();

            return response()->json([
                'success' => true,
                'message' => 'Reserva entregada exitosamente',
                'data' => [
                    'reservation' => [
                        'id' => $reservation->id,
                        'code' => $reservation->code,
                        'user' => [
                            'name' => $reservation->user->full_name,
                            'email' => $reservation->user->email,
                        ],
                        'menu' => [
                            'appetizer' => $reservation->menu->appetizer?->name,
                            'main_dish' => $reservation->menu->mainDish?->name,
                            'drink' => $reservation->menu->drink?->name,
                        ],
                        'pickup_time_slot' => $reservation->pickup_time_slot->format('H:i'),
                        'delivered_at' => $reservation->delivered_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Código no encontrado o inválido'
            ], 404);
        }
    }
}