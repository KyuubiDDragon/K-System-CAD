ALTER TABLE kdd_law_books ADD COLUMN published TINYINT NOT NULL DEFAULT 1;
-- Existing books retain visibility. New books begin privately in the editor.
ALTER TABLE kdd_law_books ALTER COLUMN published SET DEFAULT 0;
