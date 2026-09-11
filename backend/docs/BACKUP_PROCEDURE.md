# TLL OS Mall Database Backup & Restore Procedure

## P0-B: Verified Backup and Restore Closure

### Backup Configuration

- **Script**: `/usr/local/bin/tllos-db-backup.sh`
- **Schedule**: Daily at 02:00 (cron)
- **Backup directory**: `/var/backups/tllos-mall/`
- **Retention**: 7 days
- **Database**: `tllos_mall` (MariaDB 10.11.18)
- **Log**: `/var/log/tllos-db-backup.log`

### Fail-Closed Mechanism

The backup script uses `set -euo pipefail` and will exit non-zero on:
- `mysqldump` failure (wrong credentials, connection error)
- Empty SQL file (0 bytes)
- SQL file smaller than 50KB (minimum threshold)
- `gzip` compression failure
- `gzip -t` integrity check failure
- Fewer than 10 tables in the dump

On failure, partial backup files are automatically cleaned up.

### Deprecated Script

- **Old script**: `/usr/local/bin/tllos-backup.sh.deprecated.p0`
- **Reason**: Incorrect credentials (DB_NAME="tllos", DB_PASS="tllos2026")
- **Status**: Removed from crontab, renamed to `.deprecated.p0`
- **Impact**: Produced empty 20-byte backup files from 2026-09-04 to 2026-09-11

### Restore Drill Results (2026-09-11)

| Item | Value |
|------|-------|
| Backup file | `tllos_mall_20260911_020001.sql.gz` |
| Backup size | 88,881 bytes |
| SHA256 | `236b4f6999c804f5ea2c751473e833e98d2499ed8a610279075d11cabe34146d` |
| gzip integrity | PASS |
| Restore database | `tllos_mall_restore_test` (isolated) |
| Restore exit code | 0 |
| Restore duration | 6 seconds |
| Tables restored | 201 |
| users (prod/restore) | 6 / 6 [MATCH] |
| products (prod/restore) | 84 / 84 [MATCH] |
| orders (prod/restore) | 29 / 29 [MATCH] |
| payments (prod/restore) | 21 / 20 [DIFF - 1 new record after backup] |

### Manual Restore Procedure

```bash
# 1. Verify backup integrity
gzip -t /var/backups/tllos-mall/tllos_mall_YYYYMMDD_HHMMSS.sql.gz

# 2. Create isolated restore database (as root)
mysql -u root -e "CREATE DATABASE tllos_mall_restore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Grant access
mysql -u root -e "GRANT ALL PRIVILEGES ON tllos_mall_restore.* TO 'tllos'@'localhost'; GRANT ALL PRIVILEGES ON tllos_mall_restore.* TO 'tllos'@'127.0.0.1'; FLUSH PRIVILEGES;"

# 4. Restore
zcat /var/backups/tllos-mall/tllos_mall_YYYYMMDD_HHMMSS.sql.gz | mysql -u tllos -p'TllosMall2026Secure' -h 127.0.0.1 tllos_mall_restore

# 5. Verify table count
mysql -u tllos -p'TllosMall2026Secure' -h 127.0.0.1 -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='tllos_mall_restore';"

# 6. After verification, drop the restore database
mysql -u root -e "DROP DATABASE tllos_mall_restore;"
```

### Known Gaps (P1/P2)

- **No offsite backup**: Backups are local only. Need S3/OSS or remote sync.
- **No backup monitoring**: No alert on backup failure (cron logs only).
- **No automated restore verification**: Restore drill is manual.
- **Backup script outside git**: `/usr/local/bin/tllos-db-backup.sh` is not version-controlled.
