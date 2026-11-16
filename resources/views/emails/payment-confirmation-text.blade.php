Hola {{ $reservation->user->full_name }},

✅ Pago confirmado

{{ $payment->method === 'cash' ? 'Registramos tu pago en caja.' : 'Recibimos la confirmación de tu pago.' }} Este correo es tu constancia.

Monto: S/ {{ number_format((float) $payment->amount, 2) }}
Método: {{ $methodLabel }}
Código de pago: {{ $payment->transaction_code }}
Fecha y hora del pago: {{ ($payment->paid_at ?? now())->format('d/m/Y H:i') }}
Código de reserva: {{ $reservation->code }}
@if ($reservation->status === 'pending')

Recoge tu almuerzo el {{ $reservation->reservation_date->format('d/m/Y') }} entre las {{ $reservation->pickup_time_slot->format('H:i') }} y las {{ $reservation->pickup_window_end->format('H:i') }} presentando tu código de reserva.
@endif

El pago es simulado: no se realizó ningún cobro real.

---
KAVAS-LUNCH
Sistema de Reservas del Comedor Universitario
Universidad Peruana Cayetano Heredia

Este es un correo automático, por favor no responder.
