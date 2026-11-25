<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendPaymentConfirmationEmail;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Pasarela de pagos SIMULADA (RF-014). No se cobran montos reales ni se capturan datos de tarjeta.
 *  - Yape / Plin y tarjeta: se genera una solicitud de pago que vence a los 3 minutos
 *    (POST /payment/request). El pago solo se acepta si se confirma dentro de ese plazo
 *    (POST /payment/process). Al confirmarse se envía el correo de confirmación de pago.
 *  - Efectivo: queda pendiente y se cobra en el comedor al entregar el pedido; en ese momento
 *    se envía la confirmación de pago.
 */
class PaymentController extends Controller
{
    private const METHODS = ['yape', 'plin', 'credit_card', 'cash'];
    private const ONLINE_METHODS = ['yape', 'plin', 'credit_card'];

    /**
     * Generar la solicitud de pago (Yape/Plin o tarjeta). Vence a los 3 minutos.
     * POST /api/user/payment/request
     */
    public function requestPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|uuid|exists:reservations,id',
            'payment_method' => 'required|in:' . implode(',', self::ONLINE_METHODS),
        ], [
            'payment_method.in' => 'La solicitud de pago solo aplica a Yape/Plin o tarjeta.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $reservation = Reservation::with('menu')->findOrFail($request->reservation_id);
        if ($error = $this->payableError($reservation)) {
            return $error;
        }

        $method = $request->payment_method;

        $payment = DB::transaction(function () use ($reservation, $method) {
            // Un solo registro de pago por reserva: una nueva solicitud reemplaza a la anterior
            $payment = Payment::updateOrCreate(
                ['reservation_id' => $reservation->id],
                [
                    'amount' => $reservation->menu->price,
                    'method' => $method,
                    'status' => 'pending',
                    'transaction_code' => $this->transactionCode($method),
                    'expires_at' => now()->addMinutes(Payment::PAYMENT_WINDOW_MINUTES),
                    'paid_at' => null,
                ]
            );

            $reservation->update(['payment_method' => $method, 'payment_status' => 'pending']);

            return $payment;
        });

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de pago generada. Confírmala dentro de ' . Payment::PAYMENT_WINDOW_MINUTES . ' minutos.',
            'data' => ['payment_request' => $this->formatRequest($payment)],
        ], 201);
    }

    /**
     * Confirmar el pago de una reserva
     * POST /api/user/payment/process
     */
    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|uuid|exists:reservations,id',
            'payment_method' => 'required|in:' . implode(',', self::METHODS),
            'payment_proof_url' => 'nullable|url|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $reservation = Reservation::with(['menu', 'payment'])->findOrFail($request->reservation_id);
        if ($error = $this->payableError($reservation)) {
            return $error;
        }

        $method = $request->payment_method;
        $isCash = $method === 'cash';

        // Yape/Plin y tarjeta: solo se acepta la solicitud vigente del mismo método (máximo 3 minutos)
        if (!$isCash) {
            $current = $reservation->payment;

            if ($current && $current->status === 'pending' && $current->expires_at && $current->method !== $method) {
                return response()->json([
                    'success' => false,
                    'message' => 'La solicitud de pago es para ' . Payment::methodLabel($current->method) . '. Genera una nueva para cambiar de método.',
                    'error' => 'payment_request_method_mismatch',
                ], 422);
            }

            if (!$current || $current->status !== 'pending' || !$current->expires_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Primero genera la solicitud de pago.',
                    'error' => 'payment_request_missing',
                ], 422);
            }

            if ($current->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La solicitud de pago venció: el pago debe confirmarse dentro de ' . Payment::PAYMENT_WINDOW_MINUTES . ' minutos. Genera una nueva solicitud.',
                    'error' => 'payment_request_expired',
                ], 422);
            }
        }

        $payment = DB::transaction(function () use ($reservation, $request, $method, $isCash) {
            $payment = Payment::updateOrCreate(
                ['reservation_id' => $reservation->id],
                $isCash
                    // Efectivo se cobra al entregar: sin plazo, queda pendiente
                    ? [
                        'amount' => $reservation->menu->price,
                        'method' => 'cash',
                        'status' => 'pending',
                        'transaction_code' => $this->transactionCode('cash'),
                        'expires_at' => null,
                        'paid_at' => null,
                        'payment_proof_url' => $request->payment_proof_url,
                    ]
                    : [
                        'status' => 'completed',
                        'paid_at' => now(),
                        'payment_proof_url' => $request->payment_proof_url,
                    ]
            );

            $reservation->update([
                'payment_status' => $isCash ? 'pending' : 'paid',
                'payment_method' => $method,
            ]);

            return $payment;
        });

        // Confirmación de pago por correo (RF-014: dentro de 2 minutos; la cola la envía al instante)
        if (!$isCash) {
            SendPaymentConfirmationEmail::dispatch($payment->id);
        }

        return response()->json([
            'success' => true,
            'message' => $isCash ? 'Pago en efectivo registrado: pagarás al recoger tu pedido' : 'Pago confirmado. Te enviamos la confirmación por correo.',
            'data' => [
                'payment' => $payment->fresh(),
                'reservation' => $reservation->fresh(['menu.appetizer', 'menu.mainDish', 'menu.drink'])
            ]
        ]);
    }

    // Solo el dueño puede pagar, y solo una reserva pendiente que aún no se pagó
    private function payableError(Reservation $reservation)
    {
        if ($reservation->user_id !== auth('api')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para procesar pago de esta reserva'
            ], 403);
        }

        if ($reservation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede pagar una reserva pendiente'
            ], 422);
        }

        if ($reservation->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Esta reserva ya fue pagada'
            ], 422);
        }

        return null;
    }

    private function formatRequest(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'reservation_id' => $payment->reservation_id,
            'method' => $payment->method,
            'amount' => $payment->amount,
            'transaction_code' => $payment->transaction_code,
            'expires_at' => $payment->expires_at?->toIso8601String(),
            // El frontend cuenta desde aquí para no depender del reloj del dispositivo
            'expires_in_seconds' => max(0, (int) now()->diffInSeconds($payment->expires_at, false)),
            'window_minutes' => Payment::PAYMENT_WINDOW_MINUTES,
            'phone' => in_array($payment->method, ['yape', 'plin'], true) ? config('services.payments.yape_number') : null,
        ];
    }

    private function transactionCode(string $method): string
    {
        $prefix = match ($method) {
            'yape', 'plin' => 'YAPE',
            'credit_card' => 'CC',
            default => 'CASH',
        };

        return $prefix . '-' . strtoupper(Str::random(12));
    }

    /**
     * Métodos de pago disponibles
     * GET /api/user/payment/methods
     */
    public function getPaymentMethods()
    {
        $window = Payment::PAYMENT_WINDOW_MINUTES;

        $methods = [
            [
                'id' => 'yape',
                'name' => 'Yape / Plin',
                'description' => 'Pago móvil instantáneo',
                'instructions' => "Al confirmar se genera la solicitud de pago: envía el monto a este número y confírmalo dentro de {$window} minutos",
                'phone' => config('services.payments.yape_number'),
                'enabled' => true,
            ],
            [
                'id' => 'credit_card',
                'name' => 'Tarjeta',
                'description' => 'Crédito/Débito',
                'instructions' => "Al confirmar se genera la solicitud de pago: confírmala dentro de {$window} minutos",
                'phone' => null,
                'enabled' => true,
            ],
            [
                'id' => 'cash',
                'name' => 'Efectivo',
                'description' => 'Pago en caja',
                'instructions' => 'Pagarás al momento de recoger tu pedido',
                'phone' => null,
                'enabled' => true,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => ['methods' => $methods]
        ]);
    }
}
