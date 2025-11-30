Estimado/a {{ $reservation->user->full_name }},

🚫 Reserva Cancelada

Te confirmamos que tu reserva ha sido cancelada exitosamente según tu solicitud.

CÓDIGO DE RESERVA: {{ $reservation->code }}

📅 Detalles de la Reserva Cancelada
Fecha: {{ $reservation->reservation_date->format('d/m/Y') }}
Horario que tenías asignado: {{ $reservation->pickup_time_slot->format('h:i A') }}
Estado: Cancelada

🍴 Menú Liberado
Entrada: {{ $reservation->menu->appetizer?->name ?? 'No especificado' }}
Plato Principal: {{ $reservation->menu->mainDish?->name ?? 'No especificado' }}
Bebida: {{ $reservation->menu->drink?->name ?? 'No especificado' }}

Esperamos poder atenderte en una próxima oportunidad.

---
KAVAS-LUNCH
Sistema de Reservas del Comedor Universitario
Universidad Peruana Cayetano Heredia

Este es un correo automático, por favor no responder.
