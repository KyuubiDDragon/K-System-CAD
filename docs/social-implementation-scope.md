# Kyuubisoft: abgestimmter Social-Umfang

Stand: 15. September 2026. Aus dem Planungsgespräch und den Designkorrekturen übernommen.

## Umsetzung und vorhandene Grundlagen

Die Implementierung liegt im bestehenden Repository `/Users/kyuubiddragon/Development/k-system-cad`, auf dem Branch `codex/social-platform`, ausgehend von `c76dd32`. Die folgenden Abschnitte halten den vereinbarten Produktumfang fest. Installation, Tests und verbleibende Betriebsschritte stehen in [social-platform.md](social-platform.md).

Die bestehende Vue-/PHP-/MariaDB-Architektur bleibt erhalten. Social verwendet eine eigene Vue-Oberfläche, einen eigenen API-Bereich und getrennte Sitzungen. Registrierung erzeugt einen persönlichen Mandanten und eine Rolle ohne CAD-Rechte. Das behördliche Firmenregister und Personenakten bleiben fachlich getrennt.

Die bestehenden Behörden werden nicht anhand vermuteter Namen umklassifiziert; ihre korrekten Typen müssen bei der Einrichtung anhand der tatsächlichen Organisationen gesetzt werden. Mehrere RP-Charaktere werden zunächst über getrennte Konten abgebildet. Gruppenchats bleiben wie besprochen späterer Umfang.

## Betrieb und Einstieg

- Jede RP-Community betreibt eine eigene Installation; kein communityübergreifendes Netzwerk.
- Social ist eine eigene Oberfläche mit eigener Adresse, im selben Repository und Docker-Stack wie CAD, als zusätzlicher Container.
- Gemeinsames Backend; klar abgegrenzter Social-API-Bereich.
- Installationskonfiguration erlaubt CAD allein, Social allein oder beide. Gemeinsame benötigte Dienste bleiben verfügbar.
- CAD startet auf seinem bestehenden Desktop. Ist Social aktiviert, liegt dort für jeden CAD-Benutzer eine feste Social-App-Verknüpfung. Kein separat abschaltbares Desktop-Icon.
- Erst INNERHALB der Social-App erscheint die Plattformauswahl: Social Media, Gram/Fotos, Marktplatz, Video. Keine Auswahl zwischen CAD und Social in diesem Bildschirm.
- Bei mehreren aktivierten Plattformen erscheint diese Auswahl bei jedem normalen Start; bei einer Plattform direkter Einstieg. Direkte Inhaltslinks sollen ihr Ziel behalten.
- Startbildschirm mit Los-Santos-inspiriertem Hintergrund und vier klaren App-Icons. Dauerhaft erreichbarer App-Wechsler innerhalb der Oberflächen.
- Plattformnamen sind frei konfigurierbar; die Mockup-Namen sind Platzhalter.

## Konten, Mandanten und Berechtigungen

- Getrennte Konten für PD, FD, privat usw. bleiben bestehen; kein vereinbartes SSO und keine stillschweigende Kontoverknüpfung.
- Behörden, Unternehmen und Privatnutzer besitzen unterschiedliche Funktionsfreigaben.
- Persönlicher Mandant pro Zivilistenkonto ist das geplante Modell; vor Integration Mandantenanlage, Standardrollen und Organisationsannahmen im Code prüfen.
- Keine Einsicht in Polizeiakten durch Anlage eines Privatkontos; Profile und behördliche Personenakten getrennt halten.
- Drei Ebenen: installierte/aktivierte Module, Funktionen je Mandantentyp, individuelle Rollenrechte.
- Registrierung legt persönlichen Mandanten, Konto und Minimalrolle konsistent an. Keine automatische Behörden-Administratorrolle.
- Social-Login muss ohne Kenntnis eines persönlichen Mandanten eindeutig auflösbar sein. Konkrete Kennung nach Prüfung des bestehenden Login-Modells festlegen.
- Globaler Registrierungsmodus pro Installation: sofortige Freigabe oder manuelle Prüfung durch die Betreiberfirma.
- Wartende Benutzer sehen Status und konfigurierbare RP-Kontaktanweisung. Annahme/Ablehnung mit interner Benachrichtigung.
- Kontofreigabe und öffentliches Verifiziert-Abzeichen nicht verwechseln.
- Die genaue Abbildung mehrerer RP-Charaktere auf Konten/Profile ist noch nicht abschließend entschieden.

