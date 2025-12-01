<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Reserva</title>
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
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 28px;
        }
        .header p {
            color: #666;
            margin: 5px 0 0 0;
        }
        .code-box {
            background-color: #eff6ff;
            border: 2px dashed #2563eb;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .code {
            font-size: 32px;
            font-weight: bold;
            color: #2563eb;
            letter-spacing: 2px;
        }
        .info-section {
            background-color: #f9fafb;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
        }
        .info-section h3 {
            margin-top: 0;
            color: #2563eb;
        }
        .menu-item {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .menu-item:last-child {
            border-bottom: none;
        }
        .menu-item strong {
            color: #2563eb;
        }
        .instructions {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
        }
        .instructions h3 {
            margin-top: 0;
            color: #f59e0b;
        }
        .instructions ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .price {
            font-size: 24px;
            font-weight: bold;
            color: #059669;
            text-align: center;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #666;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2563eb;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍽️ KAVAS-LUNCH</h1>
            <p>Universidad Peruana Cayetano Heredia</p>
        </div>

        <p>Estimado/a <strong>{{ $reservation->user->full_name }}</strong>,</p>
        
        <p>¡Tu reserva ha sido confirmada exitosamente!</p>

        <div class="code-box">
            <p style="margin: 0; color: #666; font-size: 14px;">TU CÓDIGO DE RESERVA</p>
            <div class="code">{{ $reservation->code }}</div>
            <p style="margin: 10px 0 0 0; color: #666; font-size: 12px;">Presenta este código en caja para recoger tu pedido</p>
        </div>

        <div class="info-section">
            <h3>📅 Detalles de tu Reserva</h3>
            <p><strong>Fecha:</strong> {{ $reservation->reservation_date->format('d/m/Y') }}</p>
            <p><strong>Horario de Recojo:</strong> <span style="color: #2563eb; font-size: 18px; font-weight: bold;">{{ $reservation->pickup_time_slot->format('h:i A') }}</span></p>
            <p style="color: #f59e0b; font-size: 14px;">⏰ Tienes hasta las {{ $reservation->pickup_time_slot->copy()->addMinutes(15)->format('h:i A') }} para recoger tu pedido</p>
            <p><strong>Estado:</strong> <span style="color: #f59e0b;">Pendiente</span></p>
            <p><strong>Método de Pago:</strong> {{ ucfirst($reservation->payment_method) }}</p>
        </div>

        <div class="info-section">
            <h3>🍴 Tu Menú</h3>
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

        <div class="price">
            Precio Total: S/. {{ number_format($reservation->menu->price, 2) }}
        </div>

        <div class="instructions">
            <h3>📋 Instrucciones para Recoger</h3>
            <ol>
                <li>Llega al comedor entre las <strong>{{ $reservation->pickup_time_slot->format('h:i A') }}</strong> y las <strong>{{ $reservation->pickup_time_slot->copy()->addMinutes(15)->format('h:i A') }}</strong></li>
                <li>Presenta tu código de reserva en la caja</li>
                <li>Si pagaste con Yape/Plin, presenta también tu comprobante de pago</li>
                <li><strong style="color: #dc2626;">IMPORTANTE:</strong> Si no recoges tu pedido dentro de los 15 minutos, tu reserva expirará automáticamente</li>
            </ol>
        </div>

        <p style="text-align: center; color: #666; font-size: 14px;">
            Si necesitas cancelar tu reserva, puedes hacerlo desde la aplicación hasta las 10:00 AM del día de tu reserva.
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