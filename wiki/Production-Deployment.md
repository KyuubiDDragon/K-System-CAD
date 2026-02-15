# Production Deployment

This guide covers deploying K-Systems to a production environment using the Docker-based setup with Traefik reverse proxy.

## Prerequisites

- **Docker** 20.10+ and **Docker Compose** 2.0+
- A server with at least 2 CPU cores and 2 GB RAM (4 cores / 8 GB recommended)
- A registered domain name with DNS pointing to your server
- SSH access to the server

### Recommended Production Specs

| Resource | Minimum | Recommended |
|----------|---------|-------------|
| CPU | 2 cores | 4+ cores |
| RAM | 2 GB | 8 GB |
| Disk | 10 GB free | 50 GB SSD |
| OS | Ubuntu 20.04+ | Ubuntu 22.04 LTS |
| Bandwidth | 10 Mbps | 100 Mbps+ |

## Architecture Overview

The production stack consists of four Docker services orchestrated via `docker-compose.yml`:

```
                    Internet
                       |
                   [Traefik]  (reverse proxy, TLS termination)
                   /    |    \
            [frontend] [backend] [socket-server]
                          |
                        [db]
```

- **frontend** -- Nginx serving the built Vue 3 SPA
- **backend** -- Apache/PHP 8.1+ serving the REST API
- **socket-server** -- Node.js Socket.io server for real-time features
- **db** -- MariaDB 10.11 database

All services communicate over an internal Docker network. Traefik handles HTTPS termination and routing via labels on each service.

## Step 1: Server Preparation

### Install Docker

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com | sh

# Add your user to docker group
sudo usermod -aG docker $USER

# Install Docker Compose plugin
sudo apt install docker-compose-plugin -y

# Verify
docker --version
docker compose version
```

### Create the Traefik network

The production `docker-compose.yml` expects an external Docker network named `web`:

```bash
docker network create web
```

### Set up Traefik (if not already running)

If you do not already have Traefik running on the server, create a basic Traefik instance:

```bash
mkdir -p /opt/traefik
```

Create `/opt/traefik/docker-compose.yml`:

```yaml
version: '3.8'

services:
  traefik:
    image: traefik:v2.11
    restart: unless-stopped
    command:
      - --api.dashboard=true
      - --providers.docker=true
      - --providers.docker.exposedbydefault=false
      - --providers.docker.network=web
      - --entrypoints.web.address=:80
      - --entrypoints.websecure.address=:443
      - --certificatesresolvers.letsencrypt.acme.email=admin@yourdomain.com
      - --certificatesresolvers.letsencrypt.acme.storage=/letsencrypt/acme.json
      - --certificatesresolvers.letsencrypt.acme.httpchallenge.entrypoint=web
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - traefik_letsencrypt:/letsencrypt
    networks:
      - web
    labels:
      - traefik.enable=true
      - traefik.http.middlewares.https-redirect.redirectscheme.scheme=https
      - traefik.http.middlewares.https-redirect.redirectscheme.permanent=true

volumes:
  traefik_letsencrypt:

networks:
  web:
    external: true
```

```bash
cd /opt/traefik
docker compose up -d
```

## Step 2: Deploy K-Systems

### Clone the repository

```bash
cd /opt
git clone https://github.com/KyuubiDDragon/K-Systems.git
cd K-Systems
```

### Create the environment file

```bash
cp backend/.env.template .env
```

Edit `.env` with your production values:

```bash
# Domain (no protocol, no trailing slash)
DOMAIN=ksystems.yourdomain.com
STACK_NAME=ksystems

# Database
DB_ROOT_PASSWORD=<strong-random-password>
DB_DATABASE=ksystems
DB_USERNAME=ksystems
DB_PASSWORD=<strong-random-password>
DB_HOST_PORT=3307

# Security
JWT_SECRET_KEY=<long-random-string-at-least-64-chars>
SOCKET_API_KEY=<random-api-key>

# Environment
APP_ENV=production
NODE_ENV=production
```

Generate strong passwords:

```bash
# Generate a random password
openssl rand -base64 32

# Generate JWT secret
openssl rand -hex 64
```

### Build and start services

```bash
docker compose up -d --build
```

This will:
1. Build the frontend, backend, and socket-server Docker images
2. Pull the MariaDB image
3. Start all four services
4. Traefik will automatically detect the containers and provision TLS certificates via Let's Encrypt

### Verify deployment

```bash
# Check all containers are running
docker compose ps

# Check logs for errors
docker compose logs --tail=50

# Check individual services
docker compose logs backend --tail=20
docker compose logs frontend --tail=20
docker compose logs socket-server --tail=20
docker compose logs db --tail=20
```

## Step 3: Database Initialization

The database schema is automatically imported on first start via the Docker entrypoint:

```yaml
volumes:
  - ./database/database.sql:/docker-entrypoint-initdb.d/01-init.sql:ro
```

If the database volume already exists and you need to reimport:

```bash
# Stop services
docker compose down

# Remove the database volume (WARNING: destroys all data)
docker volume rm k-systems_db_data

# Restart (will recreate and import)
docker compose up -d
```

To manually import into a running database:

```bash
docker compose exec db mysql -u root -p${DB_ROOT_PASSWORD} ${DB_DATABASE} < database/database.sql
```

## Step 4: Verify All Services

### Frontend

Visit `https://ksystems.yourdomain.com` -- you should see the K-Systems login screen.

### Backend API