## Social Media

- Startseite und chronologischer Feed, Text- und Bildbeiträge, eigene Beitragsseiten.
- Profile, gegenseitige Freundschaftsanfragen, Pinnwände.
- Likes, Dislikes, Kommentare, Emojis und Teilen gehören zum vollständigen Erstumfang.
- Öffentlich, Freunde sowie Freunde von Freunden als Sichtbarkeitsstufen.
- Eine Reaktion pro Konto und Beitrag, wechselbar und entfernbar.
- Bilder und Kommentare erben die Sichtbarkeit ihres Inhalts. Teilen erweitert keine Berechtigungen.
- Eigene Inhalte bearbeiten/löschen; melden und blockieren.
- Unternehmensseiten; berechtigte Mitarbeiter veröffentlichen im Firmennamen, einschließlich Texten und Bildern für Werbung.
- Öffentliche Social-Sichtbarkeit wird gezielt geregelt, nicht durch generelles Abschalten von Mandantenfiltern.

## Profil und Privatsphäre

- Avatar, eigenes Headerbild, Beschreibung und konfigurierbare persönliche Signatur.
- Signatur pro Benutzer in den Profileinstellungen; nicht automatisch pro Rolle.
- Profilsichtbarkeit, Beitragsvoreinstellung, Nachrichtenempfang, Freundschaftsanfragen und Pinnwand-Schreibrechte getrennt einstellen.
- Online-Status, Lesebestätigungen und blockierte Benutzer verwalten.
- Geänderte Voreinstellungen veröffentlichen alte Inhalte nicht nachträglich.
- Datenschutzregeln müssen auch für Suche, direkte Medienlinks und geteilte Inhalte greifen.
- Keine zusätzlich vereinbarte Follower-Funktion: einzelne „Folgen“-Elemente in generierten Bildern sind keine verbindlichen Anforderungen.

## Gram / Fotos

- Eigener fotografischer Bereich innerhalb derselben Social-Suite, dieselben Social-Konten und Profile.
- Fotoupload, Profilgalerie/Fotogitter, Likes und Interaktionen.
- Eigene Gestaltung und Icons; kein Instagram-Logo.
- Stories sind nicht beauftragt.

## Marktplatz

- Verkaufsanzeigen mit Text, Bildern, Preis in RP-Dollar, Kategorien und Filtern.
- Status verfügbar, reserviert, verkauft; eigene Anzeigen verwalten.
- Verkäufer aus der Anzeige über interne Nachricht kontaktieren.
- Keine realen Zahlungen oder externer Checkout.
- Bestandteil der Social-Suite, kein weiterer unabhängiger Accountdienst.

## Video

- Videouploads, kein Livestreaming.
- Übersicht, Wiedergabeseite, Titel/Beschreibung, Kommentare, Likes/Dislikes, Teilen im Social-Feed.
- Verarbeitung im Hintergrund mit sichtbarem Uploadstatus.
- Jedes Video muss vor Veröffentlichung von der Betreiberfirma freigegeben werden.
- Technischer Verarbeitungsstatus und redaktionelle Freigabe getrennt führen; Veröffentlichung setzt beides voraus.
- Ablehnung mit Begründung und interner Nachricht.
- Nicht freigegebene Videos sind nicht über Feed, Suche, geteilte Beiträge oder direkte Medienlinks öffentlich abrufbar.
- Ersetzen einer Videodatei löst erneute Prüfung aus.
- Einstellbare Grenzen für Länge, Größe und Speicher. Exakte Standardwerte sind noch festzulegen.

## Nachrichten

