# Despliegue y flujo de ramas

Guía para continuar el proyecto (backend + frontend) y desplegarlo en Railway.

## 1. Ramas (en los dos repos)

| Rama | Para qué |
| --- | --- |
| `Developer/<nombre>` | Trabajo de cada uno. Sale de `develop`. |
| `develop` | Integración: solo entra lo revisado y probado en local. |
| `produccion` | Lo que despliega Railway. Solo recibe merges desde `develop`. |

Crear `produccion` **una sola vez** en cada repo:

```bash
git checkout develop && git pull
git checkout -b produccion && git push -u origin produccion
```

Flujo diario:

```bash
git checkout develop && git pull
git checkout -b Developer/<nombre>        # o actualiza la tuya: git merge develop
# ...cambios y commits...
git push -u origin Developer/<nombre>     # Pull Request hacia develop
```

Publicar (cuando el equipo aprueba lo que está en `develop`):

```bash
git checkout produccion && git pull
git merge --no-ff develop && git push     # Railway despliega solo
```

Reglas: nunca hacer commit directo en `produccion`. Si hay un arreglo urgente, hazlo en una rama desde `produccion`, intégrala en `produccion` y luego también en `develop`.

## 2. Railway

Crear un proyecto con 4 servicios: **PostgreSQL**, **Redis**, **backend** (este repo) y **frontend** (repo del frontend). Configura backend y frontend para desplegar desde la rama `produccion`.

### Ajustes al backend antes del primer despliegue (hacerlos en `develop`)

Hoy la imagen está hecha para desarrollo local:

1. **Dockerfile:** no copia el código, porque en local se monta con un volumen. Agrega `COPY . .` y `composer install --no-dev --optimize-autoloader`.
2. **Un solo contenedor:** en local nginx y php-fpm van separados. En Railway, nginx debe ir en la misma imagen y escuchar en `$PORT`.
3. **Entrypoint:** en producción solo debe ejecutar `php artisan migrate --force`. Hay que saltar `db:seed` y `DailyMenuSeeder`, que crean datos de prueba; se puede condicionar con `APP_ENV`.
4. **QR:** se guardan en `storage/app/public/qr-codes`. Agrega un Volume en `/var/www/html/storage`, o se borrarán en cada despliegue.

### Variables del backend

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=            # ver aviso abajo
APP_URL=https://<backend>.up.railway.app
FRONTEND_URL=https://<frontend>.up.railway.app   # también define CORS
APP_TIMEZONE=America/Lima
APP_LOCALE=es
JWT_SECRET=         # php artisan jwt:secret --show
DB_CONNECTION=pgsql
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_DATABASE=${{Postgres.PGDATABASE}}
DB_USERNAME=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}
REDIS_HOST=${{Redis.REDISHOST}}
REDIS_PORT=${{Redis.REDISPORT}}
REDIS_PASSWORD=${{Redis.REDISPASSWORD}}
QUEUE_CONNECTION=redis
PAYMENT_YAPE_NUMBER=<número para Yape/Plin>
```

> ⚠️ **APP_KEY:** el nombre y el correo de los usuarios se guardan cifrados con esta clave. Si la base empieza vacía, genera una nueva con `php artisan key:generate --show`. Si se migra una base existente, hay que usar **la misma** clave. Guárdala en un lugar seguro: si se pierde o cambia, nadie puede iniciar sesión.

### Correo con Gmail

1. En la cuenta de Google, activa la Verificación en 2 pasos y crea una **Contraseña de aplicación** (Seguridad → Contraseñas de aplicaciones).
2. Configura estas variables:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=<tu-correo>@gmail.com
MAIL_PASSWORD=<contraseña de aplicación de 16 caracteres>
MAIL_FROM_ADDRESS=<tu-correo>@gmail.com
MAIL_FROM_NAME="KAVAS-LUNCH"
```

> ⚠️ Railway bloquea el SMTP saliente (puertos 465 y 587, Gmail incluido) en los planes **Free, Trial y Hobby**. Hay dos salidas:
> - Plan **Pro**: Gmail funciona tal cual.
> - Proveedor con **API por HTTPS** (por ejemplo Brevo, que permite usar tu Gmail como remitente verificado). Requiere agregar su transporte de correo en Laravel.

### Primer arranque

Los seeders no corren en producción, así que no hay administrador. Créalo una vez desde la consola del servicio backend:

```bash
php artisan tinker --execute="App\Models\User::create(['full_name'=>'Administrador','email'=>'admin@upch.pe','password'=>'<Clave123!>','role'=>'admin']);"
```

Luego, desde el panel, crea los platos y los menús diarios.

## 3. Frontend

Ver `DESPLIEGUE.md` del repo del frontend: rama `produccion`, variable de build `VITE_API_URL` y puerto 80.

## 4. Comprobar después de desplegar

- Registrarse: debe llegar el correo de bienvenida.
- Reservar con Yape/Plin: aparece la cuenta regresiva de 3 minutos y llega el correo "Pago confirmado".
- Entrar como admin y entregar esa reserva desde Despacho.
