Estimado/a {{ $reservation->user->full_name }},

¡Tu reserva ha sido confirmada exitosamente!

CÓDIGO DE RESERVA: {{ $reservation->code }}
Presenta este código en caja

📅 Detalles de tu Reserva
Fecha: {{ $reservation->reservation_date->format('d/m/Y') }}
Horario de Recojo: {{ $reservation->pickup_time_slot->format('h:i A') }} - {{ $reservation->pickup_window_end->format('h:i A') }}
Estado: Pendiente
Método de Pago: {{ \App\Models\Payment::methodLabel($reservation->payment_method) }}

🍴 Tu Menú
Entrada: {{ $reservation->menu->appetizer?->name ?? 'No especificado' }}
Plato Principal: {{ $reservation->menu->mainDish?->name ?? 'No especificado' }}
Bebida: {{ $reservation->menu->drink?->name ?? 'No especificado' }}

Precio Total: S/. {{ number_format($reservation->menu->price, 2) }}

📋 Instrucciones para Recoger
1. Llega al comedor entre las {{ $reservation->pickup_time_slot->format('h:i A') }} y las {{ $reservation->pickup_window_end->format('h:i A') }}
2. Presenta tu código {{ $reservation->code }} en la caja
3. Si pagas con Yape/Plin o tarjeta, recibirás otro correo con la confirmación de tu pago; si pagas en efectivo, paga en caja al recoger

⚠️ IMPORTANTE: 
- Recibirás un recordatorio 10 minutos antes de tu horario
- Si no recoges tu pedido dentro de tu horario de recojo, tu reserva expirará automáticamente
- Puedes cancelar hasta las 10:00 AM del día de tu reserva

Si necesitas cancelar tu reserva, puedes hacerlo desde la aplicación hasta las 10:00 AM del día de tu reserva.

---
KAVAS-LUNCH
Sistema de Reservas del Comedor Universitario
Universidad Peruana Cayetano Heredia

Este es un correo automático, por favor no responder.