- Private Einzelchats ab erstem vollständigem Stand, mit Anhängen, Emojis und Textformatierung.
- Editor für Fett/Kursiv, Listen und Links, persönliche Signatur.
- Mehrfachversand: separate Zustellung an mehrere Empfänger, keine gegenseitige Offenlegung, getrennte Antworten.
- Gruppenchats ausdrücklich später.
- Interner Posteingang und Benachrichtigungen; Nachrichten, Freunde und Profil oben rechts erreichbar.
- Kein allgemeiner Zugriff auf private Chats durch die Betreiberfirma. Gezielte gemeldete Inhalte können geprüft werden.
- Anhänge nur für berechtigte Gesprächsteilnehmer bzw. gezielt autorisierte Prüfung zugänglich.
- RP-Kommunikation bleibt vollständig in der Plattform; Werbebestätigungen sind keine externen E-Mails.

## Betreiberfirma, Werbung und Moderation

- Eigenes Admin-Panel für ausdrücklich berechtigte Mitarbeiter der Betreiberfirma.
- Moderation und Werbeverwaltung mit getrennten Rechten.
- Meldungen, Ausblenden/Löschen, zeitweise Sperren, Einsprüche, nachvollziehbare Entscheidungen mit Mitarbeiter und Begründung.
- Keine technischen Installationsrechte allein aus einer RP-Firmenrolle.
- Feste freigebbare Werbeslots, z. B. Header, rechter Bereich, angepinnter gesponserter Beitrag. Unbelegte Slots unsichtbar ohne Leerraum.
- Anzeigen klar als Werbung kennzeichnen.
- Betreiberfirma legt verfügbare Slots, Zeiträume und Bedingungen fest.
- Unternehmen wählen Slot und Zeitraum, reichen Text/Bild/Ziel und Extras ein, sehen Vorschau und Status.
- Betreiberfirma nimmt Anfragen an oder lehnt sie ab; keine eigenmächtige Veröffentlichung durch Unternehmen.
- Bei Annahme Zeitraum reservieren, Doppelbuchungen verhindern. Rotation nur für entsprechend konfigurierte Slots.
- Konfigurierbare interne Nachrichtenvorlagen für Annahme/Ablehnung mit Firma, Slot, Zeitraum, RP-Betrag und Zahlungsanweisung.
- Freigabe und Zahlung getrennt führen. Wenn Zahlung erforderlich, bestätigt ein berechtigter Mitarbeiter den RP-Zahlungseingang vor Aktivierung.
- Automatischer Beginn/Ende nach Zeitplan und Voraussetzungen.
- Optionale Extras: Countdown, Aktionsbutton, kurze Zusatztexte, wechselnde Banner. Technische Administration gibt erlaubte Bausteine vor.
- Countdown mit festem Ziel und eindeutigem Endzustand; kein künstlich zurückgesetzter Timer.
- Infoblöcke, Community-Links und deren Reihenfolge ausschließlich durch Betreiberfirma pflegbar, nicht durch beliebige Unternehmen.

## Community-Konfiguration

- Technische Community-Leitung verwaltet globale Farben, Logo, grundlegendes Layout und optionalen Header.
- Heller, dunkler und systemabhängiger Modus; lesbare Farbkontraste in beiden Modi.
- Fünf vorgesehene Icon-Sets: vorhandenes Los-Santos-Set plus Pacific, After Hours, City Signs, Studio.
- Eigene Icons pro Plattform hochladen; Vorschau und Rückkehr zum Standard.
- Generierte Übersichten sind Designreferenzen, keine fertigen einzelnen Icon-Dateien.
- Einzelne Social-Module ein-/ausschalten, einschließlich Video, Fotos, Marktplatz und Nachrichten.
- Deaktivierung gilt für Oberfläche, Suche, Backend, direkte Links, Einbettungen und Hintergrundverarbeitung.
- Standard: Daten behalten. Separater expliziter Löschvorgang durch technische Administration möglich.
- Löschdialog zeigt betroffene Moduldaten/Dateien und verlangt ausdrückliche Bestätigung; Wiedereinschalten stellt gelöschte aktive Daten nicht wieder her.
- Nutzer möchte keinen Backup-Hinweis im Löschdialog. Formulierung präzise auf Daten der laufenden Installation begrenzen, keine unzutreffende Behauptung über sämtliche Kopien.
- Abhängige geteilte Inhalte nach Löschen als nicht verfügbar darstellen.

## Weitere vereinbarte Grundlagen

