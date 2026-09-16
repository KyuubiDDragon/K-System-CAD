-- Jurisdiction covers the entire app. Existing grants retain their strongest rights.
CREATE TABLE kdd_law_app_grants (
 authority_id INT PRIMARY KEY,
 draft_read TINYINT NOT NULL DEFAULT 0, edit TINYINT NOT NULL DEFAULT 0,
 publish TINYINT NOT NULL DEFAULT 0, repeal TINYINT NOT NULL DEFAULT 0,
 FOREIGN KEY(authority_id) REFERENCES kdd_authorities(id)
) ENGINE=InnoDB;
INSERT INTO kdd_law_app_grants(authority_id,draft_read,edit,publish,repeal)
SELECT authority_id,MAX(draft_read),MAX(edit),MAX(publish),MAX(repeal)
FROM kdd_law_grants GROUP BY authority_id;
UPDATE kdd_permissions SET description='Redaktioneller Zugang zur gesamten Gesetze-App' WHERE module='laws' AND sub_module IS NOT NULL;
UPDATE kdd_authority_features SET description='Zugang zur gesamten Gesetze-App; zusätzliche CAD-Rollenrechte erforderlich' WHERE code='laws';
