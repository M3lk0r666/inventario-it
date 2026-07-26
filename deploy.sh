#!/usr/bin/env bash
#
# deploy.sh — Despliegue/actualización idempotente de Inventario TI
# Ejecutar como usuario 'deploy' desde la raíz del proyecto:
#   ./deploy.sh
#
# Variables opcionales:
#   BRANCH=main ./deploy.sh        # rama a desplegar (default: main)
#   SKIP_GIT=1 ./deploy.sh         # no hacer git pull (despliegue local)
#
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BRANCH="${BRANCH:-main}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

cd "$APP_DIR"
echo "==> Desplegando en: $APP_DIR (rama: $BRANCH)"

# 1) Código
if [[ "${SKIP_GIT:-0}" != "1" ]]; then
    echo "==> Actualizando código (git)"
    git fetch --all --prune
    git checkout "$BRANCH"
    git pull --ff-only origin "$BRANCH"
fi

# 2) Modo mantenimiento (si ya estaba instalado)
if [[ -f artisan ]]; then
    "$PHP_BIN" artisan down --render="errors::503" --retry=60 || true
fi

# 3) Dependencias PHP (sin dev, optimizado)
echo "==> composer install"
"$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# 4) .env y APP_KEY
if [[ ! -f .env ]]; then
    echo "==> Creando .env desde .env.example (edítalo antes de continuar)"
    cp .env.example .env
fi
if ! grep -q "^APP_KEY=base64" .env; then
    "$PHP_BIN" artisan key:generate --force
fi

# 5) Assets front-end (Node)
if command -v npm >/dev/null 2>&1; then
    echo "==> npm ci && build"
    npm ci
    npm run build
else
    echo "!! npm no encontrado: omitiendo build de assets (compila manualmente)"
fi

# 6) Migraciones
echo "==> Migraciones"
"$PHP_BIN" artisan migrate --force

# 7) Enlace de storage
"$PHP_BIN" artisan storage:link || true

# 8) Cachés de producción
echo "==> Cacheando config/rutas/vistas/eventos"
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan event:cache

# 9) Permisos de carpetas escribibles
echo "==> Permisos storage/ y bootstrap/cache"
chmod -R ug+rwX storage bootstrap/cache

# 10) Reiniciar cola/worker si aplica (opcional)
"$PHP_BIN" artisan queue:restart || true

# 11) Salir de mantenimiento
"$PHP_BIN" artisan up

echo "==> Despliegue completado."
echo "   Recuerda (solo primera vez): definir contraseñas con 'php artisan user:password <correo>'"
