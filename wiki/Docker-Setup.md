# Docker Setup

## Overview

K-Systems consists of four Docker services:

| Service | Technology | Internal Port |
|---------|-----------|---------------|
| **frontend** | Vue 3 + Nginx | 80 |
| **backend** | PHP 8.1 + Apache | 80 |
| **socket-server** | Node.js + Socket.io | 3001 |
| **db** | MariaDB 10.11 | 3306 |

## Docker Compose Files

| File | Purpose | Reverse Proxy |
|------|---------|---------------|
| `docker-compose.dev.yml` | Local development | None (direct port mapping) |
| `docker-compose.yml` | Production | Traefik with auto HTTPS |

---

## Local Development

```bash
# Start all services
docker-compose -f docker-compose.dev.yml up -d

# With rebuild (after code changes)
docker-compose -f docker-compose.dev.yml up -d --build
```

### Accessible Ports

| Service | URL |
|---------|-----|
| Frontend | http://localhost:5173 |
| Backend API | http://localhost:8080 |
| Socket Server | http://localhost:3001 |
| Database | localhost:3307 |

### Default Database Credentials (Dev)

| Setting | Value |
|---------|-------|
| Host | localhost |
| Port | 3307 |
| Database | ksystems |
| User | ksystems |
| Password | *(set in your `.env` file)* |
| Root Password | *(set in your `.env` file)* |

---

## Production Setup

The production `docker-compose.yml` uses [Traefik](https://traefik.io/) as reverse proxy with automatic Let's Encrypt HTTPS certificates.

### Prerequisites

1. Traefik must be running as reverse proxy
2. External Docker network `web` must exist
3. A `.env` file at the project root

```bash
# Create the external network (once)
docker network create web

# Create .env from template
cp backend/.env.template .env
# Edit .env with your production values

# Start services
docker-compose up -d
```

### Required .env Variables for Production

See [[Configuration]] for all variables.

---

## Building Images Manually

```bash
# Frontend
docker build -t k-systems-frontend:latest ./frontend

# Backend
docker build -t k-systems-backend:latest ./backend

# Socket Server
docker build -t k-systems-socket:latest ./socket-server
```

---

## Common Commands

```bash
# View status of all containers
docker-compose -f docker-compose.dev.yml ps

# View logs
docker-compose -f docker-compose.dev.yml logs

# View logs for a specific service
docker-compose -f docker-compose.dev.yml logs backend

# Restart a specific service
docker-compose -f docker-compose.dev.yml restart backend

# Stop all services
docker-compose -f docker-compose.dev.yml down

# Stop and remove volumes (resets database!)
docker-compose -f docker-compose.dev.yml down -v
```

## Shell Access

```bash
# Frontend container
docker-compose -f docker-compose.dev.yml exec frontend sh

# Backend container
docker-compose -f docker-compose.dev.yml exec backend bash

# Socket server container
docker-compose -f docker-compose.dev.yml exec socket-server sh

# Database
docker-compose -f docker-compose.dev.yml exec db mysql -u root -p'$DB_ROOT_PASSWORD' ksystems
```

## Health Checks

| Service | Endpoint |
|---------|----------|
| Backend | `GET /api/health` |
| Socket Server | `GET /health` |
| Database | `mysqladmin ping` |
