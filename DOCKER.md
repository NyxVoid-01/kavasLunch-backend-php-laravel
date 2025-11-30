# 🐳 Docker - Guía de Inicialización

## Requisitos Previos

- [Docker Desktop](https://www.docker.com/products/docker-desktop) instalado y ejecutándose
- Git instalado
- PowerShell o Terminal

---

## Construir y Levantar Contenedores

```powershell
docker compose up -d --build
```

### Probar la API

Abre tu navegador en:

- **API Backend**: http://localhost:8000
- **MailHog (ver correos)**: http://localhost:8025

Prueba un endpoint:

```powershell
curl http://localhost:8000/api/health
```

---

## Comandos Útiles

### Gestión de Contenedores

```powershell
# Levantar contenedores
docker compose up -d

# Detener contenedores
docker compose down

# Ver logs de todos los servicios
docker compose logs -f

# Ver logs de un servicio específico
docker compose logs backend -f

# Reiniciar un servicio
docker compose restart backend

# Reconstruir solo el backend
docker compose up -d --build backend
```

### Ejecutar Comandos Laravel

```powershell
# Ejecutar artisan
docker compose exec backend php artisan <comando>

# Ejemplos:
docker compose exec backend php artisan migrate
docker compose exec backend php artisan db:seed
docker compose exec backend php artisan cache:clear
docker compose exec backend php artisan route:list
docker compose exec backend php artisan tinker

# Ejecutar Composer
docker compose exec backend composer install
docker compose exec backend composer update
docker compose exec backend composer require <paquete>
```

### Base de Datos

```powershell
# Conectarse a PostgreSQL
docker compose exec database psql -U laravel -d kavas_lunch

# Ejecutar migraciones frescas
docker compose exec backend php artisan migrate:fresh

# Ejecutar seeders
docker compose exec backend php artisan db:seed

# Refresh completo (⚠️ borra todos los datos)
docker compose exec backend php artisan migrate:fresh --seed
```

### Debugging

```powershell
# Acceder al contenedor backend
docker compose exec backend bash

# Ver logs en tiempo real
docker compose logs -f

# Ver logs de error de Nginx
docker compose logs nginx --tail 100

# Verificar conectividad de Redis
docker compose exec backend php artisan redis:ping
```

---

## Flujo de trabajo

```powershell
# Levantar contenedores
docker compose up -d

# Ver que todo esté funcionando
docker compose ps
```

## Limpiar Todo (Reset Completo)

Si necesitas empezar desde cero:

```powershell
# Detener y eliminar contenedores + volúmenes
docker compose down -v

# Eliminar imágenes
docker compose down --rmi all

# Reconstruir desde cero
docker compose up -d --build
```