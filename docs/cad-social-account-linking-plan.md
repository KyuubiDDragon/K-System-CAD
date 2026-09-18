# CAD und Social: Konten und Zugriffe

Status: Implementiert im Entwicklungszweig; Migration 0029 und Deployment erforderlich.

## Eigene Konten

Unter **Konten & Zugriffe** können Nutzer weitere eigene Social-Konten mit deren Passwort anmelden und speichern. Der Wechsel gilt pro Browserfenster/Tab. Servergespeicherte Kontoverweise ersetzen keine Berechtigung: Zu jedem Eintrag muss eine gültige Sitzung bestehen. Passwörter und Sitzungstoken werden nicht im Browser-Speicher abgelegt; dort liegen ausschließlich nicht geheime Profil-IDs. Die Sitzungscookies sind HttpOnly und in Produktion Secure.

Bereits angemeldete Tabs behalten ihre Identität, auch wenn in einem anderen Tab ein weiteres Konto angemeldet wird. Beim Wechsel werden offene Eingaben nach Bestätigung verworfen, niemals unter einer anderen Identität gesendet. „Aus Liste entfernen“ entfernt einen gespeicherten Zugang auf diesem Browser, ohne das Konto oder Unternehmensmitgliedschaften zu löschen. Eine Abmeldung widerruft die verwendete Sitzung; weitere eigene Konten bleiben verfügbar. Gespeicherte Anmeldungen laufen nach sieben Tagen ab, die Browser-Kontenliste nach 30 Tagen.

## Freigegebene Profile und Unternehmen

Eigentum, eigene Kontoverbindung und Mitarbeiterzugriff sind getrennt:

- Der Haupteigner eines persönlichen Profils ist dessen Kontoinhaber. Ein delegierter Zugang erhält niemals Eigentümerrechte oder das Passwort.
- Der Haupteigner einer Unternehmensseite kann Mitarbeiter einladen oder entfernen. Mehrere Unternehmensmitgliedschaften mit verschiedenen Rechten sind möglich.
- Für Profile gibt es **öffentliche Beiträge** und **Profilpflege** (Name, Beschreibung, Bilder). Private Nachrichten, Freundeslisten, private Beiträge, Sicherheitseinstellungen und Administratorrechte werden nicht übertragen.
- Für Unternehmen gibt es **Beiträge**, **Profil und Öffnungsstatus** sowie **Werbeanfragen**. Unternehmensbeiträge bleiben mit der handelnden Person verbunden. Bei Löschung ihres persönlichen Kontos bleiben Firmenbeiträge und Firmenbilder erhalten; die Person wird als gelöschtes Konto angezeigt.
- Einladungen und geänderte Rechte müssen vom Empfänger angenommen werden. Bis dahin ist der betreffende Zugriff nicht aktiv. Alte Mitgliedschaften aus der Zeit vor Migration 0029 bleiben mit ihren bisherigen Rechten erhalten.
- Passwortbestätigung ist für Freigaben, Entzug, Kontotrennung und Eigentümerwechsel erforderlich. Für den Eigentümerwechsel eines Unternehmens muss zusätzlich der neue Eigentümer zustimmen. Bis zur Annahme bleibt der bisherige Eigentümer verantwortlich. Persönliche Konten sind nicht übertragbar.
- Unternehmen werden zunächst vom Social-Verwalter erstellt; dieser ist zunächst Haupteigner und kann die Übernahme dem Geschäftsleiter anbieten. Das allgemeine Unternehmensformular vergibt keine versteckten Mitarbeiterrechte mehr.

Der Rechteentzug wirkt auf die nächste API-Anfrage aller Geräte und Fenster, einschließlich Bearbeiten/Löschen bereits verfasster Firmenbeiträge. Offene Delegationsansichten prüfen den Zugriff zusätzlich im Hintergrund. Die Oberfläche zeigt den Verlust an und sendet keine offenen Eingaben unter einem Ersatzkonto. Andere eigene Konten und Unternehmensmitgliedschaften bleiben erhalten. Bereits heruntergeladene Inhalte können nicht zurückgerufen werden.

## CAD-Verbindung

Im CAD-Fenster der Social-App öffnet **Kontoverbindungen** die Liste der verbundenen Social-Konten. **Aktuelles Social-Konto verbinden** fordert eine Bestätigung samt Social-Passwort im eingebetteten Social-Fenster an. Gleiche Namen oder E-Mail-Adressen werden niemals automatisch verbunden. Mehrere Social-Konten pro CAD-Konto und mehrere CAD-Kontexte pro Social-Konto sind möglich.

