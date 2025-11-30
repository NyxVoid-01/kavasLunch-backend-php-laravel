Estimado/a {{ $reservation->user->full_name }},

¡Tu reserva ha sido confirmada exitosamente!

CÓDIGO DE RESERVA: {{ $reservation->code }}
Presenta este código o escanea el QR en caja

📅 Detalles de tu Reserva
Fecha: {{ $reservation->reservation_date->format('d/m/Y') }}
Horario de Recojo: {{ $reservation->pickup_time_slot->format('h:i A') }} - {{ $reservation->pickup_time_slot->copy()->addMinutes(15)->format('h:i A') }}
Estado: Pendiente
Método de Pago: {{ ucfirst($reservation->payment_method) }}

🍴 Tu Menú
Entrada: {{ $reservation->menu->appetizer?->name ?? 'No especificado' }}
Plato Principal: {{ $reservation->menu->mainDish?->name ?? 'No especificado' }}
Bebida: {{ $reservation->menu->drink?->name ?? 'No especificado' }}

Precio Total: S/. {{ number_format($reservation->menu->price, 2) }}

📋 Instrucciones para Recoger
1. Llega al comedor entre las {{ $reservation->pickup_time_slot->format('h:i A') }} y las {{ $reservation->pickup_time_slot->copy()->addMinutes(15)->format('h:i A') }}
2. Presenta tu código QR o el código {{ $reservation->code }} en la caja
3. Si pagaste con Yape/Plin, presenta también tu comprobante de pago

⚠️ IMPORTANTE: 
- Recibirás un recordatorio 10 minutos antes de tu horario
- Si no recoges tu pedido dentro de los 15 minutos, tu reserva expirará automáticamente
- Puedes cancelar hasta las 10:00 AM del día de tu reserva

Si necesitas cancelar tu reserva, puedes hacerlo desde la aplicación hasta las 10:00 AM del día de tu reserva.

---
KAVAS-LUNCH
Sistema de Reservas del Comedor Universitario
Universidad Peruana Cayetano Heredia

Este es un correo automático, por favor no responder.