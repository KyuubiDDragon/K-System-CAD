# Erste Schritte

Dieser Leitfaden hilft Ihnen, K-Systems auf Ihrem lokalen Rechner zum Laufen zu bringen.

## Voraussetzungen

Bevor Sie beginnen, stellen Sie sicher, dass Sie Folgendes installiert haben:

- **Docker & Docker Compose** (empfohlen)
- ODER:
  - Node.js 18+ und NPM
  - PHP 8.0+ mit Erweiterungen (pdo, pdo_mysql, mbstring, openssl)
  - MySQL/MariaDB 10.11+
  - Composer

## Schnellstart mit Docker

Der schnellste Weg, um zu beginnen, ist die Verwendung von Docker:

### 1. Repository klonen

```bash
git clone https://github.com/yourusername/K-Systems.git
cd K-Systems
```

### 2. Umgebungsdatei erstellen

```bash
cp ENV_EXAMPLE.md .env
```

Bearbeiten Sie `.env` mit Ihrer Konfiguration:

```env
# Domain
DOMAIN=localhost
STACK_NAME=ksystems

# Datenbank
DB_HOST_PORT=3307
DB_DATABASE=ksystems
DB_USERNAME=ksystems
DB_PASSWORD=ihr_sicheres_passwort
DB_ROOT_PASSWORD=ihr_root_passwort

# JWT
JWT_SECRET_KEY=ihr_jwt_geheimschluessel_mindestens_32_zeichen

# Socket Server
SOCKET_API_KEY=ihr_socket_api_schluessel_hier

# Umgebung
APP_ENV=development
NODE_ENV=development
```

### 3. Dienste starten

```bash
docker-compose up -d
```

Dadurch werden folgende Dienste gestartet:
- Frontend (Nginx) auf Port 80
- Backend (PHP/Apache) auf Port 8080
- Socket Server (Node.js) auf Port 3001
- Datenbank (MariaDB) auf Port 3307

### 4. Datenbank importieren

```bash
docker exec -i ksystems_db_1 mysql -u root -p"ihr_root_passwort" ksystems < database/database.sql
```

### 5. Auf die Anwendung zugreifen

Öffnen Sie Ihren Browser und navigieren Sie zu:
- **Frontend**: http://localhost
- **Backend API**: http://localhost:8080
- **Socket Server**: http://localhost:3001

## Manuelle Installation (Ohne Docker)

Wenn Sie die Dienste lieber manuell ausführen möchten:

### 1. Datenbank einrichten

```bash
# Datenbank erstellen
mysql -u root -p -e "CREATE DATABASE ksystems CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Benutzer erstellen
mysql -u root -p -e "CREATE USER 'ksystems'@'localhost' IDENTIFIED BY 'ihr_passwort';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON ksystems.* TO 'ksystems'@'localhost';"

# Schema importieren
mysql -u root -p ksystems < database/database.sql
```

### 2. Backend einrichten

```bash
cd backend

# Abhängigkeiten installieren
composer install

# Umgebung konfigurieren
cp .env.example .env
# Bearbeiten Sie .env mit Ihren Datenbankzugangsdaten und JWT-Schlüssel
```

### 3. Frontend einrichten

```bash
cd frontend

# Abhängigkeiten installieren
npm install

# Umgebung konfigurieren
cp .env.example .env
# Bearbeiten Sie .env mit API- und Socket-URLs
```

### 4. Socket Server einrichten

```bash
cd socket-server

# Abhängigkeiten installieren
npm install

# Umgebung konfigurieren
cp .env.example .env
# Bearbeiten Sie .env mit API-Schlüssel und Ports
```

### 5. Dienste starten

```bash
# Terminal 1 - Frontend
cd frontend
npm run dev
# Läuft auf http://localhost:5173

# Terminal 2 - Backend
cd backend
php -S localhost:8080
# Oder konfigurieren Sie Apache/Nginx

# Terminal 3 - Socket Server
cd socket-server
npm run dev
# Läuft auf http://localhost:3001
```

## Erstkonfiguration

### Erste Behörde erstellen

```sql
INSERT INTO kdd_authorities (name, active, created_at)
VALUES ('Standard Organisation', 1, NOW());
```

### Ersten Zugang anlegen

