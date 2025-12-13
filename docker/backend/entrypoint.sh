#!/bin/sh
set -e
# Ensure var directory exists and has correct permissions (handles mounted volumes)
mkdir -p /app/var /app/var/cache /app/var/log
chown -R www-data:www-data /app/var || true
chmod -R 775 /app/var || true

# If additional startup tasks are needed, they can be added here

# Exec the container CMD
exec "$@"
