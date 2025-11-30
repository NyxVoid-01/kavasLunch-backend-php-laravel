<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva Cancelada - KAVAS-LUNCH</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #6b7280;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #6b7280;
            margin: 0;
            font-size: 28px;
        }
        .alert-box {
            background-color: #f3f4f6;
            border: 2px solid #6b7280;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .alert-box h2 {
            color: #374151;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .info-section {
            background-color: #f9fafb;
            border-left: 4px solid #9ca3af;
            padding: 15px;
            margin: 20px 0;
        }
        .menu-item {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .menu-item:last-child {
            border-bottom: none;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚫 KAVAS-LUNCH</h1>
            <p>Universidad Peruana Cayetano Heredia</p>
        </div>

        <div class="alert-box">
            <h2>Reserva Cancelada</h2>
            <p style="margin: 10px 0;">Código: <strong>{{ $reservation->code }}</strong></p>
        </div>

        <p>Estimado/a <strong>{{ $reservation->user->full_name }}</strong>,</p>
        
        <p>Te confirmamos que tu reserva ha sido cancelada exitosamente según tu solicitud.</p>

        <div class="info-section">
            <h3>📅 Detalles de la Reserva Cancelada</h3>
            <p><strong>Fecha:</strong> {{ $reservation->reservation_date->format('d/m/Y') }}</p>
            <p><strong>Horario que tenías asignado:</strong> {{ $reservation->pickup_time_slot->format('h:i A') }}</p>
            <p><strong>Estado:</strong> <span style="color: #6b7280;">Cancelada</span></p>
        </div>

        <div class="info-section">
            <h3>🍴 Menú Liberado</h3>
            <div class="menu-item">
                <strong>Entrada:</strong> {{ $reservation->menu->appetizer?->name ?? 'No especificado' }}
            </div>
            <div class="menu-item">
                <strong>Plato Principal:</strong> {{ $reservation->menu->mainDish?->name ?? 'No especificado' }}
            </div>
            <div class="menu-item">
                <strong>Bebida:</strong> {{ $reservation->menu->drink?->name ?? 'No especificado' }}
            </div>
        </div>

        <p style="text-align: center; margin: 30px 0;">
            Esperamos poder atenderte en una próxima oportunidad.
        </p>

        <div class="footer">
            <p><strong>KAVAS-LUNCH</strong></p>
            <p>Sistema de Reservas del Comedor Universitario</p>
            <p>Universidad Peruana Cayetano Heredia</p>
            <p style="margin-top: 10px; font-size: 12px; color: #999;">
                Este es un correo automático, por favor no responder.
            </p>
        </div>
    </div>
</body>
</html>
