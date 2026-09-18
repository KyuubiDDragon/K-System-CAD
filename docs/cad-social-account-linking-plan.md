# CAD und Social: Plan zur Kontoverknüpfung

Status: Vorschlag zur gemeinsamen Abstimmung, noch nicht implementiert.

## Persönliche Verbindung

CAD-Einstellungen erhalten „Social-Konto verbinden“. Der Nutzer meldet sich im gewünschten Social-Konto an und bestätigt dort die Verbindung. Beide Seiten zeigen das verbundene Konto und bieten „Verbindung trennen“. Keine automatische Zuordnung anhand gleicher Namen oder E-Mail-Adressen. Registrierung und manuelle Freigabe bleiben erhalten.

Ein Nutzer kann mehrere eigene Social-Konten speichern, mit seinen CAD-Kontextkonten verbinden und gleichzeitig Mitglied mehrerer Unternehmen sein. Jede eigene Kontoverbindung wird separat durch Anmeldung und Bestätigung nachgewiesen. Charaktere werden nicht automatisch zusammengeführt.

Die Kontenübersicht unterscheidet eigene Konten, freigegebene Konten anderer Eigentümer und Unternehmensprofile. „Speichern“ hinterlegt serverseitig die bestätigte Verbindung und Anzeigepräferenzen, keine Passwörter im Browser. Ein gespeicherter Eintrag allein erteilt keine Zugriffsrechte. Das Entfernen eines Eintrags löscht weder das Konto noch eine Unternehmensmitgliedschaft; Verbindung trennen und Mitgliedschaft beenden sind ausdrücklich getrennte Aktionen.

Beim Öffnen von Social im CAD:
- Ohne Verbindung: Gastansicht/Anmeldung mit Verbindungshinweis.
- Mit einer Verbindung oder einem ausdrücklich gewählten Standardkonto, ohne Social-Sitzung: automatische Anmeldung über einen kurzlebigen Einmalcode. Bei mehreren Konten ohne Standard zunächst Kontoauswahl.
- Bereits richtig angemeldet: Sitzung beibehalten.
- Bereits unter anderem Profil angemeldet: Auswahl „Als … fortfahren“ oder „Verbundenes Konto verwenden“. Keine stille Ersetzung.
- Social-Abmeldung verhindert automatisches erneutes Einloggen im aktuellen Fenster. Das Profilmenü zeigt die aktive Identität eindeutig.

## Kontowechsel und aktive Identität

Oben rechts können Nutzer zwischen ihren gespeicherten, aktuell berechtigten Konten und Unternehmen wechseln oder ein weiteres Konto anmelden. Name, Avatar und bei Unternehmen die handelnde Person bleiben eindeutig erkennbar. Ein bevorzugtes Startkonto kann bewusst gespeichert werden.

Die aktive Identität gilt pro Fenster beziehungsweise Tab. Ein Wechsel verändert nicht stillschweigend andere geöffnete Fenster. Jeder API-Aufruf enthält einen serverseitig geprüften Handlungskontext; eine vom Client übermittelte Profil-ID ist keine Berechtigung. Der Entwurf eines Beitrags oder einer Nachricht bleibt an seine ursprüngliche Identität gebunden und wird bei einem Wechsel nicht unter einem anderen Konto abgeschickt.

## Unternehmen

Die technische Verwaltung bestätigt die Verbindung zwischen CAD-Unternehmensmandant und Social-Unternehmensseite. Ein berechtigter Geschäftsleiter vergibt danach Mitarbeiterrechte: Beiträge erstellen/verwalten, Profil und Öffnungsstatus pflegen, Werbeanfragen bearbeiten.

Mitarbeiter handeln mit ihrem persönlichen Konto im Namen der Unternehmensseite; keine gemeinsamen Firmenpasswörter. Eine Firmenfreigabe verknüpft niemals eigenmächtig ein privates Konto und erlaubt keinen Zugriff auf dessen Nachrichten. Eine Person darf gleichzeitig mehrere Unternehmensmitgliedschaften mit unterschiedlichen Rechten besitzen. Firmenrechteentzug wirkt unmittelbar, ohne die private Kontoverbindung oder andere Mitgliedschaften zu löschen.

## Haupteigner und freigegebene Zugriffe

Eigentum, persönliche Kontoverbindung und delegierter Zugriff sind getrennte Beziehungen. Der Haupteigner eines Kontos oder Unternehmensprofils kann andere Personen einladen, konkrete Rechte vergeben und diese Personen wieder entfernen. Einladungen werden vom Empfänger angenommen; die Freigabe gibt weder das Passwort noch automatisch Zugriff auf private Nachrichten oder Sicherheitseinstellungen. Delegierte Personen erhalten nur ausdrücklich vergebene Funktionen und dürfen sich keine weiteren Rechte geben oder den Haupteigner entfernen.

Beim Entfernen werden die betroffene Mitgliedschaft und alle daraus abgeleiteten Zugriffe, offenen Einladungen sowie noch nicht eingelösten Codes widerrufen. Bereits laufende Sitzungen verlieren auf allen Geräten und in allen Fenstern den Zugriff auf dieses Konto, auch wenn es dort gespeichert ist. Die persönliche Anmeldung und Rechte bei anderen Unternehmen bleiben erhalten.

