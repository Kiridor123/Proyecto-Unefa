#!/bin/sh
# Respaldo de Salud UNEFA: base de datos (pg_dump comprimido) y adjuntos clínicos.
#
# Uso (desde la raíz del repositorio, con los contenedores en marcha):
#   sh scripts/backup.sh
# Variables opcionales:
#   BACKUP_DIR   carpeta de destino (por defecto ./backups)
#   BACKUP_KEEP  cantidad de respaldos que se conservan (por defecto 14)
#   COMPOSE      comando de Compose (por defecto "docker compose")
#
# Cada respaldo queda en backups/AAAAMMDD-HHMMSS/ con db.dump y adjuntos.tgz.
# Copie la carpeta fuera del servidor: contiene datos clínicos confidenciales.
set -eu
# En Git Bash (Windows) evita que las rutas del contenedor se conviertan en rutas de Windows.
export MSYS_NO_PATHCONV=1

cd "$(dirname "$0")/.."
BACKUP_DIR=${BACKUP_DIR:-backups}
BACKUP_KEEP=${BACKUP_KEEP:-14}
COMPOSE=${COMPOSE:-docker compose}

case "$BACKUP_KEEP" in
    ''|*[!0-9]*) echo "BACKUP_KEEP debe ser un número entero." >&2; exit 1 ;;
esac

STAMP=$(date +%Y%m%d-%H%M%S)
TARGET="$BACKUP_DIR/$STAMP"
umask 077
mkdir -p "$TARGET"

echo "Respaldando la base de datos en $TARGET/db.dump ..."
if ! $COMPOSE exec -T db sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc' > "$TARGET/db.dump"; then
    echo "Error: no se pudo respaldar la base de datos. ¿Está en marcha el contenedor db?" >&2
    rm -rf "$TARGET"
    exit 1
fi

echo "Respaldando los adjuntos en $TARGET/adjuntos.tgz ..."
if ! $COMPOSE exec -T web tar -C /var/www/storage -czf - attachments > "$TARGET/adjuntos.tgz"; then
    echo "Error: no se pudieron respaldar los adjuntos. ¿Está en marcha el contenedor web?" >&2
    rm -rf "$TARGET"
    exit 1
fi

if [ ! -s "$TARGET/db.dump" ]; then
    echo "Error: el volcado de la base de datos está vacío." >&2
    rm -rf "$TARGET"
    exit 1
fi

# Rotación: conserva solo los BACKUP_KEEP respaldos más recientes.
if [ "$BACKUP_KEEP" -gt 0 ]; then
    ls -1d "$BACKUP_DIR"/[0-9]*-[0-9]* 2>/dev/null | sort -r | tail -n +"$((BACKUP_KEEP + 1))" | while IFS= read -r old; do
        echo "Eliminando respaldo antiguo: $old"
        rm -rf "$old"
    done
fi

echo "Respaldo completado: $TARGET"
