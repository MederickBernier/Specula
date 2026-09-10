#!/bin/sh
# Nightly Postgres dump for ClearSight.
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
#   ln -s /opt/clearsight/deploy/backup-db.sh /etc/cron.daily/clearsight-backup
# or an explicit crontab entry:
#   15 3 * * * /opt/clearsight/deploy/backup-db.sh >> /var/log/clearsight-backup.log 2>&1
#
# Restore a dump into a running stack:
#   gunzip -c /var/backups/clearsight/clearsight-2026-09-07.sql.gz \
#     | docker compose -f /opt/clearsight/compose.prod.yaml exec -T pgsql \
#       psql -U clearsight -d clearsight
set -eu

PROJECT_DIR="${CLEARSIGHT_DIR:-/opt/clearsight}"
COMPOSE_FILE="${PROJECT_DIR}/compose.prod.yaml"
BACKUP_DIR="${CLEARSIGHT_BACKUP_DIR:-/var/backups/clearsight}"
KEEP_DAYS="${CLEARSIGHT_BACKUP_KEEP_DAYS:-14}"

DB_NAME="${DB_DATABASE:-clearsight}"
DB_USER="${DB_USERNAME:-clearsight}"

STAMP="$(date +%F)"
TARGET="${BACKUP_DIR}/clearsight-${STAMP}.sql.gz"

mkdir -p "$BACKUP_DIR"

# Write to a temp file first: a truncated dump that looks like a backup is
# worse than an obviously missing one.
TMP="$(mktemp "${BACKUP_DIR}/.clearsight-${STAMP}.XXXXXX")"
trap 'rm -f "$TMP"' EXIT

docker compose -f "$COMPOSE_FILE" exec -T pgsql \
    pg_dump --clean --if-exists -U "$DB_USER" "$DB_NAME" \
    | gzip -9 > "$TMP"

# gzip of an empty dump is still ~20 bytes, so check for something real.
if [ "$(wc -c < "$TMP")" -lt 1024 ]; then
    echo "clearsight-backup: dump looks empty, refusing to keep it" >&2
    exit 1
fi

mv "$TMP" "$TARGET"
trap - EXIT
chmod 600 "$TARGET"

find "$BACKUP_DIR" -name 'clearsight-*.sql.gz' -mtime "+${KEEP_DAYS}" -delete

echo "clearsight-backup: wrote ${TARGET} ($(du -h "$TARGET" | cut -f1))"

# TODO: push off-box. Everything above still lives on the droplet, so a droplet
# that dies takes the backups with it. Add the S3-compatible push here once the
# bucket exists, e.g.:
#   aws s3 cp "$TARGET" "s3://${CLEARSIGHT_BACKUP_BUCKET}/" \
#     --endpoint-url "https://${CLEARSIGHT_SPACES_REGION}.digitaloceanspaces.com"
