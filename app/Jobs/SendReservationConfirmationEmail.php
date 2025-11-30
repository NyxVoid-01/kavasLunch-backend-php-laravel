<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Mail\ReservationConfirmation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReservationConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $reservation;

    /**
     * Create a new job instance.
     */
    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Cargar relaciones necesarias
            $this->reservation->load(['user', 'menu.appetizer', 'menu.mainDish', 'menu.drink']);

            // Enviar email usando Mailable
            Mail::to($this->reservation->user->email)
                ->send(new ReservationConfirmation($this->reservation));

            Log::info('Email de confirmación enviado exitosamente', [
                'reservation_id' => $this->reservation->id,
                'reservation_code' => $this->reservation->code,
                'user_email' => $this->reservation->user->email,
                'user_name' => $this->reservation->user->full_name,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al enviar email de confirmación', [
                'reservation_id' => $this->reservation->id,
                'reservation_code' => $this->reservation->code,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);

            // Re-lanzar la excepción para que el job se reintente
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job de email falló después de reintentos', [
            'reservation_id' => $this->reservation->id,
            'reservation_code' => $this->reservation->code,
            'error' => $exception->getMessage(),
        ]);
    }
}