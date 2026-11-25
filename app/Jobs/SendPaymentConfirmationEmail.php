<?php

namespace App\Jobs;

use App\Mail\PaymentConfirmationMail;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// Correo de confirmación de pago (RF-014). Va por la cola: se envía en segundos, dentro del plazo de 2 minutos.
class SendPaymentConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3;
    public $backoff = 20;

    public function __construct(public string $paymentId)
    {
        // Se despacha cuando la transacción del pago ya se guardó
        $this->afterCommit();
    }

    public function handle(): void
    {
        $payment = Payment::with(['reservation.user', 'reservation.menu.mainDish'])->find($this->paymentId);

        // Solo se confirma un pago completado
        if (!$payment || $payment->status !== 'completed' || !$payment->reservation?->user) {
            return;
        }

        Mail::to($payment->reservation->user->email)->send(new PaymentConfirmationMail($payment));

        Log::info('Confirmación de pago enviada', [
            'payment_id' => $payment->id,
            'reservation_code' => $payment->reservation->code,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('No se pudo enviar la confirmación de pago', [
            'payment_id' => $this->paymentId,
            'error' => $exception->getMessage(),
        ]);
    }
}