```bash
curl -s https://ksystems.yourdomain.com/api/ | head -20
```

You should receive an API response or status page.

### Socket Server

```bash
curl -s https://ksystems.yourdomain.com/socket.io/socket.io.js | head -5
```

You should see Socket.io JavaScript content.

### Database

```bash
docker compose exec db mysql -u root -p${DB_ROOT_PASSWORD} -e "USE ksystems; SHOW TABLES;" | wc -l
```

You should see 50+ tables listed.

## Routing Configuration

The production `docker-compose.yml` uses Traefik labels for routing:

| Path | Service | Priority |
|------|---------|----------|
| `/api/*` | backend | 10 |
| `/uploads/*` | backend | 10 |
| `/socket.io/*` | socket-server | 10 |
| `/*` (everything else) | frontend | 1 |

The frontend acts as a catch-all with lowest priority. API and socket requests are routed to their respective backends with higher priority.

## Environment Variables Reference

### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `DOMAIN` | Your production domain | `app.example.com` |
| `STACK_NAME` | Unique identifier for Traefik labels | `ksystems` |
| `DB_ROOT_PASSWORD` | MariaDB root password | `<random>` |
| `DB_PASSWORD` | Application database password | `<random>` |
| `JWT_SECRET_KEY` | JWT signing secret | `<random-hex-64>` |
| `SOCKET_API_KEY` | Socket server API key | `<random>` |

### Optional Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_DATABASE` | `ksystems` | Database name |
| `DB_USERNAME` | `ksystems` | Database user |
| `DB_HOST_PORT` | `3307` | Exposed DB port on host |
| `APP_ENV` | `production` | Application environment |
| `NODE_ENV` | `production` | Node.js environment |

## Updating K-Systems

### Standard Update

```bash
cd /opt/K-Systems

# Pull latest code
git pull origin main

# Rebuild and restart
docker compose up -d --build

# Check logs
docker compose logs --tail=50
```

### Database Migrations

If a release includes database changes, apply them after updating:

```bash
# Check for migration files in database/ directory
ls database/

# Apply migration manually
docker compose exec db mysql -u root -p${DB_ROOT_PASSWORD} ${DB_DATABASE} < database/migration_xxx.sql
```

### Rollback

```bash
# Revert to previous commit
git log --oneline -5
git checkout <previous-commit-hash>

# Rebuild
docker compose up -d --build
```

## HTTPS / TLS Configuration

Traefik handles TLS automatically with Let's Encrypt. The configuration is embedded in the `docker-compose.yml` labels:

- `tls=true` -- enables TLS on the router
- `tls.certresolver=letsencrypt` -- uses the Let's Encrypt resolver
- HTTP to HTTPS redirect is handled by the `https-redirect` middleware

Certificates are stored in the Traefik volume and auto-renewed before expiry.

### Custom Certificates

If you want to use your own certificates instead of Let's Encrypt, mount them in the Traefik container and configure file-based certificate providers.

## Scaling Considerations

### Single Server (up to ~500 users)

The default Docker Compose setup works well for up to approximately 500 concurrent users on a single 4-core/8GB server.

### Multi-Server (500+ users)

For larger deployments:
- Use a managed database (AWS RDS, Azure Database for MySQL)
- Run multiple backend containers behind a load balancer
- Use Redis for session storage
- Deploy the socket server on a dedicated instance
- Use a CDN for frontend static assets

### Cloud Deployment Options

| Platform | Frontend | Backend | Database | Socket |
|----------|----------|---------|----------|--------|
| **AWS** | S3 + CloudFront | EC2 / Beanstalk | RDS MySQL | EC2 |
| **Azure** | Static Web Apps | App Service (PHP) | Azure MySQL | VM |
| **DigitalOcean** | Droplet | Droplet | Managed MySQL | Droplet |

## Security Checklist

Before going live, ensure the following:

- [ ] Strong, unique passwords for database root and application user
- [ ] JWT_SECRET_KEY is a long random string (not the default)
- [ ] SOCKET_API_KEY is changed from the default
- [ ] HTTPS is working (check certificate in browser)
- [ ] HTTP redirects to HTTPS
- [ ] Database port (3307) is NOT exposed to the public internet
- [ ] Server firewall allows only ports 80, 443, and SSH (22)
- [ ] Default admin password changed after first login
- [ ] Automatic backups configured (see [[Backup Strategy]])
- [ ] Log rotation configured for Docker and application logs

## Monitoring

### Container Health

```bash
# Check container status
docker compose ps

# View resource usage
docker stats
```

### Log Locations

| Service | How to Access |
|---------|--------------|
| Frontend (Nginx) | `docker compose logs frontend` |
| Backend (Apache/PHP) | `docker compose logs backend` |
| Socket Server | `docker compose logs socket-server` |
| Database | `docker compose logs db` |

### Health Check Endpoints

- Frontend: `https://yourdomain.com/` (HTTP 200)
- Backend: `https://yourdomain.com/api/health` (HTTP 200)
- Socket: `https://yourdomain.com/socket.io/socket.io.js` (HTTP 200)
- Database: Checked internally via `mysqladmin ping`

---

## Related Pages

- [[Docker Setup]] - Development Docker configuration
- [[Configuration]] - Environment variables and settings
- [[Backup Strategy]] - Backup and recovery procedures
- [[Troubleshooting]] - Common issues and solutions
- [[Architecture Overview]] - System design and components
- [[Security]] - Security features and best practices
