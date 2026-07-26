#!/usr/bin/env bash
#
# backup.sh — Respaldo de base de datos + storage del inventario.
# Pensado para ejecutarse por cron. Conserva los últimos N días.
#
# Uso:
#   ./deploy/backup.sh
# Variables (o expórtalas en el crontab):
#   BACKUP_DIR=/var/backups/inventario-it
#   RETENTION_DAYS=14
#
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"

BACKUP_DIR="${BACKUP_DIR:-/var/backups/inventario-it}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"
STAMP="$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Leer credenciales de la BD desde .env
get_env() { grep -E "^$1=" .env | head -1 | cut -d= -f2- | tr -d '"'; }
DB_DATABASE="$(get_env DB_DATABASE)"
DB_USERNAME="$(get_env DB_USERNAME)"
DB_PASSWORD="$(get_env DB_PASSWORD)"
DB_HOST="$(get_env DB_HOST)"; DB_HOST="${DB_HOST:-127.0.0.1}"

echo "==> Respaldo BD ($DB_DATABASE)"
MYSQL_PWD="$DB_PASSWORD" mysqldump --single-transaction --quick --lock-tables=false \
    -h "$DB_HOST" -u "$DB_USERNAME" "$DB_DATABASE" | gzip > "$BACKUP_DIR/db_${STAMP}.sql.gz"

echo "==> Respaldo storage (archivos subidos)"
tar czf "$BACKUP_DIR/storage_${STAMP}.tar.gz" -C "$APP_DIR" storage/app/public

echo "==> Limpiando respaldos con más de ${RETENTION_DAYS} días"
find "$BACKUP_DIR" -name 'db_*.sql.gz' -mtime +"$RETENTION_DAYS" -delete
find "$BACKUP_DIR" -name 'storage_*.tar.gz' -mtime +"$RETENTION_DAYS" -delete

echo "==> Respaldo completado en $BACKUP_DIR"
