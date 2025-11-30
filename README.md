# KAVAS-LUNCH Backend API

API RESTful para el sistema de gestión del comedor universitario KAVAS-LUNCH. Construido con Laravel 11.

## 🔐 Autenticación

El sistema utiliza **JWT (JSON Web Tokens)**.
Todas las rutas protegidas (User y Admin) requieren el header:
`Authorization: Bearer <token>`

---

## 📚 Referencia Completa de Endpoints

### 🔑 Autenticación
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `POST` | `/api/auth/register` | Registrar nuevo usuario |
| `POST` | `/api/auth/login` | Iniciar sesión y obtener token |
| `POST` | `/api/auth/logout` | Cerrar sesión (invalida token) |
| `POST` | `/api/auth/refresh` | Refrescar token expirado |
| `GET` | `/api/auth/profile` | Obtener perfil del usuario actual |
| `PUT` | `/api/auth/profile` | Actualizar perfil |

### 🌐 Públicos (Sin Auth)
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/public/menus` | Listar menús disponibles |
| `GET` | `/api/public/menus/today` | Ver menú del día actual |
| `GET` | `/api/public/menus/{id}` | Ver detalles de un menú |
| `GET` | `/api/public/dishes` | Listar platos disponibles |

### 👤 Usuario (Requiere Auth)
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/user/menus` | Listar menús (vista usuario) |
| `GET` | `/api/user/menus/today` | Ver menú de hoy |
| `GET` | `/api/user/menus/{id}` | Ver detalles de menú |
| `GET` | `/api/user/reservations` | Listar mis reservas |
| `POST` | `/api/user/reservations` | **Crear reserva** (asigna slot y QR) |
| `GET` | `/api/user/reservations/available-slots` | **Ver slots disponibles** |
| `GET` | `/api/user/reservations/{id}` | Ver detalle de reserva |
| `DELETE` | `/api/user/reservations/{id}/cancel` | **Cancelar reserva** |
| `GET` | `/api/user/payment/methods` | Ver métodos de pago |
| `POST` | `/api/user/payment/process` | Procesar pago (simulado) |

