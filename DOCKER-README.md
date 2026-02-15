# Docker Setup Guide

## Übersicht

Dieses Projekt besteht aus drei Hauptkomponenten:

1. **Frontend**: Vue.js/Vite-Anwendung, bereitgestellt mit Nginx
2. **Backend**: PHP/Apache-Anwendung
3. **Socket-Server**: Node.js/Express-Anwendung für Echtzeit-Kommunikation

Alle Komponenten sind dockerisiert und können mit Docker Compose als Gesamtsystem gestartet werden.

## Voraussetzungen

- Docker und Docker Compose installiert
- Git für den Zugriff auf das Repository
- Zugriff auf die GitHub Container Registry (für Produktion/Staging)

## Lokale Entwicklung

### Projekt klonen

```bash
git clone https://github.com/KyuubiDDragon/K-Systems.git
cd K-Systems
```

### Starten der Entwicklungsumgebung

Für lokale Entwicklung verwende die Dev-Konfiguration (ohne Traefik, mit direkten Port-Mappings):

```bash
# Baue und starte alle Dienste
docker-compose -f docker-compose.dev.yml up --build

# Oder im Hintergrund
docker-compose -f docker-compose.dev.yml up -d --build
```

Es werden vorkonfigurierte Entwicklungs-Werte verwendet, sodass keine `.env`-Datei nötig ist.

### Zugriff auf die Dienste

- Frontend: http://localhost:5173
- Backend API: http://localhost:8080
- Socket-Server: http://localhost:3001
- Datenbank: localhost:3307 (User: `ksystems`, Passwort: `ksystems_password`)

## Produktion/Staging

Die Produktions-Konfiguration (`docker-compose.yml`) verwendet [Traefik](https://traefik.io/) als Reverse Proxy mit automatischem HTTPS.

### Voraussetzungen für Produktion

- Traefik muss als Reverse Proxy laufen
- Das externe Docker-Netzwerk `web` muss existieren: `docker network create web`
- Eine `.env`-Datei im Projektroot mit allen Konfigurationswerten

### Manuelle Bereitstellung

```bash
# Projekt klonen
git clone https://github.com/KyuubiDDragon/K-Systems.git
cd K-Systems

# .env-Datei erstellen
cp backend/.env.template .env
# Bearbeiten: DOMAIN, DB_PASSWORD, JWT_SECRET_KEY, SOCKET_API_KEY etc. setzen

# Docker Compose starten
docker-compose up -d
```

### GitHub Workflows

Das Projekt enthält GitHub Actions Workflows für automatisches Deployment:

- Push auf `main` → Produktion
- Push auf `develop` → Staging

Konfiguriere die benötigten Secrets in deinen GitHub Repository Settings (Settings → Secrets).

## Docker Images

Die Docker Images werden in der GitHub Container Registry (GHCR) gespeichert und sind mit Tags versehen:

- `latest`: Produktion (main-Branch)
- `develop`: Staging (develop-Branch)
- `[commit-hash]`: Spezifische Version

### Manuelles Bauen der Images

```bash
# Frontend
docker build -t frontend:latest ./frontend

# Backend
docker build -t backend:latest ./backend

# Socket-Server
docker build -t socket-server:latest ./socket-server
```

## Troubleshooting

### Überprüfen des Status

```bash
# Status aller Container anzeigen
docker-compose ps

# Logs anzeigen
docker-compose logs

# Logs für einen bestimmten Dienst anzeigen
docker-compose logs frontend
docker-compose logs backend
docker-compose logs socket-server
```

### Neustarten der Dienste

```bash
# Alle Dienste neu starten
docker-compose restart

# Einen bestimmten Dienst neu starten
docker-compose restart frontend
```

### Container-Shells

```bash
# Shell im Frontend-Container
docker-compose exec frontend sh

# Shell im Backend-Container
docker-compose exec backend bash

# Shell im Socket-Server-Container
docker-compose exec socket-server sh
```

### Healthchecks

Alle Container sind mit Healthchecks konfiguriert:

- Frontend: http://localhost:5173/
- Backend: http://localhost:8080/api/health
- Socket-Server: http://localhost:3001/health

## Weitere Informationen

- Siehe `docker-compose.yml` für die Definition der Dienste
- Jede Komponente hat ein eigenes `Dockerfile` im jeweiligen Verzeichnis 