Bei genau einer Verbindung oder einem ausdrücklich gewählten Startkonto kann Social beim Öffnen automatisch angemeldet werden. Mehrere Verbindungen ohne Startkonto erfordern eine Auswahl. Eine bereits vorhandene Social-Anmeldung wird nur nach sichtbarer Bestätigung ersetzt. Eine bewusste Social-Abmeldung verhindert die automatische Wiederanmeldung im aktuellen Tab; „Konto öffnen“ bleibt als bewusste Aktion verfügbar.

Die technische Systemverwaltung kann unter **Unternehmen mit CAD zuordnen** einen CAD-Unternehmensmandanten einer Social-Unternehmensseite zuordnen. Das erteilt noch keine Mitarbeiterrechte. Der Social-Haupteigner sieht danach bereits verknüpfte CAD-Mitarbeiter als Einladungsvorschläge und kann für deren Social-Profile konkrete Rechte vergeben. Wer noch kein Social-Konto verbunden hat, muss dies zunächst selbst tun.

## Technische Absicherung

- Verbindungen, Wallet-Verweise, Einladungen, Unternehmensrechte, Eigentümerübernahmen und Herkunft eingebetteter Sitzungen haben eigene Tabellen.
- CAD-Authentifizierung benötigt neben dem JWT eine aktuell gültige Datenbanksitzung, einen nicht gesperrten Benutzer und einen aktiven Mandanten.
- Zufällige Einmalcodes sind nur gehasht gespeichert, 60 Sekunden gültig und werden atomar einmal eingelöst. Sie sind an CAD-Sitzung, Zweck, Kontoverbindung, Ziel-Origin und einen zufälligen Fensterzustand gebunden.
- Beide Fenster prüfen exakten Origin und `event.source`; die Codes werden per `postMessage` und anschließend per POST übertragen. Keine Zugangstoken in URLs.
- Bei jeder abgeleiteten Social-Sitzung werden CAD-Sitzung und Verbindung erneut geprüft. Trennen, Sperren und CAD-Abmeldung machen diese Sitzungen unbrauchbar; unabhängige manuelle Social-Anmeldungen bleiben erhalten.
- Schreibzugriffe werden mit einem datenbankweiten, kurz wartenden `social_access`-Lock geordnet, damit Widerruf und parallel eintreffende Schreiboperationen nicht widersprüchliche Berechtigungsstände verwenden. Dies ist für die derzeit kleine Installation ausgelegt; bei deutlich höherem Schreibvolumen sollte die Sperre auf einzelne Rechtebeziehungen verfeinert werden.
- Neue Uploads vermerken die tatsächliche hochladende Person. Delegierte Zugriffe dürfen keine ungenutzten privaten Uploads des Eigentümers veröffentlichen.
- Freigaben, Entzug, Eigentümerübernahme und delegierte Änderungen werden mit tatsächlichem Akteur und betroffenem Konto protokolliert.

## Betrieb und Grenzen

`SOCIAL_ENABLED`, `SOCIAL_ORIGIN`, `FRONTEND_URL_PROD`, `VITE_SOCIAL_ENABLED`, `VITE_SOCIAL_URL` und `CAD_FRAME_ORIGIN` müssen dieselben vorgesehenen Adressen beschreiben. Für die aktuelle Installation sind CAD und Social HTTPS-Subdomains derselben Hauptdomain. Unterschiedliche Hauptdomains können eingebettete Cookies blockieren; die eigenständige Social-Anmeldung bleibt dann der Ausweichweg. Es gibt keine Umgehung der Browser-Cookie-Schutzmechanismen.

Die Umsetzung übernimmt keine Konten automatisch und ändert keine Produktivdaten vor dem regulären Deployment. Persönliche Kontoverbindungen und Unternehmensfreigaben werden nach dem Deployment ausdrücklich eingerichtet.

## Prüfung

Automatisierte Tests prüfen unter anderem zwei gespeicherte Konten, voneinander unabhängige Tabs, angenommene und offene Einladungen, private Inhalte, Verhinderung von Rechteausweitung, mehrere Unternehmen, laufende Zugriffe nach Entzug, Eigentümerwechsel, Einmalcode-Wiederverwendung, falschen Fensterzustand, Trennen sowie CAD-Abmeldung. Browserprüfungen ergänzen den tatsächlichen Kontowechsel und die Bedienung der Freigaben.
