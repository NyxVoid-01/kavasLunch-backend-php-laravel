<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido/a - KAVAS-LUNCH</title>
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
            border-bottom: 3px solid #9A1919;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #9A1919;
            margin: 0;
            font-size: 28px;
        }
        .button-wrap {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            background-color: #9A1919;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: bold;
            padding: 14px 32px;
            border-radius: 10px;
        }
        .info-section {
            background-color: #FFFAEB;
            border-left: 4px solid #FFBF00;
            padding: 15px;
            margin: 20px 0;
        }
        .link {
            word-break: break-all;
            font-size: 13px;
            color: #9A1919;
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
            <h1>🍽️ KAVAS-LUNCH</h1>
            <p>Universidad Peruana Cayetano Heredia</p>
        </div>

        <p>Hola <strong>{{ $user->full_name }}</strong>,</p>

        <p>¡Tu cuenta <strong>{{ $user->email }}</strong> ya está lista! Ahora puedes reservar tu almuerzo en el comedor universitario sin hacer fila.</p>

        <div class="info-section">
            <p style="margin: 0;"><strong>Recuerda:</strong> puedes reservar para hoy (hasta las 15:00) o para mañana, de lunes a viernes. Cada reserva se recoge en su turno de 1 hora y vence al terminar.</p>
        </div>

        <div class="button-wrap">
            <a href="{{ $menusUrl }}" class="button">Ver menús disponibles</a>
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
