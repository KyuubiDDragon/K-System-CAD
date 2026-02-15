# Backup Strategy

This guide covers backup and recovery procedures for K-Systems running in the Docker-based production environment.

## What to Back Up

K-Systems has three critical data stores that must be backed up:

| Component | Location | Priority | Frequency |
|-----------|----------|----------|-----------|
| **Database** | Docker volume `db_data` | Critical | Daily |
| **Uploaded Files** | Docker volume `uploads_data` | High | Daily |
| **Configuration** | `.env` file in project root | Medium | Weekly / on change |

### Database (Most Critical)

The MariaDB database contains all application data: users, documents, reports, messages, calendar entries, invoices, whiteboard data, and more. Loss of the database means loss of all operational data.

### Uploaded Files

The `uploads_data` volume contains all user-uploaded files: invoice attachments, file manager content, document images, and profile pictures. These files are stored in authority-specific subdirectories.

### Configuration

The `.env` file and `docker-compose.yml` contain all environment-specific settings. While they can be recreated, having a backup saves time during disaster recovery.

## Automated Backup Script

### Create the backup script

Create `/usr/local/bin/k-systems-backup.sh`:

```bash
#!/bin/bash
# K-Systems Automated Backup Script
# Run daily via cron

set -euo pipefail

# Configuration
BACKUP_DIR="/backups/k-systems"
K_SYSTEMS_DIR="/opt/K-Systems"
DB_CONTAINER="k-systems-db-1"          # Adjust to your container name
DB_NAME="${DB_DATABASE:-ksystems}"
DB_USER="root"
DB_PASS="${DB_ROOT_PASSWORD:-rootpassword}"  # Or read from .env
RETENTION_DAYS=30
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p "${BACKUP_DIR}"

echo "[$(date)] Starting K-Systems backup..."

# 1. Database backup
echo "[$(date)] Backing up database..."
docker exec "${DB_CONTAINER}" mysqldump \
    -u "${DB_USER}" \
    -p"${DB_PASS}" \
    --single-transaction \
    --routines \
    --triggers \
    --databases "${DB_NAME}" \
    | gzip > "${BACKUP_DIR}/db-${DATE}.sql.gz"

if [ $? -eq 0 ]; then
    echo "[$(date)] Database backup complete: db-${DATE}.sql.gz"
else
    echo "[$(date)] ERROR: Database backup failed!" >&2
    exit 1
fi

# 2. Uploads backup
echo "[$(date)] Backing up uploads..."
docker run --rm \
    -v k-systems_uploads_data:/data:ro \
    -v "${BACKUP_DIR}":/backup \
    alpine tar czf "/backup/uploads-${DATE}.tar.gz" -C /data .

if [ $? -eq 0 ]; then
    echo "[$(date)] Uploads backup complete: uploads-${DATE}.tar.gz"
else
    echo "[$(date)] ERROR: Uploads backup failed!" >&2
    exit 1
fi

# 3. Configuration backup
echo "[$(date)] Backing up configuration..."
cp "${K_SYSTEMS_DIR}/.env" "${BACKUP_DIR}/env-${DATE}"
cp "${K_SYSTEMS_DIR}/docker-compose.yml" "${BACKUP_DIR}/docker-compose-${DATE}.yml"

echo "[$(date)] Configuration backup complete."

# 4. Clean up old backups
echo "[$(date)] Cleaning up backups older than ${RETENTION_DAYS} days..."
find "${BACKUP_DIR}" -type f -mtime +${RETENTION_DAYS} -delete

echo "[$(date)] Backup complete."

# 5. Print backup sizes
echo ""
echo "Backup sizes:"
ls -lh "${BACKUP_DIR}/db-${DATE}.sql.gz"
ls -lh "${BACKUP_DIR}/uploads-${DATE}.tar.gz"
echo ""
echo "Total backup directory size: $(du -sh ${BACKUP_DIR} | cut -f1)"
```

### Make executable and schedule

```bash
chmod +x /usr/local/bin/k-systems-backup.sh

# Test the script
/usr/local/bin/k-systems-backup.sh

# Add to crontab - run daily at 2:00 AM
crontab -e
```

Add the following line:

```
0 2 * * * /usr/local/bin/k-systems-backup.sh >> /var/log/k-systems-backup.log 2>&1
```

## Database-Only Quick Backup

For quick manual backups before updates or risky operations:

```bash
# Quick database dump
docker compose exec db mysqldump -u root -p"${DB_ROOT_PASSWORD}" \
    --single-transaction ksystems | gzip > ~/ksystems-db-$(date +%Y%m%d).sql.gz
```

## Backup Verification

Regularly verify that backups are valid and restorable.

### Verify Database Backup

```bash
# Check file is not empty
ls -lh /backups/k-systems/db-*.sql.gz

# Test decompression
gunzip -t /backups/k-systems/db-YYYYMMDD_HHMMSS.sql.gz

# Inspect contents (without full extraction)
zcat /backups/k-systems/db-YYYYMMDD_HHMMSS.sql.gz | head -50
```

### Verify Uploads Backup

```bash
# Check file is not empty
ls -lh /backups/k-systems/uploads-*.tar.gz

# List contents
tar tzf /backups/k-systems/uploads-YYYYMMDD_HHMMSS.tar.gz | head -20
```

### Full Restore Test (recommended monthly)

Spin up a test environment and restore from backup to verify the entire process works end-to-end. See the Recovery Procedures section below.

## Recovery Procedures

### Full System Recovery

If the server is completely lost and you need to set up from scratch:

