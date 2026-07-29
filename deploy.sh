#!/usr/bin/env bash
#
# deploy.sh — Despliegue/actualización idempotente de Inventario TI
# Ejecutar como usuario 'deploy' (NO con sudo) desde la raíz del proyecto:
#   ./deploy.sh
# Si el archivo no tiene permiso de ejecución:  bash deploy.sh
#
# Variables opcionales:
#   BRANCH=main ./deploy.sh               # rama a desplegar (default: main)
#   SKIP_GIT=1 ./deploy.sh                # no tocar git (despliegue local / sin remoto)
#   SKIP_PERMS=1 ./deploy.sh              # no ajustar propietario/permisos
#   DEPLOY_OWNER=deploy:www-data ./...    # propietario a fijar (default: deploy:www-data)
#   PHP_BIN=/usr/bin/php8.2 ./...         # binarios si no están en PATH
#
# IMPORTANTE: no edites este archivo en el servidor. Cualquier ajuste hazlo con
# las variables de arriba. Editarlo rompe el 'git pull' (cambios locales).
#
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BRANCH="${BRANCH:-main}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
DEPLOY_OWNER="${DEPLOY_OWNER:-deploy:www-data}"

cd "$APP_DIR"
echo "==> Desplegando en: $APP_DIR (rama: $BRANCH)"

# 1) Código
if [[ "${SKIP_GIT:-0}" != "1" ]]; then
    # Ignora cambios de bits de permiso (evita que chmod marque archivos como modificados).
    git config core.fileMode false || true

    # Preflight: el árbol debe estar limpio (solo cambios de CONTENIDO cuentan).
    if [[ -n "$(git status --porcelain)" ]]; then
        echo "!! Hay cambios locales sin confirmar en el repositorio:"
        git status --short
        echo "   Descártalos con 'git checkout .' (o 'git stash'),"
        echo "   o ejecuta con SKIP_GIT=1 para desplegar sin tocar git."
        exit 1
    fi
    echo "==> Actualizando código (git)"
    git fetch --all --prune
    git checkout "$BRANCH"
    git pull --ff-only origin "$BRANCH"
fi

# 2) Modo mantenimiento (si ya estaba instalado) + recuperación automática
if [[ -f artisan ]]; then
    "$PHP_BIN" artisan down --render="errors::503" --retry=60 || true
    trap '"$PHP_BIN" artisan up || true' EXIT
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
    npm ci || npm install
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

# 9) Propietario y permisos de carpetas escribibles
# Algunos archivos los crea el servidor web (www-data) al subir logo/temporales,
# por eso se normaliza el propietario con sudo antes del chmod.
if [[ "${SKIP_PERMS:-0}" != "1" ]]; then
    echo "==> Propietario ($DEPLOY_OWNER) y permisos de storage/ y bootstrap/cache"
    sudo chown -R "$DEPLOY_OWNER" storage bootstrap/cache
    # Directorios 2775 (setgid: los archivos nuevos heredan el grupo www-data).
    # Archivos 664: así los .gitignore rastreados NO quedan ejecutables.
    sudo find storage bootstrap/cache -type d -exec chmod 2775 {} +
    sudo find storage bootstrap/cache -type f -exec chmod 664 {} +
else
    echo "==> Permisos omitidos (SKIP_PERMS=1)"
fi

# 10) Reiniciar cola/worker si aplica (opcional)
"$PHP_BIN" artisan queue:restart || true

# 11) Salir de mantenimiento
"$PHP_BIN" artisan up
trap - EXIT   # despliegue OK: cancela la recuperación del trap

echo "==> Despliegue completado."
echo "   Recuerda (solo primera vez): definir contraseñas con 'php artisan user:password <correo>'"
