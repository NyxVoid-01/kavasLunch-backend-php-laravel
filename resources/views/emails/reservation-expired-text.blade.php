Estimado/a {{ $reservation->user->full_name }},

❌ Tu reserva ha expirado

Lamentamos informarte que tu reserva no fue recogida dentro del horario asignado y ha expirado automáticamente.

CÓDIGO DE RESERVA: {{ $reservation->code }}

📅 Detalles de la Reserva Expirada
Fecha: {{ $reservation->reservation_date->format('d/m/Y') }}
Horario asignado: {{ $reservation->pickup_time_slot->format('h:i A') }} - {{ $reservation->pickup_window_end->format('h:i A') }}
Estado: Expirada

🍴 Menú
Entrada: {{ $reservation->menu->appetizer?->name ?? 'No especificado' }}
Plato Principal: {{ $reservation->menu->mainDish?->name ?? 'No especificado' }}
Bebida: {{ $reservation->menu->drink?->name ?? 'No especificado' }}

ℹ️ ¿Qué pasó?
Tu reserva expiró porque no fue recogida dentro de tu horario de recojo. El stock ha sido liberado para que otros estudiantes puedan reservar.

💡 Para tu próxima reserva
- Asegúrate de llegar dentro de tu horario asignado
- Recibirás un recordatorio 10 minutos antes
- Recoge tu pedido dentro del horario que elegiste

¡Esperamos verte pronto en el comedor!

---
KAVAS-LUNCH
Sistema de Reservas del Comedor Universitario
Universidad Peruana Cayetano Heredia

Este es un correo automático, por favor no responder.
