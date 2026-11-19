#!/usr/bin/env bash
# Ejecuta un script de database/sql como root de MariaDB (XAMPP),
# reemplazando {{CLAVE_MIGRADOR}} y {{CLAVE_APP}} con las claves del .env.
# Uso: bash database/sql/ejecutar.sh database/sql/01_crear_base_y_usuarios.sql
set -euo pipefail

RAIZ="$(cd "$(dirname "$0")/../.." && pwd)"
SCRIPT="$1"
MYSQL="${MYSQL_BIN:-/c/xampp/mysql/bin/mysql.exe}"

leer_env() {
  grep -E "^$1=" "$RAIZ/.env" | head -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'
}

CLAVE_APP="$(leer_env DB_PASSWORD)"
CLAVE_MIGRADOR="$(leer_env DB_MIGRATION_PASSWORD)"

if [ -z "$CLAVE_APP" ] || [ -z "$CLAVE_MIGRADOR" ]; then
  echo "Faltan DB_PASSWORD o DB_MIGRATION_PASSWORD en .env" >&2
  exit 1
fi

sed -e "s/{{CLAVE_APP}}/${CLAVE_APP}/g" -e "s/{{CLAVE_MIGRADOR}}/${CLAVE_MIGRADOR}/g" "$SCRIPT" \
  | "$MYSQL" -u root ${MYSQL_ROOT_PASSWORD:+-p"$MYSQL_ROOT_PASSWORD"}

echo "Ejecutado: $SCRIPT"