### 🛡️ Administrador (Requiere Auth + Rol Admin)
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/admin/dashboard/stats` | Estadísticas generales |
| `GET` | `/api/admin/reservations` | Listar todas las reservas |
| `GET` | `/api/admin/reservations/today` | Reservas de hoy |
| `GET` | `/api/admin/reservations/by-timeslot` | **Ver por Time Slots** |
| `POST` | `/api/admin/reservations/scan-qr` | **Escanear QR (Entrega)** |
| `GET` | `/api/admin/reservations/{id}` | Ver detalle reserva |
| `PUT` | `/api/admin/reservations/{id}/deliver` | Marcar como entregado (manual) |
| `PUT` | `/api/admin/reservations/{id}/cancel` | Cancelar reserva (admin) |
| `GET` | `/api/admin/menus` | Gestión: Listar menús |
| `POST` | `/api/admin/menus` | Gestión: Crear menú |
| `GET` | `/api/admin/menus/{id}` | Gestión: Ver menú |
| `PUT` | `/api/admin/menus/{id}` | Gestión: Actualizar menú |
| `DELETE` | `/api/admin/menus/{id}` | Gestión: Eliminar menú |
| `PUT` | `/api/admin/menus/{id}/stock` | Gestión: Actualizar stock |
| `GET` | `/api/admin/dishes` | Gestión: Listar platos |
| `POST` | `/api/admin/dishes` | Gestión: Crear plato |
| `GET` | `/api/admin/dishes/{id}` | Gestión: Ver plato |
| `PUT` | `/api/admin/dishes/{id}` | Gestión: Actualizar plato |
| `DELETE` | `/api/admin/dishes/{id}` | Gestión: Eliminar plato |
| `GET` | `/api/admin/reports/reservations` | Reporte: Reservas |
| `GET` | `/api/admin/reports/sales` | Reporte: Ventas |
| `GET` | `/api/admin/reports/stock-audits` | Reporte: Auditoría Stock |

---

## 📝 Detalles de Uso (Endpoints Clave)

### 1. Ver Slots Disponibles
Consulta los horarios y capacidad disponible para una fecha.

- **GET** `/api/user/reservations/available-slots`
- **Query Params**:
  - `menu_id` (requerido): UUID del menú
  - `date` (opcional): `YYYY-MM-DD`
- **Response**:
```json
{
    "success": true,
    "data": {
        "date": "2025-11-27",
        "available_slots": [
            {
                "time": "11:00",
                "datetime": "2025-11-27 11:00:00",
                "capacity_available": 7
            },
            ...
        ]
    }
}
```

### 2. Crear Reserva
Crea una reserva, asigna automáticamente un time slot y genera el código QR.

- **POST** `/api/user/reservations`
- **Body**:
```json
{
    "menu_id": "uuid-del-menu",
    "reservation_date": "2025-11-27", // Opcional, default: hoy
    "payment_method": "cash" // cash, yape, plin, credit_card
}
```
- **Response**:
```json
{
    "success": true,
    "data": {
        "reservation": {
            "id": "uuid",
            "code": "KL-12345678",
            "pickup_time_slot": "12:00",
            "pickup_window_end": "12:15",
            "qr_code_url": "http://.../qr-codes/qr-KL-12345678.png",
            "status": "pending"
        }
    }
}
```

### 3. Reservas por Time Slot (Admin)
Vista agrupada para gestión de entregas.

- **GET** `/api/admin/reservations/by-timeslot`
- **Query Params**: `date` (YYYY-MM-DD)
- **Response**:
```json
{
    "success": true,
    "data": {
        "time_slots": [
            {
                "time_slot": "11:00",
                "capacity_used": 5,
                "reservations": [...]
            }
        ]
    }
}
```

### 4. Escanear QR (Admin)
Marca una reserva como entregada escaneando su código.

- **POST** `/api/admin/reservations/scan-qr`
- **Body**:
```json
{
    "code": "KL-12345678"
}
```

---

## ⚙️ Características del Sistema

### Gestión de Horarios (Time Slots)
- **Horario**: 11:00 AM - 3:00 PM
- **Intervalo**: 15 minutos
- **Capacidad**: 10 personas por slot
- **Asignación**: Automática (siguiente slot disponible)

### Ciclo de Vida de Reserva
1. **Pending**: Reserva creada, QR generado.
2. **Delivered**: Entregada (vía Scan QR o manual).
3. **Cancelled**: Cancelada por usuario (antes 10AM) o admin.
4. **Expired**: No recogida 15 min después del slot (Stock liberado automáticamente).

### Jobs Automáticos
- **Recordatorio**: Email enviado 10 min antes del slot.
- **Expiración**: Ejecutado 15 min después del slot si no se ha entregado.

---

## 📧 Verificación de Emails (Mailhog)

El entorno de desarrollo incluye **Mailhog** para capturar y visualizar todos los correos electrónicos enviados por el sistema sin necesidad de un servidor SMTP real.

### Acceso
- **URL**: [http://localhost:8025](http://localhost:8025)
- **Interfaz**: Web UI simple donde aparecen todos los correos salientes.

### Correos a Verificar
1. **Confirmación de Reserva**: Se envía inmediatamente al crear una reserva (`POST /api/user/reservations`). Debe incluir el código QR y el horario asignado.
2. **Recordatorio de Recojo**: Se envía automáticamente 10 minutos antes de la hora de inicio del slot asignado.
3. **Notificación de Expiración**: Se envía 15 minutos después del inicio del slot si la reserva no fue marcada como entregada.
4. **Cancelación de Reserva**: Se envía inmediatamente al cancelar una reserva (`DELETE /api/user/reservations/{id}/cancel`).

### Troubleshooting
Si los correos no aparecen en Mailhog:
1. Verifica que el contenedor `kavas_lunch_mailhog` esté corriendo: `docker compose ps`
2. Revisa la configuración en `backend-php/.env`:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=mailhog
   MAIL_PORT=1025
   ```
3. Asegúrate de que los jobs de cola estén corriendo (para recordatorios y expiraciones):
   - El contenedor `scheduler` debe estar activo.
   - Revisa los logs: `docker compose logs scheduler`

