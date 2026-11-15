<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Pasarela de pagos SIMULADA (RF-014). No se capturan datos de tarjeta ni se espera
 * un webhook: cada método se resuelve al instante.
 *  - Yape / Plin y tarjeta: el pago queda registrado como realizado al confirmar.
 *  - Efectivo: queda pendiente y se cobra en el comedor al entregar el pedido.
 */
class PaymentController extends Controller
{
    private const METHODS = ['yape', 'plin', 'credit_card', 'cash'];

    /**
     * Procesar el pago de una reserva
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

        $reservation = Reservation::with('menu')->findOrFail($request->reservation_id);
        $user = auth('api')->user();

        // Verificar que la reserva pertenece al usuario
        if ($reservation->user_id !== $user->id) {
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

        $method = $request->payment_method;
        $isCash = $method === 'cash';

        $payment = DB::transaction(function () use ($reservation, $request, $method, $isCash) {
            // Un solo registro de pago por reserva: si se reintenta, se actualiza
            $payment = Payment::updateOrCreate(
                ['reservation_id' => $reservation->id],
                [
                    'amount' => $reservation->menu->price,
                    'method' => $method,
                    // Efectivo se cobra al entregar; el resto se da por pagado al confirmar
                    'status' => $isCash ? 'pending' : 'completed',
                    'transaction_code' => $this->transactionCode($method),
                    'payment_proof_url' => $request->payment_proof_url,
                ]
            );

            $reservation->update([
                'payment_status' => $isCash ? 'pending' : 'paid',
                'payment_method' => $method,
            ]);

            return $payment;
        });

        return response()->json([
            'success' => true,
            'message' => $isCash ? 'Pago en efectivo registrado: pagarás al recoger tu pedido' : 'Pago procesado exitosamente',
            'data' => [
                'payment' => $payment,
                'reservation' => $reservation->fresh(['menu.appetizer', 'menu.mainDish', 'menu.drink'])
            ]
        ]);
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
        $methods = [
            [
                'id' => 'yape',
                'name' => 'Yape / Plin',
                'description' => 'Pago móvil instantáneo',
                'instructions' => 'Envía el pago al número indicado y confirma tu reserva',
                'phone' => config('services.payments.yape_number'),
                'enabled' => true,
            ],
            [
                'id' => 'credit_card',
                'name' => 'Tarjeta',
                'description' => 'Crédito/Débito',
                'instructions' => 'El cobro se realiza al confirmar la reserva',
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
