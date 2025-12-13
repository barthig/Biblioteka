#!/bin/sh
set -eu

DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-5432}
DB_USER=${DB_USER:-biblioteka}
DB_NAME=${DB_NAME:-biblioteka_dev}
MAX_RETRIES=${MAX_RETRIES:-30}
SLEEP=${SLEEP:-2}

if [ -z "${PGPASSWORD:-}" ]; then
  echo "PGPASSWORD is not set. Set PGPASSWORD to the Postgres superuser password and retry."
  exit 1
fi

echo "Waiting for Postgres at ${DB_HOST}:${DB_PORT}..."
i=0
until PGPASSWORD="$PGPASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c '\q' >/dev/null 2>&1; do
  i=$((i+1))
  if [ "$i" -ge "$MAX_RETRIES" ]; then
    echo "Timed out waiting for Postgres after $((MAX_RETRIES * SLEEP)) seconds"
    exit 1
  fi
  sleep "$SLEEP"
done

echo "Connected to Postgres. Checking schema..."
exists=$(PGPASSWORD="$PGPASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -tAc "SELECT to_regclass('public.app_user');" 2>/dev/null || true)
exists=$(echo "$exists" | tr -d '[:space:]')

if [ -z "$exists" ] || [ "$exists" = "NULL" ]; then
  echo "Schema not found — initializing DB from /init-db.sql"
  PGPASSWORD="$PGPASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -f /init-db.sql
  echo "DB initialization complete."
else
  echo "DB already initialized (app_user exists). Skipping init."
fi

exit 0
