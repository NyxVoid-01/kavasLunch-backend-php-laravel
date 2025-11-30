<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Mail\PickupReminderMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPickupReminderEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function handle(): void
    {
        try {
            // Verificar que la reserva siga pendiente
            if ($this->reservation->status !== 'pending') {
                Log::info('Reminder not sent - reservation not pending', [
                    'reservation_id' => $this->reservation->id,
                    'status' => $this->reservation->status
                ]);
                return;
            }

            // Cargar relaciones necesarias
            $this->reservation->load(['user', 'menu.appetizer', 'menu.mainDish', 'menu.drink']);

            // Enviar email de recordatorio
            Mail::to($this->reservation->user->email)
                ->send(new PickupReminderMail($this->reservation));

            Log::info('Pickup reminder sent successfully', [
                'reservation_id' => $this->reservation->id,
                'reservation_code' => $this->reservation->code,
                'pickup_time' => $this->reservation->pickup_time_slot->format('H:i'),
                'user_email' => $this->reservation->user->email,
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending pickup reminder', [
                'reservation_id' => $this->reservation->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