Jede geschützte Anfrage prüft den aktuellen Berechtigungsstand; langlebige Tokens dürfen keine dauerhaft gültigen Mitarbeiterrechte enthalten. Nach Widerruf werden gespeicherte Einträge entfernt oder als nicht mehr verfügbar angezeigt. Offene Ansichten schließen den betroffenen Handlungskontext mit einem verständlichen Hinweis und bieten die Rückkehr zum eigenen Konto an. Es erfolgt kein automatisches Absenden unter einer Ersatzidentität. Bereits heruntergeladene Inhalte lassen sich durch einen Rechteentzug nicht nachträglich zurückholen.

Das Entfernen und sicherheitsrelevante Rechteänderungen erfordern eine erneute Authentifizierung des Berechtigten. Ein Eigentümerwechsel ist eine gesonderte Aktion mit erneuter Authentifizierung und Annahme durch den neuen Eigentümer. Das Konto darf dabei niemals ohne Haupteigner bleiben. Systemweite Verwaltungsrechte bleiben gesondert geregelt und werden durch eine Mitgliedschaft nicht erteilt.

## Umsetzung und Sicherheit

Eigene Tabellen für Mehrfach-Kontoverbindungen, gespeicherte Kontoauswahl, Eigentümer, Mitgliedschaften mit Einzelrechten und Widerrufsstand, ausstehende Bestätigungen, Einmalcodes und Unternehmenszuordnungen. Eindeutige Beziehungen verhindern doppelte Mitgliedschaften; Widerruf und Code-Einlösung werden transaktional gegeneinander abgesichert. Verknüpfung erfordert nachgewiesene Sitzungen beider Konten; Benutzer-IDs des Clients allein reichen nicht. Sensible Verbindungsänderungen erfordern erneute Authentifizierung.

Einmalcodes: kryptografisch zufällig, nur gehasht gespeichert, etwa 60 Sekunden gültig, atomar einmal einlösbar. Bindung an CAD-Sitzung, Verbindung, Ziel-Origin und zufälligen Anfragestatus. CAD fordert den Code authentifiziert an; ein postMessage-Handshake prüft auf beiden Seiten exakten Origin und event.source. Übergabe nur an das erwartete Social-Fenster, Einlösung dort per POST. Keine JWTs oder langlebigen Zugangsdaten in URLs/localStorage.

Bei Einlösung erneut prüfen: Sitzung gültig, CAD-Benutzer nicht gesperrt, Behörde aktiv, Social-Profil freigegeben, Verbindung nicht widerrufen und Modul aktiviert. Die erzeugte Social-Sitzung erhält einen Bezug zur Verbindung und CAD-Sitzung. Trennen und CAD-Abmeldung widerrufen diese Sitzungen; unabhängig manuell eröffnete Social-Sitzungen bleiben bestehen. Firmenrechte werden pro Anfrage aktuell geprüft. Verbindungs-, Einladungs-, Rechteänderungs-, Widerrufs- und Vertretungsaktionen werden mit handelnder Person und vertretenem Konto, aber ohne Zugangscodes protokolliert.

Zunächst HTTPS und gleiche Hauptdomain unterstützen. Bei verschiedenen Hauptdomains können Browser eingebettete Cookies blockieren; dafür einen verständlichen Anmelde-Fallback anbieten und keine automatische Funktion versprechen.

## Etappen

1. Mehrere Konten verbinden/trennen, Eigentum und Mitgliedschaften modellieren, Kontenübersicht und Audit; getrennte Anmeldung bleibt zunächst bestehen.
2. Einmal-Anmeldung im CAD-Fenster, Kontowechsel pro Fenster, Standardkonto, Abmeldung und Widerruf auf allen Geräten.
3. Unternehmenszuordnung, Einladungen und Handeln im Firmennamen mit Mitarbeiterrechten; Haupteigner kann Zugriffe vollständig entziehen.

Abnahmetests: fremde/fehlende zweite Sitzung, abgelaufene oder wiederverwendete Codes, parallele Einlösung, falscher Origin/Fensterabsender, Sperren, offene Freigabe, deaktivierte Behörde, Widerruf, Firmenrechteentzug, CAD-Abmeldung, bereits anderes aktives Social-Profil, mehrere Fenster und blockierte Cookies.

Zusätzliche Abnahmetests: zwei eigene Social-Konten an mehreren CAD-Kontexten, mehrere Unternehmen mit unterschiedlichen Rollen, gespeicherte Auswahl ohne gültige Rechte, voneinander unabhängige Fenster, identitätsgebundene Entwürfe, Entfernung eines online aktiven Mitarbeiters auf mehreren Geräten, Widerruf parallel zur Code-Einlösung, alte Einladungen und gespeicherte Einträge nach Entfernung, unveränderte Rechte bei anderen Unternehmen sowie verhinderte Rechteausweitung und Entfernung des Haupteigners durch Delegierte.

Vor Implementierung gemeinsam festlegen: Welche CAD-Rolle darf Unternehmenszuordnungen bestätigen? Mehrere eigene Konten und mehrere Unternehmensmitgliedschaften gehören fest zum Umfang.
