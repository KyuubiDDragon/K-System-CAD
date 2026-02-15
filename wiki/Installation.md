# Installation

## Quick Start (Docker - Recommended)

The fastest way to get K-Systems running locally:

```bash
# 1. Clone the repository
git clone https://github.com/KyuubiDDragon/K-Systems.git
cd K-Systems

# 2. Start all services (no configuration needed!)
docker-compose -f docker-compose.dev.yml up -d

# 3. Access
# Frontend:      http://localhost:5173
# Backend API:   http://localhost:8080
# Socket Server: http://localhost:3001
```

### Default Login

| Field | Value |
|-------|-------|
| **Username** | `admin` |
| **Password** | `password` |
| **Email** | `admin@example.com` |

> **Important:** Change the default password immediately after first login!

---

## Manual Setup (without Docker)

### Requirements

- **PHP 8.1+** with extensions: pdo_mysql, mbstring, json, gd, zip
- **MariaDB 10.5+** or MySQL 8.0+
- **Node.js 18+**
- **Composer** (PHP dependency manager)

### Step 1: Database

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE ksystems CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema and seed data
mysql -u root -p ksystems < database/database.sql
```

### Step 2: Backend (PHP)

```bash
cd backend

# Install PHP dependencies
composer install

# Create environment config
cp .env.template .env

# Edit .env with your database credentials and settings
# Required: DB_DATABASE, DB_USERNAME, DB_PASSWORD, JWT_SECRET_KEY, SOCKET_API_KEY
```

### Step 3: Frontend (Vue 3)

```bash
cd frontend
npm install

# Development server (port 5173)
npm run dev

# OR build for production
npm run build
```

### Step 4: Socket Server (Node.js)

```bash
cd socket-server
npm install

# Development server (port 3001)
npm run dev
```

---

## System Requirements

### Minimum
- **CPU:** 2 cores
- **RAM:** 2 GB
- **Disk:** 10 GB
- **OS:** Ubuntu 20.04+, Debian 11+, or Docker-compatible

### Recommended (Production)
- **CPU:** 4+ cores
- **RAM:** 8 GB
- **Disk:** 50 GB SSD
- **OS:** Ubuntu 22.04 LTS

---

## Next Steps

- [[Docker Setup]] - Detailed Docker guide
- [[Configuration]] - All environment variables
- [[Production Deployment]] - Deploy to production
