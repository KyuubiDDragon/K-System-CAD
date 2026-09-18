# CAD und Social: Plan zur Kontoverknüpfung

Status: Vorschlag zur gemeinsamen Abstimmung, noch nicht implementiert.

## Persönliche Verbindung

CAD-Einstellungen erhalten „Social-Konto verbinden“. Der Nutzer meldet sich im gewünschten Social-Konto an und bestätigt dort die Verbindung. Beide Seiten zeigen das verbundene Konto und bieten „Verbindung trennen“. Keine automatische Zuordnung anhand gleicher Namen oder E-Mail-Adressen. Registrierung und manuelle Freigabe bleiben erhalten.

Vorgeschlagener Standard: Ein Social-Profil pro CAD-Konto; mehrere eigene CAD-Kontextkonten können nach ausdrücklicher Bestätigung dasselbe Social-Profil verwenden. Charaktere werden nicht automatisch zusammengeführt.

Beim Öffnen von Social im CAD:
- Ohne Verbindung: Gastansicht/Anmeldung mit Verbindungshinweis.
- Mit Verbindung, ohne Social-Sitzung: automatische Anmeldung über einen kurzlebigen Einmalcode.
- Bereits richtig angemeldet: Sitzung beibehalten.
- Bereits unter anderem Profil angemeldet: Auswahl „Als … fortfahren“ oder „Verbundenes Konto verwenden“. Keine stille Ersetzung.
- Social-Abmeldung verhindert automatisches erneutes Einloggen im aktuellen Fenster. Das Profilmenü zeigt die aktive Identität eindeutig.

## Unternehmen

Die technische Verwaltung bestätigt die Verbindung zwischen CAD-Unternehmensmandant und Social-Unternehmensseite. Ein berechtigter Geschäftsleiter vergibt danach Mitarbeiterrechte: Beiträge erstellen/verwalten, Profil und Öffnungsstatus pflegen, Werbeanfragen bearbeiten.

Mitarbeiter handeln mit ihrem persönlichen Konto im Namen der Unternehmensseite; keine gemeinsamen Firmenpasswörter. Eine Firmenfreigabe verknüpft niemals eigenmächtig ein privates Konto und erlaubt keinen Zugriff auf dessen Nachrichten. Firmenrechteentzug wirkt unmittelbar, ohne die private Kontoverbindung zu löschen.

## Umsetzung und Sicherheit

Eigene Tabellen für Kontoverbindungen, ausstehende Bestätigungen, Einmalcodes und Unternehmenszuordnungen. Verknüpfung erfordert nachgewiesene Sitzungen beider Konten; Benutzer-IDs des Clients allein reichen nicht. Sensible Verbindungsänderungen erfordern erneute Authentifizierung.

Einmalcodes: kryptografisch zufällig, nur gehasht gespeichert, etwa 60 Sekunden gültig, atomar einmal einlösbar. Bindung an CAD-Sitzung, Verbindung, Ziel-Origin und zufälligen Anfragestatus. CAD fordert den Code authentifiziert an; ein postMessage-Handshake prüft auf beiden Seiten exakten Origin und event.source. Übergabe nur an das erwartete Social-Fenster, Einlösung dort per POST. Keine JWTs oder langlebigen Zugangsdaten in URLs/localStorage.

Bei Einlösung erneut prüfen: Sitzung gültig, CAD-Benutzer nicht gesperrt, Behörde aktiv, Social-Profil freigegeben, Verbindung nicht widerrufen und Modul aktiviert. Die erzeugte Social-Sitzung erhält einen Bezug zur Verbindung und CAD-Sitzung. Trennen und CAD-Abmeldung widerrufen diese Sitzungen; unabhängig manuell eröffnete Social-Sitzungen bleiben bestehen. Firmenrechte werden pro Anfrage aktuell geprüft. Verbindungs- und Vertretungsaktionen werden ohne Zugangscodes protokolliert.

Zunächst HTTPS und gleiche Hauptdomain unterstützen. Bei verschiedenen Hauptdomains können Browser eingebettete Cookies blockieren; dafür einen verständlichen Anmelde-Fallback anbieten und keine automatische Funktion versprechen.

## Etappen

1. Verbinden/Trennen, Kontenübersicht und Audit; getrennte Anmeldung bleibt zunächst bestehen.
2. Einmal-Anmeldung im CAD-Fenster, Kontowechsel, Abmeldung und Widerruf.
3. Unternehmenszuordnung und Handeln im Firmennamen mit Mitarbeiterrechten.

Abnahmetests: fremde/fehlende zweite Sitzung, abgelaufene oder wiederverwendete Codes, parallele Einlösung, falscher Origin/Fensterabsender, Sperren, offene Freigabe, deaktivierte Behörde, Widerruf, Firmenrechteentzug, CAD-Abmeldung, bereits anderes aktives Social-Profil, mehrere Fenster und blockierte Cookies.

Vor Implementierung gemeinsam festlegen: Welche CAD-Rolle darf Unternehmenszuordnungen bestätigen? Dürfen mehrere eigene CAD-Kontexte dieselbe Social-Identität nutzen? Oben steht der vorgeschlagene Standard.