1. **Provision a new server** with Docker installed (see [[Production Deployment]])

2. **Clone the repository:**
   ```bash
   cd /opt
   git clone https://github.com/KyuubiDDragon/K-Systems.git
   cd K-Systems
   ```

3. **Restore configuration:**
   ```bash
   cp /backups/k-systems/env-YYYYMMDD_HHMMSS .env
   ```

4. **Start services (without database init):**
   ```bash
   # Start only the database first
   docker compose up -d db

   # Wait for it to be healthy
   docker compose ps
   ```

5. **Restore the database:**
   ```bash
   # Create the database
   docker compose exec db mysql -u root -p"${DB_ROOT_PASSWORD}" \
       -e "CREATE DATABASE IF NOT EXISTS ksystems CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

   # Import the backup
   gunzip < /backups/k-systems/db-YYYYMMDD_HHMMSS.sql.gz | \
       docker compose exec -T db mysql -u root -p"${DB_ROOT_PASSWORD}"
   ```

6. **Restore uploaded files:**
   ```bash
   # Copy backup into the uploads volume
   docker run --rm \
       -v k-systems_uploads_data:/data \
       -v /backups/k-systems:/backup:ro \
       alpine sh -c "cd /data && tar xzf /backup/uploads-YYYYMMDD_HHMMSS.tar.gz"
   ```

7. **Start remaining services:**
   ```bash
   docker compose up -d
   ```

8. **Verify:**
   - Access the frontend at `https://yourdomain.com`
   - Log in with an existing account
   - Check that uploaded files are accessible
   - Verify real-time features (socket connection)

### Database-Only Recovery

If only the database is corrupted or lost:

```bash
# Stop the application (keep DB running)
docker compose stop backend socket-server frontend

# Drop and recreate
docker compose exec db mysql -u root -p"${DB_ROOT_PASSWORD}" \
    -e "DROP DATABASE IF EXISTS ksystems; CREATE DATABASE ksystems CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Restore from backup
gunzip < /backups/k-systems/db-YYYYMMDD_HHMMSS.sql.gz | \
    docker compose exec -T db mysql -u root -p"${DB_ROOT_PASSWORD}"

# Restart application
docker compose up -d
```

### Uploads-Only Recovery

If uploaded files are lost:

```bash
# Restore the uploads volume
docker run --rm \
    -v k-systems_uploads_data:/data \
    -v /backups/k-systems:/backup:ro \
    alpine sh -c "rm -rf /data/* && cd /data && tar xzf /backup/uploads-YYYYMMDD_HHMMSS.tar.gz"

# Restart backend to pick up file changes
docker compose restart backend
```

## Off-Site Backup

Backups stored on the same server are not sufficient for disaster recovery. Implement at least one off-site strategy:

### Option 1: Rsync to Remote Server

```bash
# Add to backup script or cron
rsync -avz --delete /backups/k-systems/ user@backup-server:/backups/k-systems/
```

### Option 2: AWS S3

```bash
# Install AWS CLI
sudo apt install awscli -y
aws configure

# Sync backups to S3
aws s3 sync /backups/k-systems/ s3://your-bucket/k-systems-backups/ \
    --storage-class STANDARD_IA

# Add lifecycle rule to transition to Glacier after 90 days
```

### Option 3: Rclone (supports many cloud providers)

```bash
# Install rclone
curl https://rclone.org/install.sh | sudo bash
rclone config  # Set up your remote

# Sync backups
rclone sync /backups/k-systems/ remote:k-systems-backups/
```

## Backup Retention Policy

| Backup Type | Retention | Storage |
|-------------|-----------|---------|
| Daily database dumps | 30 days | Local + off-site |
| Daily uploads archives | 30 days | Local + off-site |
| Weekly configuration snapshots | 90 days | Local + off-site |
| Monthly full backup (verified) | 12 months | Off-site only |

## Monitoring Backups

### Check backup freshness

Add a monitoring check to ensure backups are running:

```bash
#!/bin/bash
# /usr/local/bin/check-backup-freshness.sh
BACKUP_DIR="/backups/k-systems"
MAX_AGE_HOURS=26  # Allow 2 hours of slack on the daily schedule

LATEST_DB=$(find "${BACKUP_DIR}" -name "db-*.sql.gz" -mmin -$((MAX_AGE_HOURS * 60)) | head -1)

if [ -z "${LATEST_DB}" ]; then
    echo "CRITICAL: No database backup found in the last ${MAX_AGE_HOURS} hours!"
    exit 2
fi

echo "OK: Latest backup: ${LATEST_DB}"
exit 0
```

### Backup log review

Check `/var/log/k-systems-backup.log` regularly for errors:

```bash
# Check for recent errors
grep -i "error\|fail" /var/log/k-systems-backup.log | tail -20

# Check last backup ran successfully
tail -20 /var/log/k-systems-backup.log
```

## Docker Volume Management

### List volumes

```bash
docker volume ls | grep k-systems
```

### Inspect volume details

```bash
docker volume inspect k-systems_db_data
docker volume inspect k-systems_uploads_data
```

### Volume locations on host

Docker volumes are stored at `/var/lib/docker/volumes/` by default. You can back up volumes at the filesystem level as well, but the recommended approach is using `docker exec` for database dumps and `docker run` with mounted volumes for file archives.

---

## Related Pages

- [[Production Deployment]] - Production setup guide
- [[Docker Setup]] - Docker configuration details
- [[Troubleshooting]] - Common issues and solutions
- [[Configuration]] - Environment variables reference
