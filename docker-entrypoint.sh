#!/bin/bash
set -e

echo "🚀 Iniciando KAVAS-LUNCH Backend..."

# Esperar a que PostgreSQL esté listo
echo "⏳ Esperando a PostgreSQL..."
until pg_isready -h database -U ${DB_USERNAME:-laravel} -d ${DB_DATABASE:-kavas_lunch} > /dev/null 2>&1; do
    echo "PostgreSQL no está listo - esperando..."
    sleep 2
done
echo "✅ PostgreSQL está listo!"

# Instalar dependencias si no existen
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "📦 Instalando dependencias de Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
    echo "✅ Dependencias instaladas!"
else
    echo "✅ Dependencias ya instaladas!"
fi

# Generar APP_KEY si no existe
if [ -f ".env" ]; then
    if grep -q "APP_KEY=$" .env || grep -q "APP_KEY=\"\"" .env 2>/dev/null; then
        echo "🔑 Generando APP_KEY..."
        php artisan key:generate --force
        echo "✅ APP_KEY generada!"
    fi
else
    echo "⚠️  Archivo .env no encontrado. Por favor copia .env.example a .env"
fi

# Ejecutar migraciones automáticamente en primera ejecución
if [ ! -f "storage/app/migrations_done" ]; then
    echo "🗄️  Ejecutando migraciones..."
    php artisan migrate --force
    mkdir -p storage/app
    touch storage/app/migrations_done
    echo "✅ Migraciones completadas!"
else
    echo "✅ Migraciones ya ejecutadas!"
fi

# Limpiar caché
echo "🧹 Limpiando caché..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo "✅ Caché limpiado!"

echo "🎉 Backend listo! Iniciando PHP-FPM..."

# Ejecutar el comando original (php-fpm)
exec "$@"
