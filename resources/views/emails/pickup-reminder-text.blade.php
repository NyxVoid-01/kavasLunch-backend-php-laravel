Estimado/a {{ $reservation->user->full_name }},

⏰ RECORDATORIO: Tu pedido está listo para recoger en 10 minutos

CÓDIGO DE RESERVA: {{ $reservation->code }}

📅 Detalles de Recojo
Horario: {{ $reservation->pickup_time_slot->format('h:i A') }} - {{ $reservation->pickup_time_slot->copy()->addMinutes(15)->format('h:i A') }}
Fecha: {{ $reservation->reservation_date->format('d/m/Y') }}

🍴 Tu Menú
Entrada: {{ $reservation->menu->appetizer?->name ?? 'No especificado' }}
Plato Principal: {{ $reservation->menu->mainDish?->name ?? 'No especificado' }}
Bebida: {{ $reservation->menu->drink?->name ?? 'No especificado' }}

📋 Instrucciones
1. Dirígete al comedor universitario
2. Presenta tu código QR o el código {{ $reservation->code }} en caja
3. Recoge tu pedido entre {{ $reservation->pickup_time_slot->format('h:i A') }} y {{ $reservation->pickup_time_slot->copy()->addMinutes(15)->format('h:i A') }}

⚠️ IMPORTANTE: Si no recoges tu pedido dentro de los 15 minutos de tu horario asignado, tu reserva expirará automáticamente y el stock será liberado.

¡Te esperamos!

---
KAVAS-LUNCH
Sistema de Reservas del Comedor Universitario
Universidad Peruana Cayetano Heredia

Este es un correo automático, por favor no responder.
