# Backup & Disaster Recovery

Guide for administrators to backup and restore K-Systems data.

---

## Overview

Regular backups are critical for:
- **Data loss prevention** - Hardware failure, accidents, corruption
- **Disaster recovery** - Restore after catastrophic events
- **Compliance** - Meet data retention requirements
- **Testing** - Create test environments from production data

---

## What to Backup

### Essential Data

**1. Database**
- All application data (users, employees, reports, documents, etc.)
- Most critical component
- **Frequency:** Daily minimum

**2. Uploaded Files**
- Document attachments
- Employee photos
- Report attachments
- Website media
- **Location:** `/uploads` or configured upload directory
- **Frequency:** Daily

**3. Configuration Files**
- `.env` files (backend, frontend, socket-server)
- `docker-compose.yml`
- Web server configurations
- **Frequency:** After any changes

**4. Custom Code/Modifications** (if applicable)
- Custom modules
- Theme customizations
- Integration code
- **Frequency:** After any changes

### Optional Backups

- Application logs (for debugging)
- Analytics data
- Temporary files (usually not needed)

---

## Backup Methods

### Method 1: Docker-Based Backup

**For Docker Compose deployments:**

#### Database Backup

```bash
#!/bin/bash
# backup-database.sh

# Configuration
BACKUP_DIR="/backups/ksystems/database"
DATE=$(date +%Y%m%d_%H%M%S)
DB_CONTAINER="ksystems_db_1"  # Adjust to your container name
DB_NAME="ksystems"
DB_USER="root"
DB_PASS="your_root_password"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
docker exec $DB_CONTAINER mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Keep only last 30 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +30 -delete

echo "Database backup completed: db_$DATE.sql.gz"
```

**Make executable and run:**
```bash
chmod +x backup-database.sh
./backup-database.sh
```

#### Uploads Backup

```bash
#!/bin/bash
# backup-uploads.sh

BACKUP_DIR="/backups/ksystems/uploads"
DATE=$(date +%Y%m%d)
UPLOADS_DIR="/var/lib/docker/volumes/ksystems_uploads_data/_data"  # Adjust path

mkdir -p $BACKUP_DIR

# Incremental backup (faster)
tar -czf "$BACKUP_DIR/uploads_$DATE.tar.gz" -C $(dirname $UPLOADS_DIR) $(basename $UPLOADS_DIR)

# Keep only last 7 daily backups
find $BACKUP_DIR -name "uploads_*.tar.gz" -mtime +7 -delete

echo "Uploads backup completed: uploads_$DATE.tar.gz"
```

### Method 2: Manual Backup

**For manual installations:**

#### Database Backup

```bash
# Direct MySQL backup
mysqldump -u root -p ksystems | gzip > backup_$(date +%Y%m%d).sql.gz

# With specific options
mysqldump -u root -p \
  --single-transaction \
  --quick \
  --lock-tables=false \
  ksystems | gzip > backup_$(date +%Y%m%d).sql.gz
```

#### Files Backup

```bash
# Backup uploads directory
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz /path/to/uploads

# Backup configuration files
tar -czf config_backup_$(date +%Y%m%d).tar.gz \
  /path/to/K-Systems/backend/.env \
  /path/to/K-Systems/frontend/.env \
  /path/to/K-Systems/socket-server/.env \
  /path/to/K-Systems/docker-compose.yml
```

---

## Automated Backup Schedule

### Creating Backup Script

**Complete backup script:**

```bash
#!/bin/bash
# /usr/local/bin/ksystems-backup.sh

# Configuration
BACKUP_ROOT="/backups/ksystems"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Database backup
DB_DIR="$BACKUP_ROOT/database"
mkdir -p $DB_DIR
docker exec ksystems_db_1 mysqldump -u root -p"$DB_ROOT_PASSWORD" ksystems | \
  gzip > "$DB_DIR/db_$DATE.sql.gz"

# Uploads backup
UPLOADS_DIR="$BACKUP_ROOT/uploads"
mkdir -p $UPLOADS_DIR
tar -czf "$UPLOADS_DIR/uploads_$DATE.tar.gz" /var/lib/docker/volumes/ksystems_uploads_data/_data

# Configuration backup
CONFIG_DIR="$BACKUP_ROOT/config"
mkdir -p $CONFIG_DIR
cp /path/to/K-Systems/backend/.env "$CONFIG_DIR/backend.env_$DATE"
cp /path/to/K-Systems/frontend/.env "$CONFIG_DIR/frontend.env_$DATE"
cp /path/to/K-Systems/socket-server/.env "$CONFIG_DIR/socket.env_$DATE"

# Cleanup old backups
find $DB_DIR -name "db_*.sql.gz" -mtime +$RETENTION_DAYS -delete
find $UPLOADS_DIR -name "uploads_*.tar.gz" -mtime +$RETENTION_DAYS -delete
find $CONFIG_DIR -name "*.env_*" -mtime +$RETENTION_DAYS -delete

# Log completion
echo "[$(date)] Backup completed successfully" >> $BACKUP_ROOT/backup.log
```

