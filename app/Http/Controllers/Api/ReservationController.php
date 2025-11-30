<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\DailyMenu;
use App\Jobs\SendReservationConfirmationEmail;
use App\Jobs\SendPickupReminderEmail;
use App\Jobs\ReleaseExpiredReservationStock;
use App\Services\TimeSlotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReservationController extends Controller
{
    protected $timeSlotService;

    public function __construct(TimeSlotService $timeSlotService)
    {
        $this->timeSlotService = $timeSlotService;
    }

    /**
     * Listar reservas del usuario autenticado
     * GET /api/user/reservations
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();

        $reservations = Reservation::with(['menu.appetizer', 'menu.mainDish', 'menu.drink'])
            ->where('user_id', $user->id)
            ->orderBy('reservation_date', 'desc')
            ->orderBy('pickup_time_slot', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'reservations' => $reservations->map(function ($reservation) {
                    return $this->formatReservationResponse($reservation);
                })
            ]
        ]);
    }

    /**
     * Ver detalle de una reserva
     * GET /api/user/reservations/{id}
     */
    public function show($id)
    {
        $user = auth('api')->user();

        $reservation = Reservation::with(['menu.appetizer', 'menu.mainDish', 'menu.drink'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'reservation' => $this->formatReservationResponse($reservation)
            ]
        ]);
    }

    /**
     * Crear nueva reserva con time slot automático y QR
     * POST /api/user/reservations
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'menu_id' => 'required|uuid|exists:daily_menus,id',
            'reservation_date' => 'sometimes|date',
            'payment_method' => 'sometimes|in:cash,yape,plin,credit_card',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth('api')->user();
        $menuId = $request->menu_id;
        $reservationDate = $request->has('reservation_date') 
            ? Carbon::parse($request->reservation_date) 
            : today();

        try {
            DB::beginTransaction();

            // 1. Validar fecha de reserva
            $dateValidation = $this->timeSlotService->validateReservationDate($reservationDate);
            if (!$dateValidation['valid']) {
                throw ValidationException::withMessages([
                    'reservation_date' => [$dateValidation['message']]
                ]);
            }

            // 2. Validar que el usuario no tenga reserva activa para esa fecha
            $existingReservation = Reservation::where('user_id', $user->id)
                ->whereDate('reservation_date', $reservationDate->toDateString())
                ->whereIn('status', ['pending', 'delivered'])
                ->first();

            if ($existingReservation) {
                throw ValidationException::withMessages([
                    'reservation' => ['Ya tienes una reserva activa para esta fecha']
                ]);
            }

            // 3. Obtener menú con bloqueo
            $menu = DailyMenu::with(['appetizer', 'mainDish', 'drink'])
                ->lockForUpdate()
                ->findOrFail($menuId);

            // 4. Validar que el menú sea para la fecha solicitada
            if (!$menu->date->isSameDay($reservationDate)) {
                throw ValidationException::withMessages([
                    'menu' => ['Este menú no está disponible para la fecha seleccionada']
                ]);
            }

            // 5. Validar stock
            if ($menu->stock <= 0) {
                throw ValidationException::withMessages([
                    'menu' => ['Menú agotado']
                ]);
            }

            // 6. Validar que el menú esté activo
            if (!$menu->is_active) {
                throw ValidationException::withMessages([
                    'menu' => ['Este menú no está disponible']
                ]);
            }

            // 7. Asignar time slot automáticamente
            $pickupTimeSlot = $this->timeSlotService->assignNextAvailableSlot($menuId, $reservationDate);
            
            if (!$pickupTimeSlot) {
                throw ValidationException::withMessages([
                    'time_slot' => ['No hay horarios disponibles para esta fecha']
                ]);
            }

            // 8. Calcular tiempo de expiración
            $expiredAt = $this->timeSlotService->calculateExpirationTime($pickupTimeSlot);

            // 9. Decrementar stock
            $menu->decrement('stock');

            // 10. Crear reserva
            $reservation = Reservation::create([
                'user_id' => $user->id,
                'menu_id' => $menuId,
                'reservation_date' => $reservationDate,
                'pickup_time_slot' => $pickupTimeSlot,
                'expired_at' => $expiredAt,
                'status' => 'pending',
                'payment_method' => $request->get('payment_method', 'cash'),
                'payment_status' => 'pending',
            ]);

            // 11. Generar código QR
            $qrCodePath = $this->generateQRCode($reservation);
            $reservation->update(['qr_code_path' => $qrCodePath]);

            // 12. Recargar relaciones
            $reservation->load(['menu.appetizer', 'menu.mainDish', 'menu.drink', 'user']);

            DB::commit();

            // 13. Programar jobs
            $this->scheduleReservationJobs($reservation);

            // 14. Enviar email de confirmación
            dispatch(new SendReservationConfirmationEmail($reservation));

            return response()->json([
                'success' => true,
                'message' => 'Reserva creada exitosamente. Recibirás un email de confirmación.',
                'data' => [
                    'reservation' => $this->formatReservationResponse($reservation)
                ]
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar reserva
     * DELETE /api/user/reservations/{id}/cancel
     */
    public function cancel($id)
    {
        $user = auth('api')->user();

        try {
            $reservation = Reservation::where('user_id', $user->id)
                ->findOrFail($id);

            // Validar que se pueda cancelar
            $cancellationValidation = $this->timeSlotService->validateCancellation($reservation);
            if (!$cancellationValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $cancellationValidation['message']
                ], 400);
            }

            if ($reservation->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo puedes cancelar reservas pendientes'
                ], 400);
            }

            DB::transaction(function () use ($reservation) {
                $reservation->status = 'cancelled';
                $reservation->save();
                
                // Incrementar stock
                $reservation->menu->increment('stock');
            });

            // Enviar email de cancelación
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)
                    ->send(new \App\Mail\ReservationCancelledMail($reservation));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error sending cancellation email: ' . $e->getMessage());
                // No fallamos la request si falla el email, pero lo logueamos
            }

            return response()->json([
                'success' => true,
                'message' => 'Reserva cancelada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener slots disponibles para un menú
     * GET /api/user/reservations/available-slots
     */
    public function availableSlots(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'menu_id' => 'required|uuid|exists:daily_menus,id',
            'date' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $menuId = $request->menu_id;
        $date = $request->has('date') ? Carbon::parse($request->date) : today();

        $availableSlots = $this->timeSlotService->getAvailableSlots($menuId, $date);

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date->format('Y-m-d'),
                'available_slots' => $availableSlots->map(function ($slot) use ($date) {
                    $used = $this->timeSlotService->getSlotCapacity($slot, $date);
                    $available = \App\Services\TimeSlotService::MAX_CAPACITY_PER_SLOT - $used;
                    
                    return [
                        'time' => $slot->format('H:i'),
                        'datetime' => $slot->format('Y-m-d H:i:s'),
                        'capacity_available' => $available,
                        'capacity_used' => $used,
                    ];
                })
            ]
        ]);
    }

    /**
     * Generar código QR para la reserva
     */
    protected function generateQRCode(Reservation $reservation): string
    {
        $qrData = json_encode([
            'code' => $reservation->code,
            'id' => $reservation->id,
            'date' => $reservation->reservation_date->format('Y-m-d'),
            'time' => $reservation->pickup_time_slot->format('H:i'),
        ]);

        $filename = 'qr-' . $reservation->code . '.png';
        $path = 'qr-codes/' . $filename;

        // Generar QR code
        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->generate($qrData);

        // Guardar en storage
        Storage::disk('public')->put($path, $qrCode);

        return $path;
    }

    /**
     * Programar jobs de recordatorio y expiración
     */
    protected function scheduleReservationJobs(Reservation $reservation)
    {
        // Job de recordatorio 10 minutos antes
        $reminderTime = $reservation->pickup_time_slot->copy()->subMinutes(10);
        if ($reminderTime->isFuture()) {
            SendPickupReminderEmail::dispatch($reservation)
                ->delay($reminderTime);
        }

        // Job de expiración 15 minutos después del slot
        $expirationTime = $reservation->expired_at;
        ReleaseExpiredReservationStock::dispatch($reservation)
            ->delay($expirationTime);
    }

    /**
     * Formatear respuesta de reserva
     */
    protected function formatReservationResponse(Reservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'code' => $reservation->code,
            'reservation_date' => $reservation->reservation_date->format('Y-m-d'),
            'pickup_time_slot' => $reservation->pickup_time_slot?->format('H:i'),
            'pickup_time_slot_full' => $reservation->pickup_time_slot?->format('Y-m-d H:i:s'),
            'pickup_window_end' => $reservation->pickup_time_slot?->copy()->addMinutes(15)->format('H:i'),
            'expired_at' => $reservation->expired_at?->format('Y-m-d H:i:s'),
            'status' => $reservation->status,
            'payment_method' => $reservation->payment_method,
            'payment_status' => $reservation->payment_status,
            'qr_code_url' => $reservation->qr_code_url,
            'delivered_at' => $reservation->delivered_at?->format('Y-m-d H:i:s'),
            'menu' => [
                'id' => $reservation->menu->id,
                'date' => $reservation->menu->date->format('Y-m-d'),
                'price' => (string) $reservation->menu->price,
                'appetizer' => $reservation->menu->appetizer?->name,
                'main_dish' => $reservation->menu->mainDish?->name,
                'drink' => $reservation->menu->drink?->name,
            ],
        ];
    }
}