<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Models\StockAudit;
use App\Mail\ReservationExpiredMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReleaseExpiredReservationStock implements ShouldQueue
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
                Log::info('Stock not released - reservation not pending', [
                    'reservation_id' => $this->reservation->id,
                    'status' => $this->reservation->status
                ]);
                return;
            }

            DB::transaction(function () {
                // Marcar como expirada
                $this->reservation->update([
                    'status' => 'expired',
                    'expired_at' => now(),
                ]);

                // Incrementar stock del menú
                $this->reservation->menu->increment('stock');

                // Registrar en auditoría de stock
                StockAudit::create([
                    'menu_id' => $this->reservation->menu_id,
                    'change_type' => 'release',
                    'quantity_change' => 1,
                    'previous_stock' => $this->reservation->menu->stock - 1,
                    'new_stock' => $this->reservation->menu->stock,
                    'reason' => 'Reserva expirada - no recogida a tiempo',
                    'performed_by' => null, // Sistema automático
                ]);

                Log::info('Reservation expired and stock released', [
                    'reservation_id' => $this->reservation->id,
                    'reservation_code' => $this->reservation->code,
                    'menu_id' => $this->reservation->menu_id,
                    'stock_released' => 1,
                ]);
            });

            // Enviar email de notificación al usuario
            $this->reservation->load(['user', 'menu']);
            Mail::to($this->reservation->user->email)
                ->send(new ReservationExpiredMail($this->reservation));

        } catch (\Exception $e) {
            Log::error('Error releasing expired reservation stock', [
                'reservation_id' => $this->reservation->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
