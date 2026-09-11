-- Migration: drei Altmigrationen als angewendet vermerken
-- Datum: 2026-09-11
--
-- Der Runner erkannte bisher nur Dateien nach dem Muster vier Ziffern gefolgt
-- von einem Unterstrich. Drei Migrationen tragen stattdessen ein durchgehendes
-- Datum im Namen und wurden deshalb nie automatisch ausgefuehrt. Ihr Inhalt
-- steht trotzdem in der Datenbank, jemand hat sie von Hand eingespielt:
--
--   kdd_dashboard_layout             vorhanden
--   kdd_dashboard_templates          sechs Zeilen vorhanden
--   kdd_blackboard blackboard=admin  keine Zeile mehr
--
-- Nachdem das Muster jetzt weiter gefasst ist, wuerde der naechste Lauf sie
-- ausfuehren. Bei zweien waere das harmlos, die dritte loescht die
-- Dashboard-Vorlagen und legt sie neu an - damit waeren alle spaeteren
-- Aenderungen daran weg. Dieser Eintrag verhindert das.
--
-- Diese Datei beginnt mit 0000, damit sie vor allen datierten Migrationen
-- laeuft. Der Runner prueft seit derselben Aenderung je Datei frisch nach,
-- ob sie schon eingetragen ist - sonst wuerde dieser Eintrag im selben
-- Durchlauf uebergangen.

INSERT IGNORE INTO schema_migrations (filename) VALUES
    ('20251028_create_dashboard_layout_table.sql'),
    ('20251028_insert_dashboard_templates.sql'),
    ('20251029_rename_blackboard_admin_to_all.sql');
