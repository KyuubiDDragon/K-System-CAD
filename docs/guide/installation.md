# Installation Guide

This guide is for system administrators who need to install and configure K-Systems.

::: info For End Users
If you're an end user looking to learn how to use K-Systems, see the **[Getting Started Guide](/guide/getting-started)** instead.
:::

---

## Overview

K-Systems can be deployed using:
- **Docker Compose** (Recommended for production)
- **Manual Installation** (Custom server setups)
- **Managed Hosting** (Plesk/cPanel environments)

---

## System Requirements

### Minimum Server Requirements

| Component | Requirement |
|-----------|-------------|
| **CPU** | 2+ cores |
| **RAM** | 4GB minimum, 8GB recommended |
| **Storage** | 20GB minimum (grows with usage) |
| **OS** | Linux (Ubuntu 20.04+ recommended) |
| **Network** | Static IP or domain name |
| **HTTPS** | SSL/TLS certificate (Let's Encrypt supported) |

### Software Requirements

**For Docker Installation:**
- Docker Engine 20.10+
- Docker Compose 2.0+

**For Manual Installation:**
- **PHP** 8.1+ with extensions:
  - pdo, pdo_mysql, mbstring, json, exif, pcntl, bcmath, gd, zip
- **MySQL/MariaDB** 10.11+
- **Node.js** 20+
- **Composer** (PHP dependency manager)
- **NPM** (Node package manager)
- **Web Server**: Nginx or Apache

### Client Requirements (End Users)

| Requirement | Details |
|-------------|---------|
| **Browser** | Chrome 90+, Firefox 88+, Safari 14+, Edge 90+ |
| **JavaScript** | Must be enabled |
| **Cookies** | Must be enabled |
| **WebSocket** | Required for real-time features |
| **Screen Resolution** | 1024x768 minimum (1920x1080 recommended) |

---

## Installation Methods

## Method 1: Docker Installation (Recommended)

### Prerequisites

Install Docker and Docker Compose on your server:

**Ubuntu/Debian:**
```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify installation
docker --version
docker-compose --version
```

### Step 1: Clone Repository

```bash
# Clone the repository
git clone https://github.com/yourusername/K-Systems.git
cd K-Systems
```

### Step 2: Configure Environment Variables

Create environment files from templates:

```bash
# Copy environment templates
cp backend/.env.template backend/.env
cp frontend/.env.template frontend/.env
cp socket-server/.env.template socket-server/.env
```

#### Backend Configuration (`backend/.env`)

```env
# Database Configuration
DB_HOST=db
DB_PORT=3306
DB_DATABASE=ksystems
DB_USERNAME=ksystems
DB_PASSWORD=YOUR_SECURE_DATABASE_PASSWORD_HERE

# JWT Authentication
JWT_SECRET_KEY=YOUR_LONG_RANDOM_SECRET_KEY_MINIMUM_32_CHARACTERS
JWT_EXPIRATION_TIME=3600

# Application URLs
APP_URL_PROD=https://yourdomain.com/api
FRONTEND_URL_PROD=https://yourdomain.com

# Cookie Settings (IMPORTANT for production)
COOKIE_DOMAIN=yourdomain.com
COOKIE_SECURE=true
COOKIE_SAMESITE=Lax

# Socket Server
SOCKET_SERVER_URL=http://socket-server:3001
SOCKET_API_KEY=YOUR_RANDOM_SOCKET_API_KEY_HERE

# File Uploads
UPLOAD_BASE_DIR=/var/www/html/uploads
PUBLIC_UPLOAD_URL=https://yourdomain.com/uploads

# Environment
APP_ENV=production
```

::: warning Security Critical
- Generate a strong random `JWT_SECRET_KEY` (32+ characters)
- Use a strong `DB_PASSWORD`
- Create a unique `SOCKET_API_KEY`
- Never commit `.env` files to version control
:::

#### Frontend Configuration (`frontend/.env`)

```env
# API Configuration
VITE_API_URL=https://yourdomain.com/api
VITE_SOCKET_URL=wss://yourdomain.com/socket.io
VITE_REDIRECT_URL=https://yourdomain.com

# Application
VITE_APP_TITLE=K-Systems

# Development (set to false in production)
VITE_SHOW_DEV_TOOLS=false
```

#### Socket Server Configuration (`socket-server/.env`)

```env
# Server Configuration
PORT=3001
NODE_ENV=production

# Authentication (MUST match backend)
JWT_SECRET=YOUR_LONG_RANDOM_SECRET_KEY_MINIMUM_32_CHARACTERS
API_KEY=YOUR_RANDOM_SOCKET_API_KEY_HERE

# CORS
CORS_ORIGIN=https://yourdomain.com

# Backend API
PHP_API_URL=http://backend:80

# Logging
DEBUG=false
LOG_LEVEL=info
```

### Step 3: Configure Docker Compose

Edit `docker-compose.yml` if needed for your environment:

```yaml
# Example: Change exposed database port
services:
  db:
    ports:
      - "3307:3306"  # Change 3307 to your preferred port
```

### Step 4: Start Services

```bash
# Start all services in detached mode
docker-compose up -d

# Check status
docker-compose ps

# View logs
docker-compose logs -f
```

Expected output:
```
Name                    State    Ports
k-systems_backend_1     Up       0.0.0.0:8080->80/tcp
k-systems_db_1          Up       0.0.0.0:3307->3306/tcp
k-systems_frontend_1    Up       0.0.0.0:80->80/tcp
k-systems_socket_1      Up       0.0.0.0:3001->3001/tcp
```

### Step 5: Initialize Database

The database will automatically initialize on first startup using `/database/database.sql`.

Verify database is created:
```bash
docker-compose exec db mysql -u root -p -e "SHOW DATABASES;"
# Enter the DB_ROOT_PASSWORD when prompted
```

### Step 6: Create First Authority & Admin User

```bash
# Access MySQL
docker-compose exec db mysql -u root -p ksystems
```

```sql
-- Create first organization/authority
INSERT INTO kdd_authorities (name, active, created_at)
VALUES ('Your Organization Name', 1, NOW());

-- Create admin user (password: admin123)
INSERT INTO kdd_users (username, password, email, authority_id, created_at)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'admin@yourorganization.com', 1, NOW());

-- Create admin role
INSERT INTO kdd_roles (name, description, authority_id, power)
VALUES ('Super Admin', 'Full system access', 1, 999);

-- Get permission ID for ALL_PERMISSIONS
SET @perm_id = (SELECT id FROM kdd_permissions WHERE name = 'ALL_PERMISSIONS');

-- Assign ALL_PERMISSIONS to admin role
INSERT INTO kdd_role_permissions (role_id, permission_id)
VALUES (1, @perm_id);

-- Assign admin role to user
INSERT INTO kdd_user_roles (user_id, role_id, authority_id)
VALUES (1, 1, 1);

-- Enable all features for admin user
INSERT INTO kdd_user_features (user_id, feature, enabled)
VALUES
  (1, 'employee', 1),
  (1, 'document', 1),
  (1, 'reports', 1),
  (1, 'calendar', 1),
  (1, 'dispatch', 1),
  (1, 'training', 1),
  (1, 'mail', 1),
  (1, 'map', 1),
  (1, 'whiteboard', 1),
  (1, 'todo', 1),
  (1, 'blackboard', 1),
  (1, 'invoice', 1),
  (1, 'filemanager', 1),
  (1, 'person_file', 1),
  (1, 'vehicle_file', 1),
  (1, 'apartment_file', 1);
```

### Step 7: Configure Reverse Proxy (Production)

For production with SSL, configure Nginx or Traefik as reverse proxy.

**Example Nginx Configuration:**

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # Frontend
    location / {
        proxy_pass http://localhost:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    # Backend API
    location /api {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    # Socket.io
    location /socket.io {
        proxy_pass http://localhost:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }

    # Uploads
    location /uploads {
        alias /path/to/uploads;
    }
}
```

### Step 8: Verify Installation

**Check Service Health:**

```bash
# Backend health check
curl http://localhost:8080/health.php
# Expected: {"status":"ok"}

# Socket server health check
curl http://localhost:3001/health
# Expected: {"status":"healthy","uptime":...}

# Frontend
curl http://localhost/
# Should return HTML
```

**Test Login:**

1. Open browser: `https://yourdomain.com`
2. Select "Your Organization Name"
3. Login: `admin` / `admin123`
4. Change password immediately!

---

## Method 2: Manual Installation

### Step 1: Install Dependencies

**Ubuntu/Debian:**
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.1
sudo apt install php8.1 php8.1-cli php8.1-fpm php8.1-mysql php8.1-mbstring \
  php8.1-xml php8.1-zip php8.1-gd php8.1-curl php8.1-bcmath -y

# Install MySQL/MariaDB
sudo apt install mariadb-server -y
sudo mysql_secure_installation

# Install Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Nginx
sudo apt install nginx -y
```

### Step 2: Setup Database

```bash
# Login to MySQL
sudo mysql -u root -p
```

```sql
-- Create database
CREATE DATABASE ksystems CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'ksystems'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON ksystems.* TO 'ksystems'@'localhost';
FLUSH PRIVILEGES;
EXIT;

-- Import schema
mysql -u ksystems -p ksystems < /path/to/K-Systems/database/database.sql
```

### Step 3: Configure Backend

```bash
cd /var/www/K-Systems/backend

# Install dependencies
composer install --no-dev --optimize-autoloader

# Configure environment
cp .env.template .env
nano .env  # Edit with your settings

# Set permissions
sudo chown -R www-data:www-data /var/www/K-Systems/backend
sudo chmod -R 755 /var/www/K-Systems/backend
```

### Step 4: Configure Frontend

```bash
cd /var/www/K-Systems/frontend

# Install dependencies
npm install

# Configure environment
cp .env.template .env
nano .env  # Edit with your settings

# Build for production
npm run build

# Copy build to web root
sudo cp -r dist/* /var/www/html/
```

### Step 5: Configure Socket Server

```bash
cd /var/www/K-Systems/socket-server

# Install dependencies
npm install --production

# Configure environment
cp .env.template .env
nano .env  # Edit with your settings

# Install PM2 for process management
sudo npm install -g pm2

# Start socket server
pm2 start server.js --name k-systems-socket
pm2 save
pm2 startup  # Follow instructions to enable startup script
```

### Step 6: Configure Web Server

Create Nginx site configuration (see Docker Method Step 7 for example).

---

## Post-Installation Tasks

### 1. Setup Automated Backups

```bash
# Create backup script
sudo nano /usr/local/bin/ksystems-backup.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/backups/ksystems"
DATE=$(date +%Y%m%d_%H%M%S)

# Backup database
docker-compose exec -T db mysqldump -u root -p"$DB_ROOT_PASSWORD" ksystems | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Backup uploads
tar -czf "$BACKUP_DIR/uploads_$DATE.tar.gz" /path/to/uploads

# Keep only last 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/ksystems-backup.sh

# Add to crontab (daily at 2 AM)
sudo crontab -e
0 2 * * * /usr/local/bin/ksystems-backup.sh
```

### 2. Configure Firewall

```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw enable
```

### 3. Setup SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Obtain certificate
sudo certbot --nginx -d yourdomain.com

# Auto-renewal (certbot creates cron job automatically)
```

### 4. Configure Log Rotation

```bash
# Create logrotate config
sudo nano /etc/logrotate.d/ksystems
```

```
/var/log/ksystems/*.log {
    daily
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

### 5. Setup Monitoring (Optional)

Consider setting up:
- **Uptime monitoring** (UptimeRobot, Pingdom)
- **Log aggregation** (ELK Stack, Graylog)
- **Performance monitoring** (New Relic, Datadog)
- **Server monitoring** (Prometheus, Grafana)

---

## Updating K-Systems

### Docker Update

```bash
cd /path/to/K-Systems

# Pull latest code
git pull origin main

# Rebuild containers
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Run database migrations if any
docker-compose exec backend php migrations/migrate.php
```

### Manual Update

```bash
# Pull latest code
cd /var/www/K-Systems
git pull origin main

# Update backend
cd backend
composer install --no-dev
php migrations/migrate.php

# Update frontend
cd ../frontend
npm install
npm run build
sudo cp -r dist/* /var/www/html/

# Update socket server
cd ../socket-server
npm install --production
pm2 restart k-systems-socket
```

---

## Troubleshooting

### Frontend Shows Blank Page

**Check:**
```bash
# Browser console for JavaScript errors
# Check nginx error log
sudo tail -f /var/log/nginx/error.log

# Verify frontend build completed
ls -la /var/www/html/assets/
```

**Fix:**
```bash
cd frontend
rm -rf dist node_modules
npm install
npm run build
```

### Backend API Returns 404

**Check:**
```bash
# Test backend directly
curl http://localhost:8080/health.php

# Check PHP-FPM logs
sudo tail -f /var/log/php8.1-fpm.log
```

**Fix:**
- Verify .htaccess file exists in backend/
- Check Apache mod_rewrite is enabled: `sudo a2enmod rewrite`
- Restart Apache: `sudo systemctl restart apache2`

### Database Connection Failed

**Check:**
```bash
# Test database connection
docker-compose exec backend php -r "new PDO('mysql:host=db;dbname=ksystems', 'ksystems', 'password');"
```

**Fix:**
- Verify DB credentials in backend/.env
- Check database is running: `docker-compose ps db`
- Check database logs: `docker-compose logs db`

### Socket Server Not Connecting

**Check:**
```bash
# Test socket health endpoint
curl http://localhost:3001/health

# Check socket logs
docker-compose logs socket-server
```

**Fix:**
- Verify SOCKET_API_KEY matches in backend/.env and socket-server/.env
- Check JWT_SECRET matches in backend/.env and socket-server/.env
- Restart socket server: `docker-compose restart socket-server`

### Permission Denied Errors

```bash
# Fix file permissions (Docker)
docker-compose exec backend chown -R www-data:www-data /var/www/html
docker-compose exec backend chmod -R 755 /var/www/html

# Fix upload directory
sudo chown -R www-data:www-data /path/to/uploads
sudo chmod -R 755 /path/to/uploads
```

---

## Security Checklist

Before going to production:

- [ ] Change default admin password
- [ ] Use strong JWT_SECRET_KEY (32+ characters)
- [ ] Use strong database passwords
- [ ] Enable HTTPS/SSL certificates
- [ ] Set COOKIE_SECURE=true in production
- [ ] Configure firewall rules (only 80/443/22)
- [ ] Disable PHP error display (log only)
- [ ] Set up automated backups
- [ ] Configure log rotation
- [ ] Review CORS settings
- [ ] Disable directory listing
- [ ] Remove development tools from production
- [ ] Set proper file permissions (644 files, 755 directories)
- [ ] Enable database query logging for audit
- [ ] Set up intrusion detection (fail2ban)
- [ ] Configure rate limiting
- [ ] Review and test backup restoration

---

## Performance Optimization

### PHP Optimization

```ini
; /etc/php/8.1/fpm/php.ini
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 64M
post_max_size = 64M
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

### MySQL Optimization

```ini
; /etc/mysql/mariadb.conf.d/50-server.cnf
innodb_buffer_pool_size = 2G
innodb_log_file_size = 512M
max_connections = 200
query_cache_size = 64M
```

### Node.js Optimization

```bash
# Run socket server in cluster mode
pm2 start server.js -i max --name k-systems-socket
```

---

## Support

For additional help:
- [FAQ](/guide/faq)
- [Troubleshooting Guide](/guide/troubleshooting)
- [Developer Documentation](https://github.com/yourusername/K-Systems/blob/main/README_DEVELOPERS.md)

---

**Last Updated:** 2025-10-27
**Version:** 2.1.0
