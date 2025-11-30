<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes - KAVAS-LUNCH
|--------------------------------------------------------------------------
*/

// ==========================================
// RUTAS PÚBLICAS (Sin autenticación)
// ==========================================
Route::prefix('public')->group(function () {
    // Ver catálogo de menús (público)
    Route::get('/menus', [MenuController::class, 'publicIndex']);
    Route::get('/menus/today', [MenuController::class, 'publicToday']);
    Route::get('/menus/{id}', [MenuController::class, 'publicShow']);
    Route::get('/dishes', [MenuController::class, 'publicDishes']);
});

// Autenticación
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// ==========================================
// RUTAS PROTEGIDAS (Requieren autenticación)
// ==========================================
Route::middleware('auth:api')->group(function () {
    
    // ==========================================
    // Auth Management
    // ==========================================
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });
    
    // ==========================================
    // MÓDULO USUARIO (Comunidad Herediana)
    // ==========================================
    Route::prefix('user')->group(function () {
        
        // Menús (autenticado)
        Route::get('/menus', [MenuController::class, 'index']);
        Route::get('/menus/today', [MenuController::class, 'today']);
        Route::get('/menus/{id}', [MenuController::class, 'show']);
        
        // Reservas
        Route::get('/reservations', [ReservationController::class, 'index']);
        Route::post('/reservations', [ReservationController::class, 'store']);
        Route::get('/reservations/available-slots', [ReservationController::class, 'availableSlots']);
        Route::get('/reservations/{id}', [ReservationController::class, 'show']);
        Route::delete('/reservations/{id}/cancel', [ReservationController::class, 'cancel']);
        
        // Pagos
        Route::get('/payment/methods', [PaymentController::class, 'getPaymentMethods']);
        Route::post('/payment/process', [PaymentController::class, 'processPayment']);
    });
    
    // ==========================================
    // MÓDULO ADMINISTRADOR
    // ==========================================
    Route::prefix('admin')->middleware('admin')->group(function () {
        
        // Dashboard Stats
        Route::get('/dashboard/stats', [AdminController::class, 'dashboardStats']);
        
        // Gestión de Platos
        Route::prefix('dishes')->group(function () {
            Route::get('/', [AdminController::class, 'getDishes']);
            Route::post('/', [AdminController::class, 'storeDish']);
            Route::get('/{id}', [AdminController::class, 'showDish']);
            Route::put('/{id}', [AdminController::class, 'updateDish']);
            Route::delete('/{id}', [AdminController::class, 'destroyDish']);
        });
        
        // Gestión de Menús Diarios
        Route::prefix('menus')->group(function () {
            Route::get('/', [AdminController::class, 'getMenus']);
            Route::post('/', [AdminController::class, 'storeMenu']);
            Route::get('/{id}', [AdminController::class, 'showMenu']);
            Route::put('/{id}', [AdminController::class, 'updateMenu']);
            Route::delete('/{id}', [AdminController::class, 'destroyMenu']);
            Route::put('/{id}/stock', [AdminController::class, 'updateStock']);
        });
        
        // Gestión de Reservas
        Route::prefix('reservations')->group(function () {
            Route::get('/', [AdminController::class, 'getAllReservations']);
            Route::get('/today', [AdminController::class, 'todayReservations']);
            Route::get('/by-timeslot', [AdminController::class, 'reservationsByTimeSlot']);
            Route::post('/scan-qr', [AdminController::class, 'scanQrCode']);
            Route::get('/{id}', [AdminController::class, 'showReservation']);
            Route::put('/{id}/deliver', [AdminController::class, 'markAsDelivered']);
            Route::put('/{id}/cancel', [AdminController::class, 'adminCancelReservation']);
        });
        
        // Reportes
        Route::prefix('reports')->group(function () {
            Route::get('/reservations', [AdminController::class, 'reservationsReport']);
            Route::get('/sales', [AdminController::class, 'salesReport']);
            Route::get('/stock-audits', [AdminController::class, 'stockAudits']);
        });
    });
});

// Health check
Route::get('/', function () {
    return response()->json([
        'message' => 'KAVAS-LUNCH API',
        'version' => '1.0.0',
        'status' => 'running',
        'timestamp' => now()->toISOString(),
        'modules' => [
            'public' => 'Catálogo de menús (sin auth)',
            'user' => 'Reservas y pagos (requiere auth)',
            'admin' => 'Gestión completa (solo admin)'
        ]
    ]);
});