### Scheduling with Cron

```bash
# Edit crontab
sudo crontab -e

# Add backup schedule (daily at 2 AM)
0 2 * * * /usr/local/bin/ksystems-backup.sh

# Or multiple times per day
0 2,14 * * * /usr/local/bin/ksystems-backup.sh  # 2 AM and 2 PM
```

**Verify cron job:**
```bash
sudo crontab -l
```

---

## Backup Best Practices

### 3-2-1 Rule

- **3** copies of data (original + 2 backups)
- **2** different media types (local disk + cloud/tape)
- **1** copy off-site (cloud storage, remote server)

### Backup Frequency

**Recommended schedule:**
- **Database:** Daily (or more for high-activity systems)
- **Uploads:** Daily
- **Configuration:** After changes
- **Full system:** Weekly

**High-availability systems:**
- Database: Every 6-12 hours
- Or use continuous replication

### Retention Policy

**Recommended:**
- **Daily backups:** Keep 30 days
- **Weekly backups:** Keep 12 weeks (3 months)
- **Monthly backups:** Keep 12 months (1 year)
- **Yearly backups:** Keep indefinitely (or per compliance requirements)

### Testing Backups

**Critical:** Backups are useless if they can't be restored!

**Test quarterly:**
1. Restore backup to test environment
2. Verify all data is present
3. Check data integrity
4. Test application functionality
5. Document any issues

---

## Restoration Procedures

### Restoring Database

**Docker environment:**

```bash
# 1. Stop application containers
docker-compose stop backend frontend socket-server

# 2. Decompress backup
gunzip backup_20250127.sql.gz

# 3. Restore to database
docker exec -i ksystems_db_1 mysql -u root -p"$DB_ROOT_PASSWORD" ksystems < backup_20250127.sql

# 4. Restart containers
docker-compose start backend frontend socket-server

# 5. Verify application works
```

**Manual environment:**

```bash
# Stop application
sudo systemctl stop ksystems-backend
sudo systemctl stop ksystems-socket

# Restore database
gunzip < backup_20250127.sql.gz | mysql -u root -p ksystems

# Restart application
sudo systemctl start ksystems-backend
sudo systemctl start ksystems-socket
```

### Restoring Uploads

```bash
# Stop services
docker-compose stop

# Extract uploads backup
tar -xzf uploads_20250127.tar.gz -C /var/lib/docker/volumes/ksystems_uploads_data/

# Fix permissions if needed
docker-compose exec backend chown -R www-data:www-data /var/www/html/uploads

# Restart
docker-compose start
```

### Full System Restore

**Complete disaster recovery:**

1. **Reinstall K-Systems** (see Installation Guide)
2. **Restore configuration files**
   ```bash
   cp config_backup/backend.env backend/.env
   cp config_backup/frontend.env frontend/.env
   cp config_backup/socket.env socket-server/.env
   ```
3. **Restore database** (see above)
4. **Restore uploads** (see above)
5. **Start services**
   ```bash
   docker-compose up -d
   ```
6. **Verify functionality**
7. **Check logs for errors**

---

## Off-Site Backup

### Cloud Storage Options

**AWS S3:**
```bash
# Install AWS CLI
apt-get install awscli

# Configure credentials
aws configure

# Sync backups to S3
aws s3 sync /backups/ksystems s3://your-bucket/ksystems-backups/
```

**Rsync to Remote Server:**
```bash
#!/bin/bash
# Sync backups to remote server

rsync -avz --delete \
  /backups/ksystems/ \
  user@backup-server:/backups/ksystems/
```

