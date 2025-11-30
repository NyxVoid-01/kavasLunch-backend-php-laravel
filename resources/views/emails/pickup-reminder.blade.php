<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de Recojo - KAVAS-LUNCH</title>
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
            border-bottom: 3px solid #f59e0b;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #f59e0b;
            margin: 0;
            font-size: 28px;
        }
        .header p {
            color: #666;
            margin: 5px 0 0 0;
        }
        .alert-box {
            background-color: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .alert-box h2 {
            color: #f59e0b;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .time {
            font-size: 32px;
            font-weight: bold;
            color: #f59e0b;
            margin: 10px 0;
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
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
            letter-spacing: 2px;
        }
        .qr-code {
            max-width: 200px;
            margin: 15px auto;
            display: block;
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
            background-color: #dcfce7;
            border-left: 4px solid #059669;
            padding: 15px;
            margin: 20px 0;
        }
        .instructions h3 {
            margin-top: 0;
            color: #059669;
        }
        .instructions ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .warning {
            background-color: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin: 20px 0;
        }
        .warning p {
            margin: 0;
            color: #dc2626;
            font-weight: bold;
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
            <h1>⏰ KAVAS-LUNCH</h1>
            <p>Universidad Peruana Cayetano Heredia</p>
        </div>

        <div class="alert-box">
            <h2>¡Tu pedido está listo!</h2>
            <p style="margin: 10px 0; font-size: 16px;">Recógelo en:</p>
            <div class="time">{{ $reservation->pickup_time_slot->format('h:i A') }}</div>
            <p style="margin: 5px 0; color: #666;">Tienes hasta las {{ $reservation->pickup_time_slot->copy()->addMinutes(15)->format('h:i A') }}</p>
        </div>

        <p>Estimado/a <strong>{{ $reservation->user->full_name }}</strong>,</p>
        
        <p>Este es un recordatorio de que tu pedido estará listo para recoger en <strong>10 minutos</strong>.</p>

        <div class="code-box">
            <p style="margin: 0; color: #666; font-size: 14px;">CÓDIGO QR DE RESERVA</p>
            @if($reservation->qr_code_url)
                <img src="{{ $reservation->qr_code_url }}" alt="QR Code" class="qr-code">
            @endif
            <div class="code">{{ $reservation->code }}</div>
            <p style="margin: 10px 0 0 0; color: #666; font-size: 12px;">Presenta este código o escanea el QR</p>
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

        <div class="instructions">
            <h3>📋 Instrucciones</h3>
            <ol>
                <li>Dirígete al comedor universitario <strong>ahora</strong></li>
                <li>Presenta tu código QR o el código <strong>{{ $reservation->code }}</strong> en caja</li>
                <li>Recoge tu pedido entre <strong>{{ $reservation->pickup_time_slot->format('h:i A') }}</strong> y <strong>{{ $reservation->pickup_time_slot->copy()->addMinutes(15)->format('h:i A') }}</strong></li>
                @if($reservation->payment_method !== 'cash')
                <li>Presenta tu comprobante de pago</li>
                @endif
            </ol>
        </div>

        <div class="warning">
            <p>⚠️ IMPORTANTE: Si no recoges tu pedido dentro de los 15 minutos, tu reserva expirará automáticamente.</p>
        </div>

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
