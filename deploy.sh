#!/usr/bin/env bash
#
# deploy.sh — Despliegue/actualización idempotente de Inventario TI
# Ejecutar como usuario 'deploy' desde la raíz del proyecto:
#   ./deploy.sh
#
# Variables opcionales:
#   BRANCH=main ./deploy.sh          # rama a desplegar (default: main)
#   SKIP_GIT=1 ./deploy.sh           # no tocar git (despliegue local / sin remoto)
#   DEPLOY_CHOWN=deploy:www-data ... # si se define, ajusta el propietario con sudo
#   PHP_BIN=/usr/bin/php8.2 ...       # binario de PHP/Composer si no están en PATH
#
# IMPORTANTE: no edites este archivo en el servidor. Si necesitas ajustes,
# usa las variables de arriba. Editarlo rompe el 'git pull' (cambios locales)
# y puede sobrescribir el script mientras corre.
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
    # Preflight: el árbol de trabajo debe estar limpio, o git checkout/pull fallará.
    if [[ -n "$(git status --porcelain)" ]]; then
        echo "!! Hay cambios locales sin confirmar en el repositorio:"
        git status --short
        echo "   Confirma o descarta esos cambios (git stash / git checkout .),"
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
    # Si el script aborta por un error, se vuelve a levantar la app.
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
    # 'npm ci' requiere package-lock.json en sincronía; si falla, cae a 'npm install'.
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

# 9) Permisos de carpetas escribibles
echo "==> Permisos storage/ y bootstrap/cache"
# Ownership: solo si se pide explícitamente (requiere sudo NOPASSWD para 'deploy').
if [[ -n "${DEPLOY_CHOWN:-}" ]]; then
    sudo chown -R "$DEPLOY_CHOWN" storage bootstrap/cache
fi
chmod -R ug+rwX storage bootstrap/cache

# 10) Reiniciar cola/worker si aplica (opcional)
"$PHP_BIN" artisan queue:restart || true

# 11) Salir de mantenimiento
"$PHP_BIN" artisan up
trap - EXIT   # despliegue OK: cancela la recuperación del trap

echo "==> Despliegue completado."
echo "   Recuerda (solo primera vez): definir contraseñas con 'php artisan user:password <correo>'"
