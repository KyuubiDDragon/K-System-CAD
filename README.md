<div align="center">

# K-Systems

### Multi-Tenant Enterprise Management Platform with Desktop Interface

[![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)](LICENSE)
[![Ko-fi](https://img.shields.io/badge/Ko--fi-Support%20Me-FF5E5B?style=for-the-badge&logo=ko-fi&logoColor=white)](https://ko-fi.com/kyuubiddragon)
[![Discord](https://img.shields.io/badge/Discord-Support%20Server-5865F2?style=for-the-badge&logo=discord&logoColor=white)](https://dsc.gg/kyuubisoft)

<img src="https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Docker"/>
<img src="https://img.shields.io/badge/Vue.js_3-Frontend-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white" alt="Vue.js"/>
<img src="https://img.shields.io/badge/PHP_8.1-Backend-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP"/>
<img src="https://img.shields.io/badge/Node.js-Socket_Server-339933?style=for-the-badge&logo=node.js&logoColor=white" alt="Node.js"/>
<img src="https://img.shields.io/badge/MariaDB-Database-003545?style=for-the-badge&logo=mariadb&logoColor=white" alt="MariaDB"/>

---

**[Deutsch](#deutsch)** | **[English](#english)** | **[Quick Start](#-quick-start)** | **[Documentation](docs/)**

</div>

---

## English

K-Systems is a comprehensive, multi-tenant enterprise management platform featuring a unique Windows-style desktop interface. Manage employees, documents, reports, invoices, dispatch operations, and much more - all within a modern, real-time web application.

### Features

| Category | Features |
|----------|----------|
| **Desktop Interface** | Windows-style desktop with draggable windows, taskbar, start menu, desktop icons, widgets, and mini-games |
| **Employee Management** | Profiles, departments, ranks, licenses, training, certifications, vacation management |
| **Communication** | Internal messaging, group chats, real-time notifications via Socket.io |
| **Calendar & Events** | Event management, recurring events, groups, RSVP, vacation calendar |
| **Documents & Reports** | Document areas, report templates, custom fields, status workflows, PDF generation |
| **Invoices & Billing** | Invoice management, line items, payment tracking, PDF export |
| **File Management** | File manager with folders, upload/download, per-authority storage |
| **Person & Vehicle Files** | Contact management, vehicle records, maintenance tracking, GPS data |
| **Dispatch & Operations** | Dispatch management, unit tracking, GPS integration, status updates |
| **Crew Management** | Shift planning, personnel assignment, qualifications, overtime tracking |
| **Map Integration** | Leaflet-based maps with custom markers, categories, and GPS tracking |
| **Collaboration** | Whiteboard (real-time collaborative), blackboard (announcements), todo lists |
| **Company Management** | Company profiles, types, contacts, documents, website builder |
| **Applications** | Applicant management, custom forms, evaluation workflow |
| **Multi-Tenant** | Complete data isolation per authority, custom branding, feature flags |
| **Permission System** | Bitmask-based roles & permissions, 200+ fine-grained permissions |
| **Multilingual** | Full German and English support (i18n) |
| **Mail System** | Internal mail with real-time sync across tabs |

### Tech Stack

| Component | Technology |
|-----------|------------|
| **Frontend** | Vue 3, TypeScript, Vuetify 3, Pinia, Vue Router |
| **Backend** | PHP 8.1+, JWT Authentication, REST API (50+ modules) |
| **Socket Server** | Node.js, Socket.io, Express |
| **Database** | MariaDB / MySQL with 100+ tables |
| **Infrastructure** | Docker, Traefik, Nginx/Apache |

---

## Deutsch

K-Systems ist eine umfassende, mandantenfähige Enterprise-Management-Plattform mit einer einzigartigen Windows-ähnlichen Desktop-Oberfläche. Verwalte Mitarbeiter, Dokumente, Berichte, Rechnungen, Einsätze und vieles mehr - alles in einer modernen Echtzeit-Webanwendung.

### Funktionen

| Kategorie | Funktionen |
|-----------|------------|
| **Desktop-Oberfläche** | Windows-ähnlicher Desktop mit verschiebbaren Fenstern, Taskleiste, Startmenü, Desktop-Icons, Widgets und Mini-Games |
| **Mitarbeiterverwaltung** | Profile, Abteilungen, Dienstgrade, Lizenzen, Schulungen, Zertifizierungen, Urlaubsverwaltung |
| **Kommunikation** | Internes Messaging, Gruppenchats, Echtzeit-Benachrichtigungen via Socket.io |
| **Kalender & Events** | Eventmanagement, wiederkehrende Ereignisse, Gruppen, RSVP, Urlaubskalender |
| **Dokumente & Berichte** | Dokumentenbereiche, Berichtsvorlagen, benutzerdefinierte Felder, Status-Workflows, PDF-Generierung |
| **Rechnungen** | Rechnungsverwaltung, Positionen, Zahlungsverfolgung, PDF-Export |
| **Dateiverwaltung** | Dateimanager mit Ordnern, Upload/Download, Authority-basierter Speicher |
| **Personen- & Fahrzeugakten** | Kontaktverwaltung, Fahrzeugdaten, Wartung, GPS-Daten |
| **Einsatzverwaltung** | Einsatzleitung, Einheitenverfolgung, GPS-Integration, Statusaktualisierungen |
| **Mannschaftsverwaltung** | Schichtplanung, Personalzuweisung, Qualifikationen, Überstunden |
| **Kartenintegration** | Leaflet-basierte Karten mit benutzerdefinierten Markern, Kategorien und GPS-Tracking |
| **Zusammenarbeit** | Whiteboard (Echtzeit), Schwarzes Brett, Aufgabenlisten |
| **Firmenverwaltung** | Firmenprofile, Typen, Kontakte, Dokumente, Website-Builder |
| **Bewerbungen** | Bewerbungsmanagement, individuelle Formulare, Bewertungs-Workflow |
| **Mandantenfähigkeit** | Vollständige Datenisolierung pro Authority, eigenes Branding, Feature-Flags |
| **Berechtigungssystem** | Bitmask-basierte Rollen & Berechtigungen, 200+ feingranulare Berechtigungen |
| **Mehrsprachig** | Vollständige Deutsch- und Englischunterstützung (i18n) |
| **Mail-System** | Internes Mailsystem mit Echtzeit-Synchronisation über mehrere Tabs |

---

## Quick Start

### Docker - Local Development

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
# Default login: admin / password
```

### Docker - Production (with Traefik)

The main `docker-compose.yml` is configured for production with [Traefik](https://traefik.io/) as reverse proxy with automatic HTTPS.

```bash
# 1. Create .env at project root with your settings
cp backend/.env.template .env
# Edit .env: set DOMAIN, DB_PASSWORD, JWT_SECRET_KEY, SOCKET_API_KEY, etc.

# 2. Make sure Traefik is running and the 'web' network exists
docker network create web  # if not already created

# 3. Start all services
docker-compose up -d
```

See [Deployment Guide](DEPLOY_README.md) for detailed production setup.

### Manual Setup (without Docker)

**Requirements:**
- PHP 8.1+ (with extensions: pdo_mysql, mbstring, json, gd, zip)
- MariaDB 10.5+ / MySQL 8.0+
- Node.js 18+
- Composer

```bash
# Backend
cd backend
composer install
cp .env.template .env  # Configure your .env

# Frontend
cd frontend
npm install
npm run dev    # Development (port 5173)
npm run build  # Production

# Socket Server
cd socket-server
npm install
npm run dev  # Development (port 3001)

# Database
mysql -u root -p -e "CREATE DATABASE ksystems CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p ksystems < database/database.sql
```

### Default Login

| Field | Value |
|-------|-------|
| **Username** | `admin` |
| **Password** | `password` |
| **Email** | `admin@example.com` |

> **Important:** Change the default password immediately after first login!

---

## Architecture

```
K-Systems/
├── frontend/          # Vue 3 + TypeScript + Vuetify 3
│   ├── src/views/     # Feature-based views
│   ├── src/components/# Reusable components
│   ├── src/stores/    # Pinia state management
│   ├── src/api.ts     # Centralized API client
│   └── src/locales/   # i18n translations (en, de)
├── backend/           # PHP 8.1 REST API
│   ├── [module]/      # Each module: index.php (CRUD)
│   ├── auth_check.php # JWT authentication
│   ├── jwt.php        # Token creation/validation
│   └── bootstrap.php  # App initialization
├── socket-server/     # Node.js real-time server
│   ├── server.js      # Socket.io server
│   └── modules/       # Chat, notifications, status, mail
├── database/          # Database schema
│   └── database.sql   # Full schema + seed data
├── docs/              # Documentation (VitePress)
└── docker-compose.yml # Docker orchestration
```

### Multi-Tenant Architecture

Every table includes `authority_id` for complete data isolation between organizations. Each tenant gets their own:
- Custom branding (logo, colors, site name)
- Feature flags (enable/disable modules)
- Role & permission configuration
- Isolated data storage

---

## Environment Variables

### Required

| Variable | Description |
|----------|-------------|
| `DOMAIN` | Your public domain (e.g., `ksystems.example.com`) - production only |
| `DB_DATABASE` | Database name (default: `ksystems`) |
| `DB_USERNAME` | Database user (default: `ksystems`) |
| `DB_PASSWORD` | Database password |
| `DB_ROOT_PASSWORD` | MariaDB root password (used in docker-compose) |
| `JWT_SECRET_KEY` | Secret key for JWT signing (min. 32 characters) |
| `SOCKET_API_KEY` | API key for backend ↔ socket server communication |
| `CADDY_DOMAIN` | Public domain for frontend build (same as DOMAIN) |

### Optional

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_ENV` | `production` | Environment (`production`, `development`) |
| `JWT_EXPIRATION_TIME` | `3600` | Token lifetime in seconds |
| `COOKIE_SECURE` | `true` | Secure cookies (requires HTTPS) |
| `COOKIE_SAMESITE` | `Lax` | SameSite cookie policy |

See [`backend/.env.template`](backend/.env.template) for all options.

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

## Documentation

- **[Deployment Guide](DEPLOY_README.md)** - Production deployment
- **[Docker Guide](DOCKER-README.md)** - Docker setup
- **[API Documentation](docs/api/)** - REST API reference
- **[Architecture](docs/architecture/)** - System architecture
- **[User Guides](docs/guide/)** - Feature documentation

---

## Security

- JWT token authentication with refresh tokens
- Password hashing (bcrypt)
- SQL injection protection (prepared statements)
- XSS protection (CSP headers)
- Input validation and sanitization
- File upload type validation
- Authority-based access control (multi-tenant isolation)
- Session management with device tracking

---

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Make sure to:
- Update both EN and DE translations when adding features
- Include `authority_id` in all database queries (multi-tenant!)
- Check permissions in both frontend and backend
- Test with multiple authorities

---

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

<div align="center">

## Support

If you find this project helpful, consider supporting me!

[![Ko-fi](https://img.shields.io/badge/Ko--fi-Buy%20me%20a%20coffee-FF5E5B?style=for-the-badge&logo=ko-fi&logoColor=white)](https://ko-fi.com/kyuubiddragon)
[![Discord](https://img.shields.io/badge/Discord-Join%20Server-5865F2?style=for-the-badge&logo=discord&logoColor=white)](https://dsc.gg/kyuubisoft)

**Discord Support Server:** [dsc.gg/kyuubisoft](https://dsc.gg/kyuubisoft)

---

K-Systems - Made with :heart: by [KyuubiDDragon](https://github.com/KyuubiDDragon)

</div>