**Automated cloud backup:**
Add to backup script:
```bash
# After local backup completes
aws s3 sync $BACKUP_ROOT s3://your-bucket/ksystems-backups/ --delete
```

---

## Backup Monitoring

### Verifying Backup Success

**Check backup script output:**
```bash
# View backup log
tail -f /backups/ksystems/backup.log

# Check for errors in cron
sudo grep CRON /var/log/syslog
```

**Verify backup files exist:**
```bash
# List recent backups
ls -lh /backups/ksystems/database/ | tail -10

# Check file sizes (should be consistent)
du -sh /backups/ksystems/*
```

### Alerting on Backup Failures

**Email notifications:**
```bash
#!/bin/bash
# Add to backup script

if [ $? -eq 0 ]; then
  echo "Backup succeeded" | mail -s "K-Systems Backup Success" admin@example.com
else
  echo "Backup FAILED! Check logs." | mail -s "K-Systems Backup FAILURE" admin@example.com
fi
```

**Monitoring tools:**
- **UptimeRobot** - Monitor backup script URL endpoint
- **Cron-Monitor** - Track cron job execution
- **Custom monitoring** - Check backup file age

---

## Disaster Recovery Plan

### Preparation

**Document everything:**
1. **Backup locations** - Where are backups stored?
2. **Access credentials** - How to access backups?
3. **Restoration procedures** - Step-by-step instructions
4. **Contact information** - Who to call?
5. **RTO/RPO** - Recovery Time/Point Objectives

**Test annually:**
- Full disaster recovery drill
- Time the restoration process
- Update procedures based on findings

### Recovery Scenarios

**Scenario 1: Database Corruption**
- Restore from last good backup
- May lose recent data (since last backup)
- Downtime: 30-60 minutes

**Scenario 2: Server Failure**
- Provision new server
- Restore from backups
- Update DNS if needed
- Downtime: 2-4 hours

**Scenario 3: Ransomware/Security Breach**
- Isolate affected systems
- Restore from clean backup (before breach)
- Investigate and patch security
- Downtime: 4-24 hours

---

## Compliance & Legal

### Data Retention Requirements

**Consider:**
- Local regulations (GDPR, etc.)
- Industry standards (fire service requirements)
- Organizational policies

**Typical requirements:**
- Personnel records: 7+ years
- Incident reports: 10+ years
- Financial records: 7 years

**Implement:**
- Longer retention for compliance data
- Automated deletion of old backups (with exceptions)

### Backup Security

**Protect backups:**
- Encrypt backup files
- Secure backup storage location
- Restrict access (limited personnel)
- Off-site storage for critical data

**Encryption example:**
```bash
# Encrypt backup with GPG
gpg --symmetric --cipher-algo AES256 backup.sql.gz

# Decrypt for restoration
gpg --decrypt backup.sql.gz.gpg > backup.sql.gz
```

---

## Troubleshooting

**Problem: Backup Script Fails**
- Check disk space (`df -h`)
- Verify database credentials
- Check permissions on backup directory
- Review script logs/output

**Problem: Restoration Fails**
- Verify backup file integrity (`gunzip -t backup.sql.gz`)
- Check database character set/collation match
- Ensure sufficient disk space
- Check MySQL error logs

**Problem: Slow Backups**
- Database too large? Consider incremental backups
- Network slow? Compress before transfer
- Use `--single-transaction` to avoid locks

---

## Related Guides

- **[Installation Guide](/guide/installation)** - System setup
- **[System Settings](/guide/admin/system-settings)** - Configuration

---

## Quick Reference

**Backup Command:**
```bash
# Database
docker exec ksystems_db_1 mysqldump -u root -p"$DB_PASS" ksystems | gzip > backup.sql.gz

# Uploads
tar -czf uploads_backup.tar.gz /path/to/uploads
```

**Restore Command:**
```bash
# Database
gunzip < backup.sql.gz | docker exec -i ksystems_db_1 mysql -u root -p"$DB_PASS" ksystems

# Uploads
tar -xzf uploads_backup.tar.gz -C /path/to/restore
```

**Schedule:**
- Daily: Database + Uploads
- Weekly: Full system
- After changes: Configuration

**Required:**
- Root/admin server access
- Database credentials
- Sufficient storage space

---

**Last Updated:** 2025-10-27