Von Hand ist hier nichts mehr zu tun. Der Grundstock der Datenbank liefert
bewusst kein Konto mit; beim ersten Aufruf oeffnet die Anwendung stattdessen
die Ersteinrichtung unter `/setup` und fragt nach Benutzername, E-Mail und
einem Passwort von mindestens zwoelf Zeichen.

Der dort angelegte Zugang bekommt die Rolle `System Administrator` und damit
saemtliche Rechte. Sobald ein Benutzer existiert, verweigert die Einrichtung
den Dienst.

> Frueher stand an dieser Stelle ein SQL-Block, der ein Konto `admin` mit einem
> fest eingetragenen Hash anlegte. Der Hash gehoert zu einem allgemein
> bekannten Standardpasswort - jede Anlage waere damit offen gewesen.

## Erste Anmeldung

1. Navigieren Sie zu http://localhost (oder Ihrer Domain)
2. Wählen Sie "Standard Organisation" aus der Behörden-Dropdown-Liste
3. Geben Sie die Anmeldedaten ein:
   - **Benutzername**: admin
   - **Passwort**: admin123
4. Klicken Sie auf "Anmelden"

::: warning Standardpasswort ändern
Ändern Sie nach der ersten Anmeldung sofort das Standardpasswort im Benutzerprofil!
:::

## Installation überprüfen

### Dienste überprüfen

```bash
# Docker-Container überprüfen
docker-compose ps

# Sollte alle Dienste als "Up" anzeigen
```

### Logs überprüfen

```bash
# Alle Logs anzeigen
docker-compose logs -f

# Spezifischen Dienst anzeigen
docker-compose logs -f frontend
docker-compose logs -f backend
docker-compose logs -f socket-server
```

### API testen

```bash
curl http://localhost:8080/health
# Sollte zurückgeben: {"status":"ok"}
```

### Socket Server testen

```bash
curl http://localhost:3001/health
# Sollte zurückgeben: {"status":"healthy","uptime":...}
```

## Fehlerbehebung

### Frontend lädt nicht

```bash
# Prüfen Sie, ob Container läuft
docker-compose ps frontend

# Logs prüfen
docker-compose logs frontend

# Bei Bedarf neu erstellen
docker-compose up -d --build frontend
```

### Backend-Fehler

```bash
# PHP-Fehler prüfen
docker-compose logs backend

# Datenbankverbindung überprüfen
docker exec -it ksystems_backend_1 php -r "new PDO('mysql:host=db;dbname=ksystems', 'ksystems', 'password');"
```

### Datenbankverbindung fehlgeschlagen

```bash
# Prüfen Sie, ob Datenbank läuft
docker-compose ps db

# Prüfen Sie, ob Datenbank existiert
docker exec -it ksystems_db_1 mysql -u root -p -e "SHOW DATABASES;"

# Verbindung testen
docker exec -it ksystems_db_1 mysql -u ksystems -p ksystems
```

### Socket-Verbindung fehlgeschlagen

```bash
# Prüfen Sie, ob Socket-Server läuft
docker-compose ps socket-server

# Logs prüfen
docker-compose logs socket-server

# Endpunkt testen
curl http://localhost:3001/health
```

### Port bereits in Verwendung

```bash
# Prozess finden, der Port verwendet
lsof -i :80
lsof -i :8080
lsof -i :3001

# Prozess beenden oder Port in docker-compose.yml ändern
```

## Nächste Schritte

Jetzt, da K-Systems läuft:

1. [📖 Desktop-Oberfläche kennenlernen](/de/guide/desktop-interface)
2. [👥 Mitarbeiter verwalten](/de/guide/mitarbeiterverwaltung)
3. [📝 Berichte erstellen](/de/guide/berichte)
4. [⚙️ Systemeinstellungen konfigurieren](/de/guide/admin/systemeinstellungen)
5. [🎨 Desktop anpassen](/de/guide/desktop-anpassung)

---

::: tip Entwicklungsmodus
Die Entwicklungsserver beinhalten Hot-Reload, sodass Änderungen am Code den Browser automatisch aktualisieren!
:::

::: info Benötigen Sie Hilfe?
Wenn Sie auf Probleme stoßen, überprüfen Sie den Fehlerbehebungsleitfaden oder schauen Sie sich die Logs an.
:::
