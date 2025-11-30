<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva Expirada - KAVAS-LUNCH</title>
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
            border-bottom: 3px solid #dc2626;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #dc2626;
            margin: 0;
            font-size: 28px;
        }
        .header p {
            color: #666;
            margin: 5px 0 0 0;
        }
        .alert-box {
            background-color: #fee2e2;
            border: 2px solid #dc2626;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .alert-box h2 {
            color: #dc2626;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .info-section {
            background-color: #f9fafb;
            border-left: 4px solid #6b7280;
            padding: 15px;
            margin: 20px 0;
        }
        .info-section h3 {
            margin-top: 0;
            color: #6b7280;
        }
        .menu-item {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .menu-item:last-child {
            border-bottom: none;
        }
        .explanation {
            background-color: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 20px 0;
        }
        .explanation h3 {
            margin-top: 0;
            color: #3b82f6;
        }
        .tips {
            background-color: #dcfce7;
            border-left: 4px solid #059669;
            padding: 15px;
            margin: 20px 0;
        }
        .tips h3 {
            margin-top: 0;
            color: #059669;
        }
        .tips ul {
            margin: 10px 0;
            padding-left: 20px;
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
            <h1>❌ KAVAS-LUNCH</h1>
            <p>Universidad Peruana Cayetano Heredia</p>
        </div>

        <div class="alert-box">
            <h2>Tu reserva ha expirado</h2>
            <p style="margin: 10px 0;">Código: <strong>{{ $reservation->code }}</strong></p>
        </div>

        <p>Estimado/a <strong>{{ $reservation->user->full_name }}</strong>,</p>
        
        <p>Lamentamos informarte que tu reserva no fue recogida dentro del horario asignado y ha expirado automáticamente.</p>

        <div class="info-section">
            <h3>📅 Detalles de la Reserva</h3>
            <p><strong>Fecha:</strong> {{ $reservation->reservation_date->format('d/m/Y') }}</p>
            <p><strong>Horario asignado:</strong> {{ $reservation->pickup_time_slot->format('h:i A') }} - {{ $reservation->pickup_time_slot->copy()->addMinutes(15)->format('h:i A') }}</p>
            <p><strong>Estado:</strong> <span style="color: #dc2626;">Expirada</span></p>
        </div>

        <div class="info-section">
            <h3>🍴 Menú</h3>
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

        <div class="explanation">
            <h3>ℹ️ ¿Qué pasó?</h3>
            <p>Tu reserva expiró porque no fue recogida dentro de los 15 minutos posteriores a tu horario asignado ({{ $reservation->pickup_time_slot->format('h:i A') }} - {{ $reservation->pickup_time_slot->copy()->addMinutes(15)->format('h:i A') }}).</p>
            <p>El stock ha sido liberado automáticamente para que otros estudiantes puedan reservar.</p>
        </div>

        <div class="tips">
            <h3>💡 Para tu próxima reserva</h3>
            <ul>
                <li>Asegúrate de llegar dentro de tu horario asignado</li>
                <li>Recibirás un recordatorio 10 minutos antes</li>
                <li>Tienes 15 minutos desde tu horario para recoger</li>
                <li>Puedes cancelar hasta las 10:00 AM si no podrás asistir</li>
            </ul>
        </div>

        <p style="text-align: center; margin: 30px 0;">
            <strong>¡Esperamos verte pronto en el comedor!</strong>
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
