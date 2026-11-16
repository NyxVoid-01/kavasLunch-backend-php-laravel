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
            // Las canceladas y vencidas no cuentan como reservas del día ni como ingreso
            'today_reservations' => Reservation::counted()->whereDate('reservation_date', $today)->count(),
            'today_revenue' => Reservation::counted()->whereDate('reservation_date', $today)
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
                ->where('stock', '<=', DailyMenu::LOW_STOCK_THRESHOLD)
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
            'name' => ['required', 'string', 'max:255', $this->uniqueDishName()],
            'description' => 'nullable|string',
            'category' => 'required|in:appetizer,main,drink',
            'price' => 'required|numeric|gt:0',
            'image_url' => 'nullable|url|max:2048',
            'is_active' => 'sometimes|boolean',
        ], $this->dishMessages());

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
            'name' => ['sometimes', 'required', 'string', 'max:255', $this->uniqueDishName($dish->id)],
            'description' => 'nullable|string',
            'category' => 'sometimes|required|in:appetizer,main,drink',
            'price' => 'sometimes|required|numeric|gt:0',
            'image_url' => 'nullable|url|max:2048',
            'is_active' => 'sometimes|boolean',
        ], $this->dishMessages());

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
            // Combinación válida: cada plato debe existir, estar activo y ser de su categoría
            'appetizer_id' => ['nullable', 'uuid', $this->activeDishOfCategory('appetizer')],
            'main_dish_id' => ['required', 'uuid', $this->activeDishOfCategory('main')],
            'drink_id' => ['nullable', 'uuid', $this->activeDishOfCategory('drink')],
            'price' => 'required|numeric|gt:0',
            'stock' => 'required|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ], $this->menuMessages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validar que no exista un menú idéntico (misma fecha y misma combinación de platos)
        $duplicateMenu = DailyMenu::where('date', $request->date)
            ->where('appetizer_id', $request->appetizer_id)
            ->where('main_dish_id', $request->main_dish_id)
            ->where('drink_id', $request->drink_id)
            ->exists();

        if ($duplicateMenu) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un menú con esta misma combinación de platos para esta fecha. Por favor, elige una combinación diferente.'
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
            // Un plato que ya estaba en el menú se acepta aunque luego se haya desactivado
            'appetizer_id' => ['nullable', 'uuid', $this->activeDishOfCategory('appetizer', $menu->appetizer_id)],
            'main_dish_id' => ['sometimes', 'required', 'uuid', $this->activeDishOfCategory('main', $menu->main_dish_id)],
            'drink_id' => ['nullable', 'uuid', $this->activeDishOfCategory('drink', $menu->drink_id)],
            'price' => 'sometimes|required|numeric|gt:0',
            'stock' => 'sometimes|required|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ], $this->menuMessages());

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
        ], [
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock no puede ser negativo.',
            'reason.required' => 'Indica el motivo del cambio de stock.',
            'reason.max' => 'El motivo no puede superar los 255 caracteres.',
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
    
    /**
     * Reservas de un día (por defecto, hoy) para el despacho, con el resumen por estado (RF-012)
     * GET /api/admin/reservations?date=YYYY-MM-DD&status=pending
     */
    public function getAllReservations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|date',
            'status' => 'sometimes|in:pending,delivered,cancelled,expired',
        ], [
            'date.date' => 'La fecha no es válida.',
            'status.in' => 'El estado no es válido.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $date = $request->filled('date') ? Carbon::parse($request->date)->toDateString() : now()->toDateString();

        $dayReservations = Reservation::with(['user', 'menu.appetizer', 'menu.mainDish', 'menu.drink'])
            ->whereDate('reservation_date', $date)
            ->orderBy('pickup_time_slot')
            ->orderBy('created_at')
            ->get();

        // El resumen cuenta todo el día, aunque la lista venga filtrada por estado
        $summary = [
            'total' => $dayReservations->count(),
            'pending' => $dayReservations->where('status', 'pending')->count(),
            'delivered' => $dayReservations->where('status', 'delivered')->count(),
            'cancelled' => $dayReservations->where('status', 'cancelled')->count(),
            'expired' => $dayReservations->where('status', 'expired')->count(),
        ];

        $list = $request->filled('status') ? $dayReservations->where('status', $request->status) : $dayReservations;

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'reservations' => $list->values()->map(fn ($r) => $this->formatAdminReservation($r)),
                'summary' => $summary,
            ]
        ]);
    }

    // Reserva tal como la usa el panel: usuario, turno, pago y nombres de los platos
    private function formatAdminReservation(Reservation $reservation): array
    {
        $menu = $reservation->menu;

        return [
            'id' => $reservation->id,
            'code' => $reservation->code,
            'status' => $reservation->status,
            'reservation_date' => $reservation->reservation_date->format('Y-m-d'),
            'pickup_time_slot' => $reservation->pickup_time_slot?->format('H:i'),
            'pickup_window_end' => $reservation->pickup_window_end?->format('H:i'),
            'payment_method' => $reservation->payment_method,
            'payment_status' => $reservation->payment_status,
            'created_at' => $reservation->created_at?->toIso8601String(),
            'delivered_at' => $reservation->delivered_at?->toIso8601String(),
            'user' => [
                'id' => $reservation->user?->id,
                'full_name' => $reservation->user?->full_name,
                'email' => $reservation->user?->email,
            ],
            'menu' => [
                'id' => $menu?->id,
                'price' => (string) ($menu?->price ?? 0),
                'appetizer' => $menu?->appetizer?->name,
                'main_dish' => $menu?->mainDish?->name,
                'drink' => $menu?->drink?->name,
            ],
        ];
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
                'message' => $this->deliveryBlockedMessage($reservation)
            ], 400);
        }

        $reservation->markAsDelivered();

        return response()->json([
            'success' => true,
            'message' => 'Reserva marcada como entregada',
            'data' => ['reservation' => $this->formatAdminReservation($reservation->fresh(['user', 'menu.appetizer', 'menu.mainDish', 'menu.drink']))]
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

        $reservations = Reservation::with(['user', 'menu.appetizer', 'menu.mainDish', 'menu.drink'])
            ->whereDate('reservation_date', '>=', $request->date_from)
            ->whereDate('reservation_date', '<=', $request->date_to)
            ->orderBy('reservation_date', 'desc')
            ->orderBy('pickup_time_slot', 'desc')
            ->get();

        // Solo las reservas vigentes generan ingreso: las canceladas y vencidas no
        $counted = $reservations->whereNotIn('status', ['cancelled', 'expired']);
        $totalRevenue = $counted->sum(fn($r) => $r->menu->price);

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'from' => $request->date_from,
                    'to' => $request->date_to,
                ],
                'summary' => [
                    'total_reservations' => $reservations->count(),
                    'counted_reservations' => $counted->count(),
                    'total_revenue' => $totalRevenue,
                    'average_reservation_value' => $counted->count() > 0 ? $totalRevenue / $counted->count() : 0,
                ],
                'status_breakdown' => [
                    'pending' => $reservations->where('status', 'pending')->count(),
                    'delivered' => $reservations->where('status', 'delivered')->count(),
                    'cancelled' => $reservations->where('status', 'cancelled')->count(),
                    'expired' => $reservations->where('status', 'expired')->count(),
                ],
                'payment_methods' => [
                    'cash' => $reservations->where('payment_method', 'cash')->count(),
                    'yape' => $reservations->where('payment_method', 'yape')->count(),
                    'plin' => $reservations->where('payment_method', 'plin')->count(),
                    'credit_card' => $reservations->where('payment_method', 'credit_card')->count(),
                ],
                'reservations' => $reservations->map(fn ($r) => $this->formatAdminReservation($r))->values(),
            ]
        ]);
    }

    public function salesReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'group_by' => 'sometimes|in:day,week,month',
        ], [
            'date_to.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'group_by.in' => 'Agrupa por día, semana o mes.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $groupBy = $request->get('group_by', 'day');

        // Ventas diarias, semanales o mensuales (ERS): solo reservas vigentes
        $reservations = Reservation::with(['menu'])
            ->counted()
            ->whereDate('reservation_date', '>=', $request->date_from)
            ->whereDate('reservation_date', '<=', $request->date_to)
            ->get();

        $sales = $reservations
            ->groupBy(fn ($r) => match ($groupBy) {
                // Semana: se identifica por su lunes
                'week' => $r->reservation_date->copy()->startOfWeek()->format('Y-m-d'),
                'month' => $r->reservation_date->format('Y-m'),
                default => $r->reservation_date->format('Y-m-d'),
            })
            ->map(fn ($group, $period) => [
                'period' => (string) $period,
                'total_reservations' => $group->count(),
                'total_revenue' => $group->sum(fn ($r) => $r->menu->price),
            ])
            ->sortKeys()
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'from' => $request->date_from,
                    'to' => $request->date_to,
                ],
                'group_by' => $groupBy,
                'sales' => $sales,
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

        // Los 100 cambios más recientes: el historial de un menú no necesita más
        $audits = $query->orderBy('created_at', 'desc')->limit(100)->get();

        return response()->json([
            'success' => true,
            'data' => ['audits' => $audits]
        ]);
    }

    // ==========================================
    // VALIDACIONES DE PLATOS Y MENÚS
    // ==========================================

    // El nombre del plato debe ser único (sin distinguir mayúsculas)
    private function uniqueDishName(?string $ignoreId = null): \Closure
    {
        return function ($attribute, $value, $fail) use ($ignoreId) {
            $exists = Dish::whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $value))])
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists();

            if ($exists) {
                $fail('Ya existe un plato con ese nombre.');
            }
        };
    }

    // Combinación válida de un menú: el plato existe, es de la categoría que corresponde y está activo
    private function activeDishOfCategory(string $category, ?string $currentId = null): \Closure
    {
        $labels = ['appetizer' => 'Entrada', 'main' => 'Plato Principal', 'drink' => 'Bebida'];

        return function ($attribute, $value, $fail) use ($category, $currentId, $labels) {
            if ($value === null) {
                return;
            }

            $dish = Dish::find($value);

            if (!$dish) {
                $fail('El plato seleccionado no existe.');
            } elseif ($dish->category !== $category) {
                $fail("El plato «{$dish->name}» no es de la categoría {$labels[$category]}.");
            } elseif (!$dish->is_active && $value !== $currentId) {
                $fail("El plato «{$dish->name}» está inactivo.");
            }
        };
    }

    private function dishMessages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'category.required' => 'Selecciona la categoría.',
            'category.in' => 'La categoría debe ser Entrada, Principal o Bebida.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número.',
            'price.gt' => 'El precio debe ser mayor a 0.',
            'image_url.url' => 'La imagen debe ser un enlace válido (https://...).',
        ];
    }

    private function menuMessages(): array
    {
        return [
            'date.required' => 'Selecciona una fecha.',
            'date.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'main_dish_id.required' => 'Selecciona el plato principal.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número.',
            'price.gt' => 'El precio debe ser mayor a 0.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock no puede ser negativo.',
        ];
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

        // Se ignoran espacios y mayúsculas/minúsculas: el código se puede escribir a mano
        $code = strtoupper(trim($request->code));

        $reservation = Reservation::with(['user', 'menu.appetizer', 'menu.mainDish', 'menu.drink'])
            ->where('code', $code)
            ->first();

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Código no encontrado. Revisa que esté bien escrito.'
            ], 404);
        }

        if ($reservation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => $this->deliveryBlockedMessage($reservation),
                'data' => [
                    'reservation' => $this->formatAdminReservation($reservation),
                    'current_status' => $reservation->status
                ]
            ], 400);
        }

        $reservation->markAsDelivered();

        return response()->json([
            'success' => true,
            'message' => 'Reserva entregada exitosamente',
            'data' => [
                'reservation' => $this->formatAdminReservation($reservation->fresh(['user', 'menu.appetizer', 'menu.mainDish', 'menu.drink'])),
            ]
        ]);
    }

    // Por qué una reserva no se puede entregar (no se puede entregar dos veces la misma, RF-013)
    private function deliveryBlockedMessage(Reservation $reservation): string
    {
        return match ($reservation->status) {
            'delivered' => 'Esta reserva ya fue entregada' . ($reservation->delivered_at ? ' a las ' . $reservation->delivered_at->format('H:i') : '') . '.',
            'cancelled' => 'Esta reserva fue cancelada.',
            'expired' => 'Esta reserva venció: no se recogió dentro de su turno.',
            default => 'La reserva no está pendiente.',
        };
    }
}