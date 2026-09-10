-- Migration: Willkommens-Banner auf seine Inhaltshoehe bringen
-- Datum: 2026-09-10
--
-- Der Baustein zeigt eine Begruessung, das Datum und ein Namenskuerzel - eine
-- Zeile Text. Die Vorlage "employee" gab ihm dafuer sechs Rasterzeilen, also
-- rund 215 px, von denen die unteren zwei Drittel leer blieben.
--
-- Grund war die Kennzahlenreihe (Nachrichten, Aufgaben, Termine), fuer die
-- showStats gedacht war. Sie wurde nie an eine Quelle angeschlossen und schob
-- durchgehend Platzhalter; sie ist inzwischen ausgeblendet, die reservierte
-- Hoehe blieb stehen. Drei Zeilen entsprechen dem, was der Baustein wirklich
-- braucht - so viel gibt ihm die Vorlage "member" schon heute.
--
-- Die uebrigen Bausteine ruecken um dieselben drei Zeilen nach oben.

UPDATE kdd_dashboard_templates
SET layout = '[{"i": "welcome-banner", "x": 0, "y": 0, "w": 12, "h": 3}, {"i": "my-crew", "x": 0, "y": 3, "w": 6, "h": 6}, {"i": "blackboard-feed", "x": 6, "y": 3, "w": 6, "h": 5}, {"i": "my-vacations", "x": 0, "y": 9, "w": 6, "h": 6}, {"i": "calendar-widget", "x": 6, "y": 9, "w": 6, "h": 6}, {"i": "messages-feed", "x": 0, "y": 15, "w": 6, "h": 6}, {"i": "todo-list", "x": 6, "y": 15, "w": 6, "h": 6}, {"i": "weather-widget", "x": 0, "y": 21, "w": 12, "h": 5}]'
WHERE template_key = 'employee';
