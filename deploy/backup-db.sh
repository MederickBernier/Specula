#!/bin/sh
# Nightly Postgres dump for Specula.
#
# The data is the asset worth protecting, not the OS install, so this dumps the
# database rather than snapshotting the droplet.
#
# The work modules are encrypted in the database, so this dump carries their
# content as ciphertext. It is readable only with the instance's APP_KEY, which
# means the key must be stored somewhere other than beside these files: a copy
# of both together is a plain text backup.
#
# Install (as root on the droplet):
#   ln -s /opt/specula/deploy/backup-db.sh /etc/cron.daily/specula-backup
# or an explicit crontab entry:
#   15 3 * * * /opt/specula/deploy/backup-db.sh >> /var/log/specula-backup.log 2>&1
#
# Restore a dump into a running stack:
#   gunzip -c /var/backups/specula/specula-2026-09-07.sql.gz \
#     | docker compose -f /opt/specula/compose.prod.yaml exec -T pgsql \
#       psql -U specula -d specula
set -eu

PROJECT_DIR="${SPECULA_DIR:-/opt/specula}"
COMPOSE_FILE="${PROJECT_DIR}/compose.prod.yaml"
BACKUP_DIR="${SPECULA_BACKUP_DIR:-/var/backups/specula}"
KEEP_DAYS="${SPECULA_BACKUP_KEEP_DAYS:-14}"

DB_NAME="${DB_DATABASE:-specula}"
DB_USER="${DB_USERNAME:-specula}"

STAMP="$(date +%F)"
TARGET="${BACKUP_DIR}/specula-${STAMP}.sql.gz"

mkdir -p "$BACKUP_DIR"

# Write to a temp file first: a truncated dump that looks like a backup is
# worse than an obviously missing one.
TMP="$(mktemp "${BACKUP_DIR}/.specula-${STAMP}.XXXXXX")"
trap 'rm -f "$TMP"' EXIT

docker compose -f "$COMPOSE_FILE" exec -T pgsql \
    pg_dump --clean --if-exists -U "$DB_USER" "$DB_NAME" \
    | gzip -9 > "$TMP"

# gzip of an empty dump is still ~20 bytes, so check for something real.
if [ "$(wc -c < "$TMP")" -lt 1024 ]; then
    echo "specula-backup: dump looks empty, refusing to keep it" >&2
    exit 1
fi

mv "$TMP" "$TARGET"
trap - EXIT
chmod 600 "$TARGET"

find "$BACKUP_DIR" -name 'specula-*.sql.gz' -mtime "+${KEEP_DAYS}" -delete

echo "specula-backup: wrote ${TARGET} ($(du -h "$TARGET" | cut -f1))"

# TODO: push off-box. Everything above still lives on the droplet, so a droplet
# that dies takes the backups with it. Add the S3-compatible push here once the
# bucket exists, e.g.:
#   aws s3 cp "$TARGET" "s3://${SPECULA_BACKUP_BUCKET}/" \
#     --endpoint-url "https://${SPECULA_SPACES_REGION}.digitaloceanspaces.com"