- Rechtegefilterte Suche über Personen, Unternehmen, Beiträge, Fotos, Videos und Anzeigen.
- Zentrale Benachrichtigungen mit persönlichen Einstellungen.
- Passwort ändern, Wiederherstellung und Kontolöschung; konkreten Wiederherstellungsweg passend zum bestehenden System festlegen.
- Lokaler oder S3-Speicher, systemweit konfigurierbar; vorhandene Dateien bei Wechsel weiter erreichbar halten.
- Uploadprüfung, Größen-/Speicherlimits, Entfernen von Standortmetadaten bei Bildern, geregeltes Dateilöschen.
- Backups und Wiederherstellbarkeit als Betriebsfunktionen einplanen.

## Designreferenzen und verbindliche Korrekturen

Siehe `design/README.md` und `design/approved/`.

- Bestehende CAD-Oberfläche nicht durch Mockup-Kacheln ersetzen.
- Social-Launcher zeigt die vier Social-Plattformen, nicht CAD/Aktenverwaltung.
- Innenansichten mit zentriertem, begrenztem Inhaltsbereich und ausgewogenen Außenrändern.
- Hauptinhalt/Feed mittig, links Hashtags/Themen bzw. Kategorien; rechts Werbung und passende Zusatzinformationen.
- Keine linke Kontonavigation; Nachrichten, Freunde, Benachrichtigungen und Profil oben rechts.
- Ruhige, vertraute Weboberflächen auf Mobbin-Referenzbasis. Keine dekorativen Palmen im Anwendungschrome, Neonrahmen oder Marketingfloskeln.
- Namen, Zahlen, Inhalte und vereinzelt unpassende Bedienelemente generierter Bilder sind Beispieldaten. Anforderungen haben Vorrang vor Bilddetails.

## Integrationsreihenfolge nach Bereitstellung des Codes

1. Repository-Regeln, Datenmodell, Authentifizierung, Mandantenanlage, Rechte, Deployment und bestehende Tests lesen.
2. Konfiguration, Migrationen, Rollen, Identitätsmodell und Speicherintegration in vorhandene Architektur einpassen.
3. Social-Container/API, Desktop-Verknüpfung, Launcher und gemeinsame responsive Oberfläche erstellen.
4. Registrierung/Profile/Privatsphäre, Feed/Freunde/Kommentare/Reaktionen, Nachrichten umsetzen.
5. Fotos, Marktplatz, Videoverarbeitung und Freigaben ergänzen.
6. Betreiberverwaltung, Werbeanfragen/-laufzeiten/-vorlagen, Moderation und Modulverwaltung integrieren.
7. Funktionale und Berechtigungstests sowie visuelle Prüfung in Hell/Dunkel und schmalen Ansichten.

Diese Reihenfolge reduziert den vereinbarten Umfang nicht. Sie bezeichnet technische Abhängigkeiten.

## Wesentliche Abnahmefälle

- Zivilist A kann öffentliche Inhalte von B lesen, aber weder Bs private Inhalte noch Behördenakten; Mutation fremder IDs abgewiesen.
- Nachrichten, Uploads, Suchtreffer und Medienlinks folgen denselben Regeln wie die Oberfläche.
- Freunde-von-Freunden und Blockieren werden serverseitig konsistent ausgewertet.
- Neue Registrierung kann keine privilegierte Rolle/Mandantenart wählen.
- Ausgeschaltetes Modul bleibt über API und geteilte Medien unerreichbar.
- Ungeprüftes/verarbeitendes Video bleibt unveröffentlicht, Dateiersatz erfordert erneute Prüfung.
- Gleichzeitige Buchungen desselben exklusiven Werbeslots führen nicht zur Doppelbelegung.
- Nicht bezahlte zustimmungspflichtige Werbung startet nicht; Termine und Endzustände funktionieren.
- Nachrichten beim Mehrfachversand offenbaren keine anderen Empfänger.
- Technische Administration und Betreiberrollen sind wirksam getrennt.
- Social-only/CAD-only/beide Startkonfigurationen funktionieren ohne ungewollte Abhängigkeit von deaktivierten Oberflächen.
- Plattformnamen, Icon-Uploads und Theme-Konfiguration erscheinen konsistent in Startmenü und Navigation.
