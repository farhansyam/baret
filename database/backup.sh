#!/usr/bin/env bash
# Backup database BARET (jalankan via cron, mis. setiap hari pukul 02.00):
#   0 2 * * * /path/ke/baret/database/backup.sh >> /path/ke/baret/storage/logs/backup.log 2>&1
# Menyimpan 14 backup terakhir di storage/backups/. Uji pemulihan secara berkala:
#   gunzip -c storage/backups/baret-YYYYmmdd-HHMM.sql.gz | mysql -u USER -p NAMA_DB_UJI
set -euo pipefail
DIR="$(cd "$(dirname "$0")/.." && pwd)"
ENV="$DIR/app/config/.env"
get() { grep -E "^$1=" "$ENV" | head -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'; }
DB_HOST=$(get DB_HOST); DB_PORT=$(get DB_PORT); DB_NAME=$(get DB_DATABASE); DB_USER=$(get DB_USERNAME); DB_PASS=$(get DB_PASSWORD)
OUT="$DIR/storage/backups/baret-$(date +%Y%m%d-%H%M).sql.gz"
MYSQL_PWD="$DB_PASS" mysqldump --single-transaction --routines --no-tablespaces \
  -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "$DB_USER" "$DB_NAME" | gzip > "$OUT"
chmod 600 "$OUT"
ls -1t "$DIR"/storage/backups/baret-*.sql.gz | tail -n +15 | xargs -r rm -f
echo "$(date '+%F %T') backup OK: $OUT"
