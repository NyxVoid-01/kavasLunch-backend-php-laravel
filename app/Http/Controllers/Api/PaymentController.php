<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|uuid|exists:reservations,id',
            'payment_method' => 'required|in:yape,plin,credit_card,cash',
            'payment_proof_url' => 'required_if:payment_method,yape,plin|url',
            'card_number' => 'required_if:payment_method,credit_card|string|size:16',
            'card_expiry' => 'required_if:payment_method,credit_card|string',
            'card_cvv' => 'required_if:payment_method,credit_card|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reservation = Reservation::findOrFail($request->reservation_id);
            $user = auth('api')->user();

            // Verificar que la reserva pertenece al usuario
            if ($reservation->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para procesar pago de esta reserva'
                ], 403);
            }

            // Simular procesamiento de pago según el método
            $paymentStatus = $this->simulatePaymentProcessing($request->payment_method, $request->all());

            if ($paymentStatus['success']) {
                // Crear registro de pago
                $payment = Payment::create([
                    'reservation_id' => $reservation->id,
                    'amount' => $reservation->menu->price,
                    'method' => $request->payment_method,
                    'status' => 'completed',
                    'transaction_code' => $paymentStatus['transaction_code'],
                    'payment_proof_url' => $request->payment_proof_url ?? null,
                ]);

                // Actualizar estado de la reserva
                $reservation->update([
                    'payment_status' => 'paid',
                    'payment_method' => $request->payment_method,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Pago procesado exitosamente',
                    'data' => [
                        'payment' => $payment,
                        'reservation' => $reservation->fresh(['menu.appetizer', 'menu.mainDish', 'menu.drink'])
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $paymentStatus['message']
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function simulatePaymentProcessing($method, $data)
    {
        switch ($method) {
            case 'yape':
            case 'plin':
                // Simular número para pago
                $phoneNumber = '9' . rand(10000000, 99999999);
                // Simular verificación (en realidad esperaríamos un webhook)
                sleep(2); // Simular delay de verificación
                
                return [
                    'success' => true,
                    'transaction_code' => 'YAPE-' . strtoupper(uniqid()),
                    'message' => 'Pago verificado exitosamente'
                ];

            case 'credit_card':
                // Validación básica de tarjeta (simulada)
                $cardNumber = $data['card_number'];
                $expiry = $data['card_expiry'];
                $cvv = $data['card_cvv'];

                // Simular validación
                if (strlen($cardNumber) === 16 && 
                    preg_match('/^(0[1-9]|1[0-2])\/?([0-9]{2})$/', $expiry) &&
                    strlen($cvv) === 3) {
                    
                    sleep(3); // Simular procesamiento
                    
                    return [
                        'success' => true,
                        'transaction_code' => 'CC-' . strtoupper(uniqid()),
                        'message' => 'Pago con tarjeta procesado exitosamente'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Datos de tarjeta inválidos'
                    ];
                }

            case 'cash':
                // Para efectivo, solo crear el registro
                return [
                    'success' => true,
                    'transaction_code' => 'CASH-' . strtoupper(uniqid()),
                    'message' => 'Pago en efectivo registrado'
                ];

            default:
                return [
                    'success' => false,
                    'message' => 'Método de pago no soportado'
                ];
        }
    }

    public function getPaymentMethods()
    {
        $methods = [
            [
                'code' => 'yape',
                'name' => 'Yape',
                'value' => 'yape',
                'label' => 'Yape',
                'description' => 'Pago con Yape',
                'instructions' => 'Se generará un número al cual enviar el pago'
            ],
            [
                'code' => 'plin',
                'name' => 'Plin',
                'value' => 'plin',
                'label' => 'Plin',
                'description' => 'Pago con Plin',
                'instructions' => 'Se generará un número al cual enviar el pago'
            ],
            [
                'code' => 'credit_card',
                'name' => 'Tarjeta de Crédito',
                'value' => 'credit_card',
                'label' => 'Tarjeta de Crédito',
                'description' => 'Pago con tarjeta de crédito',
                'instructions' => 'Ingresa los datos de tu tarjeta'
            ],
            [
                'code' => 'cash',
                'name' => 'Efectivo',
                'value' => 'cash',
                'label' => 'Efectivo',
                'description' => 'Pagar en el comedor',
                'instructions' => 'Presenta el código en caja para pagar'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => ['payment_methods' => $methods]
        ]);
    }
}