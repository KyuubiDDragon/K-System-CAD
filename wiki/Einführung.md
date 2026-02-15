# Einführung

## Was ist K-Systems?

K-Systems ist ein umfassendes mandantenfähiges Enterprise-Management-System, das moderne Webtechnologien mit einer einzigartigen Desktop-Oberfläche kombiniert. Es bietet Organisationen eine komplette Suite von Werkzeugen zur Verwaltung von Mitarbeitern, Berichten, Dokumenten, Nachrichten, Terminplanung und vielem mehr.

## Hauptmerkmale

### Desktop-Oberfläche
Erleben Sie eine vertraute Windows-Oberfläche mit:
- **Mehrere Fenster**: Öffnen Sie mehrere Anwendungen gleichzeitig
- **Verschiebbar & Größenveränderbar**: Vollständige Fensterverwaltung
- **Startmenü**: Kategorisierter Anwendungsstarter
- **Taskleiste**: Schnellzugriff auf offene Fenster
- **Desktop-Symbole**: Anpassbare Symbolpositionierung
- **Widgets**: Wetter-, Kalender- und Notizen-Widgets

### Mandantenfähige Architektur
Perfekt für Organisationen, die mehrere Kunden oder Abteilungen verwalten:
- **Vollständige Datenisolierung**: Jede Behörde (Mandant) hat isolierte Daten
- **Individuelles Branding**: Logo, Farben und App-Titel pro Behörde
- **Feature-Flags**: Module pro Mandant aktivieren/deaktivieren
- **Benutzerdefinierte Felder**: Behördenspezifische Felddefinitionen

### Sicherheit zuerst
Sicherheitsfunktionen auf Unternehmensniveau:
- **JWT-Authentifizierung**: Sichere Token-basierte Authentifizierung
- **Rollenbasierte Zugriffskontrolle**: Feinkörnige Berechtigungen
- **Feature-basierter Zugriff**: Modulverfügbarkeit steuern
- **Sitzungsverwaltung**: Automatische Token-Aktualisierung und -Ablauf

### Echtzeit-Kommunikation
Bleiben Sie in Verbindung mit:
- **Live-Benachrichtigungen**: Toast- und System-Benachrichtigungen
- **Chat-System**: Private und Gruppen-Nachrichten
- **Status-Updates**: Online/Offline/Abwesend-Indikatoren
- **Echtzeit-Synchronisation**: Sofortige Updates über alle Benutzer

## Kernmodule

### [[Mitarbeiterverwaltung]]
- Mitarbeiterdatensätze mit Abteilungen und Rängen
- Schulungs- und Lizenzverfolgung
- Urlaubsverwaltung
- Beförderungshistorie
- Firmenzuweisungen

### Berichtssystem
- Anpassbare Berichtsvorlagen
- Benutzerdefinierte Felddefinitionen
- Status-Workflow (Entwurf → In Bearbeitung → Abgeschlossen)
- Berichtsfreigabe mit ablaufenden Links
- Personen- und Firmenverknüpfungen

### Dokumentenverwaltung
- Dynamische Dokumentbereiche
- Berechtigungsbasierte Zugriffskontrolle
- Kategorieorganisation
- Dateianhänge
- Suche und Filterung

### [[Kalender]] & Termine
- Terminplanung mit Zuweisungen
- Kalendergruppen
- Unterstützung wiederkehrender Termine
- Mehrbenutzer-Zuweisungen
- Terminerinnerungen

### Nachrichtensystem
- Private Unterhaltungen
- Gruppennachrichten
- Nachrichtenordner
- Echtzeit-Zustellung
- Lesebestätigungen

### Zusätzliche Funktionen
- **[[Rechnungen]]**: Positionen, PDF-Generierung
- **Aufgabenlisten**: Aufgaben mit Checklisten
- **[[Karte]]**: Standortverfolgung mit Markierungen
- **[[Whiteboard]]**: Kollaboratives Zeichnen
- **[[Schwarzes-Brett]]**: Bulletin-Board-System
- **[[Dateimanager]]**: Organisierte Dateispeicherung

## Technologie-Stack

### Frontend
- **Vue 3**: Modernes reaktives Framework mit Composition API
- **TypeScript**: Typsichere Entwicklung
- **Vuetify 3**: Material Design Komponentenbibliothek
- **Pinia**: Zustandsverwaltung
- **Socket.io**: Echtzeit-Kommunikation
- **Vite**: Blitzschnelles Build-Tool

### Backend
- **PHP 8**: Modernes PHP mit Typdeklarationen
- **MariaDB**: Zuverlässiges Datenbanksystem
- **JWT**: Sichere Authentifizierung
- **PDO**: Sicherer Datenbankzugriff
- **Composer**: Abhängigkeitsverwaltung

### Socket Server
- **Node.js**: JavaScript-Laufzeitumgebung
- **Express**: Webserver-Framework
- **Socket.io**: WebSocket-Bibliothek
- **Winston**: Logging-System

### Infrastruktur
- **Docker**: Containerisierung
- **Docker Compose**: Multi-Container-Orchestrierung
- **Nginx**: Frontend-Webserver
- **Apache/PHP-FPM**: Backend-Server
- **Traefik**: Reverse Proxy (optional)

## Für wen ist das gedacht?

K-Systems ist ideal für:

- **Unternehmensorganisationen**: Verwaltung mehrerer Abteilungen oder Kunden
- **Dienstleister**: Angebot von Verwaltungslösungen für mehrere Kunden
- **Behörden**: Sichere mandantenfähige Datenverwaltung
- **Große Teams**: Kollaborative Arbeit mit rollenbasiertem Zugriff
- **Systemintegratoren**: Anpassbare Plattform für spezifische Anforderungen

## Systemanforderungen

### Entwicklung
- Node.js 18+
- PHP 8.0+
- MySQL/MariaDB 10.11+
- Composer
- NPM oder Yarn

### Produktion
- Docker & Docker Compose
- 2GB+ RAM
- 10GB+ Festplattenspeicher
- SSL-Zertifikat (empfohlen)

## Hilfe erhalten

- **Dokumentation**: Sie lesen sie gerade!
- **Issues**: Fehler auf GitHub melden
- **Support**: Kontaktieren Sie Ihren Systemadministrator
- **E-Mail**: support@k-systems.com

## Nächste Schritte

Bereit loszulegen? Hier ist, was Sie als Nächstes tun sollten:

1. [[Desktop-Oberfläche]] - System-Oberfläche verstehen
2. [[Mitarbeiterverwaltung]] - Mitarbeiter verwalten
3. [[Kalender]] - Termine planen
4. [[Nachrichten]] - Interne Kommunikation

---

> **Tipp:** Bereit zu starten? Fahren Sie mit der [[Desktop-Oberfläche]] fort, um die K-Systems-Oberfläche kennenzulernen.
