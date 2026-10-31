<?php

namespace App\Services;

use App\Models\DailyMenu;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimeSlotService
{
    // Constantes de configuración
    const OPENING_HOUR = 11; // 11:00 AM
    const CLOSING_HOUR = 15; // 3:00 PM
    const SLOT_INTERVAL_MINUTES = 15;
    const MAX_CAPACITY_PER_SLOT = 10;
    const EXPIRATION_MINUTES = 15; // Tiempo después del slot para expirar
    
    /**
     * Generar todos los time slots para una fecha específica
     * 
     * @param Carbon $date
     * @return Collection
     */
    public function generateSlotsForDate(Carbon $date): Collection
    {
        $slots = collect();
        
        // Crear slots desde 11:00 AM hasta 3:00 PM en intervalos de 15 minutos
        $currentSlot = $date->copy()->setTime(self::OPENING_HOUR, 0, 0);
        $endTime = $date->copy()->setTime(self::CLOSING_HOUR, 0, 0);
        
        while ($currentSlot->lt($endTime)) {
            $slots->push($currentSlot->copy());
            $currentSlot->addMinutes(self::SLOT_INTERVAL_MINUTES);
        }
        
        return $slots;
    }
    
    /**
     * Obtener slots disponibles para un menú y fecha
     * Considera tanto el stock del menú como la capacidad por slot
     * 
     * @param string $menuId
     * @param Carbon $date
     * @return Collection
     */
    public function getAvailableSlots(string $menuId, Carbon $date): Collection
    {
        // Validar que sea día laboral
        if (!$this->isWeekday($date)) {
            return collect();
        }
        
        // Obtener menú y verificar stock
        $menu = DailyMenu::find($menuId);
        if (!$menu || $menu->available_stock <= 0) {
            return collect();
        }
        
        // Generar todos los slots del día
        $allSlots = $this->generateSlotsForDate($date);
        
        // Filtrar slots disponibles
        return $allSlots->filter(function ($slot) use ($date) {
            // No permitir slots en el pasado
            if ($slot->isPast()) {
                return false;
            }
            
            // Verificar capacidad del slot
            $slotCapacity = $this->getSlotCapacity($slot, $date);
            return $slotCapacity < self::MAX_CAPACITY_PER_SLOT;
        })->values();
    }
    
    /**
     * Asignar automáticamente el siguiente slot disponible
     * 
     * @param string $menuId
     * @param Carbon $date
     * @return Carbon|null
     */
    public function assignNextAvailableSlot(string $menuId, Carbon $date): ?Carbon
    {
        $availableSlots = $this->getAvailableSlots($menuId, $date);
        
        if ($availableSlots->isEmpty()) {
            return null;
        }
        
        // Retornar el primer slot disponible
        return $availableSlots->first();
    }
    
    /**
     * Obtener la capacidad usada de un slot específico
     * 
     * @param Carbon $slot
     * @param Carbon $date
     * @return int
     */
    public function getSlotCapacity(Carbon $slot, Carbon $date): int
    {
        // Comparar solo la hora del datetime (PostgreSQL compatible)
        $timeString = $slot->format('H:i:s');
        
        return Reservation::where('reservation_date', $date->toDateString())
            ->whereRaw('CAST(pickup_time_slot AS TIME) = ?', [$timeString])
            ->whereIn('status', ['pending', 'delivered'])
            ->count();
    }
    
    /**
     * Verificar si una fecha es día laboral (Lunes a Viernes)
     * 
     * @param Carbon $date
     * @return bool
     */
    public function isWeekday(Carbon $date): bool
    {
        return $date->isWeekday();
    }
    
    /**
     * Calcular timestamp de expiración
     * 
     * @param Carbon $pickupTimeSlot
     * @return Carbon
     */
    public function calculateExpirationTime(Carbon $pickupTimeSlot): Carbon
    {
        return $pickupTimeSlot->copy()->addMinutes(self::EXPIRATION_MINUTES);
    }
    
    /**
     * Obtener reservas agrupadas por time slot para una fecha
     * 
     * @param Carbon $date
     * @return Collection
     */
    public function getReservationsByTimeSlot(Carbon $date): Collection
    {
        $slots = $this->generateSlotsForDate($date);
        
        return $slots->map(function ($slot) use ($date) {
            $reservations = Reservation::with(['user', 'menu'])
                ->where('reservation_date', $date->toDateString())
                ->where('pickup_time_slot', $slot)
                ->whereIn('status', ['pending', 'delivered'])
                ->get();
            
            return [
                'time_slot' => $slot->format('H:i'),
                'time_slot_full' => $slot->format('Y-m-d H:i:s'),
                'capacity_used' => $reservations->count(),
                'capacity_available' => self::MAX_CAPACITY_PER_SLOT - $reservations->count(),
                'is_full' => $reservations->count() >= self::MAX_CAPACITY_PER_SLOT,
                'reservations' => $reservations,
            ];
        });
    }
    
    /**
     * Validar si se puede reservar para una fecha específica
     * 
     * @param Carbon $date
     * @return array
     */
    public function validateReservationDate(Carbon $date): array
    {
        $now = now();
        $today = $now->copy()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        
        // Solo se puede reservar para hoy o mañana
        if ($date->lt($today) || $date->gt($tomorrow)) {
            return [
                'valid' => false,
                'message' => 'Solo puedes reservar para hoy o mañana'
            ];
        }
        
        // Verificar que sea día laboral
        if (!$this->isWeekday($date)) {
            return [
                'valid' => false,
                'message' => 'Solo se puede reservar para días laborales (Lunes a Viernes)'
            ];
        }
        
        // Si es para hoy, verificar que no sea muy tarde
        if ($date->isToday() && $now->hour >= self::CLOSING_HOUR) {
            return [
                'valid' => false,
                'message' => 'Ya no se pueden hacer reservas para hoy'
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Validar si se puede cancelar una reserva
     * 
     * @param Reservation $reservation
     * @return array
     */
    public function validateCancellation(Reservation $reservation): array
    {
        $now = now();
        $reservationDate = Carbon::parse($reservation->reservation_date);
        
        // Solo se puede cancelar antes de las 10 AM del día de la reserva
        $cancellationDeadline = $reservationDate->copy()->setTime(10, 0, 0);
        
        if ($now->gte($cancellationDeadline)) {
            return [
                'valid' => false,
                'message' => 'Solo puedes cancelar antes de las 10:00 AM del día de tu reserva'
            ];
        }
        
        return ['valid' => true];
    }
}
