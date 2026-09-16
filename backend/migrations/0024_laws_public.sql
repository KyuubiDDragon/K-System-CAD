-- Public reading is the default; preserve installations explicitly configured since setup.
ALTER TABLE kdd_law_settings MODIFY guest TINYINT NOT NULL DEFAULT 1;
UPDATE kdd_law_settings SET guest=1, revision=revision+1 WHERE id=1 AND revision=1 AND guest=0;
